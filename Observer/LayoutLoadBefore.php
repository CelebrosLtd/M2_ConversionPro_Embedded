<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\ConversionPro\Observer;

use Magento\Framework\Event\ObserverInterface;

class LayoutLoadBefore implements ObserverInterface
{
    /**
     * @var mixed[]
     */
    private $handles;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    private $layout;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Celebros\ConversionPro\Helper\Data
     */
    private $helper;

    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\App\RequestInterface $request,
        \Celebros\ConversionPro\Helper\Data $helper
    ) {
        $this->layout = $layout;
        $this->request = $request;
        $this->helper = $helper;

        $this->addCelHandle('catalog_product_view', 'catalog_product_view_celebros');
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $currentHandle = $observer->getEvent()->getFullActionName();
        $allHandles =  $this->layout->getUpdate()->getHandles();
        if ($this->helper->isActiveEngine(get_class($this))) {
            $this->_addHandleToLayout($observer, $currentHandle . '_celebros');
            if (in_array('catalog_category_view_type_layered', $allHandles)) {
                $this->_addHandleToLayout($observer, 'catalog_layered_celebros');
            }
        }

        if (isset($this->handles[$currentHandle])) {
            $this->_addHandleToLayout($observer, $this->handles[$currentHandle]);
        }
    }

    protected function _addHandleToLayout($observer, $handleName)
    {
        $layout = $observer->getEvent()->getData('layout');
        $layout->getUpdate()->addHandle($handleName);

        return $layout->getUpdate();
    }

    public function addCelHandle($handle, $celHandle)
    {
        $this->handles[$handle] = $celHandle;
    }
}
