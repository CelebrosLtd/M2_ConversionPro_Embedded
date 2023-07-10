<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\ConversionPro\Plugin\CatalogGraphQl\Model\Resolver;

use Magento\CatalogGraphQl\Model\Resolver\Products as ProductsResolver;
use Celebros\ConversionPro\Helper\Data as Helper;

class Products
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param Helper $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    public function beforeResolve(
        ProductsResolver $resolver,
        $field,
        $context,
        $info,
        $value,
        $args
    ) {
        if (isset($args['filter']['category_id']['eq'])
            && $this->helper->isActiveEngine()
        ) {
            $queryText = 'CatId' . $args['filter']['category_id']['eq'];
            $args['search'] = $queryText;
        }

        return [$field, $context, $info, $value, $args];
    }
}
