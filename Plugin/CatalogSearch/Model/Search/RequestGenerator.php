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

namespace Celebros\ConversionPro\Plugin\CatalogSearch\Model\Search;

use Celebros\ConversionPro\Helper\Data as Helper;
use Celebros\ConversionPro\Helper\Search as SearchHelper;
use Magento\CatalogSearch\Model\Search\RequestGenerator as RequestGeneratorSubject;
use Magento\Framework\Search\Request\BucketInterface;
use Magento\Framework\Search\Request\FilterInterface;
use Magento\Framework\Search\Request\QueryInterface;

class RequestGenerator
{
    /**
     * @var Helper
     */
    private Helper $helper;

    /**
     * @var SearchHelper
     */
    private SearchHelper $searchHelper;

    /**
     * @param Helper $helper
     * @param SearchHelper $searchHelper
     */
    public function __construct(
        Helper $helper,
        SearchHelper $searchHelper
    ) {
        $this->helper = $helper;
        $this->searchHelper = $searchHelper;
    }

    /**
     * Update quick_search_container in dynamic fields requests
     *
     * @param RequestGeneratorSubject $subject
     * @param array $result
     * @return array
     */
    public function afterGenerate(RequestGeneratorSubject $subject, array $result): array
    {
        if (!$this->helper->isActiveEngine()) {
            return $result;
        }

        $requests['quick_search_container'] = $this->generateQuickSearchRequest();

        return $requests;
    }

    /**
     * Generate quick search request
     *
     * @return array
     */
    protected function generateQuickSearchRequest(): array
    {
        $allQuestions = $this->searchHelper->getAllQuestions();

        if (empty($allQuestions)) {
            return [];
        }

        $request = [];
        $qNames = [];
        foreach ($allQuestions as $question) {
            $name = $question->getAttribute('Text');
            if (in_array($name, $qNames)) {
                continue;
            }

            $queryName = $name . '_query';
            $request['queries']['quick_search_container']['queryReference'][] = [
                'clause' => 'should',
                'ref' => $queryName
            ];

            $filterName = $name . RequestGeneratorSubject::FILTER_SUFFIX;
            $request['queries'][$queryName] = [
                'name' => $queryName,
                'type' => QueryInterface::TYPE_FILTER,
                'filterReference' => [
                    ['ref' => $filterName]
                ]
            ];
            $bucketName = $name . RequestGeneratorSubject::BUCKET_SUFFIX;
            $request['filters'][$filterName] = [
                'type' => FilterInterface::TYPE_TERM,
                'name' => $filterName,
                'field' => $name,
                'value' => '$' . $name . '$'
            ];
            $request['aggregations'][$bucketName] = [
                'type' => BucketInterface::TYPE_TERM,
                'name' => $bucketName,
                'field' => $name,
                'metric' => [
                    ["type" => "count"]
                ]
            ];
            $qNames[] = $name;
        }

        return $request;
    }
}
