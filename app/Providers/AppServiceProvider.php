<?php

namespace App\Providers;

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
        $uploadPath = public_path('uploads');

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        if (!is_writable($uploadPath)) {
            chmod($uploadPath, 0777);
        }
    }
}
