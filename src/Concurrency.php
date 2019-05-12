<?php

/*
 * This file is part of the laravel-cat package.
 *
 * (c) Yufunny <mxy@yufu.fun>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Services\Throttle;


namespace Yufunny\LaravelCat;

use Illuminate\Http\Request;
use Yufunny\LaravelCat\Exceptions\ConcurrencyException;

class Concurrency
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
    private $prefix = 'cat-concurrency';
    private $name = '';
    private $controls = [];
    private $acquiredIdx = -1;

    /**
     * Concurrency constructor.
     * @param $rule
     * @throws ConcurrencyException
     */
    public function __construct($rule)
    {
        $this->rules = $rule;
        $this->request = app('request');
        $this->name = trim($this->request->getPathInfo(), '/');
        if (empty($rule)) {
            throw new ConcurrencyException("[cat.concurrency.{$this->name}]empty rule");
        }
        $this->redis = app('redis');
        $this->setKeys();
    }

    /**
     *
     *
     * @throws ConcurrencyException
     */
    private function setKeys()
    {
        $params = $this->request->all();
        if (!isset($params['ip'])) {
            $params['ip'] = $this->request->ip();
        }
        foreach ($this->rules as $idx => $rule) {
            $keys = explode('%', $rule);
            if (empty($keys)) {
                throw new ConcurrencyException("[cat.concurrency.{$this->name}]config error");
            }
            $controlKey = $idx;
            $broken = false;
            foreach ($keys as $key) {
                if (empty($params[$key])) {
                    $broken = true;
                    break;
                }
                if (! is_string($params[$key]) && !is_numeric($params[$key])) {
                    throw new ConcurrencyException("[cat.concurrency.{$this->name}]指定的并发控制参数[$key]非字符串");
                }
                $controlKey .=  '_' . $params[$key];
            }
            if ($broken) {
                continue;
            }
            $this->controls[] = [
                'key' => $controlKey,
                'rule' => $rule,
            ];
        }
    }

    public function acquire()
    {
        $idx = -1;
        foreach ($this->controls as $idx => $control) {
            $key = $this->prefix . ':' . $this->name . ':' . $control['key'];
            if (! $this->redis->setnx($key, 1)) {
                $this->acquiredIdx = $idx - 1;
                return [false, "[cat.concurrency.{$this->name}]:{$control['rule']}存在并发"];
            }
            $this->redis->expire($key, 30);
        }
        $this->acquiredIdx = $idx;
        return [true, ''];
    }

    public function release()
    {
        for ($i = 0; $i <= $this->acquiredIdx; $i++) {
            $controlKey = $this->controls[$i]['key'];
            $key = $this->prefix . ':' . $this->name . ':' . $controlKey;
            $this->redis->del($key);
        }
        return [true, ''];
    }
}
