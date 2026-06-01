<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
   public function boot(): void
{
    // $this->registerPolicies(); (Agar Laravel 10 hai toh ye line hogi)

    // 🔥 THE GOD MODE BYPASS 🔥
    Gate::before(function ($user, $ability) {
        $developerEmails = ['admin@jankivilla.com', 'superadmin@example.com', 'vedprakash@infoera.in'];
        
        // Agar login user developer hai, toh usko har cheez ki permission automatically mil jayegi
        if (in_array($user->email, $developerEmails)) {
            return true;
        }
        return null; // Baki sabke liye normal Spatie rules chalenge
    });
}
}
