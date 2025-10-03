<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot(): void
    {
        $this->routes(function () {
            // Carrega as rotas de API
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            // Carrega as rotas web
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
