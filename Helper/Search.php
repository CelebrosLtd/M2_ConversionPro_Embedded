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

use Celebros\ConversionPro\Model\Config\Source\CategoryQueryType;
use Celebros\ConversionPro\Model\Config\Source\RangeFilterTypes;
use Celebros\ConversionPro\Model\Search as SearchModel;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Helper;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Simplexml\Element as XmlElement;

class Search extends Helper\AbstractHelper
{
    /**#@+
     * Constants for keys of data array
     */
    public const CATEGORY_QUESTION_TEXT = 'Category';
    public const CAT_ID_DYN_PROPERTY = 'MagEntityID';
    public const CACHE_ID = 'conversionpro';
    public const REDIRECT_DYNAMIC_PROPERTY_NAME = 'redirection url';
    public const PRICE_QUESTION_ID = 'PriceQuestion';
    /**#@-*/

    /**
     * @var array
     */
    protected $customResultsCache = [];

    /**
     * @var iterable
     */
    protected $allQuestionsCache;

    /**
     * @var array
     */
    protected $questionAnswers = [];

    /**
     * @var int
     */
    protected $currentPage;

    /**
     * @var int
     */
    protected $pageSize;

    /**
     * @var array
     */
    protected $order;

    /**
     * @var DataObject
     */
    protected $currentSearchParams;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var SearchModel
     */
    protected $search;

    /**
     * @var ResponseHttp
     */
    protected $response;

    /**
     * @param Helper\Context $context
     * @param Data $helper
     * @param SearchModel $search
     * @param ResponseHttp $response
     */
    public function __construct(
        Helper\Context $context,
        Data $helper,
        SearchModel $search,
        ResponseHttp $response
    ) {
        $this->helper = $helper;
        $this->search = $search;
        $this->response = $response;
        parent::__construct($context);
    }

    /**
     * Get Search params
     *
     * @return DataObject
     * @throws NoSuchEntityException
     */
    public function getSearchParams()
    {
        if (!$this->currentSearchParams) {
            $request = $this->_getRequest();
            $params = new DataObject();
            $queryText = '';

            // search query text
            if ($request->getParam('q')) {
                $queryText = $request->getParam('q');
            }

            // category query text
            $category = $this->helper->getCurrentCategory();
            if ($category && $category->getId() != $this->helper->getCurrentStore()->getRootCategoryId()) {
                if (!$this->helper->isTextualNav2Search()) {
                    $queryText = $this->helper->getAnswerIdPrefix() . $category->getId();
                } else {
                    $queryText = $this->getCategoryQueryTerm($category);
                }
            }

            $params->setQuery($queryText);

            // filters
            $filters = [];
            foreach ($this->getFilterRequestVars() as $requestVar) {
                $value = $this->getFilterValueAsArray($requestVar);
                if (!empty($value)) {
                    $filters[$requestVar] = $value;
                }
            }

            $params->setFilters($filters);
            $this->currentSearchParams = $params;
        }

        return $this->currentSearchParams;
    }

    /**
     * Get Custom search results
     *
     * @param DataObject|null $params
     * @return false|XmlElement
     * @throws NoSuchEntityException
     */
    public function getCustomResults(DataObject $params = null)
    {
        $params = ($params === null) ? $this->getSearchParams() : clone $params;

        // order
        if ($this->order !== null && !$params->hasSortBy()) {
            $params->setSortBy($this->order);
        }

        // page size
        if ($this->pageSize !== null && !$params->hasPageSize()) {
            $params->setPageSize($this->pageSize);
        }

        // current page
        if ($this->currentPage !== null && !$params->hasCurrentPage()) {
            $params->setCurrentPage($this->currentPage - 1);
        }

        $searchHandle = $this->search->createSearchHandle($params);
        if (!isset($this->customResultsCache[$searchHandle])) {
            $this->customResultsCache[$searchHandle] = $this->search->getCustomResults($searchHandle, true, '');
            if ($this->helper->isCampaignsEnabled()
                && $this->helper->isRedirectAvailable()) {
                $this->checkRedirects($this->customResultsCache[$searchHandle]);
            }
        }
        return $this->customResultsCache[$searchHandle];
    }

