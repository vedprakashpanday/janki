<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    public function index()
    {
       $customers = Customer::with('branch')->orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $customers]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'customer_mobile' => 'required',
            'booking_date' => 'required|date',
            'member_id' => 'required|exists:members,member_id',
        ]);

       $data = $request->all();

    // 1. Branch fetch karke details nikalna
    $branch = \App\Models\Branch::findOrFail($request->branch_id);
    $branchParts = explode('/', $branch->branch_id); // [JV, BR, DBG1, 2025]
    
    $stateCode = $branchParts[1] ?? 'ST';
    $distCode  = $branchParts[2] ?? 'DIST';

    // 2. Highest Sequence + 1 Logic
    $lastCust = \App\Models\Customer::where('branch_id', $branch->id)
                                    ->orderBy('id', 'desc')
                                    ->first();

    if ($lastCust && $lastCust->customer_id) {
        $lastIdParts = explode('/', $lastCust->customer_id);
        $lastSeq = (int) end($lastIdParts); 
        $nextSeq = $lastSeq + 1;
    } else {
        $nextSeq = 1;
    }

    $sequence = str_pad($nextSeq, 2, '0', STR_PAD_LEFT);

    // 3. Final ID: CUST/BR/DBG1/01
    $data['customer_id'] = "CUST/{$stateCode}/{$distCode}/{$sequence}";
    
    // 4. Password (6 digits)
    $data['password'] = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);

        // 3. File Upload Loop (Sari files ek sath handle)
        $fileFields = [
            'aadharcard',
            'pancard',
            'bank_passbook_pdf',
            'drivinglicense',
            'passport',
            'passport_photo',
            'tenthmarksheet',
            'twelvethmarksheet',
            'graduationcertificate',
            'pgcertificate',
            'otherdoc',
            'nom_aadharcard',
            'nom_pancard',
            'nom_bankpassbook',
            'nom_drivinglicense',
            'nom_passport',
            'nom_passport_photo',
            'nom_tenthmarksheet',
            'nom_twelvethmarksheet',
            'nom_graduationcertificate',
            'nom_pgcertificate',
            'nom_otherdoc'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                
                // VS Code ko batane ke liye ki ye ek UploadedFile class hai
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $request->file($field);
                
                $ext = $file->getClientOriginalExtension();
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $file->move(public_path('uploads/customers'), $filename);
                $data[$field] = 'uploads/customers/' . $filename;
            }
        }

        $customer = Customer::create($data);

        return response()->json(['status' => 'success', 'message' => "Saved! ID: {$data['customer_id']}"]);
    }

   // GET: Ek specific customer fetch karein (Edit aur View Modal ke liye)
    public function show($id)
    {
        // Yahan 'with('branch')' add kar diya gaya hai
        $customer = Customer::with('branch')->findOrFail($id);
        
        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
      // Password ko allow kiya gaya hai, par 'customer_id' fixed rahega
$data = $request->except(['_token', 'customer_id', '_method']);

// Agar password field khali aayi hai, to purana password hi rehne do
if(empty($data['password'])) {
    unset($data['password']);
}

        // File Update Logic (Purani file delete nahi kar rahe abhi safety ke liye)
        $fileFields = [
            'aadharcard',
            'pancard',
            'bank_passbook_pdf',
            'drivinglicense',
            'passport',
            'passport_photo',
            'tenthmarksheet',
            'twelvethmarksheet',
            'graduationcertificate',
            'pgcertificate',
            'otherdoc',
            'nom_aadharcard',
            'nom_pancard',
            'nom_bankpassbook',
            'nom_drivinglicense',
            'nom_passport',
            'nom_passport_photo',
            'nom_tenthmarksheet',
            'nom_twelvethmarksheet',
            'nom_graduationcertificate',
            'nom_pgcertificate',
            'nom_otherdoc'
            ];


        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                
                // VS Code ko batane ke liye ki ye ek UploadedFile class hai
                /** @var \Illuminate\Http\UploadedFile $file */
                $file = $request->file($field);
                
                $ext = $file->getClientOriginalExtension();
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $file->move(public_path('uploads/customers'), $filename);
                $data[$field] = 'uploads/customers/' . $filename;
            }
        }

        $customer->update($data);
        return response()->json(['status' => 'success', 'message' => 'Customer updated']);
    }

    public function destroy($id)
    {
        Customer::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Deleted']);
    }
}
