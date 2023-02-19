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

use Magento\Framework\Api\AbstractSimpleObject;

class Document extends AbstractSimpleObject implements \IteratorAggregate
{
    public const CUSTOM_ATTRIBUTES = 'document_fields';
    public const ID = 'document_id';

    /**
     * @param string $documentId
     * @param array $documentFields
     * @param array $data
     */
    public function __construct(
        $documentId,
        array $documentFields,
        array $data = []
    ) {
        parent::__construct($data);
        $this->_data[self::CUSTOM_ATTRIBUTES] = $documentFields;
        $this->_data[self::ID] = $documentId;
    }

    /**
     * Get Document ID
     *
     * @return bool|int
     */
    public function getId()
    {
        return isset($this->_data[self::ID]) ? (int)$this->_data[self::ID] : false;
    }

    /**
     * Set Document ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get custom Attribute by its code
     *
     * @param string $attributeCode
     * @return mixed
     */
    public function getCustomAttribute($attributeCode)
    {
        return $this->_data[self::CUSTOM_ATTRIBUTES][$attributeCode] ?? null;
    }

    /**
     * Set custom Attribute value by its code
     *
     * @param string $attributeCode
     * @param mixed $attributeValue
     * @return $this
     */
    public function setCustomAttribute($attributeCode, $attributeValue)
    {
        $attributes = $this->getCustomAttributes();
        $attributes[$attributeCode] = $attributeValue;
        return $this->setCustomAttributes($attributes);
    }

    /**
     * Get list of custom attributes
     *
     * @return array
     */
    public function getCustomAttributes()
    {
        return $this->_get(self::CUSTOM_ATTRIBUTES);
    }

    /**
     * Set custom attributes
     *
     * @param array $attributes
     * @return $this
     */
    public function setCustomAttributes(array $attributes)
    {
        return $this->setData(self::CUSTOM_ATTRIBUTES, $attributes);
    }

    /**
     * Implementation of \IteratorAggregate::getIterator()
     *
     * @return \ArrayIterator
     * @since 100.1.0
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $attributes = (array)$this->getCustomAttributes();
        return new \ArrayIterator($attributes);
    }
}
