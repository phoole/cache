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

use Phoole\Base\Storage\Filesystem;

/**
 * FileAdaptor
 *
 * @package Phoole\Cache
 */
class FileAdaptor extends Filesystem implements AdaptorInterface
{
    /**
     * @param  string $rootPath   the cache directory
     * @param  int    $hashLevel  directory hash depth
     * @throws       \RuntimeException  if mkdir failed
     */
    public function __construct(
        string $rootPath = '',
        int $hashLevel = 2
    ) {
        if (empty($rootPath)) {
            $rootPath = \sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'phoole_cache';
        }
        parent::__construct($rootPath, $hashLevel);
    }
}