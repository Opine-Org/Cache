<?php
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
		$this->memcache->pconnect($this->host, $this->port);
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
		$this->memcache->pconnect($this->host, $this->port);
		return $this->memcache->set($key, $value, $flag, $expire);
	}

	public function get ($key, $options, $host='localhost', $port=11211) {
		if (!$this->check()) {
			return false;
		}
		$this->memcache->pconnect($this->host, $this->port);
		return $this->memcache->get($key, MEMCACHE_COMPRESSED);
	}

	public function getSetGet ($key, $callback, $ttl=0, $host='localhost', $port=11211) {
		if (!$this->check()) {
			return $callback();
			return false;
		}
		$this->memcache->pconnect($this->host, $this->port);
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
		$this->memcache->pconnect($this->host, $this->port);
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
		$this->memcache->pconnect($this->host, $this->port);
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
		$this->memcache->pconnect($this->host, $this->port);
		foreach ($items as $item) {
			$this->memcache->delete($item);
		}
	}
}