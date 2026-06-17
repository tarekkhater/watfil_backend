<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\CustomerCompanyLink;
use App\Models\Order;
use App\Models\WithdrawalRequest;
use App\Policies\CustomerCompanyLinkPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\OrderPolicy;
use App\Policies\WithdrawalRequestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(WithdrawalRequest::class, WithdrawalRequestPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CustomerCompanyLink::class, CustomerCompanyLinkPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
    }
}
