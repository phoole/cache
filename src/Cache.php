<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Cache
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types=1);

namespace Phoole\Cache;

use Psr\SimpleCache\CacheInterface;
use Phoole\Cache\Adaptor\AdaptorInterface;
use Phoole\Base\Exception\NotFoundException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Cache
 *
 * @package Phoole\Cache
 */
class Cache implements CacheInterface
{
    /**
     * @var AdaptorInterface
     */
    protected $adaptor;

    /**
     * bypass the cache switch
     *
     * @var bool
     */
    protected $byPass = FALSE;

    /**
     * default TTL
     *
     * @var int
     */
    protected $defaultTTL;

    /**
     * distributed expiration time for DIFFERENT files
     *
     * 0 - 5 (%)
     *
     * @var int
     */
    protected $distributedExpiration;

    /**
     * Avoid stampede for one file by alter expire for each get
     *
     * 0 - 60 second
     *
     * @var int
     */
    protected $stampedeGap;

    /**
     * @param  AdaptorInterface $adaptor
     * @param  int              $defaultTTL             86400 sec (one day)
     * @param  int              $distributedExpiration  0 - 5(%)
     * @param  int              $stampedeGap            0 - 60 sec
     */
    public function __construct(
        AdaptorInterface $adaptor,
        int $defaultTTL = 86400,
        int $distributedExpiration = 5,
        int $stampedeGap = 60
    ) {
        $this->adaptor = $adaptor;
        $this->defaultTTL = $defaultTTL;
        $this->distributedExpiration = $distributedExpiration;
        $this->stampedeGap = $stampedeGap;
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = NULL)
    {
        // bypass the cache
        if ($this->byPass) {
            return $default;
        }

        // verify the key first
        $key = $this->checkKey($key);

        // try read using adaptor
        try {
            list($res, $time) = $this->adaptor->get($key);
        } catch (NotFoundException $e) {
            return $default;
        }

        // verify expiration time
        if ($this->checkTime($time)) {
            return $this->unSerialize($res);
        }

        // default
        return $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = NULL)
    {
        $ttl = $this->getTTL($ttl);
        $key = $this->checkKey($key);
        $val = $this->serialize($value);
        return $value ? $this->adaptor->set($key, $val, $ttl) : FALSE;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $key = $this->checkKey($key);
        return $this->adaptor->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        return $this->adaptor->clear();
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple($keys, $default = NULL)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple($values, $ttl = NULL)
    {
        $res = TRUE;
        foreach ($values as $key => $value) {
            $res &= $this->set($key, $value, $ttl);
        }
        return (bool) $res;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple($keys)
    {
        $res = TRUE;
        foreach ($keys as $key) {
            $res &= $this->delete($key);
        }
        return (bool) $res;
    }

    /**
     * {@inheritDoc}
     */
    public function has($key)
    {
        return NULL !== $this->get($key);
    }

    /**
     * @param  bool $bypass  explicitly bypass the cache
     * @return void
     */
    public function setByPass(bool $bypass = TRUE)
    {
        $this->byPass = $bypass;
    }

    /**
     * Check key is valid or not
     *
     * @param  string $key
     * @return string
     * @throws InvalidArgumentException
     */
    protected function checkKey($key): string
    {
        try {
            return (string) $key;
        } catch (\Throwable $e) {
            throw new class ($e->getMessage())
                extends \InvalidArgumentException
                implements InvalidArgumentException
            {
            };
        }
    }

    /**
     * check expiration time
     *
     * @param  int $time
     * @return bool
     */
    protected function checkTime(int $time): bool
    {
        $now = time();

        // not expired
        if ($time > $now) {
            return TRUE;
        }

        // just expired
        if ($time > $now - $this->stampedeGap) {
            // 5% chance expired (need rebuild cache)
            return rand(0, 100) > 5;
        }

        // expired
        return FALSE;
    }

    /**
     * TTL +- 5% fluctuation
     *
     * @param  null|int|\DateInterval $ttl
     * @return int
     */
    protected function getTTL($ttl): int
    {
        if ($ttl instanceof \DateInterval) {
            $ttl = (int) $ttl->format('%s');
        }

        if (is_null($ttl)) {
            $ttl = $this->defaultTTL;
        }

        $rand = rand(-$this->distributedExpiration, $this->distributedExpiration);
        return (int) round($ttl * (100 + $rand) / 100);
    }

    /**
     * Serialize the value
     *
     * @param  mixed $value
     * @return string
     */
    protected function serialize($value): string
    {
        return \serialize($value);
    }

    /**
     * unserialize the value
     *
     * @param  string $value
     * @return mixed
     */
    protected function unSerialize(string $value)
    {
        return \unserialize($value);
    }
}
