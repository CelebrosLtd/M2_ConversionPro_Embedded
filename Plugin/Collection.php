<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\ConversionPro\Plugin;

use Magento\Catalog\Model\Category;
use Magento\Framework\DB\Select;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Celebros\ConversionPro\Helper\Data as Helper;

class Collection
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var Category
     */
    private $catModel;

    /**
     * @param Helper $helper
     * @param Category $catModel
     */
    public function __construct(
        Helper $helper,
        Category $catModel
    ) {
        $this->helper = $helper;
        $this->catModel = $catModel;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param Category $category
     * @return array
     */
    public function beforeAddCategoryFilter(
        ProductCollection $collection,
        Category $category
    ) {
        if ($this->helper->isActiveEngine()) {
            $category = $this->catModel->load(
                $this->helper->getCurrentStore()->getRootCategoryId()
            );
        }

        return [$category];
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $result
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function afterAddAttributeToSort(
        ProductCollection $collection,
        $result
    ) {
        if ($this->helper->isActiveEngine()
            && ($this->helper->isPermittedHandle() || $this->helper->isGraphql())
        ) {
            $this->applyScoreSorting($collection);
        }

        return $collection;
    }

    /**
     * @param \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $collection
     * @return bool
     */
    public function applyScoreSorting(ProductCollection $collection): bool
    {
        $fromPart = $collection->getSelect()->getPart('from');
        if (is_array($fromPart) && array_key_exists('search_result', $fromPart)) {
            $collection->getSelect()->reset(Select::ORDER);
            $collection->getSelect()->columns('search_result.score')->order('score DESC');
            return true;
        }

        return false;
    }
}
