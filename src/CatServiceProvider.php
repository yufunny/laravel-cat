<?php

/*
 * This file is part of the laravel-cat package.
 *
 * (c) yufu <mxy@yufu.fun>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yufunny\LaravelCat;

use Illuminate\Support\ServiceProvider;
use Yufunny\LaravelCat\Middleware\ConcurrencyMiddleware;
use Yufunny\LaravelCat\Middleware\ThrottleMiddleware;
use Laravel\Lumen\Application as LumenApplication;

class CatServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function register()
    {
        if ($this->app instanceof LumenApplication) {
            $this->app->routeMiddleware([
                'cat.t' => ThrottleMiddleware::class,
                'cat.c' => ConcurrencyMiddleware::class,
            ]);
        } else {
            $router = $this->app->make('router');
            $method = 'middleware';
            if (method_exists($router, 'aliasMiddleware')) {
                $method = 'aliasMiddleware';
            }
            $router->$method('cat.throttle', ThrottleMiddleware::class);
            $router->$method('cat.concurrency', ConcurrencyMiddleware::class);
        }
    }
}
