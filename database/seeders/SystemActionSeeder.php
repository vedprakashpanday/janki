<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemAction;

class SystemActionSeeder extends Seeder
{
    public function run(): void
    {
        $actions = [
            ['action_name' => 'View Listing', 'action_slug' => 'view'],
            ['action_name' => 'Request Entry', 'action_slug' => 'add_request'],
            ['action_name' => 'Direct Entry', 'action_slug' => 'add_direct'],
            ['action_name' => 'Edit Record', 'action_slug' => 'edit'],
            ['action_name' => 'Delete Record', 'action_slug' => 'delete'],
            ['action_name' => 'Restore Record', 'action_slug' => 'restore'],
            ['action_name' => 'Print & Export', 'action_slug' => 'print'],
        ];

        foreach ($actions as $action) {
            SystemAction::firstOrCreate(
                ['action_slug' => $action['action_slug']],
                ['action_name' => $action['action_name'], 'status' => 'active']
            );
        }
    }
}