<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteDailyEntry;
use App\Models\SiteAllocation;
use App\Models\SiteEntryHistory; // 🔥 Naya Model
use App\Services\MediaConverterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteEntryController extends Controller
{
 // 🔥 FIX 1: Admin Tab Fix
    public function getAllowedCategories()
    {
        $context = $this->getGlobalContext();
        $today = now()->format('Y-m-d');
        
        // God Mode & Admin ko "Vehicle Trip Slip" pakka dikhega
        if ($context->is_god || $context->is_director || in_array(auth()->user()->email, ['admin@jankivilla.com'])) {
            $fallbackAllocation = \App\Models\SiteAllocation::latest()->first(); 
            return response()->json([
                'status' => 'success', 
                // Ye array update kiya hai taaki Project Name hate aur Trip Slip dikhe
                'data' => ['Labour', 'Construction Equipment Vehicle', 'Goods Carrier', 'Material', 'Other Expenses', 'Vehicle Trip Slip'],
                'allocation_id' => $fallbackAllocation ? $fallbackAllocation->id : 1
            ]);
        }

        $allocation = \App\Models\SiteAllocation::where('employee_id', auth()->user()->id)
            ->where('status', 'active')
            ->where(function($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->latest() 
            ->first();

        if (!$allocation) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $cats = $allocation->allowed_categories;
        if(is_array($cats)) {
            foreach($cats as $k => $v) {
                if($v === 'Project Name') $cats[$k] = 'Vehicle Trip Slip';
            }
        }

        return response()->json([
            'status' => 'success', 
            'data' => $cats ? array_values(array_unique($cats)) : [],
            'allocation_id' => $allocation->id
        ]);
    }

  
    public function show(Request $request, $id)
    {
        // Agar Trip Slip ki detail dekhni hai toh dusri table se data layenge
        if ($request->query('type') === 'trip') {
            $trip = \App\Models\SiteVehicleTrip::findOrFail($id);
            $supervisor = \App\Models\Employee::find($trip->site_supervisor_id);
            
            $entry = [
                'id' => $trip->id,
                'entry_date' => $trip->trip_date,
                'category' => 'Vehicle Trip Slip',
                'entered_by' => ['full_name' => $supervisor ? $supervisor->full_name : 'N/A'],
                'entry_details' => [
                    'slip_number' => $trip->slip_number,
                    'vehicle_no' => $trip->vehicle_number,
                    'arrival_time' => $trip->arrival_time,
                    'departure_time' => $trip->departure_time,
                ],
                'total_amount' => 0, 'paid_amount' => 0, 'balance_amount' => 0, 'documents' => []
            ];
            
            // Images ko bhi document array me dal diya taaki View me dikhe
            if ($trip->arrival_image) $entry['documents'][] = ['file_path' => $trip->arrival_image];
            if ($trip->departure_image) $entry['documents'][] = ['file_path' => $trip->departure_image];
            
            return response()->json(['status' => 'success', 'data' => $entry]);
        }

        // Normal Entry Data
        $entry = SiteDailyEntry::with('documents', 'enteredBy')->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $entry]);
    }


   public function store(Request $request)
    {
        $context = $this->getGlobalContext();
        $request->validate(['allocation_id' => 'required', 'entries' => 'required|array']);

        DB::beginTransaction();
        try {
            $mediaConverter = new \App\Services\MediaConverterService();

            foreach ($request->entries as $index => $entryData) {
                $totalAmount = floatval($entryData['total_amount'] ?? 0);
                $paidAmount = floatval($entryData['paid_amount'] ?? 0);
                $balanceAmount = $totalAmount - $paidAmount;
                $status = ($balanceAmount <= 0) ? 'paid' : 'pending';

                // 🔥 NAYA FIX: Arrival aur Departure photo ko JSON details me dalna
                $details = $entryData['details'] ?? [];
                
                $arrivalImgKey = "entries.{$index}.arrival_image";
                $departureImgKey = "entries.{$index}.departure_image";

                if ($request->hasFile($arrivalImgKey)) {
                    $media = $mediaConverter->uploadAndConvert($request->file($arrivalImgKey));
                    if ($media) $details['arrival_image'] = $media->file_path;
                }
                if ($request->hasFile($departureImgKey)) {
                    $media = $mediaConverter->uploadAndConvert($request->file($departureImgKey));
                    if ($media) $details['departure_image'] = $media->file_path;
                }

                $dailyEntry = SiteDailyEntry::create([
                    'site_allocation_id' => $request->allocation_id,
                    'employee_id' => auth()->user()->id,
                    'entry_date' => $request->entry_date,
                    'category' => $request->category,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'balance_amount' => $balanceAmount,
                    'status' => $status,
                    'entry_details' => $details // Updated JSON array
                ]);

                // Documents upload logic
                $fileKey = "entries.{$index}.documents";
                if ($request->hasFile($fileKey)) {
                    foreach ($request->file($fileKey) as $file) {
                        $media = $mediaConverter->uploadAndConvert($file);
                        if ($media) {
                            $dailyEntry->documents()->create([
                                'file_path' => $media->file_path, 'original_name' => $media->original_name,
                                'file_type' => $media->file_type, 'file_size' => $media->file_size,
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Entries Saved Successfully!']);
        } catch (\Exception $e) {
            DB::rollBack(); return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


   // 🔥 FIX 2: Employee Visibility & Date Format Fix
    public function index(Request $request)
    {
        $context = $this->getGlobalContext();
        // High Level User (Jisko sab kuch dikhna chahiye)
        $isHighLevel = ($context->is_god || $context->is_director || in_array(auth()->user()->email, ['admin@jankivilla.com']));

        $fetchDailyEntries = true;
        $fetchTrips = true;

        if ($request->has('category') && $request->category != '') {
            if ($request->category === 'Vehicle Trip Slip') {
                $fetchDailyEntries = false; 
            } else {
                $fetchTrips = false; 
            }
        }

        $allData = collect();

        // 1. Fetch Normal Daily Entries
        if ($fetchDailyEntries) {
            $query1 = \App\Models\SiteDailyEntry::with('documents', 'enteredBy')->latest();
            
            // 🔥 NAYA FIX: Agar High Level nahi hai, tabhi uski ID par filter lagao
            if (!$isHighLevel) {
                $query1->where('employee_id', auth()->user()->id);
            }
            if ($request->has('category') && $request->category != '' && $request->category !== 'All') {
                $query1->where('category', $request->category);
            }
            
            $normalEntries = $query1->get()->map(function ($entry) {
                // Aapke screenshot me date '2026-06-30T18:30...' jaisi aa rahi thi, usko simple '2026-06-30' me convert kiya
                if ($entry->entry_date) {
                    $entry->entry_date = \Carbon\Carbon::parse($entry->entry_date)->format('Y-m-d');
                }
                return $entry;
            });
            $allData = $allData->merge($normalEntries);
        }

        // 2. Fetch Vehicle Trips
        if ($fetchTrips) {
            $query2 = \App\Models\SiteVehicleTrip::latest();
            
            // 🔥 NAYA FIX: Agar High Level nahi hai, tabhi uski ID par filter lagao
            if (!$isHighLevel) {
                $query2->where('site_supervisor_id', auth()->user()->id);
            }
            
            $trips = $query2->get()->map(function ($trip) {
                $supervisor = \App\Models\Employee::find($trip->site_supervisor_id);
                return [
                    'id' => $trip->id,
                    'entry_date' => \Carbon\Carbon::parse($trip->trip_date)->format('Y-m-d'),
                    'category' => 'Vehicle Trip Slip',
                    'entered_by' => [
                        'full_name' => $supervisor ? $supervisor->full_name : 'N/A'
                    ],
                    'entry_details' => [
                        'slip_number' => $trip->slip_number,
                        'vehicle_no' => $trip->vehicle_number,
                        'arrival' => $trip->arrival_time ?? '-',
                        'departure' => $trip->departure_time ?? '-',
                    ],
                    'total_amount' => 0, 
                    'paid_amount' => 0,
                    'balance_amount' => 0,
                ];
            });
            
            $allData = $allData->merge($trips);
        }

        $sortedData = $allData->sortByDesc('entry_date')->values();

        return response()->json([
            "draw"            => intval($request->input('draw', 0)),
            "recordsTotal"    => count($sortedData),
            "recordsFiltered" => count($sortedData),
            "is_high_level"   => $isHighLevel,
            "user_id"         => auth()->user() ? auth()->user()->id : null, 
            "data"            => $sortedData
        ]);
    }

   // 🔥 FIX 2: Print error ("is_god on null") hamesha ke liye khatam
    public function printPreview($id)
    {
        // Web route se hit hone par context kabhi kabhi null ho jata hai, 
        // isliye hum directly table se find karenge bina is_god check kiye.
        $entry = SiteDailyEntry::with(['enteredBy', 'allocation.company', 'allocation.branch'])->findOrFail($id);

        $data = [
            'entry' => $entry,
            'company' => $entry->allocation->company ?? null,
            'branch' => $entry->allocation->branch ?? null,
            'details' => $entry->entry_details // Ye JSON Array View me jayega
        ];

        return view('admin.site_allocations.print', $data);
    }
// 🔥 NAYA: UNIQUE SHOPS FETCH KARNE KE LIYE (Auto-fill)
    public function getShops()
    {
        $shops = SiteDailyEntry::where('category', 'Material')
            ->whereNotNull('entry_details->shop_name')
            ->select('entry_details')
            ->get()
            ->pluck('entry_details')
            ->unique('shop_name')
            ->values();

        return response()->json(['status' => 'success', 'data' => $shops]);
    }

 public function update(Request $request, $id)
    {
        $entry = SiteDailyEntry::findOrFail($id);
        $entryData = $request->entries[0];
        
        $oldData = [
            'total_amount' => $entry->total_amount, 'paid_amount' => $entry->paid_amount,
            'balance_amount' => $entry->balance_amount, 'entry_details' => $entry->entry_details
        ];

        DB::beginTransaction();
        try {
            $totalAmount = floatval($entryData['total_amount'] ?? 0);
            $paidAmount = floatval($entryData['paid_amount'] ?? 0);
            $balanceAmount = $totalAmount - $paidAmount;
            $status = ($balanceAmount <= 0) ? 'paid' : 'pending';

            $mediaConverter = new \App\Services\MediaConverterService();
            $details = $entryData['details'] ?? [];

            // 🔥 Old photos ko preserve rakhna agar naye upload na hue hon
            if(isset($entry->entry_details['arrival_image'])) $details['arrival_image'] = $entry->entry_details['arrival_image'];
            if(isset($entry->entry_details['departure_image'])) $details['departure_image'] = $entry->entry_details['departure_image'];

            // Naye upload hue hain to update kar do
            if ($request->hasFile("entries.0.arrival_image")) {
                $media = $mediaConverter->uploadAndConvert($request->file("entries.0.arrival_image"));
                if ($media) $details['arrival_image'] = $media->file_path;
            }
            if ($request->hasFile("entries.0.departure_image")) {
                $media = $mediaConverter->uploadAndConvert($request->file("entries.0.departure_image"));
                if ($media) $details['departure_image'] = $media->file_path;
            }

            $entry->update([
                'total_amount' => $totalAmount, 'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount, 'status' => $status,
                'entry_details' => $details
            ]);

            // Save FULL History Array
            SiteEntryHistory::create([
                'site_daily_entry_id' => $entry->id,
                'edited_by_id' => auth()->user()->id,
                'action' => 'edited',
                'old_data' => $oldData,
                'new_data' => [
                    'total_amount' => $totalAmount, 'paid_amount' => $paidAmount,
                    'balance_amount' => $balanceAmount, 'entry_details' => $details
                ]
            ]);
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Entry Updated!']);
        } catch (\Exception $e) {
            DB::rollBack(); return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // 🔥 NAYA: GET EDIT HISTORY
    public function getHistory($id)
    {
        $context = $this->getGlobalContext();
        if (!$context->is_god && !$context->is_director && auth()->user()->email !== 'admin@jankivilla.com') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized Access'], 403);
        }

        $history = SiteEntryHistory::with('editor:id,full_name')->where('site_daily_entry_id', $id)->latest()->get();
        return response()->json(['status' => 'success', 'data' => $history]);
    }

    // 🔥 NAYA: BULK DELETE
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (empty($ids)) return response()->json(['status' => 'error', 'message' => 'No records selected!'], 400);

        SiteDailyEntry::whereIn('id', $ids)->delete();
        return response()->json(['status' => 'success', 'message' => 'Entries deleted successfully!']);
    }
}

