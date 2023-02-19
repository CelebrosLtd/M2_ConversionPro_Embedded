<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
declare(strict_types=1);

namespace Celebros\ConversionPro\Observer;

use Celebros\ConversionPro\Helper\Data as Helper;
use Celebros\ConversionPro\Helper\Search as SearchHelper;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\CatalogSearch\Block\Result as ResultBlock;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\AbstractBlock;

class SortOrderInit implements ObserverInterface
{
    /**
     * @var ListProduct
     */
    private $listProductBlock;

    /**
     * @var Helper
     */
    private Helper $helper;

    /**
     * @var SearchHelper
     */
    private SearchHelper $searchHelper;

    /**
     * @param Helper $helper
     * @param SearchHelper $searchHelper
     */
    public function __construct(
        Helper $helper,
        SearchHelper $searchHelper
    ) {
        $this->helper = $helper;
        $this->searchHelper = $searchHelper;
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if (!($this->helper->isActiveEngine() && $this->helper->isPermittedHandle())) {
            return;
        }
        /** @var AbstractBlock $block */
        $block = $observer->getDataByKey('block');
        $this->initListProductBlock($block);
        $this->initToolbarBlock($block);
    }

    /**
     * Init Sorting options in ListProduct block
     *
     * @param AbstractBlock $block
     * @return void
     * @throws LocalizedException
     */
    protected function initListProductBlock(AbstractBlock $block)
    {
        if (!($block instanceof ListProduct)) {
            return;
        }

        $this->listProductBlock = $block;
        $this->initSearchResultList();
        $this->initCategoryProductList();
    }

    /**
     * Init Sorting options for search results page in ListProduct block
     *
     * @return void
     * @throws LocalizedException
     */
    protected function initSearchResultList()
    {
        if (!$this->helper->isSearch()) {
            return;
        }

        /** @var ResultBlock $resultBlock */
        $resultBlock = $this->listProductBlock->getLayout()->getBlock('search.result');

        if ($resultBlock) {
            $resultBlock->setListOrders();
        } else {
            $category = $this->listProductBlock->getLayer()->getCurrentCategory();
            $availableOrders = $this->replacePositionOrderByRelevance($category->getAvailableSortByOptions());

            $this->listProductBlock->setAvailableOrders(
                $availableOrders
            )->setDefaultDirection(
                'desc'
            )->setDefaultSortBy(
                'relevance'
            );
        }

        $this->listProductBlock->prepareSortableFieldsByCategory(
            $this->listProductBlock->getLayer()->getCurrentCategory()
        );
    }

    /**
     * Init Sorting options for category page in ListProduct block
     *
     * @return void
     */
    protected function initCategoryProductList()
    {
        if (!$this->helper->isCategory()) {
            return;
        }

        $this->listProductBlock->prepareSortableFieldsByCategory(
            $this->listProductBlock->getLayer()->getCurrentCategory()
        );
        if ($this->listProductBlock->getSortBy() && $this->listProductBlock->getSortBy() == 'position') {
            $this->listProductBlock->setDefaultDirection('desc');
        }

        // replace 'Position' sorting by 'Relevance'
        $availableOrders = $this->replacePositionOrderByRelevance($this->listProductBlock->getAvailableOrders());
        $this->listProductBlock->setAvailableOrders($availableOrders);
        if ($this->listProductBlock->getSortBy() == 'position') {
            $this->listProductBlock->setSortBy('relevance');
        }
    }

    /**
     * Init Sorting options in Toolbar block. Set sorting and paging option to Search helper
     *
     * @param AbstractBlock $block
     * @return void
     */
    protected function initToolbarBlock(AbstractBlock $block)
    {
        if (!($block instanceof Toolbar)) {
            return;
        }
        if (!$this->listProductBlock) {
            return;
        }

        // use sortable parameters
        $orders = $this->listProductBlock->getAvailableOrders();
        if ($orders) {
            $block->setAvailableOrders($orders);
        }
        $sort = $this->listProductBlock->getSortBy();
        if ($sort) {
            $block->setDefaultOrder($sort);
        }
        $dir = $this->listProductBlock->getDefaultDirection();
        if ($dir) {
            $block->setDefaultDirection($dir);
        }

        // Init Search params
        $this->searchHelper->setCurrentPage($block->getCurrentPage());
        $this->searchHelper->setPageSize($block->getLimit());
        $this->searchHelper->setOrder(
            $block->getCurrentOrder(),
            $block->getCurrentDirection()
        );
    }

    /**
     * Replace Position sorting option by Relevance
     *
     * @param array $orders
     * @return array
     */
    protected function replacePositionOrderByRelevance($orders)
    {
        unset($orders['position']);
        $orders['relevance'] = __('Relevance');
        return $orders;
    }
}
