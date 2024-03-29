<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\ConversionPro\Model\Search;

use Celebros\ConversionPro\Helper\Data;
use Celebros\ConversionPro\Model\Search\Adapter\Celebros\Adapter;

class AdapterFactory extends \Magento\Search\Model\AdapterFactory
{
    /**
     * @inheritDoc
     */
    public function create(array $data = [])
    {
        $helper = $this->objectManager->get(Data::class);
        if ($helper->isActiveEngine(get_class($this))) {
            $adapter = $this->objectManager->create(
                Adapter::class
            );

            return $adapter;
        }

        return parent::create($data);
    }
}
