<?php
namespace Opine\Cache;

class FileCache {
    private $root;

    private function getKeyFilePath (string $name):string
    {
        return $this->root . md5($name) . '.txt';
    }

    public function __construct (string $root)
    {
        $this->root = $root . '/../var/cache/';
    }

    // just here for Memcache compatibilty
    public function pconnect($host, $port):bool
    {
        return true;
    }

    public function delete ($key):bool
    {
        $path = $this->getKeyFilePath($key);
        if (!file_exists($path)) {
            return false;
        }
        return unlink($path);
    }

    public function set($key, $value, $flag, $expire):bool
    {
        $path = $this->getKeyFilePath($key);
        if (!is_string((string)$value)) {
            return false;
        }
        $value = (string)$value;
        file_put_contents($path, $value);
        return true;
    }

    public function get ($key)
    {
        if (is_array($key)) {
            $response = [];
            foreach ($key as $value) {
                $path = $this->getKeyFilePath($value);
                if (!file_exists($path)) {
                    $response[$value] = false;
                    continue;
                }
                $response[$value] = file_get_contents($path);
            }
            return $response;
        }
        $path = $this->getKeyFilePath($key);
        if (!file_exists($path)) {
            return false;
        }
        return file_get_contents($path);
    }
}
