<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

declare(strict_types=1);

namespace Celebros\ConversionPro\Plugin\CatalogGraphQl\Model\Config;

use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\GraphQl\Schema\Type\Entity\MapperInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Celebros\ConversionPro\Helper\Search;
use Celebros\ConversionPro\Helper\Data;

class FilterAttributeReader
{
    /**
     * @var Search
     */
    private $search;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Search $search
     * @return void
     */
    public function __construct(
        Search $search,
        Data $helper
    ) {
        $this->search = $search;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\CatalogGraphQl\Model\Config\FilterAttributeReader $reader
     * @param array $result
     * @return array
     */
    public function afterRead(
        \Magento\CatalogGraphQl\Model\Config\FilterAttributeReader $reader,
        array $result
    ) : array {
        $allQuestions = $this->search->getAllQuestions();
        if (empty($allQuestions)) {
            return $result;
        }

        foreach ($allQuestions as $question) {
            $attributeCode = str_replace(" ", "_", (string) $question->getAttribute('SideText'));
            if ($attributeCode) {
                $result['ProductAttributeFilterInput']['fields'][$attributeCode] = [
                    'name' => $attributeCode,
                    'type' => 'FilterEqualTypeInput',
                    'arguments' => [],
                    'required' => false,
                    'description' => sprintf('Attribute label: %s', $attributeCode)
                ];
            }
        }

        $result['ProductAttributeFilterInput']['fields']['price'] = [
            'name' => 'price',
            'type' => 'FilterEqualTypeInput',
            'arguments' => [],
            'required' => false,
            'description' => sprintf('Attribute label: %s', 'price')
        ];

        return $result;
    }
}
