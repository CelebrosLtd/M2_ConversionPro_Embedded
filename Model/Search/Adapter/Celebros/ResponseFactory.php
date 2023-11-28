<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\ConversionPro\Model\Search\Adapter\Celebros;

use Magento\Elasticsearch\SearchAdapter\AggregationFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Simplexml\Element as XmlElement;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Search\Response\Aggregation;
use Magento\Framework\Search\Response\QueryResponse;

class ResponseFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var DocumentFactory
     */
    private $documentFactory;

    /**
     * @var BucketFactory
     */
    private $bucketFactory;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param DocumentFactory $documentFactory
     * @param BucketFactory $bucketFactory
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        DocumentFactory $documentFactory,
        BucketFactory $bucketFactory
    ) {
        $this->objectManager = $objectManager;
        $this->documentFactory = $documentFactory;
        $this->bucketFactory = $bucketFactory;
    }

    /**
     * Get Search results
     *
     * @param XmlElement $rawResponse
     * @return XmlElement|null
     */
    public function getSearchResults($rawResponse): ?XmlElement
    {
        return $rawResponse->QwiserSearchResults ?? null;
    }

    /**
     * Create Query Response instance
     *
     * @param XmlElement $rawResponse
     * @return QueryResponse|mixed
     */
    public function create($rawResponse)
    {
        $documents = [];
        $buckets = [];
        $total = 0;

        $searchResult = $this->getSearchResults($rawResponse);
        if ($searchResult) {
            $products = $searchResult->Products;
            $entityMapping = $this->prepareEntityRowIdMapping($products);
            $score = count($products->children());
            foreach ($products->children() as $rawDocument) {
                $entityId = $entityMapping[$rawDocument->getAttribute('MagId')] ?? false;
                if ($entityId) {
                    $rawDocument->setAttribute('EntityId', $entityId);
                    $documents[] = $this->documentFactory->create($rawDocument, $score--);
                }
            }
            $questions = $searchResult->Questions;
            foreach ($questions->children() as $rawDocument) {
                $buckets[] = $this->bucketFactory->create($rawDocument);
            }

            $total = $searchResult->getAttribute('RelevantProductsCount');
        }

        $aggregations = $this->objectManager->create(
            Aggregation::class,
            ['buckets' => $buckets]
        );

        return $this->objectManager->create(
            QueryResponse::class,
            [
                'documents' => $documents,
                'aggregations' => $aggregations,
                'total' => $total
            ]
        );
    }

    /**
     * Prepapre mapping for a row
     *
     * @param XmlElement $products
     * @return array
     */
    public function prepareEntityRowIdMapping($products)
    {
        if (!count($products->children())) {
            return [];
        }

        $ids = [];
        foreach ($products->children() as $rawDocument) {
            foreach ($rawDocument->Fields->children() as $rawField) {
                $name = $rawField->getAttribute('name');
                $value = $rawField->getAttribute('value');
                if ($name == 'mag_id') {
                    $ids[$value] = $value;
                    $rawDocument->setAttribute('MagId', $value);
                }
            }
        }

        if (!count($ids)) {
            return [];
        }

        $productMetadata = $this->objectManager->get(ProductMetadataInterface::class);
        if ($productMetadata->getEdition() == 'Community') {
            return $ids;
        }

        $products = $this->objectManager->create(Product::class);
        $collection = $products->getCollection()
            ->addFieldToFilter('row_id', $ids);


        $mapping = [];
        foreach ($collection as $item) {
            $mapping[$item->getRowId()] = $item->getEntityId();
        }

        return $mapping;
    }
}
