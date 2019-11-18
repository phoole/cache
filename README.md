# cache
[![Build Status](https://travis-ci.com/phoole/cache.svg?branch=master)](https://travis-ci.com/phoole/cache)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phoole/cache/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phoole/cache/?branch=master)
[![Code Climate](https://codeclimate.com/github/phoole/cache/badges/gpa.svg)](https://codeclimate.com/github/phoole/cache)
[![PHP 7](https://img.shields.io/packagist/php-v/phoole/cache)](https://packagist.org/packages/phoole/cache)
[![Latest Stable Version](https://img.shields.io/github/v/release/phoole/cache)](https://packagist.org/packages/phoole/cache)
[![License](https://img.shields.io/github/license/phoole/cache)]()

Slim and full compatible PSR-16 cache library for PHP

Installation
---
Install via the `composer` utility.

```bash
composer require "phoole/cache"
```

or add the following lines to your `composer.json`

```json
{
    "require": {
       "phoole/cache": "1.1.*"
    }
}
```

Features
---

- Fully [PSR-16](https://www.php-fig.org/psr/psr-16/) compliant.

- Support all serializable PHP data types.

- Extra features:

  - **Stampede Protection**: Whenever **ONE** cached object's lifetime is less than
    a configurable `stampedeGap` time in seconds (60s default), by a configurable
    `stampedePercent` (5% default) percentage, it will be considered stale. It may
    then trigger generating new cache depend on your decision. This feature is quite
    useful for reducing a single hot item stampede situation.
    
    ```php
    // overwrite stampede defaults
    $cache = new Cache($fileAdatpor, [
        'stampedeGap' => 120,   // 120second
        'stampedePercent' => 2  // 2%
    ]);
    ```

  - **Distributed expiration**: By setting `distributedPercent` (5% default) to a 
    reasonable percentage, system will store each cache item with its TTL(time to 
    live) a small random fluctuation. This will help avoiding large amount of items
    expired at the same time.
    
    ```php
    $cache = new Cache($fileAdaptor, [
        'distributedPercent' => 3,   // 3%, default is 5%
    ]);
    ```

- `CacheAwareInterface` and `CacheAwareTrait`

Usage
--

- Simple usage

  ```php
  use Phoole\Cache\Cache;
  
  // using default adaptor and default settings
  $cache = new Cache();
  
  // get with default value 'phoole'
  $name  = $cache->get('name', 'phoole');
    
  // set cache
  $cache->set('name', 'wow');
  ```

- Specify the adaptor

  ```php
  use Phoole\Cache\Cache;
  use Phoole\Cache\Adaptor\FileAdaptor;
 
  // use file adaptor and specific cache directory 
  $cache = new Cache(new FileAdaptor('/tmp/cache');
  ```
  
- Use with [dependency injection](https://github.com/phoole/di)

  ```php
  use Phoole\Cache\Cache;
  use Phoole\Di\Container;
  use Phoole\Config\Config;
  
  // config cache in the container
  $container = new Container(new Config(
      'di.service' => [
          'cache' => Cache::class
      ],
  ));
  
  // get from container
  $cache = $container->get('cache');
  
  // or static FACADE way
  $cache = Container::cache();
  ```
  
Testing
---

```bash
$ composer test
```

Dependencies
---

- PHP >= 7.2.0

- [phoole/base](https://github.com/phoole/base) 1.*

License
---

- [Apache 2.0](https://www.apache.org/licenses/LICENSE-2.0)