<?php
/**
 * Celebros
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 *
 ******************************************************************************
 * @category    Celebros
 * @package     Celebros_ConversionPro
 */
namespace Celebros\ConversionPro\Block\Catalog\Product\ProductList;

use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;
use Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer;
use Magento\Framework\Simplexml\Element as XmlElement;

class Toolbar extends \Magento\Catalog\Block\Product\ProductList\Toolbar
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Celebros\ConversionPro\Helper\Data
     */
    protected $helper;

    /**
     * @var \Celebros\ConversionPro\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var XmlElement
     */
    protected $response;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Config $catalogConfig,
        ToolbarModel $toolbarModel,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Catalog\Helper\Product\ProductList $productListHelper,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Framework\Registry $registry,
        \Celebros\ConversionPro\Helper\Data $helper,
        \Celebros\ConversionPro\Helper\Search $searchHelper,
        array $data = [],
        ToolbarMemorizer $toolbarMemorizer = null,
        \Magento\Framework\App\Http\Context $httpContext = null,
        \Magento\Framework\Data\Form\FormKey $formKey = null
    ) {
        $this->registry = $registry;
        $this->helper = $helper;
        $this->searchHelper = $searchHelper;

        parent::__construct(
            $context,
            $catalogSession,
            $catalogConfig,
            $toolbarModel,
            $urlEncoder,
            $productListHelper,
            $postDataHelper,
            $data,
            $toolbarMemorizer,
            $httpContext,
            $formKey
        );

        if ($this->helper->isActiveEngine() && $this->helper->isPermittedHandle()) {
            /*if (!$this->getDefaultOrder()) {
                $this->searchHelper->setOrder('relevance','desc');
            }*/

            // set block module name required to use same template as original
            $this->setModuleName('Magento_Catalog');
            // set current page, limit, order to search helper instead of collection
            //$this->searchHelper->setCurrentPage($this->getCurrentPage());
            //$this->searchHelper->setPageSize($this->getLimit());
            //$this->setDefaultDirection('asc');
            /*if ((!$this->_getData('_current_grid_order') && !$this->_toolbarModel->getOrder())
            || in_array($this->getCurrentOrder(), ['relevance','position'])) {
                $this->setDefaultDirection('desc');
            }*/

            /*$this->searchHelper->setOrder(
                $this->getCurrentOrder(),
                $this->getCurrentDirection()
            );*/

            /*$blockData = $this->searchHelper->getToolbarData();
            foreach ($blockData->getData() as $key=>$param) {
                $this->setData($key, $param);
            }*/
        }
    }

    /*public function getAvailableOrders()
    {
        $avOrders = parent::getAvailableOrders();
        if ($this->helper->isRelevanceNav2Search()
            && $this->helper->isPermittedHandle()
            && $this->helper->isActiveEngine()) {
            if (isset($avOrders['position'])) {
                unset($avOrders['position']);
            }

            $avOrders = array_merge(
                ['relevance' => 'Relevance'],
                $avOrders
            );
        }

        return $avOrders;
    }*/

    public function getTotalNum()
    {
        if ($this->helper->isActiveEngine() && $this->helper->isPermittedHandle()) {
            return (int)$this->getData('total_num');
        } else {
            return parent::getTotalNum();
        }
    }

    public function getFirstNum()
    {
        if ($this->helper->isActiveEngine() && $this->helper->isPermittedHandle()) {
            return ($this->getCurrentPage()  - 1) * $this->getLimit() + 1;
        } else {
            return parent::getFirstNum();
        }
    }

    public function getLastNum()
    {
        if ($this->helper->isActiveEngine() && $this->helper->isPermittedHandle()) {
            $collection = $this->getCollection();
            return ($this->getFirstNum() - 1) + $collection->count();
        } else {
            return parent::getLastNum();
        }
    }

    public function getLastPageNum()
    {
        if ($this->helper->isActiveEngine() && $this->helper->isPermittedHandle()) {
            return (int)$this->getData('last_page_num');
        } else {
            return parent::getLastPageNum();
        }
    }

    /*public function getWidgetOptionsJson(array $customOptions = [])
    {
        return parent::getWidgetOptionsJson(['directionDefault' => (in_array($this->getCurrentOrder(), ['relevance','position']) ? 'desc' : 'asc')]);
    }*/

    /* @TODO Remove this */
    /*protected function getOrderField()
    {
        if ($this->helper->isActiveEngine() && $this->helper->isPermittedHandle()) {
            if ($this->_orderField === null) {
                $this->_orderField = 'relevance';
            }

            return $this->_orderField;
        }

        return parent::getOrderField();
    }*/
}
