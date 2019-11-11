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
use Phoole\Cache\Exception\LogicException;

/**
 * CacheAwareTrait
 *
 * @package Phoole\Cache
 */
trait CacheAwareTrait
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param  CacheInterface $cache
     * @return $this
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return CacheInterface
     * @throw  LogicException
     */
    public function getCache(): CacheInterface
    {
        if (\is_null($this->cache)) {
            throw new LogicException("cache not initialized");
        }
        return $this->cache;
    }
}