    /**
     * Check if redirect exist in search results. Set redirect if exists.
     *
     * @param SimpleXMLElement $customResults
     * @return bool|void
     */
    protected function checkRedirects($customResults)
    {
        $currentConcepts = $customResults->QwiserSearchResults->QueryConcepts->children();
        foreach ($currentConcepts as $concept) {
            if (!isset($concept->DynamicProperties)) {
                continue;
            }

            foreach ($concept->DynamicProperties->children() as $property) {
                if ($property->getAttribute('name') == self::REDIRECT_DYNAMIC_PROPERTY_NAME) {
                    $this->response
                        ->setRedirect($property->getAttribute('value'))
                        ->sendHeaders();
                    return true;
                }
            }
        }
    }

    /**
     * Get all questions
     *
     * @return iterable
     */
    public function getAllQuestions(): iterable
    {
        if ($this->allQuestionsCache === null) {
            $this->allQuestionsCache = $this->getSearchQuestions($this->search->getAllQuestions());
        }

        return $this->allQuestionsCache;
    }

    /**
     * Get Question answers
     *
     * @param string $questionId
     * @return false|XmlElement
     */
    public function getQuestionAnswers($questionId)
    {
        if (!isset($this->questionAnswers[$questionId])) {
            $this->questionAnswers[$questionId] =
                $this->search->getQuestionAnswers($questionId);
        }
        return $this->questionAnswers[$questionId];
    }

    /**
     * Get answers as array
     *
     * @param string $questionId
     * @param string $keyAttribute
     * @param string $valueAttribute
     * @return array
     */
    public function getQuestionAnswersAsArray($questionId, $keyAttribute = 'Id', $valueAttribute = 'Text')
    {
        $options = [];
        $answers = $this->getQuestionAnswers($questionId);
        foreach ($answers->Answers->Answer as $answer) {
            $options[$answer->getAttribute('Id')] = $answer->getAttribute('Text');
        };

        return $options;
    }

    /**
     * Get query term for category
     *
     * @param Category $category
     * @param int|string|null $store
     * @return array|string|string[]
     */
    public function getCategoryQueryTerm(Category $category, $store = null)
    {
        $queryType = $this->helper->getCategoryQueryType($store);
        if ($queryType == CategoryQueryType::NAME) {
            return $category->getName();
        }

        $parents = $category->getParentCategories();
        $parentIds = array_intersect($category->getParentIds(), array_keys($parents));
        switch ($queryType) {
            case CategoryQueryType::FULL_PATH:
                break;
            case CategoryQueryType::NAME_AND_PARENT_NAME:
                $parentId = $category->getParentId();
                $parentIds = in_array($parentId, $parentIds) ? [$parentId] : [];
                break;
            case CategoryQueryType::NAME_AND_ROOT_NAME:
                $parentIds = array_slice($parentIds, 0, 1);
                break;
        }

        $names = array_map(
            function ($id) use ($parents) {
                return $parents[$id]->getName();
            },
            $parentIds
        );
        $names[] = $category->getName();

        return str_replace(',', ' ', implode(' ', $names));
    }

