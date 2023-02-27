<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\ConversionPro\Client;

use Celebros\ConversionPro\Helper\Data as Helper;
use Celebros\ConversionPro\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class Curl extends \Magento\Framework\HTTP\Client\Curl
{
    const CURLOPT_CONNECTTIMEOUT = 100;
    const CURLOPT_TIMEOUT = 400;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * Return request headers
     *
     * @return array
     */
    public function getRequestHeaders() : array
    {
        return $this->_headers;
    }

    /**
     * @param LoggerFactory $loggerFactory
     * @param Helper $helper
     * @param int|null $sslVersion
     */
    public function __construct(
        LoggerFactory $loggerFactory,
        Helper $helper,
        $sslVersion = null
    ) {
        $this->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->setOption(CURLOPT_BINARYTRANSFER, true);
        $this->setOption(CURLOPT_CONNECTTIMEOUT, self::CURLOPT_CONNECTTIMEOUT);
        $this->setOption(CURLOPT_TIMEOUT, self::CURLOPT_TIMEOUT);

        $this->logger = $loggerFactory->create();
        $this->helper = $helper;
        parent::__construct($sslVersion);
    }

    /**
     * Make request
     *
     * @param string $method
     * @param string $uri
     * @param array|string $params
     *
     * @return void
     */
    protected function makeRequest($method, $uri, $params = [])
    {
        $requestUn = microtime(true);
        $this->logger->info($requestUn . ' - Frontend Url: ' . $this->helper->getCurrentUrl());
        $this->logger->info($requestUn . ' - Request URI: ' . $uri);
        parent::makeRequest($method, $uri, $params);
        $this->logger->info($requestUn . ' - Request Headers: ' . json_encode($this->getRequestHeaders()));
        $this->logger->info($requestUn . ' - Response Headers: ' . json_encode($this->getHeaders()));
        $this->logger->info($requestUn . ' - Response Body: ' . (string)$this->getBody());
    }
}
