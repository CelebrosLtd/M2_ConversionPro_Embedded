<?php

/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\ConversionPro\Model;

use Celebros\ConversionPro\Client\Curl;
use Celebros\ConversionPro\Helper\Analytics;
use Celebros\ConversionPro\Helper\Cache;
use Celebros\ConversionPro\Logger\LoggerFactory;
use Celebros\Main\Helper\Debug;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\App\Action\Context;
use \Magento\Framework\DataObject;
use Magento\Framework\HTTP\Client\Curl as HttpCurl;
use \Magento\Framework\Simplexml\Element as XmlElement;
use Celebros\ConversionPro\Helper\Data;
use Celebros\ConversionPro\Exception\SearchException;
use Psr\Log\LoggerInterface;

class Search
{
    /**
     * @var AttributeCollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Analytics
     */
    protected $analytics;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var HttpCurl
     */
    public $curl;

    /**
     * @var bool
     */
    protected $newSearch = true;

    /**
     * @var Debug
     */
    protected $debug;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var AttributeCollection
     */
    protected $attributeCollection;

    /**
     * @var string
     */
    protected $allQuestions;

    /**
     * @var string[]
     */
    protected $systemFilters = ['category_ids', 'visibility'];

    /**
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param Session $session
     * @param Data $helper
     * @param Analytics $analytics
     * @param Cache $cache
     * @param Context $context
     * @param LoggerFactory $loggerFactory
     * @param Curl $curl
     * @param Debug $debug
     */
    public function __construct(
        AttributeCollectionFactory $attributeCollectionFactory,
        Session $session,
        Data $helper,
        Analytics $analytics,
        Cache $cache,
        Context $context,
        LoggerFactory $loggerFactory,
        Curl $curl,
        Debug $debug
    ) {
        $this->session = $session;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->helper = $helper;
        $this->analytics = $analytics;
        $this->cache = $cache;
        $this->logger = $loggerFactory->create();
        $this->curl = $curl;
        $this->context = $context;
        $this->debug = $debug;
    }

    /**
     * Create search Handler
     *
     * @param DataObject|null $params
     * @return string
     */
    public function createSearchHandle(DataObject $params = null)
    {
        $searchInfoXml = $this->createSearchInfoXml($params);
        return $this->searchInfoXmlToHandle($searchInfoXml);
    }

