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

	public static function getSetGet ($key, $callback, $host='localhost', $port=11211) {
		$cache = self::factory($host, $port);
		$data = $cache->get($key, MEMCACHE_COMPRESSED);
		if ($data === false) {
			$data = $callback();
			if ($data !== false) {
				$cache->set($key, $data, MEMCACHE_COMPRESSED, 0);
			}
		}
		return $data;
	}
}