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

namespace Celebros\ConversionPro\Model\ResourceModel\Fulltext\Collection;

use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\TotalRecordsResolverInterface;
use Magento\Framework\Api\Search\SearchResultInterface;

class TotalRecordsResolver implements TotalRecordsResolverInterface
{
    /**
     * @var SearchResultInterface
     */
    private $searchResult;

    /**
     * @param SearchResultInterface $searchResult
     */
    public function __construct(
        SearchResultInterface $searchResult
    ) {
        $this->searchResult = $searchResult;
    }

    /**
     * @inheritDoc
     */
    public function resolve(): ?int
    {
        return $this->searchResult->getTotalCount();
    }
}
