<?php
/**
 * Opine\Cache
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Opine\Cache;

use Exception;
use Closure;
use Opine\Interfaces\Cache as CacheInterface;
use Symfony\Component\Yaml\Yaml;

class Service implements CacheInterface
{
    private $engine;
    private $host = false;
    private $port = false;
    private $root;

    public function __construct($root)
    {
        $this->root = $root;

        // determine config environment
        $environment = 'default';
        $test = getenv('OPINE_ENV');
        if ($test !== false) {
            $environment = $test;
        }
        if ($environment == 'default') {
            $environment = '.';
        }

        // determine path of file
        $path = $root . '/../config/settings/' . $environment . '/cache.yml';
        if (!file_exists($path) && $environment != '.') {
            $path = $root . '/config/settings/cache.yml';
        }
        if (!file_exists($path)) {
            return;
        }

        if (class_exists('\Memcache')) {
            // read configuration
            $config = Yaml::parse(file_get_contents($path));
            $this->host = $config['settings']['host'];
            $this->port = $config['settings']['port'];
            $this->engine = new \Memcache();
            return;
        }
        $this->engine = new FileCache($this->root);
    }

    public function delete($key, $timeout = 0)
    {
        $result = @$this->engine->pconnect($this->host, $this->port);
        if ($result === false) {
            return false;
        }

        return $this->engine->delete($key, $timeout);
    }

    public function set($key, $value, $expire = 0, $flag = 2)
    {
        $result = @$this->engine->pconnect($this->host, $this->port);
        if ($result === false) {
            return false;
        }

        return $this->engine->set($key, $value, $flag, $expire);
    }

    public function get($key, $flag = 2)
    {
        $result = @$this->engine->pconnect($this->host, $this->port);
        if ($result === false) {
            return false;
        }

        return $this->engine->get($key, $flag);
    }

    public function getSetGet($key, Closure $callback, $ttl = 0, $flag = 2)
    {
        $result = @$this->engine->pconnect($this->host, $this->port);
        if ($result === false) {
            return false;
        }
        $data = $this->engine->get($key, $flag);
        if ($data === false) {
            $data = $callback();
            if ($data !== false) {
                $this->engine->set($key, $data, $flag, $ttl);
            }
        }

        return $data;
    }

    public function getSetGetBatch(Array &$items, $ttl = 0, $flag = 2)
    {
        foreach ($items as $item) {
            if (!is_callable($item)) {
                throw new Exception('each item must have a callback defined');
            }
        }
        $result = @$this->engine->pconnect($this->host, $this->port);
        if ($result === false) {
            return false;
        }
        $data = $this->engine->get(array_keys($items));
        foreach ($items as $key => &$item) {
            if (!isset($data[$key]) || $data[$key] === false) {
                $items[$key] = $item();
            } else {
                $items[$key] = $data[$key];
            }
            if ($items[$key] !== false) {
                $this->engine->set($key, $items[$key], $flag, $ttl);
            }
        }

        return true;
    }

    public function getBatch(Array &$items, $flag = 2)
    {
        $count = sizeof($items);
        $result = @$this->engine->pconnect($this->host, $this->port);
        if ($result === false) {
            return false;
        }
        $data = $this->engine->get(array_keys($items), $flag);
        $hits = 0;
        foreach ($items as $key => $item) {
            if (array_key_exists($key, $data)) {
                $items[$key] = $data[$key];
                $hits++;
            }
        }
        return $hits == $count;
    }

    public function deleteBatch(Array $items, $timeout = 0)
    {
        $result = @$this->engine->pconnect($this->host, $this->port);
        if ($result === false) {
            return false;
        }
        foreach ($items as $item) {
            $this->engine->delete($item, $timeout);
        }

        return true;
    }
}
