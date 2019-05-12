<?php

/*
 * This file is part of the laravel-cat package.
 *
 * (c) Yufunny <mxy@yufu.fun>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yufunny\LaravelCat;

use Illuminate\Http\Request;
use Yufunny\LaravelCat\Exceptions\ThrottleException;

class Throttle
{

    private $rules = [];
    /**
     * @var Request $request
     */
    private $request;
    /**
     * @var \redis $redis
     */
    private $redis;
    private $prefix = 'cat-throttle';
    private $name = '';
    private $controls = [];
    private $acquiredIdx = -1;

    /**
     * ThrottleMiddleware constructor.
     * @param $rules
     * @throws ThrottleException
     */
    public function __construct($rules)
    {
        $this->rules = $rules;
        $this->request = app('request');
        $this->name = trim($this->request->getPathInfo(), '/');
        if (empty($rules)) {
            throw new ThrottleException("[cat.throttle.{$this->name}] config not exist");
        }
        $this->redis = app('redis');
        $this->setControls();
    }

    /**
     * @throws ThrottleException
     */
    private function setControls()
    {
        $params = $this->request->all();
        if (!isset($params['ip'])) {
            $params['ip'] = $this->request->ip();
        }
        foreach ($this->rules as $idx => $rule) {
            $config = [];
            list($config['group'], $config['duration'], $config['max']) = explode('%', $rule);
            $keys = explode('-', $config['group']);
            if (empty($keys) || empty($config['duration']) || empty($config['max'])) {
                throw new ThrottleException("[cat.throttle.{$this->name}]config error");
            }
            $controlKey = $idx;
            $broken = false;
            foreach ($keys as $key) {
                if (empty($params[$key])) {
                    $broken = true;
                    break;
                }
                if (! is_string($params[$key]) && ! is_numeric($params[$key])) {
                    throw new ThrottleException("[cat.throttle.{$this->name}]throttle parameter [$key]  non-string");
                }
                $controlKey .=  '_' . $params[$key];
            }
            if ($broken) {
                continue;
            }
            $this->controls[] = [
                'key' => $controlKey,
                'rule' => $config,
            ];
        }
    }

    public function acquire()
    {
        $idx = -1;
        foreach ($this->controls as $idx => $control) {
            $key = $this->prefix . ':' . $this->name . ':' . $control['key'];
            $max = $control['rule']['max'];
            $duration = $control['rule']['duration'];

            $got = $this->redis->get($key);
            if ($got >= $max) {
                $this->acquiredIdx = $idx - 1;
                return [
                    false,
                    "[cat.throttle.{$this->name}]:{$control['rule']['group']} request more than {$max} times in {$duration} seconds"
                ];
            }
            $res = $this->redis->incr($key);
            if ($res > $max) {
                $this->acquiredIdx = $idx - 1;
                return [
                    false,
                    "[cat.throttle.{$this->name}]:{$control['rule']['group']} request more than {$max} times in {$duration} seconds"
                ];
            }
            if (1 === $res) {
                $this->redis->expire($key, $duration);
            }
        }
        $this->acquiredIdx = $idx;
        return [true, ''];
    }

    public function release()
    {
        return [true, ''];
    }
}