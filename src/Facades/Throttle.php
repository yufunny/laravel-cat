<?php

/*
 * This file is part of the laravel-cat package.
 *
 * (c) yufu <mxy@yufu.fun>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yufunny\LaravelCat\Facades;

use Illuminate\Support\Facades\Facade;

class Throttle extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cat.throttle';
    }
}