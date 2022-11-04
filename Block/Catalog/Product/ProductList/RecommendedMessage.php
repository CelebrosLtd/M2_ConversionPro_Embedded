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
    const CAMPAIGN_NAME = 'recommended_messages';

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

    public function __construct(
        Template\Context $context,
        \Celebros\ConversionPro\Helper\Data $helper,
        \Celebros\ConversionPro\Helper\Search $searchHelper,
        array $data = [])
    {
        $this->helper = $helper;
        $this->searchHelper = $searchHelper;
        parent::__construct($context, $data);
    }

    public function getRecommendedMessage()
    {
        if ($this->helper->isActiveEngine() && $this->helper->isCampaignsEnabled(self::CAMPAIGN_NAME)) {
            $response = $this->_getResponse();
            $message = $response->QwiserSearchResults->getAttribute('RecommendedMessage');
            return $message;
        } else {
            return '';
        }
    }

    protected function _getResponse()
    {
        if (is_null($this->response)) {
            $params = $this->searchHelper->getSearchParams();
            $this->response = $this->searchHelper->getCustomResults($params);
        }
        return $this->response;
    }
}
