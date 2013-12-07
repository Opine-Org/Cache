<?php
/**
 * virtuecenter\cache
 *
 * Copyright (c)2013 Ryan Mahoney, https://github.com/virtuecenter <ryan@virtuecenter.com>
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
namespace Cache;

class Cache {
	private $memcache;
	private $host;
	private $port;

	private function check () {
		if (!class_exists('\Memcache')) {
			return false;
		}
		return true;
	}

	public function delete ($key, $timeout=0, $host='localhost') {
		if (!$this->check()) {
			return false;
		}
		$result = @$this->memcache->pconnect($this->host, $this->port);
		if ($result === false) {
			return false;
		}
		return $this->memcache->delete($key, $timeout);
	}

	public function __construct ($host='localhost', $port=11211) {
		if (!$this->check()) {
			return;
		}
		$this->host = $host;
		$this->port = $port;
		$this->memcache = new \Memcache();
	}

	public function set ($key, $value, $flag=null, $expire=0) {
		if (!$this->check()) {
			return false;
		}
		$result = @$this->memcache->pconnect($this->host, $this->port);
		if ($result === false) {
			return false;
		}
		return $this->memcache->set($key, $value, $flag, $expire);
	}

	public function get ($key, $host='localhost', $port=11211) {
		if (!$this->check()) {
			return false;
		}
		$result = @$this->memcache->pconnect($this->host, $this->port);
		if ($result === false) {
			return false;
		}
		return $this->memcache->get($key, MEMCACHE_COMPRESSED);
	}

	public function getSetGet ($key, $callback, $ttl=0, $host='localhost', $port=11211) {
		if (!$this->check()) {
			return $callback();
			return false;
		}
		$result = @$this->memcache->pconnect($this->host, $this->port);
		if ($result === false) {
			return false;
		}
		$data = $this->memcache->get($key, MEMCACHE_COMPRESSED);
		if ($data === false) {
			$data = $callback();
			if ($data !== false) {
				$this->memcache->set($key, $data, MEMCACHE_COMPRESSED, $ttl);
			}
		}
		return $data;
	}

	public function getSetGetBatch (Array &$keyCallbacks, $ttl=0, $host='localhost', $port=11211) {
		if (!$this->check()) {
			return false;
		}
		$result = @$this->memcache->pconnect($this->host, $this->port);
		if ($result === false) {
			return false;
		}
		$data = $this->memcache->get(array_keys($keyCallbacks), MEMCACHE_COMPRESSED);
		foreach ($keyCallbacks as $key => &$item) {
			if (!isset($data[$key]) || $data[$key] === false) {
				$data[$key] = $item['callback']();
			}
			if ($data[$key] !== false) {
				$this->memcache->set($key, $data[$key], MEMCACHE_COMPRESSED, $ttl);
			}
		}
	}

	public function getBatch (Array &$items, $host='localhost', $port=11211) {
		if (!$this->check()) {
			return false;
		}
		$count = sizeof($items);
		$result = @$this->memcache->pconnect($this->host, $this->port);
		if ($result === false) {
			return false;
		}
		$data = $this->memcache->get(array_keys($items), MEMCACHE_COMPRESSED);
		$hits = 0;
		foreach ($items as $key => &$item) {
			if (isset($data[$key]) && $data[$key] !== false) {
				$item = $data[$key];
				$hits++;
			}
		}
		if ($hits == $count) {
			return true;
		}
		return false;
	}	

	public function deleteBatch (Array $items, $host='localhost', $port=11211) {
		if (!$this->check()) {
			return false;
		}
		$result = @$this->memcache->pconnect($this->host, $this->port);
		if ($result === false) {
			return false;
		}
		foreach ($items as $item) {
			$this->memcache->delete($item);
		}
	}
}