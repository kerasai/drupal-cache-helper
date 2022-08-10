<?php

namespace Kerasai\DrupalCacheHelper;

use Drupal\Core\Cache\Cache;

/**
 * Consolidation of cache handling.
 */
class CacheHelper {

  /**
   * The cache factory service.
   *
   * Access via ::getCacheFactory.
   *
   * @var \Drupal\Core\Cache\CacheFactoryInterface|null
   */
  protected $cacheFactory;

  /**
   * Get a value from static or persistent cache.
   *
   * @param string $cid
   *   The cache ID of the data to retrieve.
   * @param string $bin
   *   The persistent cache bin.
   * @param bool $allow_invalid
   *   Allow invalid cache data to be utilized. Optional, defaults to FALSE.
   *
   * @return object|false
   *   The cache item or FALSE on failure.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::get()
   */
  public function get($cid, $bin = 'default', $allow_invalid = FALSE) {
    $static_cid = "$bin:$cid";

    // If we have static data, use it.
    if ($data = $this->getCacheFactory()->get('static')->get($static_cid, $allow_invalid)) {
      return $data;
    }

    // If we have static data, set static and use it.
    if ($data = $this->getCacheFactory()->get($bin)->get($cid, $allow_invalid)) {
      $this->getCacheFactory()->get('static')->set($static_cid, $data->data, $data->expire, $data->tags);
      return $data;
    }

    return FALSE;
  }

  /**
   * Get multiple items from static or persistent cache.
   *
   * @param array $cids
   *   An array of cache IDs for the data to retrieve.
   * @param string $bin
   *   The persistent cache bin.
   * @param bool $allow_invalid
   *   Allow invalid cache data to be utilized. Optional, defaults to FALSE.
   *
   * @return array
   *   An array of cache item objects indexed by cache ID.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::getMultiple()
   */
  public function getMultiple(array $cids, $bin = 'default', $allow_invalid = FALSE) {
    $static_cids = array_map(function ($cid) use ($bin) {
      return "$bin:$cid";
    }, $cids);

    $result = [];

    // See what we can obtain from static cache.
    foreach ($this->getCacheFactory()->get('static')->getMultiple($static_cids, $allow_invalid) as $key => $item) {
      $result[substr($key, strlen($bin) + 1)] = $item;
    }

    // If there were items requested but not available in static cache, attempt
    // to retrieve those from persistent cache. If we are able to retrieve any
    // from persistent cache, populate them into static cache as well.
    if ($missing_cids = array_diff($cids, array_keys($result))) {
      // If we are able to retrieve any data persistent cache, merge them into
      // the result set and populate into static cache.
      if ($data = $this->getCacheFactory()->get($bin)->getMultiple($missing_cids, $allow_invalid)) {
        $result = array_merge($result, $data);
        $data = array_map(function ($item) {
          return [
            'data' => $item->data,
            'expire' => $item->expire,
            'tags' => $item->tags,
          ];
        }, $data);
        $this->getCacheFactory()->get('static')->setMultiple($data);
      }
    }

    return $result;
  }

  /**
   * Set a value into static and persistent cache.
   *
   * @param string $cid
   *   The cache ID of the data to store.
   * @param mixed $data
   *   The data to store in the cache.
   * @param string $bin
   *   The persistent cache bin.
   * @param int $expire
   *   Cache expiration.
   * @param array $tags
   *   An array of tags to be stored with the cache item.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::set()
   */
  public function set($cid, $data, $bin = 'default', $expire = Cache::PERMANENT, array $tags = []) {
    $this->getCacheFactory()->get('static')->set("$bin:$cid", $data, $expire, $tags);
    $this->getCacheFactory()->get($bin)->set($cid, $data, $expire, $tags);
  }

  /**
   * Set multiple items into static and persistent cache.
   *
   * @param array $items
   *   An array of cache items, keyed by cid.
   * @param string $bin
   *   The persistent cache bin.
   *
   * @see \Drupal\Core\Cache\CacheBackendInterface::setMultiple()
   */
  public function setMultiple(array $items, $bin = 'default') {
    $static_keys = array_map(function ($cid) use ($bin) {
      return "$bin:$cid";
    }, array_keys($items));
    $this->getCacheFactory()->get('static')->setMultiple(array_combine($static_keys, $items));
    $this->getCacheFactory()->get($bin)->setMultiple($items);
  }

  /**
   * Get the cache factory service.
   *
   * @return \Drupal\Core\Cache\CacheFactoryInterface
   *   The cache factory service.
   */
  protected function getCacheFactory() {
    if (!$this->cacheFactory) {
      $this->cacheFactory = \Drupal::service('cache.factory');
    }
    return $this->cacheFactory;
  }

}
