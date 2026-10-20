<?php

namespace App\Providers;

use App\Models\ItemReport;
use App\Models\LostItem;
use App\Policies\ItemReportPolicy;
use App\Policies\LostItemPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        // Registrasi Policies untuk Otorisasi Terpusat
        Gate::policy(LostItem::class, LostItemPolicy::class);
        Gate::policy(ItemReport::class, ItemReportPolicy::class);

        // Rate limiter untuk endpoint autentikasi publik (maks 5 request / menit per IP)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate limiter untuk pelaporan penemuan pihak ketiga (maks 10 request / menit per pengguna)
        RateLimiter::for('reports', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
