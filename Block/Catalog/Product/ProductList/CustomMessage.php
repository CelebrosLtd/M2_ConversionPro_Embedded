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

class CustomMessage extends Template
{
    private const CAMPAIGN_NAME = 'custom_message';
    private const XML_NAME = 'custom message';

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
    protected $customMessage;

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
     * Check if Custom message is set
     *
     * @return bool
     */
    public function hasCustomMessage()
    {
        $message = $this->getParsedResponseMessage();
        return $message->hasHtml();
    }

    /**
     * Get Custom message
     *
     * @return string
     */
    public function getCustomMessage()
    {
        return $this->getParsedResponseMessage()->getHtml();
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
     * Get Custom message from search result response
     *
     * @return DataObject
     */
    protected function getParsedResponseMessage()
    {
        $message = new DataObject();

        if (!$this->helper->isCampaignsEnabled(self::CAMPAIGN_NAME)) {
            return $message;
        }

        if ($this->isResponseParsed) {
            return $message;
        }

        $response = $this->getResponse();
        if (!isset($response->QwiserSearchResults->QueryConcepts)) {
            $this->isResponseParsed = true;
            return $message;
        }

        foreach ($response->QwiserSearchResults->QueryConcepts->children() as $concept) {
            if (!isset($concept->DynamicProperties)) {
                continue;
            }

            foreach ($concept->DynamicProperties->children() as $property) {
                if ($property->getAttribute('name') !== self::XML_NAME) {
                    continue;
                }
                $message->setHtml($property->getAttribute('value'));
            }
        }

        $this->isResponseParsed = true;
        return $message;
    }
}
