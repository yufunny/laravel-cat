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
use Yufunny\LaravelCat\Throttle;

class ThrottleMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @param $rule
     * @return mixed
     * @throws \Yufunny\LaravelCat\Exceptions\ThrottleException
     */
    public function handle($request, Closure $next, $rule)
    {
        $rules = explode('|', $rule);
        $throttleService = new Throttle($rules);
        list($ok, $msg) = $throttleService->acquire();
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
        $throttleService->release();
        return $response;
    }
}
