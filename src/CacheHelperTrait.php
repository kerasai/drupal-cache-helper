<?php

namespace Kerasai\DrupalCacheHelper;

/**
 * Trait to obtain cache helper.
 */
trait CacheHelperTrait {

  /**
   * The cache helper.
   *
   * Access via `::getCacheHelper`.
   *
   * @var \Kerasai\DrupalCacheHelper\CacheHelper|null
   */
  protected $cacheHelper;

  /**
   * Get the cache helper.
   *
   * @return \Kerasai\DrupalCacheHelper\CacheHelper
   *   The cache helper.
   */
  protected function getCacheHelper() {
    if (!$this->cacheHelper) {
      $this->cacheHelper = new CacheHelper();
    }
    return $this->cacheHelper;
  }

}
