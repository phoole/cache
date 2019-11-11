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
use Phoole\Cache\Adaptor\FileAdaptor;
use Phoole\Cache\Adaptor\AdaptorInterface;
use Phoole\Cache\Exception\NotFoundException;
use Phoole\Cache\Exception\InvalidArgumentException;
use Phoole\Base\Exception\NotFoundException as PhooleNotFoundException;

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
     * @var array
     */
    protected $settings = [
        'defaultTTL' => 86400,     // default TTL 86400 seconds
        'stampedeGap' => 60,        // 0-120 seconds
        'stampedePercent' => 5,     // 5% chance considered stale
        'distributedPercent' => 5,  // 5% fluctuation of expiration time
    ];

    /**
     * Inject adaptor and settings
     *
     * @param  AdaptorInterface $adaptor
     * @param  array            $settings
     */
    public function __construct(
        ?AdaptorInterface $adaptor = NULL,
        array $settings = []
    ) {
        $this->adaptor = $adaptor ?? new FileAdaptor();
        $this->settings = \array_merge($this->settings, $settings);
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = NULL)
    {
        // verify the key first
        $key = $this->checkKey($key);

        // try read using adaptor
        try {
            list($res, $time) = $this->adaptor->get($key);

            // check expiration time
            if ($this->checkTime($time)) {
                return $this->unSerialize($res);
            }

            throw new NotFoundException("KEY $key Expired");
        } catch (PhooleNotFoundException $e) {
            return $default;
        }
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
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     * check expiration time, avoiding stampede situation on **ONE HOT** item
     *
     * if  item not expired but fall into the stampedeGap (60-120 seconds),
     * then stampede percent (5%) chance to be considered stale and trigger
     * generate new contend
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

        // just expired, fall in stampedeGap
        if ($time > $now - $this->settings['stampedeGap']) {
            // 5% chance consider expired to build new cache
            return rand(0, 100) > $this->settings['stampedePercent'];
        }

        // expired
        return FALSE;
    }

    /**
     * TTL +- 5% fluctuation
     *
     * distributedExpiration **WILL** add expiration fluctuation to **ALL** items
     * which will avoid large amount of items expired at the same time
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
            $ttl = $this->settings['defaultTTL'];
        }

        // add fluctuation
        $fluctuation = $this->settings['distributedPercent'];
        $rand = rand(-$fluctuation, $fluctuation);

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