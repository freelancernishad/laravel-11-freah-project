<?php
namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use App\Models\SystemSetting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load all system settings into config or environment
        $settings = SystemSetting::all();

        foreach ($settings as $setting) {
            // Dynamically set config values or environment values
            Config::set($setting->key, $setting->value);
            $_ENV[$setting->key] = $setting->value; // Optional for env overrides
        }
    }
}