    /**
     * Get request value
     *
     * @param string $requestVar
     * @return mixed|null
     */
    public function getValueFromRequest($requestVar)
    {
        $vars = $this->getAltRequestVars($requestVar);
        foreach ($vars as $var) {
            if ($value = $this->_getRequest()->getParam($var)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Chek var in request
     *
     * @param striing $requestVar
     * @return mixed
     */
    public function checkRequestVar($requestVar)
    {
        $vars = $this->getAltRequestVars($requestVar);
        $params = $this->_getRequest()->getParams();
        foreach ($vars as $var) {
            if (isset($params[$var])) {
                return $var;
            }
        }

        return $requestVar;
    }

    /**
     * Get all request vars
     *
     * @param string $requestVar
     * @return array
     */
    public function getAltRequestVars($requestVar)
    {
        $requestVar = str_replace('.', '_', $requestVar);

        return array_unique([
            $requestVar,
            str_replace(' ', '_', $requestVar),
            str_replace(' ', '+', $requestVar),
            strtolower($requestVar)
        ]);
    }

    /**
     * Get filter value
     *
     * @param string $requestVar
     * @return false|mixed|null
     */
    public function getFilterValue($requestVar)
    {
        $value = $this->getValueFromRequest($requestVar);

        if (!($value === null) && !$this->helper->isMultiselectEnabled()) {
            $values = $this->filterValueToArray($value);
            $value = reset($values);
        }
        return $value;
    }

    /**
     * Get filter value as array
     *
     * @param string $requestVar
     * @return array
     */
    public function getFilterValueAsArray($requestVar)
    {
        $value = $this->getFilterValue($requestVar);
        return ($value === null) ? [] : $this->filterValueToArray($value);
    }

    /**
     * @param string $value
     * @return array
     */
    public function filterValueToArray($value)
    {
        return $this->helper->filterValueToArray($value);
    }

    /**
     * @return string[]
     */
    public function getFilterRequestVars()
    {
        $names = ['price'];
        foreach ($this->getAllQuestions() as $question) {
            $names[] = $question->getAttribute('SideText');
        }

        return array_filter($names);
    }

    /**
     * @param $answerId
     * @return array
     */
    public function getLabelByAnswerId($answerId)
    {
        return $this->questionAnswers;
    }

    /**
     * @param int $page
     * @return $this
     */
    public function setCurrentPage($page)
    {
        $this->currentPage = $page;
        return $this;
    }

    /**
     * @param $size
     * @return $this
     */
    public function setPageSize($size)
    {
        $this->pageSize = $size;
        return $this;
    }

    /**
     * @param $order
     * @param $dir
     * @return $this
     */
    public function setOrder($order, $dir)
    {
        $this->order = [$order, $dir];
        return $this;
    }

    /**
     * @param $handle
     * @return false|mixed
     */
    public function getCurrentCustomResults($handle = null)
    {
        if ($handle) {
            if (isset($this->customResultsCache[$handle])) {
                return $this->customResultsCache[$handle];
            }
        }

        return reset($this->customResultsCache);
    }

    /**
     * @param $value
     * @param $field
     * @return XmlElement|null
     */
    public function getQuestionByField($value, $field): ?XmlElement
    {
        foreach ($this->getAllQuestions() as $question) {
            if (in_array($value, $this->getAltRequestVars($question->getAttribute($field)))) {
                return $question;
            }
        }

        return null;
    }

    /**
     * @return XmlElement|null
     */
    public function getPriceQuestionMock(): ?XmlElement
    {
        foreach ($this->getAllQuestions() as $question) {
            $mock = clone $question;
            $mock->setAttribute('Id', self::PRICE_QUESTION_ID);
            $mock->setAttribute('Text', 'By price range');
            $mock->setAttribute('SideText', 'Price');
            $mock->setAttribute('Type', 'Price');

            return $mock;
        }

        return null;
    }

    /**
     * Re-map sorting field
     *
     * @param string $order
     * @return string
     */
    public function sortOrderMap($order)
    {
        switch ($order) {
            case 'Title':
                $result = 'name';
                break;
            case 'Relevancy':
                $result = 'relevance';
                break;
            default:
                $result = strtolower($order);
        }

        return $result;
    }

    /**
     * Get minimum and maximum values for ranges or price question
     *
     * @param string $questionId
     * @return array [min, max]
     * @throws NoSuchEntityException
     */
    protected function getMinMaxValue(string $questionId): array
    {
        $question = $this->getQuestionById($questionId);
        if (!$question) {
            return [];
        }

        $pattern = null;
        switch ($question->getAttribute('Type')) {
            case 'Price':
                $pattern = /** @lang RegExp */ '@^_P(\d+)_(\d+)$@';
                break;
            case 'Range':
                $valueSuffix = $this->getRangeValueSuffix($questionId);
                if ($valueSuffix) {
                    $pattern = /** @lang RegExp */ "@^_$valueSuffix(\d+)_(\d+)$@";
                }
                break;
        }

        if (!$pattern) {
            return [];
        }

        $values = [];
        foreach ($question->Answers->Answer as $answer) {
            $id = $answer->getAttribute('Id');
            if (preg_match($pattern, (string) $id, $matches)) {
                $values[] = (int)$matches[1];
                $values[] = (int)$matches[2];
            }
        }

        if (!count($values)) {
            return [];
        }

        return [min($values), max($values)];
    }

    /**
     * Get minimum value for range question
     *
     * @param string $questionId
     * @return false|float
     * @throws NoSuchEntityException
     */
    public function getRangeMinValue(string $questionId)
    {
        $value = $this->getMinMaxValue($questionId);
        if (!count($value)) {
            return false;
        }
        list($min) = $value;
        return $min;
    }

    /**
     * Get maximum value for range question
     *
     * @param string $questionId
     * @return false|float
     * @throws NoSuchEntityException
     */
    public function getRangeMaxValue(string $questionId)
    {
        $value = $this->getMinMaxValue($questionId);
        if (!count($value)) {
            return false;
        }
        list(, $max) = $value;
        return $max;
    }

    /**
     * @param string $questionId
     * @return string
     */
    public function getRangeValueSuffix(string $questionId): string
    {
        if ($questionId == self::PRICE_QUESTION_ID) {
            return 'P';
        }

        $valueSuffix = '';
        if (preg_match('@([A-Z]{1,})_Range$@', $questionId, $matches)) {
            $valueSuffix = $matches[1];
        }
        return $valueSuffix;
    }

    /**
     * Get Search results
     *
     * @param XmlElement $rawResponse
     * @return XmlElement|null
     */
    public function getSearchResults(XmlElement $rawResponse): ?XmlElement
    {
        return $rawResponse->QwiserSearchResults ?? null;
    }

    /**
     * Get Questions either from SearchResults or GetAllQuestions return value
     *
     * @param XmlElement $rawResponse
     * @return iterable
     */
    public function getSearchQuestions(XmlElement $rawResponse): iterable
    {
        if (!$rawResponse->Questions->Question) {
            return [];
        }

        return $rawResponse->Questions->children() ?? [];
    }

    /**
     * Get Products from SearchResults
     *
     * @param XmlElement $rawResponse
     * @return iterable
     */
    public function getSearchProducts(XmlElement $rawResponse): iterable
    {
        if (!$rawResponse->Products->Product) {
            return [];
        }

        return $rawResponse->Products->children() ?? [];
    }

    /**
     * @param string $questionId
     * @return XmlElement|null
     * @throws NoSuchEntityException
     */
    public function getQuestionById(string $questionId): ?XmlElement
    {
        $searchResults = $this->getSearchResults($this->getCustomResults());
        $questions = $this->getSearchQuestions($searchResults);

        foreach ($questions as $question) {
            if ($question->getAttribute('Id') == $questionId) {
                return $question;
            }
        }

        return null;
    }

    /**
     * @param string $questionId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getRangeQuestionDisplayType(string $questionId): string
    {
        $question = $this->getQuestionById($questionId);
        $type = RangeFilterTypes::DEF;
        if (!$question) {
            return $type;
        }

        if (!in_array($question->getAttribute('Type'),['Price', 'Range'])) {
            return $type;
        }

        if ($dynamicProperty = $this->extractDynamicProperty($question, 'RangeDisplay')) {
            $type = ($dynamicProperty instanceof XmlElement)
                ? strtolower($dynamicProperty->getAttribute('value'))
                : $type;
        }
        return $type;
    }

    /**
     * @param string $questionId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isRangeDefault(string $questionId): bool
    {
        if ($questionId == self::PRICE_QUESTION_ID) {
            return $this->helper->isPriceDefault();
        }

        return $this->getRangeQuestionDisplayType($questionId) == RangeFilterTypes::DEF;
    }

    /**
     * @param string $questionId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isRangeSlider(string $questionId): bool
    {
        if ($questionId == self::PRICE_QUESTION_ID) {
            return $this->helper->isPriceSlider();
        }

        return $this->getRangeQuestionDisplayType($questionId) == RangeFilterTypes::SLIDER;
    }

    /**
     * @param string $questionId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isRangeInputs(string $questionId): bool
    {
        if ($questionId == self::PRICE_QUESTION_ID) {
            return $this->helper->isPriceInputs();
        }

        return $this->getRangeQuestionDisplayType($questionId) == RangeFilterTypes::INPUTS;
    }

    /**
     * Extract dynamic property by name from xml element
     *
     * @param  XmlElement $element
     * @param  string $propertyName
     * @return XmlElement | null
     */
    public function extractDynamicProperty(
        XmlElement $element,
        string $propertyName = null
    ): ?XmlElement {
        if (
            !empty($element->DynamicProperties)
            && $element->DynamicProperties instanceof \Magento\Framework\Simplexml\Element
        ) {
            if (!$propertyName) {
                return $element->DynamicProperties;
            }

            foreach ($element->DynamicProperties->children() as $property) {
                if ($property->getAttribute('name') == $propertyName) {
                    return $property;
                }
            }
        }

        return null;
    }
}
