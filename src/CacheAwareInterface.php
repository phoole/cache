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

/**
 * CacheAwareInterface
 *
 * @package Phoole\Cache
 */
interface CacheAwareInterface
{
    /**
     * @param  CacheInterface $cache
     * @return $this
     */
    public function setCache(CacheInterface $cache);

    /**
     * @return CacheInterface
     * @throw  Phoole\Cache\Exception\LogicException
     */
    public function getCache(): CacheInterface;
}