<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\ConversionPro\Plugin\Search\Model;

use Celebros\ConversionPro\Helper\Data as Helper;
use Magento\Search\Model\PopularSearchTerms as MagentoPopularSearchTerms;

class PopularSearchTerms
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param Helper $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Search\Model\PopularSearchTerms $terms
     * $param callable $proceed
     * $param string $term
     * $param int $storeId
     * @return bool
     */
    public function aroundIsCacheable(
        MagentoPopularSearchTerms $terms,
        callable $proceed,
        string $term,
        int $storeId
    ) {
        if ($this->helper->isActiveEngine() && $this->helper->isPermittedHandle()) {
            return true;
        }

        return $proceed($term, $storeId);
    }
}