    /**
     * Create Search info XML
     *
     * @param DataObject|null $params
     * @return XmlElement
     */
    public function createSearchInfoXml(DataObject $params = null)
    {
        $this->newSearch = true;
        !($params === null) || $params = new DataObject();

        // Search string
        $searchInfoXml = new XmlElement('<SearchInformation/>');
        if ($params->hasQuery()) {
            $query = $this->escapeQueryString($params->getQuery());
            $searchInfoXml->addChild('Query', $query);
            $searchInfoXml->addChild('OriginalQuery', $query);
        }

        // Filters
        if ($params->hasFilters() && is_array($params->getFilters())) {
            // create answer container element
            $answersXml = $searchInfoXml->addChild('QwiserAnsweredAnswers');
            $answerCount = 0;
            foreach ($params->getFilters() as $name => $optionIds) {
                $this->newSearch = false;
                if (!in_array($name, $this->systemFilters) && $this->validateRequestVar($name)) {
                    is_array($optionIds) || $optionIds = [$optionIds];
                    foreach ($optionIds as $optionId) {
                        $optionId = $this->helper->filterValueToArray($optionId);
                        foreach ($optionId as $id) {
                            // create answer element
                            $answerXml = $answersXml->addChild('QwiserAnsweredAnswer');
                            $answerXml->setAttribute('AnswerId', $id);
                            $answerXml->setAttribute('EffectOnSearchPath', '0');
                            // add answer element
                            ++$answerCount;
                        }
                    }
                }
            }

            $answersXml->setAttribute('Count', $answerCount);
        }

        // Sorting
        if ($params->hasSortBy() && is_array($params->getSortBy())) {
            // [<field-name>, <order>]
            $sortBy = $params->getSortBy();
            $name = array_shift($sortBy);
            $order = array_shift($sortBy);
            if (!($name === null)) {
                // create sorting options element
                $fieldName = $this->getSortingFieldName($name);
                $ascending = (strtolower($order) == 'desc') ? 'false' : 'true';
                [$method, $isNumeric] = $this->getSortingMethod($name);
                $sortingOptionsXml = $searchInfoXml->addChild('SortingOptions');
                $sortingOptionsXml->setAttribute('FieldName', $fieldName);
                $sortingOptionsXml->setAttribute('Ascending', $ascending);
                $sortingOptionsXml->setAttribute('Method', $method);
                if (!($isNumeric === null)) {
                    $sortingOptionsXml->setAttribute(
                        'NumericSort',
                        $isNumeric ? 'true' : 'false'
                    );
                }
            }
        }

        //Profile Name
        if ($profileName = $this->helper->getProfileName()) {
            $searchInfoXml->setAttribute('IsDefaultSearchProfileName', 'false');
            $searchInfoXml->setAttribute('SearchProfileName', urlencode((string)$profileName));
        }

        // Page size
        if ($params->hasPageSize()) {
            $searchInfoXml->setAttribute('IsDefaultPageSize', 'false');
            $searchInfoXml->setAttribute('PageSize', $params->getPageSize());
        }

        // Current page
        if ($params->hasCurrentPage()) {
            $searchInfoXml->setAttribute('CurrentPage', $params->getCurrentPage());
        }

        // some mandatory arguments
        $searchInfoXml->setAttribute('PriceFieldName', 'Price');
        $searchInfoXml->setAttribute('NumberOfPages', 9999999);

        return $searchInfoXml;
    }

    /**
     * Validate request
     *
     * @param string $varName
     * @return bool
     */
    public function validateRequestVar(string $varName) : bool
    {
        $questions = $this->getAllQuestions();
        $names = ['price'];
        if (!empty($questions->Questions)) {
            foreach ($questions->Questions->children() as $question) {
                $names = array_merge($names, $this->getAltRequestVars($question->getAttribute('Text')));
            }
        }

        return in_array($varName, $names);
    }

    /**
     * Format request vars
     *
     * @param string $requestVar
     * @return array
     */
    public function getAltRequestVars(string $requestVar) : array
    {
        $requestVar = str_replace('.', '_', $requestVar);

        return [
            $requestVar,
            str_replace(' ', '_', $requestVar),
            str_replace(' ', '+', $requestVar)
        ];
    }

