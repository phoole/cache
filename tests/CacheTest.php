<?php

declare(strict_types=1);

namespace Phoole\Tests;

use Phoole\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Phoole\Cache\Adaptor\FileAdaptor;

class CacheTest extends TestCase
{
    private $adaptor;

    private $obj;

    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adaptor = new FileAdaptor();
        $this->obj = new Cache($this->adaptor);
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->adaptor->clear();
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
     * @covers Phoole\Cache\Cache::get()
     */
    public function testGet()
    {
        // not exist
        $this->assertTrue(NULL === $this->obj->get('bingo'));

        // default value
        $this->assertTrue('default' === $this->obj->get('bingo', 'default'));

        // set & get
        $this->obj->set('bingo', 'bingo');
        $this->assertTrue('bingo' === $this->obj->get('bingo'));
    }

    /**
     * @covers Phoole\Cache\Cache::set()
     */
    public function testSet()
    {
        // set & expired
        $this->obj->set('bingo', 'wow', -86400);
        $this->assertTrue(NULL === $this->obj->get('bingo'));

        // set different data type
        $data = ['a', 'b'];
        $this->obj->set('bingo', $data);
        $this->assertEquals($data, $this->obj->get('bingo'));
    }

    /**
     * @covers Phoole\Cache\Cache::delete()
     */
    public function testDelete()
    {
        // delete non-exists
        $this->assertFalse($this->obj->delete('bingo'));

        // set & delete
        $this->obj->set('bingo', 'bingo');
        $this->assertTrue('bingo' === $this->obj->get('bingo'));
        $this->assertTrue($this->obj->delete('bingo'));
        $this->assertTrue(NULL === $this->obj->get('bingo'));
    }

    /**
     * @covers Phoole\Cache\Cache::clear()
     */
    public function testClear()
    {
        $this->obj->set('wow', 'wow');
        $this->obj->set('bingo', 'bingo');

        $this->assertTrue('wow' === $this->obj->get('wow'));
        $this->obj->clear();

        $this->assertTrue(NULL === $this->obj->get('wow'));
        $this->assertTrue(NULL === $this->obj->get('bingo'));
    }

    /**
     * @covers Phoole\Cache\Cache::getMultiple()
     */
    public function testGetMultiple()
    {
        $this->obj->set('wow', 'wow');
        $this->obj->set('bingo', 'wow');

        // normal
        $this->assertEquals(
            ['wow' => 'wow', 'bingo' => 'wow'],
            $this->obj->getMultiple(['wow', 'bingo'])
        );

        $this->obj->clear();

        // null
        $this->assertEquals(
            ['wow' => NULL, 'bingo' => NULL],
            $this->obj->getMultiple(['wow', 'bingo'])
        );

        // default
        $this->assertEquals(
            ['wow' => 'x', 'bingo' => 'x'],
            $this->obj->getMultiple(['wow', 'bingo'], 'x')
        );
    }

    /**
     * @covers Phoole\Cache\Cache::setMultiple()
     */
    public function testSetMultiple()
    {
        $a = ['wow' => 'wow', 'bingo' => 'wow'];
        $this->obj->setMultiple($a);

        $this->assertEquals(
            $a,
            $this->obj->getMultiple(array_keys($a))
        );

        // use default
        $this->obj->delete('wow');
        $this->assertEquals(
            ['wow' => 'x', 'bingo' => 'wow'],
            $this->obj->getMultiple(array_keys($a), 'x')
        );
    }

    /**
     * @covers Phoole\Cache\Cache::deleteMultiple()
     */
    public function testDeleteMultiple()
    {
        $a = ['wow' => 'wow', 'bingo' => 'wow'];
        $this->obj->setMultiple($a);
        $this->obj->deleteMultiple(array_keys($a));

        $this->assertEquals(
            ['wow' => 'x', 'bingo' => 'x'],
            $this->obj->getMultiple(array_keys($a), 'x')
        );
    }

    /**
     * @covers Phoole\Cache\Cache::has()
     */
    public function testHas()
    {
        $this->assertFalse($this->obj->has('bingo'));
        $this->obj->set('bingo', 'bingo');
        $this->assertTrue($this->obj->has('bingo'));

        // expired
        $this->obj->set('bingo', 'bingo', -86400);
        $this->assertFalse($this->obj->has('bingo'));
    }

    /**
     * @covers Phoole\Cache\Cache::byPass()
     */
    public function testSetByPass()
    {
        $this->obj->set('bingo', 'bingo');
        $this->assertTrue($this->obj->has('bingo'));
        $this->obj->setByPass(TRUE);
        $this->assertFalse($this->obj->has('bingo'));

        $this->obj->setByPass(FALSE);
        $this->assertTrue($this->obj->has('bingo'));
    }

    /**
     * @covers Phoole\Cache\Cache::checkKey()
     */
    public function testCheckKey()
    {
        $key = 'key';
        $this->assertEquals($key, $this->invokeMethod('checkKey', [$key]));

        $key = ['a'];
        $this->expectExceptionMessage('Array to string');
        $this->assertEquals($key, $this->invokeMethod('checkKey', [$key]));
    }

    /**
     * @covers Phoole\Cache\Cache::checkTime()
     */
    public function testCheckTime()
    {
        // expired
        $time = time() - 120;
        $this->assertFalse($this->invokeMethod('checkTime', [$time]));

        // valid
        $time = time() + 2;
        $this->assertTrue($this->invokeMethod('checkTime', [$time]));
    }

    /**
     * @covers Phoole\Cache\Cache::getTTL()
     */
    public function testGetTTL()
    {
        // default TTL
        $val = $this->invokeMethod('getTTL', [NULL]);
        $this->assertTrue(abs(86400 - $val) <= 86400 * 0.055);

        // set time
        $val = $this->invokeMethod('getTTL', [1000]);
        $this->assertTrue(abs(1000 - $val) <= 1000 * 0.055);
    }
}
