<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EventDashboardController extends Controller
{
public function index(Request $request)
    {
        $filterType = $request->input('filter_type', 'today'); // 'today', 'date_range', 'month_range'
        $currentYear = Carbon::now()->format('Y');

        // 🎯 FIX: Variables ko pehle hi define kar diya taaki Undefined Variable error na aaye
        $startMd = null;
        $endMd = null;
        $startM = null;
        $endM = null;
        $displayTitle = "";

        // Filter Logic Setup
        if ($filterType === 'date_range') {
            $startMd = Carbon::parse($request->start_date)->format('m-d');
            $endMd = Carbon::parse($request->end_date)->format('m-d');
            $displayTitle = "Events from " . Carbon::parse($request->start_date)->format('d M') . " to " . Carbon::parse($request->end_date)->format('d M');
        } elseif ($filterType === 'month_range') {
            $startM = Carbon::parse($request->start_month)->format('m');
            $endM = Carbon::parse($request->end_month)->format('m');
            $displayTitle = "Events from " . Carbon::parse($request->start_month)->format('F') . " to " . Carbon::parse($request->end_month)->format('F');
        } else {
            // Default: TODAY
            $startMd = Carbon::today()->format('m-d');
            $endMd = Carbon::today()->format('m-d');
            $displayTitle = "Today's Events (" . Carbon::today()->format('d M Y') . ")";
        }

        $events = collect();

        $modelsConfig = [
            ['class' => \App\Models\Employee::class, 'type' => 'Employee', 'birthday' => 'dob', 'anniversary' => 'anniversary_date', 'work' => 'doj', 'status_col' => 'emp_status', 'id_col' => 'member_id'],
            ['class' => \App\Models\Agent::class, 'type' => 'Agent', 'birthday' => 'dob', 'anniversary' => 'anniversary_date', 'work' => 'joining_date', 'status_col' => 'agent_status', 'id_col' => 'agent_id'],
            ['class' => \App\Models\Director::class, 'type' => 'Director', 'birthday' => 'dob', 'anniversary' => 'anniversary_date', 'work' => null, 'status_col' => 'status', 'id_col' => 'director_id'],
            ['class' => \App\Models\Landowner::class, 'type' => 'Landowner', 'birthday' => 'lo_dob', 'anniversary' => null, 'work' => null, 'status_col' => 'status', 'id_col' => 'land_owner_id'],
            ['class' => \App\Models\Member::class, 'type' => 'Member', 'birthday' => 'dob', 'anniversary' => 'date_of_anniversary', 'work' => 'doj', 'status_col' => 'status', 'id_col' => 'member_id'],
            ['class' => \App\Models\Vendor::class, 'type' => 'Vendor', 'birthday' => 'dob', 'anniversary' => 'anniversary_date', 'work' => null, 'status_col' => 'vendor_status', 'id_col' => 'vendor_id'],
            ['class' => \App\Models\SuperAdmin::class, 'type' => 'SuperAdmin', 'birthday' => 'dob', 'anniversary' => 'anniversary_date', 'work' => null, 'status_col' => 'status', 'id_col' => 'ceo_id'],
            ['class' => \App\Models\Customer::class, 'type' => 'Customer', 'birthday' => 'dob', 'anniversary' => 'date_of_anniversary', 'work' => null, 'status_col' => 'status', 'id_col' => 'customer_id'],
        ];

        foreach ($modelsConfig as $config) {
            $modelClass = $config['class'];
            $type = $config['type'];
            $statusCol = $config['status_col'];
            $idCol = $config['id_col'];

            $withRelations = [];
            if (method_exists(new $modelClass, 'company')) {
                $withRelations[] = 'company';
            } elseif (method_exists(new $modelClass, 'companies')) {
                $withRelations[] = 'companies'; 
            }

            $queryBase = $modelClass::where($statusCol, 'active');
            if (!empty($withRelations)) {
                $queryBase = $queryBase->with($withRelations);
            }

            // Closure me variable pass karne se pehle wo define hone zaruri the
            $applyFilter = function($q, $column) use ($filterType, $startMd, $endMd, $startM, $endM) {
                if ($filterType === 'month_range') {
                    return $q->whereRaw("MONTH({$column}) BETWEEN ? AND ?", [$startM, $endM]);
                } else {
                    return $q->whereRaw("DATE_FORMAT({$column}, '%m-%d') BETWEEN ? AND ?", [$startMd, $endMd]);
                }
            };

            if ($config['birthday']) {
                $records = $applyFilter(clone $queryBase, $config['birthday'])->get();
                foreach ($records as $record) {
                    $events->push($this->formatEvent($record, $type, 'Date of Birth', $record->{$config['birthday']}, $idCol));
                }
            }

            if ($config['anniversary']) {
                $records = $applyFilter(clone $queryBase, $config['anniversary'])->get();
                foreach ($records as $record) {
                    $events->push($this->formatEvent($record, $type, 'Anniversary', $record->{$config['anniversary']}, $idCol));
                }
            }

            if ($config['work']) {
                $q = $applyFilter(clone $queryBase, $config['work'])->whereRaw("YEAR({$config['work']}) < ?", [$currentYear]);
                $records = $q->get();
                foreach ($records as $record) {
                    $events->push($this->formatEvent($record, $type, 'Work Anniversary', $record->{$config['work']}, $idCol));
                }
            }
        }

        $sortedEvents = $events->sortBy(function ($event) {
            return Carbon::parse($event['event_date'])->format('m-d');
        })->values();

        return response()->json([
            'success' => true,
            'display_title' => $displayTitle,
            'data' => $sortedEvents
        ]);
    }

    private function formatEvent($record, $type, $occasion, $date, $idCol)
    {
        $companyName = 'Janki Villa';

        if ($record->relationLoaded('company') && $record->company) {
            $companyName = $record->company->company_name ?? $record->company->name ?? 'Janki Villa';
        } elseif ($record->relationLoaded('companies') && $record->companies->isNotEmpty()) {
            $companyName = $record->companies->first()->company_name ?? $record->companies->first()->name ?? 'Janki Villa';
        }

        return [
            'id' => $record->id,
            'profile_id' => $record->{$idCol} ?? 'N/A',
            'name' => $record->name ?? $record->full_name ?? $record->customer_name ?? $record->member_name ?? $record->employee_name ?? 'N/A',
            'type' => $type,
            'occasion' => $occasion,
            'event_date' => $date,
            'formatted_date' => Carbon::parse($date)->format('d M'),
            'company_name' => $companyName
        ];
    }
}
