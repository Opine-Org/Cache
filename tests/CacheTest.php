<?php
namespace Opine\Cache;
use PHPUnit_Framework_TestCase;
use Opine\Cache\Service as Cache;
use Exception;

class CacheTest extends PHPUnit_Framework_TestCase {
    public function testSet () {
        $cache = new Cache();
        $this->assertTrue($cache->set('phpunit-test', 'A', 30, 0));
    }

    public function testGet () {
        $cache = new Cache();
        $this->assertTrue('A' === $cache->get('phpunit-test', 0));
    }

    public function testDelete () {
        $cache = new Cache();
        $this->assertTrue($cache->delete('phpunit-test', 0));
        $this->assertFalse($cache->get('phpunit-test', 0));
    }

    public function testGetSet () {
        $cache = new Cache();
        $this->assertTrue('B' === $cache->getSetGet('phpunit-test', function () {
            return 'B';
        }, 30, 0));
        $this->assertTrue('B' === $cache->get('phpunit-test', 0));
    }

    public function testGetSetGetBatchNoCallback () {
        $cache = new Cache();
        $items = [
            'phpunit-test'  => 'C',
            'phpunit-test2' => 'D'
        ];
        $caught = false;
        try {
            $cache->getSetGetBatch($items, 30, 0);
        } catch (Exception $e) {
            $caught = true;
        }
        $this->assertTrue($caught);
    }

    public function testGetSetGetBatch () {
        $cache = new Cache();
        $items = [
            'phpunit-test'  => function () { return 'C'; },
            'phpunit-test2' => function () { return 'D'; }
        ];
        $this->assertTrue($cache->getSetGetBatch($items, 30, 0));
        $this->assertTrue('B' === $items['phpunit-test']);
    }

    public function testGetBatch () {
        $cache = new Cache();
        $items = [
            'phpunit-test'  => function () { return 'C'; },
            'phpunit-test2' => function () { return 'D'; }
        ];
        $this->assertTrue($cache->getBatch($items, 0));
        $this->assertTrue('B' === $items['phpunit-test']);
        $this->assertTrue('D' === $items['phpunit-test2']);
    }

    public function testDeleteBatch () {
        $cache = new Cache();
        $items = ['phpunit-test', 'phpunit-test2'];
        $this->assertTrue($cache->deleteBatch($items));
        $this->assertFalse($cache->get('phpunit-test'));
        $this->assertFalse($cache->get('phpunit-test2'));
    }
}