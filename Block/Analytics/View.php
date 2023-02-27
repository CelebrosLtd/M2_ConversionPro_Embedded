<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\ConversionPro\Block\Analytics;

use Celebros\ConversionPro\Helper\Data as Helper;
use Celebros\ConversionPro\Helper\Search as SearchHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;

class View extends Template
{
    /**
     *
     */
    const ANALYTICS_JS_PATH = '/widgets/CelebrosToolbox.js';

    /**
     * @var Helper
     */
    public $helper;

    /**
     * @var SearchHelper
     */
    public $searchHelper;

    /**
     * @var Registry
     */
    public $registry;

    /**
     * @var UrlInterface
     */
    public $url;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param Template\Context $context
     * @param Helper $helper
     * @param SearchHelper $searchHelper
     * @param Registry $registry
     * @param Session $session
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Helper $helper,
        SearchHelper $searchHelper,
        Registry $registry,
        Session $session,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->searchHelper = $searchHelper;
        $this->registry = $registry;
        $this->url = $context->getUrlBuilder();
        $this->session = $session;
        parent::__construct($context, $data);
    }

    /**
     * Sets parameters for tempalte
     *
     * @return Celebros_Conversionpro_Block_Analytics_View
     */
    protected function _prepareLayout()
    {
        $this->setCustomerId($this->helper->getAnalyticsCustId());
        $this->setHost($this->helper->getAnalyticsHost());

        $product = $this->getProduct();
        //Set product click tracking params
        if (isset($product)) {
            $this->setProductSku($product->getSku());
            $this->setProductName(str_replace("'", "\'", $product->getName()));
            $this->setProductPrice($product->getFinalPrice());
            $this->setWebsessionId($this->session->getSessionId());
        } else {
            $pageReferrer = $this->url->getUrl('*/*/*', ['_current' => true]);
            $this->setPageReferrer($pageReferrer);
            //$this->setQwiserSearchSessionId(Mage::getSingleton('conversionpro/session')->getSearchSessionId());
            $this->setQwiserSearchSessionId($this->_generateGUID());
            $this->setWebsessionId($this->session->getSessionId());
        }

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    protected function _generateGUID()
    {
        global $SERVER_ADDR;

        // get the current ip, and convert it to its positive long value
        $long_ip = ip2long((string)$SERVER_ADDR);
        if ($long_ip < 0) {
            $long_ip += pow(2, 32);
        }

        // get the current microtime and make sure it's a positive long value
        $time = microtime();
        if ($time < 0) {
            $time += pow(2, 32);
        }

        // put those strings together
        $combined = $long_ip . $time;

        // md5 it and throw in some dashes for easy checking
        // phpcs:disable Magento2.Security.InsecureFunction.FoundWithAlternative
        $guid = hash('md5', $combined); // phpcs:ignore
        // phpcs:enable
        $guid = substr($guid, 0, 8) . "-" .
        substr($guid, 8, 4) . "-" .
        substr($guid, 12, 4) . "-" .
        substr($guid, 16, 4) . "-" .
        substr($guid, 20);

        return $guid;
    }

    /**
     * Retrieve current product model
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * @return false
     */
    public function getQwiserSearchLogHandle()
    {
        if (is_object($results = $this->searchHelper->getCurrentCustomResults())) {
            return $results->QwiserSearchResults->getAttribute('LogHandle');
        }

        return false;
    }

    /**
     * @return string
     */
    public function getJsPath()
    {
        return self::ANALYTICS_JS_PATH;
    }
}
