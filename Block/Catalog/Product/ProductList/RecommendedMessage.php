<?php

/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\ConversionPro\Block\Catalog\Product\ProductList;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Simplexml\Element as XmlElement;

class RecommendedMessage extends Template
{
    public const CAMPAIGN_NAME = 'recommended_messages';

    /**
     * @var \Celebros\ConversionPro\Helper\Data
     */
    protected $helper;

    /**
     * @var \Celebros\ConversionPro\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var XmlElement
     */
    protected $response;

    /**
     * @param Template\Context $context
     * @param \Celebros\ConversionPro\Helper\Data $helper
     * @param \Celebros\ConversionPro\Helper\Search $searchHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Celebros\ConversionPro\Helper\Data $helper,
        \Celebros\ConversionPro\Helper\Search $searchHelper,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->searchHelper = $searchHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get recommended message
     *
     * @return string|null
     */
    public function getRecommendedMessage()
    {
        if (!$this->helper->isActiveEngine() || !$this->helper->isCampaignsEnabled(self::CAMPAIGN_NAME)) {
            return '';
        }

        $response = $this->getResponse();
        if ($response->QwiserSearchResults !== null) {
            return $response->QwiserSearchResults->getAttribute('RecommendedMessage');
        }

        return '';
    }

    /**
     * Get Search results response
     *
     * @return false|XmlElement|mixed|\SimpleXMLElement
     */
    protected function getResponse()
    {
        if ($this->response === null) {
            $this->response = $this->searchHelper->getCustomResults();
        }
        return $this->response;
    }
}