    /**
     * Convert Search info XML to SearchHandel
     *
     * @param XmlElement $xml
     * @return string
     */
    public function searchInfoXmlToHandle(XmlElement $xml)
    {
        $handle = '';
        if (isset($xml->Query) && strlen($xml->Query) > 0) {
            $handle .= 'A=' . $this->handleEscape($this->prepareSearchQueryForRequest($xml->Query)) . '~';
        }
        if (isset($xml->OriginalQuery) && strlen($xml->OriginalQuery) > 0) {
            $handle .= 'B=' . $this->handleEscape($this->prepareSearchQueryForRequest($xml->OriginalQuery)) . '~';
        }
        if (!empty($xml->getAttribute('CurrentPage'))) {
            $handle .= 'C=' . $xml->getAttribute('CurrentPage') . '~';
        }
        if (!empty($xml->getAttribute('IsDefaultPageSize')) && ($xml->getAttribute('IsDefaultPageSize') != 'true')) {
            $handle .= 'D=' . $xml->getAttribute('PageSize') . '~';
        }
        if (isset($xml->SortingOptions) && !$this->isSortingOptionsDefault($xml->SortingOptions)) {
            $handle .= 'E=' . $this->handleEscape($this->sortingOptionsToHandleString($xml->SortingOptions)) . '~';
        }

        if (!empty($xml->getAttribute('FirstQuestionId'))) {
            $handle .= 'F=' . $this->handleEscape($xml->getAttribute('FirstQuestionId')) . '~';
        }

        if (isset($xml->QwiserAnsweredAnswers)
            && !empty($xml->QwiserAnsweredAnswers->getAttribute('Count'))
        ) {
            $handle .= 'G='
                . $this->handleEscape($this->answeredAnswersToHandleString($xml->QwiserAnsweredAnswers)) . '~';
        }

        if (!empty($xml->getAttribute('IsDefaultSearchProfileName'))
            && $xml->getAttribute('IsDefaultSearchProfileName') != 'true'
        ) {
            $handle .= 'H=' . $this->handleEscape($xml->getAttribute('SearchProfileName')) . '~';
        }
        if (!empty($xml->getAttribute('PriceFieldName'))) {
            $handle .= 'I=' . $this->handleEscape($xml->getAttribute('PriceFieldName')) . '~';
        }
        if (isset($xml->SpecialCasesDetectedInThisSession)) {
            $handle .= 'J'
                . $this->handleEscape($this->specialCasesToHandleString($xml->SpecialCasesDetectedInThisSession))
                . '~';
        }
        if (!empty($xml->getAttribute('MaxMatchClassFound'))) {
            $handle .= 'K=' . $xml->getAttribute('MaxMatchClassFound') . '~';
        }
        if (!empty($xml->getAttribute('MinMatchClassFound'))) {
            $handle .= 'L=' . $xml->getAttribute('MinMatchClassFound') . '~';
        }
        if (!empty($xml->getAttribute('NumberOfPages')) && $xml->getAttribute('NumberOfPages') != '1') {
            $handle .= 'M=' . $xml->getAttribute('NumberOfPages') . '~';
        }
        if (!empty($xml->getAttribute('Stage')) && $xml->getAttribute('Stage') != '1') {
            $handle .= 'N=' . $xml->getAttribute('Stage') . '~';
        }

        return $handle;
    }

    /**
     * Escape handle delimiter
     *
     * @param string $string
     * @return array|string|string[]
     */
    protected function handleEscape($string)
    {
        return str_replace('~', '~~', $string);
    }

    /**
     * Check if sorting options have default values
     *
     * @param XmlElement $xml
     * @return bool
     */
    protected function isSortingOptionsDefault(XmlElement $xml)
    {
        $isDefault = ($xml->getAttribute('Ascending') != 'true')
            && ($xml->getAttribute('NumericSort') != 'true')
            && empty($xml->getAttribute('FieldName'))
            && ($this->getAttribute('Method') == 'Relevancy');
        return $isDefault;
    }

    /**
     * Convert sorting options to Handle string
     *
     * @param XmlElement $xml
     * @return string
     */
    protected function sortingOptionsToHandleString(XmlElement $xml)
    {
        $params = [
            ($xml->getAttribute('Ascending') == 'true') ? '1' : '0',
            ($xml->getAttribute('NumericSort') == "true") ? '1' : '0',
            $this->sortMethodToInt($xml->getAttribute('Method')),
            $xml->getAttribute('FieldName')];
        return implode('^', $params);
    }

    /**
     * Convert answered answers to Handle string
     *
     * @param XmlElement $xml
     * @return string
     */
    protected function answeredAnswersToHandleString(XmlElement $xml)
    {
        $handle = '';
        foreach ($xml->children() as $answerXml) {
            $handle .= sprintf(
                '%s^%s^',
                $answerXml->getAttribute('AnswerId') ?? '',
                $this->effectOnSearchPathToInt(
                    $answerXml->getAttribute('EffectOnSearchPath')
                )
            );
        }
        return $handle;
    }

    /**
     * Convert special cases to Handle string
     *
     * @param XmlElement $xml
     * @return string
     */
    protected function specialCasesToHandleString(XmlElement $xml)
    {
        return implode('^', $xml->children());
    }

