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

    
    RateLimiter::for('resendOtp', function ($request) {
        $key = 'resendOtp:' . $request->ip();
        $attempts = cache()->get($key . ':attempts', 0);
        $waitTime = cache()->get($key . ':wait_time', 0);

        if (cache()->has($key . ':locked_until')) {
            $lockedUntil = cache()->get($key . ':locked_until');
            if (now()->lessThan($lockedUntil)) {
                $seconds = now()->diffInSeconds($lockedUntil);
                return Limit::none()->response(function () use ($seconds) {
                    return response()->json([
                        'message' => "Too many attempts. Try again in {$seconds} seconds."
                    ], 429);
                });
            }
        }

        
        cache()->increment($key . ':attempts');

        $attempts++;
     if ($attempts > 3) {
        $waitTime = $waitTime ? min($waitTime * 2, 3600) : 600; 
        cache()->put($key . ':wait_time', $waitTime, now()->addSeconds($waitTime));
        cache()->put($key . ':locked_until', now()->addSeconds($waitTime), now()->addSeconds($waitTime));
        cache()->put($key . ':attempts', 0, now()->addSeconds($waitTime));
    }

        return Limit::perMinute(1000); 
    });
}
}