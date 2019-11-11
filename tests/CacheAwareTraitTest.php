<?php

declare(strict_types=1);

namespace Phoole\Tests;

use Phoole\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Phoole\Cache\CacheAwareTrait;
use Phoole\Cache\CacheAwareInterface;

class MyCacheAware implements CacheAwareInterface
{
    use CacheAwareTrait;
}

class CacheAwareTraitTest extends TestCase
{
    private $adaptor;

    private $obj;

    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new MyCacheAware();
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->obj = $this->ref = NULL;
        parent::tearDown();
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(TRUE);
        return $method->invokeArgs($this->obj, $parameters);
    }

    /**
     * @covers Phoole\Cache\CacheAwareTrait::setCache()
     */
    public function testSetCache()
    {
        $cache = new Cache();
        $this->obj->setCache($cache);

        $this->assertTrue($cache === $this->obj->getCache());
    }

    /**
     * @covers Phoole\Cache\CacheAwareTrait::getCache()
     */
    public function testGetCache()
    {
        $this->expectExceptionMessage('cache not initialized');
        $this->obj->getCache();
    }
}