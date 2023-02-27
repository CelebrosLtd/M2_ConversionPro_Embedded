<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\ConversionPro\Model\Cache;

use Celebros\ConversionPro\Helper\Cache;
use Magento\Framework\App\Cache\Type\FrontendPool;

class Type extends \Magento\Framework\Cache\Frontend\Decorator\TagScope
{
    /**
     * Cache type code unique among all cache types
     */
    private const TYPE_IDENTIFIER = 'conversionpro';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(
        FrontendPool $cacheFrontendPool
    ) {
        parent::__construct(
            $cacheFrontendPool->get(Cache::CACHE_TYPE_ID),
            Cache::CACHE_TAG
        );
    }
}
