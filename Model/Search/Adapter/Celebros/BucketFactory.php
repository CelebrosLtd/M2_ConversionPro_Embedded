<?php

/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

namespace Celebros\ConversionPro\Model\Search\Adapter\Celebros;

use Magento\Framework\Simplexml\Element as XmlElement;
use Magento\Framework\Search\Response\Bucket;

class BucketFactory
{
    protected $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function create(XmlElement $rawDocument): Bucket
    {
        $documentId = null;
        $values = [];
        $label = $rawDocument->getAttribute('SideText');

        $rawAnswers = [];
        foreach ($rawDocument->Answers->children() as $rawAnswerDocument) {
            $rawAnswers[] = $rawAnswerDocument;
        }
        foreach ($rawDocument->ExtraAnswers->children() as $rawAnswerDocument) {
            $rawAnswers[] = $rawAnswerDocument;
        }
        foreach ($rawAnswers as $rawAnswerDocument) {
            $values[] = $this->objectManager->create(
                \Magento\Framework\Search\Response\Aggregation\Value::class,
                [
                    'value' => $rawAnswerDocument->getAttribute('Id'),
                    'metrics' => [
                        'value' => $rawAnswerDocument->getAttribute('Id'),
                        'label' => $rawAnswerDocument->getAttribute('Text'),
                        'count' => $rawAnswerDocument->getAttribute('ProductCount')
                    ]
                ]
            );
        }

        return $this->objectManager->create(
            \Magento\Framework\Search\Response\Bucket::class,
            [
                'name' => ($label == 'Price') ? strtolower((string) $label) : $label,
                'values' => $values
            ]
        );
    }
}
