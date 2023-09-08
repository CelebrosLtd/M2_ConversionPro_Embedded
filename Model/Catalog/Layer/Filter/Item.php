<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\ConversionPro\Model\Catalog\Layer\Filter;

use Celebros\ConversionPro\Helper\Data;
use Celebros\ConversionPro\Helper\Search as SearchHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Theme\Block\Html\Pager;

class Item extends \Magento\Catalog\Model\Layer\Filter\Item
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var SearchHelper
     */
    protected $searchHelper;

    /**
     * Construct
     *
     * @param UrlInterface $url
     * @param Pager $htmlPagerBlock
     * @param Data $helper
     * @param SearchHelper $searchHelper
     * @param array $data
     */
    public function __construct(
        UrlInterface $url,
        Pager $htmlPagerBlock,
        Data $helper,
        SearchHelper $searchHelper,
        array $data = []
    ) {
        parent::__construct($url, $htmlPagerBlock, $data);
        $this->helper = $helper;
        $this->searchHelper = $searchHelper;
    }

    /**
     * @inheritDoc
     */
    public function getUrl()
    {
        if (!$this->hasSelectedValues() || empty($this->getSelectedValues())) {
            return parent::getUrl();
        }

        if ($this->isSelected()) {
            return $this->getRemoveUrl();
        }

        /** @var array $values */
        $values =  $this->getSelectedValues();
        $values[] = $this->getValue();
        $requestVar = $this->searchHelper->checkRequestVar($this->getFilter()->getRequestVar());
        $query = [
            $requestVar => implode(',', $values),
            // exclude current page from urls
            $this->_htmlPagerBlock->getPageVarName() => null];
        return $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
    }

    /**
     * @inheritDoc
     */
    public function getRemoveUrl()
    {
        if (!$this->hasSelectedValues() || empty($this->getSelectedValues())) {
            return parent::getRemoveUrl();
        }

        /** @var array $values */
        $values = $this->getSelectedValues();
        $values = array_diff($values, [$this->getValue()]);
        if (empty($values)) {
            $values = null;
        }

        $requestVar = $this->searchHelper->checkRequestVar($this->getFilter()->getRequestVar());
        $query = [
            $requestVar => is_array($values) ? implode(',', $values) : $values,
            // exclude current page from urls
            $this->_htmlPagerBlock->getPageVarName() => null
        ];

        return $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
    }

    /**
     * Check if filter item is selected
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isSelected()
    {
        $previous_search = $this->searchHelper->getFilterValue($this->getFilter()->getRequestVar());
        return $previous_search && in_array($this->getValue(), explode(',', (string) $previous_search));
    }
}
