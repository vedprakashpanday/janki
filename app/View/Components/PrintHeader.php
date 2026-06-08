<?php
namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\Company;

class PrintHeader extends Component
{
    public $company;
    public $branch;

    // 🔥 FIX: Ab hum ID nahi, directly Controller se bheja gaya object accept karenge
    public function __construct($company = null, $branch = null)
    {
        $this->company = $company;
        $this->branch = $branch;

        // Ek final safety net (Agar galti se controller ne company na bheji ho)
        if (!$this->company) {
            $user = auth()->user();
            $this->company = Company::find($user->company_id ?? 1);
        }
    }

    public function render()
    {
        return view('components.print-header');
    }
}