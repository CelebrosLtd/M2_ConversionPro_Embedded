<?php
/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\ConversionPro\Plugin\Catalog\Model\Layer;

use Celebros\ConversionPro\Helper\Data as Helper;
use Celebros\ConversionPro\Helper\Search as SearchHelper;
use Celebros\ConversionPro\Model\Catalog\Layer\Filter\Question;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\FilterList as FilterListSubject;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Simplexml\Element as XmlElement;

class FilterList
{
    public const APPLIED_FILTERS_ATTRIBUTE = 'SideText';
    public const PRICE_FILTER_NAME = 'Price';

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var SearchHelper
     */
    private $searchHelper;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var AbstractFilter[]
     */
    private $filters = [];

    /**
     * @var array
     */
    private $appliedFilters = [];

    /**
     * FilterList constructor.
     *
     * @param Helper $helper
     * @param SearchHelper $searchHelper
     * @param RequestInterface $request
     */
    public function __construct(
        Helper $helper,
        SearchHelper $searchHelper,
        RequestInterface $request
    ) {
        $this->helper = $helper;
        $this->searchHelper = $searchHelper;
        $this->request = $request;
    }

    /**
     * Filters array getter plugin
     *
     * @param FilterListSubject $subject
     * @param array $result
     * @param Layer $layer
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetFilters(FilterListSubject $subject, array $result, Layer $layer)
    {
        if (!$this->helper->isActiveEngine()) {
            return $result;
        }

        return $this->getCelebrosFilters($layer);
    }

    /**
     * Replace filters with those got from Celebros API
     *
     * @param Layer $layer
     * @return AbstractFilter[]
     */
    private function getCelebrosFilters(Layer $layer)
    {
        if (!count($this->filters)) {
            $response = $this->searchHelper->getCustomResults();
            $questions = $response->QwiserSearchResults->Questions;
            $questionsList = $this->sortFilters($questions);
            foreach ($questionsList as $question) {
                $this->filters[] = $this->createQuestionFilter($question, $layer);
                $this->appliedFilters[] = $question->getAttribute(self::APPLIED_FILTERS_ATTRIBUTE);
            }

            $remFilters = array_diff($this->searchHelper->getFilterRequestVars(), $this->appliedFilters);
            foreach ($remFilters as $fltr) {
                $remFilters = array_merge($this->searchHelper->getAltRequestVars($fltr), $remFilters);
            }

            $priceQuestion = $this->searchHelper->getPriceQuestionMock();

            $remFilters = array_unique($remFilters);
            foreach ($this->request->getParams() as $var => $value) {
                if (in_array($var, $remFilters)
                    && !in_array($var, $this->appliedFilters)
                ) {
                    $question = $this->searchHelper->getQuestionByField($var, self::APPLIED_FILTERS_ATTRIBUTE);
                    if ($question) {
                        $var = $question->getAttribute(self::APPLIED_FILTERS_ATTRIBUTE);
                        $this->createQuestionFilter($question, $layer)->apply($this->request);
                        $this->appliedFilters[] = $var;
                    }
                }

                if ($var == 'price'
                    && !in_array($priceQuestion->getAttribute(self::APPLIED_FILTERS_ATTRIBUTE), $this->appliedFilters)
                ) {
                    $this->createQuestionFilter($priceQuestion, $layer)->apply($this->request);
                    $this->appliedFilters[] = $priceQuestion->getAttribute(self::APPLIED_FILTERS_ATTRIBUTE);
                }
            }
        }

        return $this->filters;
    }

    /**
     * Sort filters
     *
     * @param XmlElement $questions
     * @return array
     */
    private function sortFilters(XmlElement $questions): array
    {
        $priceSortOrder = $this->helper->getPriceFilterPosition();
        $questionsList = [];
        $sort = 1;
        foreach ($questions->children() as $question) {
            if ($priceSortOrder == $sort) {
                $sort++;
            }

            if ($question->getAttribute(self::APPLIED_FILTERS_ATTRIBUTE) == self::PRICE_FILTER_NAME) {
                $questionsList[$priceSortOrder] = $question;
            } else {
                $questionsList[$sort++] = $question;
            }
        }

        ksort($questionsList);

        return $questionsList;
    }

    /**
     * Create filter
     *
     * @param XmlElement $question
     * @param Layer $layer
     * @return Question|mixed
     */
    protected function createQuestionFilter(XmlElement $question, Layer $layer)
    {
        $answers = $question->Answers;
        $extraAnswers = $question->ExtraAnswers;

        $objectManager = ObjectManager::getInstance();
        $filter = $objectManager->create(
            Question::class,
            [
                'data' => [
                    'question' => $question,
                    'answers' => $answers,
                    'eanswers' => $extraAnswers
                ],
                'layer' => $layer
            ]
        );

        return $filter;
    }
}
