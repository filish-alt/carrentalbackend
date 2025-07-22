<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Car;
use App\Models\Home;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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

    Relation::morphMap([
        'car' => Car::class,
        'home' => Home::class,
    ]);

    RateLimiter::for('login', function ($request) {
        return Limit::perMinutes(5,5)
            ->by($request->input('email') . '|' . $request->ip())
             ->response(function () {
                return response()->json([
                    'message' => 'Too many login attempts. Please try again after 15 minutes.'
                ], 429);
            });

    });
    }
    
}
