<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Site;
use App\Models\Invoice;
use App\Observers\SiteObserver;
use App\Observers\InvoiceObserver;

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
        // Enregistrer les observers pour la promotion automatique prospect -> client
        Site::observe(SiteObserver::class);
        Invoice::observe(InvoiceObserver::class);
    }
}
