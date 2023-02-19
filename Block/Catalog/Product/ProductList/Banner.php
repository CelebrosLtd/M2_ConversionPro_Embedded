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

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Simplexml\Element as XmlElement;

class Banner extends Template
{
    private const BANNER_CAMPAIGN_NAME = 'banners';

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
     * @var DataObject
     */
    protected $bannerImage;

    /**
     * @var bool
     */
    protected $isResponseParsed = false;

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
     * Check if banner image is set
     *
     * @return bool
     */
    public function hasBannerImage()
    {
        return $this->getParsedResponseBanner()->hasData();
    }

    /**
     * Get banner image
     *
     * @return DataObject
     */
    public function getBannerImage()
    {
        return $this->getParsedResponseBanner();
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

    /**
     * Get baner from search result response
     *
     * @return void
     */
    protected function getParsedResponseBanner()
    {
        $banner = new DataObject();
        if (!$this->helper->isCampaignsEnabled(self::BANNER_CAMPAIGN_NAME)) {
            return $banner;
        }

        if ($this->isResponseParsed) {
            return $banner;
        }

        $response = $this->getResponse();
        if (!isset($response->QwiserSearchResults->QueryConcepts)) {
            $this->isResponseParsed = true;
            return $banner;
        }

        foreach ($response->QwiserSearchResults->QueryConcepts->children() as $concept) {
            if (!isset($concept->DynamicProperties)) {
                continue;
            }

            foreach ($concept->DynamicProperties->children() as $property) {
                $value = $property->getAttribute('value');
                switch ($property->getAttribute('name')) {
                    case 'banner image':
                        $banner->setImageUrl($value);
                        break;
                    case 'banner landing page':
                        $banner->setUrl($value);
                        break;
                    case 'start datetime':
                        $banner->setStartDatetime($value);
                        break;
                    case 'end datetime':
                        $banner->setEndDatetime($value);
                        break;
                }
            }
        }

        $this->isResponseParsed = true;
        return $banner;
    }
}
