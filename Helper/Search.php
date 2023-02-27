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

use Celebros\ConversionPro\Helper\Cache as CacheHelper;
use Celebros\ConversionPro\Model\Search as SearchModel;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Framework\App\Helper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\DataObject;
use Magento\Catalog\Model\Category;
use Celebros\ConversionPro\Model\Config\Source\CategoryQueryType;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Simplexml\Element as XmlElement;
use Magento\Store\Model\ScopeInterface;

class Search extends Helper\AbstractHelper
{
    /**#@+
     * Constants for keys of data array
     */
    public const CATEGORY_QUESTION_TEXT = 'Category';
    public const CAT_ID_DYN_PROPERTY = 'MagEntityID';
    public const CACHE_ID = 'conversionpro';
    public const REDIRECT_DYNAMIC_PROPERTY_NAME = 'redirection url';
    /**#@-*/

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var array
     */
    protected $customResultsCache = [];

    /**
     * @var string
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
     * @var Cache
     */
    protected $cache;

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var array
     */
    protected $productAttributes = [];

    /**
     * @var DataObject
     */
    protected $currentSearchParams;

    /**
     * @var array
     */
    public $appliedFilters = [];

    /**
     * @var SearchModel
     */
    protected $search;

    /**
     * @param Helper\Context $context
     * @param Data $helper
     * @param Cache $cache
     * @param SearchModel $search
     * @param Category $category
     * @param ResponseFactory $response
     */
    public function __construct(
        Helper\Context $context,
        Data $helper,
        CacheHelper $cache,
        SearchModel $search,
        CategoryModel $category,
        ResponseFactory $response
    ) {
        $this->helper = $helper;
        $this->search = $search;
        $this->cache = $cache;
        $this->category = $category;
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
     * @return false|XmlElement|mixed|\SimpleXMLElement
     * @throws NoSuchEntityException
     */
    public function getCustomResults(DataObject $params = null)
    {
        $params = ($params === null) ? $this->getSearchParams() : clone $params;

        // order
        if (!($this->order === null) && !$params->hasSortBy()) {
            $params->setSortBy($this->order);
        }

        // page size
        if (!($this->pageSize === null) && !$params->hasPageSize()) {
            $params->setPageSize($this->pageSize);
        }

        // current page
        if (!($this->currentPage === null) && !$params->hasCurrentPage()) {
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
                        ->create()
                        ->setRedirect($property->getAttribute('value'))
                        ->sendResponse();
                    return true;
                }
            }
        }
    }

    /**
     * Get all questions
     *
     * @return false|XmlElement|\SimpleXMLElement|string
     */
    public function getAllQuestions()
    {
        if ($this->allQuestionsCache === null) {
            $this->allQuestionsCache = $this->search->getAllQuestions();
        }

        return $this->allQuestionsCache;
    }

    /**
     * Get Question answers
     *
     * @param string $questionId
     * @return false|XmlElement|mixed|\SimpleXMLElement
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

        return [
            $requestVar,
            str_replace(' ', '_', $requestVar),
            str_replace(' ', '+', $requestVar)
        ];
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
        $questions = $this->getAllQuestions();
        $names = ['price'];
        if (!empty($questions->Questions)) {
            foreach ($questions->Questions->children() as $question) {
                $names[] = $question->getAttribute('Text');
            }
        }

        return $names;
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
     * @return false|XmlElement|\SimpleXMLElement|null
     */
    public function getQuestionByField($value, $field)
    {
        $allQuestions = $this->getAllQuestions()->Questions->Question;
        foreach ($allQuestions as $question) {
            if (in_array($value, $this->getAltRequestVars($question->getAttribute($field)))) {
                return $question;
            }
        }

        return false;
    }

    /**
     * @return XmlElement|\SimpleXMLElement|void|null
     */
    public function getPriceQuestionMock()
    {
        $allQuestions = $this->getAllQuestions()->Questions->Question;
        foreach ($allQuestions as $question) {
            $mock = clone $question;
            $mock->setAttribute('Id', 'PriceQuestion');
            $mock->setAttribute('Text', 'By price range');
            $mock->setAttribute('SideText', 'Price');
            $mock->setAttribute('Type', 'Price');

            return $mock;
        }
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
     * Get minimum and maximum prices of price question
     *
     * @param string|null $val
     * @return array|false|false[]|int
     * @throws NoSuchEntityException
     */
    public function getMinMaxPrices($val = null)
    {
        $result = $val ? false : ['min' => false, 'max' => false];
        $response = $this->getCustomResults();
        if (!isset($response->QwiserSearchResults->Questions->Question)) {
            return $result;
        }

        $values = [];
        foreach ($response->QwiserSearchResults->Questions->Question as $question) {
            if ($question->getAttribute('Id') == 'PriceQuestion') {
                foreach ($question->Answers->Answer as $answer) {
                    $id = $answer->getAttribute('Id');
                    if (preg_match('@^_P(\d+)_(\d+)$@', $id, $matches)) {
                        $values[] = $matches[1];
                        $values[] = $matches[2];
                    }
                }
            }
        }

        if (!count($values)) {
            return $result;
        }

        switch ($val) {
            case 'max':
                $result = (int)max($values);
                break;
            case 'min':
                $result = (int)min($values);
                break;
            default:
                $result = [
                    'min' => min($values),
                    'max' => max($values)
                ];
        }

        return $result;
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
