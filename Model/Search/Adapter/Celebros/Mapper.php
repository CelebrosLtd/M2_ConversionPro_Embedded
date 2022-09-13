<?php

/**
 * Celebros
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 * @category    Celebros
 * @package     Celebros_ConversionPro
 */

namespace Celebros\ConversionPro\Model\Search\Adapter\Celebros;

use Celebros\ConversionPro\Helper\Search as SearchHelper;
use Magento\Framework\DataObject as DataObject;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Request\FilterInterface as RequestFilterInterface;
use Magento\Framework\Search\Request\QueryInterface as RequestQueryInterface;
use Magento\Framework\Search\Request\Query\BoolExpression as BoolQuery;
use Magento\Framework\Search\Request\Query\Filter as FilterQuery;
use Magento\Framework\Search\Request\Query\MatchQuery as MatchQuery;

class Mapper
{
    /**
     * @var SearchHelper
     */
    protected $searchHelper;

    /**
     * @param SearchHelper $searchHelper
     */
    public function __construct(
        SearchHelper $searchHelper
    ) {
        $this->searchHelper = $searchHelper;
    }

    /**
     * Build query
     *
     * @param RequestInterface $request
     * @return DataObject
     */
    public function buildQuery(RequestInterface $request)
    {
        $params = $this->searchHelper->getSearchParams();
        $this->processQuery(
            $request->getQuery(),
            $params
        );

        return $params;
    }

    /**
     * Process query
     *
     * @param RequestQueryInterface $query
     * @param DataObject $params
     * @return void
     */
    protected function processQuery(
        RequestQueryInterface $query,
        DataObject $params
    ) {
        switch ($query->getType()) {
            case RequestQueryInterface::TYPE_MATCH:
                $this->processMatchQuery($query, $params);
                break;
            case RequestQueryInterface::TYPE_BOOL:
                $this->processBoolQuery($query, $params);
                break;
            case RequestQueryInterface::TYPE_FILTER:
                $this->processFilterQuery($query, $params);
                break;
        }
    }

    /**
     * Process Match query
     *
     * @param MatchQuery $query
     * @param DataObject $params
     * @return void
     */
    protected function processMatchQuery(
        MatchQuery $query,
        DataObject $params
    ) {
        $queryText = $params->hasQueryText() ? $params->getQueryText() . ' ' : '';
        $queryText .= $query->getValue();
        $params->setQuery($queryText);
    }

    /**
     * Procee Bool query
     *
     * @param BoolQuery $query
     * @param DataObject $params
     * @return void
     */
    protected function processBoolQuery(
        BoolQuery $query,
        DataObject $params
    ) {
        $this->processBoolQueryCondition(
            $query->getMust(),
            $params
        );

        $this->processBoolQueryCondition(
            $query->getShould(),
            $params
        );

        $this->processBoolQueryCondition(
            $query->getMustNot(),
            $params
        );
    }

    /**
     * Process filter query
     *
     * @param FilterQuery $query
     * @param DataObject $params
     * @return void
     */
    protected function processFilterQuery(
        FilterQuery $query,
        DataObject $params
    ) {
        switch ($query->getReferenceType()) {
            case FilterQuery::REFERENCE_QUERY:
                $this->processQuery($query->getReference(), $params);
                break;
            case FilterQuery::REFERENCE_FILTER:
                $this->processFilter($query->getReference(), $params);
                break;
        }
    }

    /**
     * Proceee bool query condition
     *
     * @param array $subQueryList
     * @param DataObject $params
     * @return void
     */
    protected function processBoolQueryCondition(
        array $subQueryList,
        DataObject $params
    ) {
        foreach ($subQueryList as $subQuery) {
            $this->processQuery($subQuery, $params);
        }
    }

    /**
     * Process filter
     *
     * @param RequestFilterInterface $filter
     * @param DataObject $params
     * @return void
     */
    protected function processFilter(
        RequestFilterInterface $filter,
        DataObject $params
    ) {
        if ($filter->getType() == RequestFilterInterface::TYPE_TERM) {
            $filters = $params->hasFilters() ? $params->getFilters() : [];
            $filters[$filter->getField()] = $filter->getValue();
            $params->setFilters($filters);
        } /* ignore otherwise */
    }
}
