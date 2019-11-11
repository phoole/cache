<?php

/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Base
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types=1);

namespace Phoole\Cache\Exception;

use Psr\SimpleCache\CacheException;

/**
 * LogicException
 *
 * @package Phoole\Base
 */
class LogicException extends \LogicException implements CacheException
{
}