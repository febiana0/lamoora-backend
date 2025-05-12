<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     */
  public function boot(): void
{
    $this->routes(function () {
    Route::prefix('api')
        ->middleware('api')
        // ->namespace($this->namespace)  // Komentari atau hapus baris ini
        ->group(base_path('routes/api.php'));

    Route::middleware('web')
        // ->namespace($this->namespace)  // Komentari atau hapus baris ini
        ->group(base_path('routes/web.php'));
    
    });
}
}
