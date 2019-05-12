<?php

/*
 * This file is part of the laravel-cat package.
 *
 * (c) yufu <mxy@yufu.fun>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yufunny\LaravelCat\Middleware;

use Closure;
use Yufunny\LaravelCat\Concurrency;
use Yufunny\LaravelCat\Exceptions\ConcurrencyException;


class ConcurrencyMiddleware
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param $rule
     * @return mixed
     * @throws ConcurrencyException
     */
    public function handle($request, Closure $next, $rule)
    {
        $rules = explode('|', $rule);
        $concurrencyService = new Concurrency($rules);
        list($ok, $msg) = $concurrencyService->acquire();
        if (!$ok) {
            $response = response($msg, 400);
            goto done;
        }
        try {
            $response = $next($request);
        } catch (\Exception $e) {
            app('log')->info('concurrency exception', [$e->getMessage(), $e->getCode()]);
            $response = response('system busy', 400);
        }
        done:
        $concurrencyService->release();
        return $response;
    }
}
