<?php

declare(strict_types=1);

namespace Phoole\Tests;

use PHPUnit\Framework\TestCase;
use Phoole\Cache\Adaptor\FileAdaptor;

class FileAdaptorTest extends TestCase
{
    private $obj;
    private $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $path = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'CacheTest';
        $this->obj = new FileAdaptor($path);
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    protected function tearDown(): void
    {
        $this->obj->clear();
        $this->obj = $this->ref = null;
        parent::tearDown();
    }

    protected function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->obj, $parameters);
    }

    /**
     * @covers Phoole\Cache\Adaptor\FileAdaptor::rmDir()
     */
    public function testRmDir()
    {
        $root = $this->invokeMethod('getRoot', []);
        $path = $this->invokeMethod('getPath', ['bingo']);

        // remove stale file
        touch($path, 0777, time() - 120);
        $this->invokeMethod('rmDir', [$root, true]);
        $this->assertFalse(file_exists($path));
        $this->assertTrue(file_exists($root));

        // remove whole directory
        $this->invokeMethod('rmDir', [$root]);
        $this->assertFalse(file_exists($root));
    }

    /**
     * @covers Phoole\Cache\Adaptor\FileAdaptor::clear()
     */
    public function testClear()
    {
        $root = $this->invokeMethod('getRoot', []);
        $path = $root . 'myfile';
        touch($path);
        $this->assertTrue(file_exists($root));
        $this->assertTrue(file_exists($path));

        $this->obj->clear();
        $this->assertFalse(file_exists($path));
        $this->assertTrue(file_exists($root));
    }

    /**
     * @covers Phoole\Cache\Adaptor\FileAdaptor::garbageCollect()
     */
    public function testGarbageCollect()
    {
        $root = $this->invokeMethod('getRoot', []);
        $path = $this->invokeMethod('getPath', ['bingo']);
        touch($path, time() - 120);
        $this->assertTrue(file_exists($path));
        $this->obj->garbageCollect();
        $this->assertFalse(file_exists($path));

        $this->obj->clear();
        $temp = glob(rtrim($root, '/\\') . '_*');
        $this->assertTrue(1 === count($temp));
        $this->obj->garbageCollect();

        $temp = glob(rtrim($root, '/\\') . '_*');
        $this->assertTrue(0 === count($temp));
        $this->assertTrue(file_exists($root));
    }

    /**
     * @covers Phoole\Cache\Adaptor\FileAdaptor::getPath()
     */
    public function testGetPath()
    {
        $root = $this->invokeMethod('getRoot', []);
        $path = $this->invokeMethod('getPath', ['bingo']);
        $this->assertEquals(
            $root . 'b' . \DIRECTORY_SEPARATOR . 'i' . \DIRECTORY_SEPARATOR . 'bingo',
            $path
        );

        $path = $this->invokeMethod('getPath', ['x']);
        $this->assertEquals(
            $root . 'x' . \DIRECTORY_SEPARATOR . '0' . \DIRECTORY_SEPARATOR . 'x',
            $path
        );
    }

    /**
     * @covers Phoole\Cache\Adaptor\FileAdaptor::getLock()
     * @covers Phoole\Cache\Adaptor\FileAdaptor::releaseLock()
     */
    public function testGetLock()
    {
        $path = $this->invokeMethod('getPath', ['bingo']);

        $this->expectOutputString('lockedlocked');
        if ($lock = $this->invokeMethod('getLock', [$path])) {
            // failed to lock
            $this->assertFalse($this->invokeMethod('getLock', [$path]));
            $this->assertFalse($this->invokeMethod('getLock', [$path]));

            echo 'locked';
            $this->invokeMethod('releaseLock', [$path, $lock]);
        }
        
        // try again
        if ($lock = $this->invokeMethod('getLock', [$path])) {
            echo 'locked';
            $this->invokeMethod('releaseLock', [$path, $lock]);
        }
    }

    /**
     * @covers Phoole\Cache\Adaptor\FileAdaptor::get()
     * @covers Phoole\Cache\Adaptor\FileAdaptor::set()
     */
    public function testGet()
    {
        // not exists
        list($res, $time) = $this->obj->get('bingo');
        $this->assertTrue(null === $res);

        //  set & exist
        $this->obj->set('bingo', 'bingo', 10);
        list($res, $time) = $this->obj->get('bingo');
        $this->assertEquals('bingo', $res);
        $this->assertTrue($time > time());
        $this->assertTrue($time < time() + 15);

        // set a stale file
        $this->obj->set('bingo', 'xxx', -10);
        list($res, $time) = $this->obj->get('bingo');
        $this->assertEquals('xxx', $res);
        $this->assertTrue($time < time());
    }

    /**
     * @covers Phoole\Cache\Adaptor\FileAdaptor::delete()
     */
    public function testDelete()
    {
        // failed to delete (not exist)
        $this->assertFalse($this->obj->delete('bingo'));

        // set & delete
        $this->obj->set('bingo', 'bingo', 10);
        $this->assertTrue($this->obj->delete('bingo'));

        // try agin
        $this->assertFalse($this->obj->delete('bingo'));
    }
}
