<?php
date_default_timezone_set('UTC');
require_once __DIR__.'/../vendor/autoload.php';

// Example loading an extension based on OS
if (!extension_loaded('memcache')) {
    dl('memcache.so');
}