    /**
     * Convert sorting method to integer value
     *
     * @param string $method
     * @return int
     */
    protected function sortMethodToInt($method)
    {
        switch ($method) {
            case 'Price':
                return 0;
            case 'Relevancy':
                return 1;
            case 'SpecifiedField':
                return 2;
            default:
                return -1;
        }
    }

    /**
     * Convert effect to integer value
     *
     * @param string $effect
     * @return int
     */
    protected function effectOnSearchPathToInt($effect)
    {
        if (is_numeric($effect)) {
            return $effect;
        }

        switch ($effect) {
            case 'Exclude':
                return 0;
            case 'ExactAnswerNode':
                return 1;
            case 'EntireAnswerPath':
                return 2;
            default:
                return -1;
        }
    }

    /**
     * Perform search
     *
     * @param string $query
     * @return false|XmlElement|\SimpleXMLElement
     */
    public function search($query)
    {
        $request = sprintf(
            'search?sitekey=%s&Query=%s',
            (string)$this->helper->getSiteKey(),
            $this->prepareSearchQueryForRequest($query)
        );
        return $this->request($request);
    }

    /**
     * Prepare search query to request
     *
     * @param string $query
     * @return string
     */
    public function prepareSearchQueryForRequest($query)
    {
        return str_replace("%2B", "%20", urlencode((string)$query));
    }

    /**
     * Get custom results
     *
     * @param string $searchHandle
     * @param bool $isNewSearch
     * @param string $previousSearchHandle
     * @return false|XmlElement|\SimpleXMLElement
     */
    public function getCustomResults($searchHandle, $isNewSearch, $previousSearchHandle = '')
    {
        // use previous search handle if not provided
        if (empty($previousSearchHandle) && $this->session->hasPreviousSearchHandle()) {
            $previousSearchHandle = $this->session->getPreviousSearchHandle();
        }

        $request = sprintf(
            'GetCustomResults?Sitekey=%s&SearchHandle=%s&NewSearch=%s&PreviousSearchHandle=%s',
            (string)$this->helper->getSiteKey(),
            $searchHandle,
            ($this->newSearch ? '1' : '0'),
            (!$this->newSearch ? $previousSearchHandle : '')
        );

        $response = $this->request($request);

        if ($this->helper->isRequestDebug()) {
            $message = [];
            $message['title'] = __('Celebros Search Engine');
            $message['products_sequence'] = $this->extractProductSequenceFromResponse($response);
            $this->debug->addMessage($this->helper->prepareDebugMessage($message));
        }

        if ($this->helper->isRedirectAvailable()) {
            $this->isFallbackRedirect($response);
            $this->isSingleProductsRedirect($response);
        }

        // save previous search handle
        $previousSearchHandle = $response->QwiserSearchResults->getAttribute('SearchHandle');
        $this->session->setPreviousSearchHandle($previousSearchHandle);

        return $response;
    }

    /**
     * Extract product sequence from response
     *
     * @param XmlElement $response
     * @return string
     */
    protected function extractProductSequenceFromResponse(XmlElement $response) : string
    {
        $productSequence = [];
        $products = $response->QwiserSearchResults->Products;

        foreach ($products->children() as $rawDocument) {
            $price = $name = '';
            foreach ($rawDocument->Fields->children() as $field) {
                if ($field->getAttribute('name') == Data::RESPONSE_XML_TITLE_ATTRIBUTE_NAME) {
                    $name = $field->getAttribute('value');
                }
                if ($field->getAttribute('name') == Data::RESPONSE_XML_PRICE_ATTRIBUTE_NAME) {
                    $price = $field->getAttribute('value');
                }
            }

            $productSequence[] = $name . '(' . $price. ')';
        }

        return implode(", ", $productSequence);
    }

