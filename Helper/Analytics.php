<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\ConversionPro\Helper;

use Celebros\ConversionPro\Helper\Data as DataHelper;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper;
use Magento\Framework\HTTP\Client\Curl;

class Analytics extends Helper\AbstractHelper
{
    /**
     * Constants
     */
    public const ANALYTICS_URL_PATH = 'ai.celebros-analytics.com/AIWriter/WriteLog.ashx';

    /**
     * @var array
     */
    protected $_urlParams = [];

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Session
     */
    private $session;

    /**
     * Analytics constructor
     *
     * @param Context $context
     * @param DataHelper $helper
     * @param Curl $curl
     * @param Session $session
     */
    public function __construct(
        Context $context,
        DataHelper $helper,
        Curl $curl,
        Session $session
    ) {
        $this->helper = $helper;
        $this->curl = $curl;
        $this->session = $session;
        $this->setUrlParam('type', 'SR');
        $this->setUrlParam('responseType', 'JSON');

        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->_getRequest()->isSecure() ? 'https' : 'http';
    }

    /**
     * @param $name
     * @param $value
     * @return void
     */
    public function setUrlParam($name, $value)
    {
        $this->_urlParams[$name] = $value;
    }

    /**
     * @return string
     */
    protected function _generateGUID()
    {
        global $SERVER_ADDR;

        $long_ip = ip2long((string)$SERVER_ADDR);
        if ($long_ip < 0) {
            $long_ip += pow(2, 32);
        }

        $time = microtime();
        if ($time < 0) {
            $time += pow(2, 32);
        }

        $combined = $long_ip . $time;
        $guid = hash('md5', $combined);
        $guid = substr($guid, 0, 8) . "-" .
        substr($guid, 8, 4) . "-" .
        substr($guid, 12, 4) . "-" .
        substr($guid, 16, 4) . "-" .
        substr($guid, 20);

        return $guid;
    }

    /**
     * @return string
     */
    public function getParamsToUrl()
    {
        $result = [];
        foreach ($this->_urlParams as $param => $value) {
            $result[] = $param . '=' . $value;
        }

        return implode('&', $result);
    }

    /**
     * @param \Magento\Framework\Simplexml\Element $results
     * @return bool
     */
    public function sendAnalyticsRequest(\Magento\Framework\Simplexml\Element $results)
    {
        $host = $this->helper->getAnalyticsHost();
        $this->setUrlParam('cid', $this->helper->getAnalyticsCustId());
        $pageReferrer = $this->_urlBuilder->getUrl('*/*/*', ['_current' => true]);
        $this->setUrlParam('ref', $this->_urlBuilder->getBaseUrl());
        $this->setUrlParam('src', $pageReferrer);
        $this->setUrlParam('wsid', $this->session->getSessionId());
        $this->setUrlParam('ssid', $this->_generateGUID());
        $this->setUrlParam('lh', $this->getQwiserSearchLogHandle($results));
        $this->setUrlParam('dc', '');
        $this->setUrlParam('userid', '');
        $this->curl->get(self::ANALYTICS_URL_PATH . '?' . $this->getParamsToUrl());
        try {
            $response = $this->parseAnalyticsResponse($this->curl->getBody());
            if ($response->Result->success) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param $body
     * @return mixed
     */
    public function parseAnalyticsResponse($body)
    {
        return json_decode(str_replace(['anlxCallback(',');'], '', (string) $body));
    }

    /**
     * @param \Magento\Framework\Simplexml\Element $results
     * @return string|null
     */
    public function getQwiserSearchLogHandle(\Magento\Framework\Simplexml\Element $results)
    {
        return $results->QwiserSearchResults->getAttribute('LogHandle');
    }
}
