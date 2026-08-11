<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ShuttleFiring;
use App\Models\TonneliFiring;
use App\Models\RawMaterialPurchase;
use App\Models\PackagingPurchase;
use App\Observers\ShuttleFiringObserver;
use App\Observers\TonneliFiringObserver;
use App\Observers\RawMaterialPurchaseObserver;
use App\Observers\PackagingPurchaseObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ShuttleFiring::observe(ShuttleFiringObserver::class);
        TonneliFiring::observe(TonneliFiringObserver::class);
        RawMaterialPurchase::observe(RawMaterialPurchaseObserver::class);
        PackagingPurchase::observe(PackagingPurchaseObserver::class);
    }
}