    /**
     * Check if there is a redirect setup if single product is returned in result
     *
     * @param \SimpleXMLElement $results
     * @return void
     */
    public function isSingleProductsRedirect($results)
    {
        $relevantProductsCount = $results->QwiserSearchResults->getAttribute('RelevantProductsCount');
        $products = $results->QwiserSearchResults->Products;
        if ($relevantProductsCount == 1 && $this->helper->isRedirectToProductEnabled()) {
            foreach ($products->Product->Fields->Field as $field) {
                if ($field->getAttribute('name') == Data::RESPONSE_XML_LINK_ATTRIBUTE_NAME) {
                    $this->context->getRedirect()->redirect(
                        $this->context->getResponse(),
                        $this->prepareUrlForRedirect(str_replace('http:', '', $field->getAttribute('value')))
                    );
                }
            }
        }
    }

    /**
     * Prepare URL for redirect
     *
     * @param string $rawUrl
     * @return string
     */
    public function prepareUrlForRedirect($rawUrl)
    {
        if (strpos($rawUrl, "//") !== false && strpos($rawUrl, "//") === 0) {
            $rawUrl = substr_replace($rawUrl, null, 0, 2);
        }

        if (!preg_match("~^(?:f|ht)tps?://~i", $rawUrl)) {
            $rawUrl = "http://" . $rawUrl;
        }

        return $rawUrl;
    }

    /**
     * Do redirect if there is a fallback redirect
     *
     * @param \SimpleXMLElement $results
     * @return void
     */
    public function isFallbackRedirect($results)
    {
        $maxMatchClassFound = $results->QwiserSearchResults->getAttribute("MaxMatchClassFound");
        $minMatchClassFound = $results->QwiserSearchResults->getAttribute("MinMatchClassFound");
        $searchInfo = $results->QwiserSearchResults->SearchInformation;
        $redirect = false;
        if ($param = $searchInfo->SpecialCasesDetectedInThisSession->asArray()) {
            if (isset($param['Value']) && $param['Value'] == 'NoResultsFallbackEmptyQuery') {
                $redirect = $this->helper->isFallbackRedirectEnabled()
                    && $this->helper->fallbackRedirectUrl();
            }
        }

        if ($maxMatchClassFound == 'None'
            && $minMatchClassFound == 'None'
            && $redirect
        ) {
            $this->analytics->sendAnalyticsRequest($results);
            $this->context->getRedirect()->redirect(
                $this->context->getResponse(),
                $this->helper->fallbackRedirectUrl()
            );
        }
    }

    /**
     * Request all questions
     *
     * @return false|XmlElement|\SimpleXMLElement|string
     */
    public function getAllQuestions()
    {
        if (!$this->allQuestions) {
            $request = sprintf(
                'GetAllQuestions?Sitekey=%s&Searchprofile=%s',
                (string)$this->helper->getSiteKey(),
                urlencode((string)$this->helper->getProfileName())
            );
            $this->allQuestions = $this->request($request);
        }

        return $this->allQuestions;
    }

    /**
     * Request question answers
     *
     * @param string $questionId
     * @return false|XmlElement|\SimpleXMLElement
     */
    public function getQuestionAnswers($questionId)
    {
        $request = sprintf(
            'GetQuestionAnswers?Sitekey=%s&QuestionId=%s',
            (string)$this->helper->getSiteKey(),
            $questionId
        );
        return $this->request($request);
    }

    /**
     * Do search request through API
     *
     * @param string $request
     * @return false|XmlElement|\SimpleXMLElement
     */
    protected function request($request)
    {
        $customerGroupName = $this->helper->getCurrentCustomerGroupName();
        if ($customerGroupName && $this->helper->isCustomerGroupNameUsedForPrinciples()) {
            $request .= '&principles=' . $customerGroupName;
        }

        $requestUrl = $this->getRequestUrl($request);
        $startTime = round(microtime(true) * 1000);
        $cacheId = $this->cache->getId(__METHOD__, [$request]);
        if ($response = $this->cache->load($cacheId)) {
            if ($this->helper->isRequestDebug()) {
                $stime = round(microtime(true) * 1000) - $startTime;
                $message = [
                    'title' => __('Celebros Search Engine'),
                    'request' => $requestUrl,
                    'cached' => 'TRUE'
                ];
                $this->debug->addMessage($this->helper->prepareDebugMessage($message));
            }
        } else {
            $this->curl->addHeader('Accept', 'text/xml');
            $this->curl->get($requestUrl);
            $response = $this->curl->getBody();

            $this->cache->save($response, $cacheId);

            if ($this->helper->isRequestDebug()) {
                $stime = round(microtime(true) * 1000) - $startTime;
                $message = [
                    'title' => __('Celebros Search Engine'),
                    'request' => $requestUrl,
                    'cached' => 'FALSE',
                    'duration' => $stime . 'ms'
                ];
                $this->debug->addMessage($this->helper->prepareDebugMessage($message));
            }
        }

        return $this->parseXmlResponse($response);
    }

