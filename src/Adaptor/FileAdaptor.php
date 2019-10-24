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
 * FileAdaptor
 *
 * @package Phoole\Cache
 */
class FileAdaptor implements AdaptorInterface
{
    /**
     * @var string
     */
    protected $rootPath;

    /**
     * @var int
     */
    protected $hashLevel;

    /**
     * @param string $rootPath   the cache directory
     * @param int    $hashLevel  directory hash depth
     * @throws       \RuntimeException  if mkdir failed
     */
    public function __construct(
        string $rootPath = '',
        int $hashLevel = 2
    ) {
        if (empty($rootPath)) {
            $rootPath = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'phoole_cache';
        }

        if (!file_exists($rootPath) && !@mkdir($rootPath, 0777, true)) {
            throw new \RuntimeException("Failed to create $rootPath");
        }
        $this->rootPath = realpath($rootPath) . \DIRECTORY_SEPARATOR;
        $this->hashLevel = $hashLevel;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): array
    {
        // get file path
        $path = $this->getPath($key);

        // file exists ?
        if (file_exists($path)) {
            // get mtime, used for expiration
            $time = filemtime($path);
            // get content
            $str = file_get_contents($path);
            return [$str, $time];
        }

        // not exist
        return [null, 0];
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, string $value, int $ttl): bool
    {
        $path = $this->getPath($key);

        // write to a temp file first
        $temp = tempnam(dirname($path), 'CACHE');
        file_put_contents($temp, $value);

        // rename the file with locking
        if ($fp = $this->getLock($path)) {
            // rename & set expiration time
            $res = rename($temp, $path) && touch($path, time() + $ttl);
            $this->releaseLock($path, $fp);
            return $res;
        }

        // locking failed
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $path = $this->getPath($key);

        // lock & remove file
        if (file_exists($path) && $fp = $this->getLock($path)) {
            $res = unlink($path);
            $this->releaseLock($path, $fp);
            return $res;
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $path = rtrim($this->getRoot(), '/\\');
        if (file_exists($path)) {
            $temp = $path . '_' . substr(md5($path . microtime(true)), -5);
            return rename($path, $temp) && mkdir($path, 0777, true);
        }
        return false;
    }

    /**
     * return void
     */
    public function garbageCollect()
    {
        $root = $this->getRoot();
        // remove staled file
        $this->rmDir($root, true);

        // remove staled directory
        $pattern = rtrim($root, '/\\') . '_*';
        foreach (glob($pattern, GLOB_MARK) as $dir) {
            $this->rmDir($dir);
        }
    }

    /**
     * return string
     */
    protected function getRoot(): string
    {
        return $this->rootPath;
    }

    /**
     * @param  string $key
     * @return string
     */
    protected function getPath(string $key): string
    {
        // get hashed directory
        $name = $key . '00';
        $path = $this->getRoot();
        for ($i = 0; $i < $this->hashLevel; ++$i) {
            $path .= $name[$i] . \DIRECTORY_SEPARATOR;
        }

        // make sure hashed directory exists
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        // return full path
        return $path . $key;
    }

    /**
     * get lock of the path
     *
     * @param  string $path
     * @return resource|false
     */
    protected function getLock(string $path)
    {
        $lock = $path . '.lock';
        $count = 0;
        if ($fp = fopen($lock, 'c')) {
            while (true) {
                // try 3 times only
                if ($count++ > 3) {
                    break;
                }

                // get lock non-blockingly
                if (flock($fp, \LOCK_EX | \LOCK_NB)) {
                    return $fp;
                }

                // sleep randon time between attempts
                usleep(rand(1, 10));
            }
            // failed
            fclose($fp);
        }
        return false;
    }

    /**
     * @param  string   $path
     * @param  resource $fp
     * @return bool
     */
    protected function releaseLock(string $path, $fp): bool
    {
        $lock = $path . '.lock';
        return flock($fp, LOCK_UN) && fclose($fp) && unlink($lock);
    }

    /**
     * Remove a whole directory or stale files only
     *
     * @param  string $dir
     * @return void
     */
    protected function rmDir(string $dir, bool $staleOnly = false)
    {
        foreach (glob($dir . '{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE) as $file) {
            if (is_dir($file)) {
                $this->rmDir($file, $staleOnly);
            } else {
                if ($staleOnly && filemtime($file) > time()) {
                    continue;
                }
                unlink($file);
            }
        }
        $staleOnly || rmdir($dir);
    }
}
