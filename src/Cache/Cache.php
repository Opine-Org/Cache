<?php
namespace Cache;

class Cache extends \Memcache {
	private static $memcache = [];

	public static function factory ($host='localhost', $port=11211) {
		if (isset(self::$memcache[$host]) === false) {
			self::$memcache[$host] = new \Memcache();
			self::$memcache[$host]->connect($host, $port);
		}
		return self::$memcache[$host];
	}

	public function get ($key, $flags=null) {
		return parent::get($key, $flags);
	}

	public static function getSetGet ($key, $callback, $ttl=0, $host='localhost', $port=11211) {
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
		$cache = self::factory($host, $port);
		$data = $cache->get(array_keys($keyCallbacks), MEMCACHE_COMPRESSED);
		foreach ($keyCallbacks as $key => &$item) {
			if (!isset($data[$key]) || $data[$key] === false) {
				$data[$key] = $item['callback']();
				if ($data[$key] !== false) {
					$cache->set($key, $data[$key], MEMCACHE_COMPRESSED, $ttl);
				}
			}
		}
	}	
}