    /**
     * Gte host URL
     *
     * @return string
     */
    protected function getHostUrl()
    {
        $host = $this->helper->getHost() ?? '';
        $host = preg_replace('@^http://@', '', $host);
        $host = 'http://' . rtrim($host);

        $port = $this->helper->getPort();

        return empty($port) ? $host : $host . ':' . $port;
    }

    /**
     * Get request URL
     *
     * @param string $request
     * @return string
     */
    protected function getRequestUrl($request)
    {
        return $this->getHostUrl() . '/' . ltrim($request, '/');
    }

    /**
     * Parse XML response
     *
     * @param string $response
     * @return false|XmlElement|\SimpleXMLElement
     */
    public function parseXmlResponse($response)
    {
        try {
            $xml = simplexml_load_string($response, XmlElement::class);
        } catch (\Exception $message) {
            $exception = (new SearchException($message))->create();
        }

        $exception = $exception ?? (new SearchException($xml))->create();

        if ($exception) {
            $this->logException(
                $response,
                $exception
            );

            $excMessage = [
                'title' => __('Celebros Search Engine'),
                'request' => $exception->getMessage()
            ];

            $this->debug->addMessage($this->helper->prepareDebugMessage($excMessage));
            //throw $exception;
        }

        return $xml->ReturnValue ?? false;
    }

    /**
     * Log exception
     *
     * @param string|null $response
     * @param \Exception|null $exception
     * @return void
     */
    protected function logException(
        $response = null,
        \Exception $exception = null
    ): void {
        if ($exception) {
            $this->logger->warning($exception->getMessage());
            if ($response) {
                $this->logger->warning('Response: ' . $response);
            }
        }
    }

    /**
     * Escape query string
     *
     * @param string $query
     * @return string
     */
    protected function escapeQueryString($query)
    {
        $query = str_replace(' ', '+', $query);
        $query = str_replace('&', '%26', $query);
        return $query;
    }

    /**
     * Get field name used for sorting
     *
     * @param string $name
     * @return string
     */
    protected function getSortingFieldName($name)
    {
        if ($name == 'name') {
            return 'Title';
        } elseif (in_array($name, ['relevance', 'position'])) {
            return 'Relevancy';
        } else {
            return ucfirst($name);
        }
    }

    /**
     * Get sorting method
     *
     * @param string $name
     * @return array
     */
    protected function getSortingMethod($name)
    {
        if (in_array($name, ['relevance', 'position'])) {
            return ['Relevancy', true];
        } elseif ($name == 'price') {
            return ['Price', true];
        } else {
            $attributeCollection = $this->getAttributeCollection();
            $attribute = $attributeCollection->getItemByColumnValue('code', $name);
            $isNumeric = false;
            if (!($attribute === null)) {
                $isNumeric = in_array($attribute->getBackendType(), ['int', 'decimal', 'datetime']);
            }
            return ['SpecifiedField', $isNumeric];
        }
    }

    /**
     * Get attribute collection
     *
     * @return AttributeCollection
     */
    protected function getAttributeCollection()
    {
        if ($this->attributeCollection === null) {
            $this->attributeCollection = $this->attributeCollectionFactory->create();
        }
        return $this->attributeCollection;
    }
}
