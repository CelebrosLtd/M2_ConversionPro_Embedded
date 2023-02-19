<?php

/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\ConversionPro\Helper;

use Magento\Framework\App\Cache as AppCache;
use Magento\Framework\App\Cache\State as CacheState;
use Magento\Framework\App\Helper;
use Magento\Store\Model\ScopeInterface;

class Cache extends Helper\AbstractHelper
{
    /**
     * Cache tag used to distinguish the cache type from all other cache
     */
    public const CACHE_TAG = 'CONVERSIONPRO';

    /**
     * Cache type identifier
     */
    public const CACHE_TYPE_ID = 'conversionpro';

    /**
     * Cache lifetime
     */
    private const CACHE_LIFETIME = 13600;

    /**
     * XML Config path
     */
    private const XML_PATH_CACHE_LIFETIME = 'conversionpro/advanced/request_lifetime';

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var AppCache
     */
    protected $cache;

    /**
     * @var CacheState
     */
    protected $cacheState;

    /**
     * @var \Celebros\ConversionPro\Model\Search
     */
    protected $search;

    /**
     * @param Helper\Context $context
     * @param Data $helper
     * @param AppCache $cache
     * @param CacheState $cacheState
     */
    public function __construct(
        Helper\Context $context,
        Data $helper,
        AppCache $cache,
        CacheState $cacheState
    ) {
        $this->helper = $helper;
        $this->cache = $cache;
        $this->cacheState = $cacheState;
        parent::__construct($context);
    }

    /**
     * Get cache ID
     *
     * @param string $method
     * @param array $vars
     * @return string
     */
    public function getId($method, $vars = [])
    {
        return sha1($method . '::' . implode('', $vars));
    }

    /**
     * Load cached data
     *
     * @param string $cacheId
     * @return false|string
     */
    public function load($cacheId)
    {
        if ($this->cacheState->isEnabled(self::CACHE_TYPE_ID)
            && ($this->getCacheLifeTime() >= 0)) {
            return $this->cache->load($cacheId);
        }

        return false;
    }

    /**
     * Save data to cache
     *
     * @param string $data
     * @param string $cacheId
     * @return bool
     */
    public function save($data, $cacheId)
    {
        if ($this->cacheState->isEnabled(self::CACHE_TYPE_ID)
        && ($this->getCacheLifeTime() >= 0)) {
            $this->cache->save(
                $data,
                $cacheId,
                [self::CACHE_TAG],
                $this->getCacheLifeTime()
            );

            return true;
        }

        return false;
    }

    /**
     * Get cache lifetime from config
     *
     * @param null|int|string $store
     * @return int
     */
    protected function getCacheLifeTime($store = null) : int
    {
        $lifeTime = (int)$this->scopeConfig->getValue(
            self::XML_PATH_CACHE_LIFETIME,
            ScopeInterface::SCOPE_STORE,
            $store
        );

        return $lifeTime ?: self::CACHE_LIFETIME;
    }
}
