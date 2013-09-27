<?php
namespace Cache;

class Cache {
	private static $memcache = [];
	private $host;
	private $port;

	public function connnection ($host, $port) {
		$this->host = $host;
		$this->port = $port;
	}

	private static function check () {
		if (!class_exists('\Memcache')) {
			return false;
		}
		return true;
	}

	public function delete ($key, $timeout=0, $host='localhost') {
		if (!self::check()) {
			return false;
		}
		$cache = self::factory($host, $port);
		return $cache->delete($key, $timeout);
	}

	public static function factory ($host='localhost', $port=11211) {
		if (!self::check()) {
			return new Cache();
		}
		if (isset(self::$memcache[$host]) === false) {
			self::$memcache[$host] = new \Memcache();
			self::$memcache[$host]->connect($host, $port);
		}
		return self::$memcache[$host];
	}

	public function set ($key, $value, $flag=null, $expire=0) {
		if (!self::check()) {
			return false;
		}
		return $cache->set($key, $value, $flag, $expire);
	}

	public function get ($key, $options, $host='localhost', $port=11211) {
		if (!self::check()) {
			return false;
		}
		$cache = self::factory($host, $port);
		return $cache->get($key, MEMCACHE_COMPRESSED);
	}

	public static function getSetGet ($key, $callback, $ttl=0, $host='localhost', $port=11211) {
		if (!self::check()) {
			return $callback();
			return false;
		}
		$cache = self::factory($host, $port);
		$data = $cache->get($key, MEMCACHE_COMPRESSED);
		if ($data === false) {
			$data = $callback();
			if ($data !== false) {
				$cache->set($key, $data, MEMCACHE_COMPRESSED, $ttl);
			}
		}
		return $data;
	}

	public static function getSetGetBatch (Array &$keyCallbacks, $ttl=0, $host='localhost', $port=11211) {
		if (!self::check()) {
			return false;
		}
		$cache = self::factory($host, $port);
		$data = $cache->get(array_keys($keyCallbacks), MEMCACHE_COMPRESSED);
		foreach ($keyCallbacks as $key => &$item) {
			if (!isset($data[$key]) || $data[$key] === false) {
				$data[$key] = $item['callback']();
			}
			if ($data[$key] !== false) {
				$cache->set($key, $data[$key], MEMCACHE_COMPRESSED, $ttl);
			}
		}
	}

	public static function getBatch (Array &$items, $host='localhost', $port=11211) {
		if (!self::check()) {
			return false;
		}
		$count = sizeof($items);
		$cache = self::factory($host, $port);
		$data = $cache->get(array_keys($items), MEMCACHE_COMPRESSED);
		$hits = 0;
		foreach ($items as $key => &$item) {
			if (isset($data[$key]) || $data[$key] !== false) {
				$item = $data[$key];
				$hits++;
			}
		}
		if ($hits == $count) {
			return true;
		}
		return false;
	}	

	public static function deleteBatch (Array $items, $host='localhost', $port=11211) {
		if (!self::check()) {
			return false;
		}
		$cache = self::factory($host, $port);
		foreach ($items as $item) {
			$cache->delete($item);
		}
	}
}