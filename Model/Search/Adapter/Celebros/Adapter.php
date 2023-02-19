<?php

/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\ConversionPro\Model\Search\Adapter\Celebros;

use Magento\Framework\DataObject;
use Magento\Framework\Search\{AdapterInterface, RequestInterface};
use Celebros\ConversionPro\Helper\Search;

class Adapter implements AdapterInterface
{
    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var Search
     */
    protected $searchHelper;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @param Mapper $mapper
     * @param Search $searchHelper
     * @param ResponseFactory $responseFactory
     * @return void
     */
    public function __construct(
        Mapper $mapper,
        Search $searchHelper,
        ResponseFactory $responseFactory
    ) {
        $this->mapper = $mapper;
        $this->searchHelper = $searchHelper;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @inheritDoc
     */
    public function query(RequestInterface $request)
    {
        $params = $this->mapper->buildQuery($request);
        $response = $this->searchHelper->getCustomResults($params);

        return $this->responseFactory->create($response);
    }
}
