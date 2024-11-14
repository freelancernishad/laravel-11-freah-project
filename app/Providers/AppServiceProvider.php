<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use App\Models\SystemSetting;
use Illuminate\Database\QueryException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            // Attempt to load all system settings into config or environment
            $settings = SystemSetting::all();

            foreach ($settings as $setting) {
                // Dynamically set config values or environment values
                Config::set($setting->key, $setting->value);
                $_ENV[$setting->key] = $setting->value; // Optional for env overrides
            }
        } catch (QueryException $e) {
            // Log the error but continue running the application
            \Log::error('Error loading system settings: ' . $e->getMessage());
        }
    }
}
