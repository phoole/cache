<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Cache
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types=1);

namespace Phoole\Cache\Adaptor;

/**
 * AdaptorInterface
 *
 * @package Phoole\Cache
 */
interface AdaptorInterface
{
    /**
     * Fetches a value from the cache.
     *
     * @param  string $key    The unique key of this item in the cache.
     * @return array          [result, time]
     */
    public function get(string $key): array;

    /**
     * Persists data in the cache, uniquely referenced by a key with an expiration TTL time.
     *
     * @param string    $key   The key of the item to store.
     * @param string    $value The value
     * @param int       $ttl   The TTL value of this item in seconds
     * @return bool     True on success and false on failure.
     */
    public function set(string $key, string $value, int $ttl): bool;

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     * @return bool  True if the item was successfully removed. False if there was an error.
     */
    public function delete(string $key): bool;

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear(): bool;
}
