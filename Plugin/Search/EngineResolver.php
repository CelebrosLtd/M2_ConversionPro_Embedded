<?php

/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\ConversionPro\Plugin\Search;

use Magento\Search\Model\EngineResolver as Resolver;
use Celebros\ConversionPro\Helper\Data as Helper;

class EngineResolver
{
    /**
     * @param \Celebros\ConversionPro\Helper\Data $helper
     * @return void
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Returns 'celebros' search engine if Celebros search is enabled
     *
     * @param Resolver $resolver
     * @param string $result
     * @return string
     */
    public function afterGetCurrentSearchEngine(Resolver $resolver, $result)
    {
        if ($this->helper->isActiveEngine() && $this->helper->isPermittedHandle()) {
            $result = 'celebros';
        }

        return $result;
    }
}
