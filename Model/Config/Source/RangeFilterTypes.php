<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\ConversionPro\Model\Config\Source;

class RangeFilterTypes
{
    const DEF = 'default';
    const INPUTS = 'inputs';
    const SLIDER = 'slider';

    public function toArray()
    {
        return [
            self::DEF    => __('Default'),
            self::INPUTS => __('Inputs'),
            self::SLIDER => __('Slider')
        ];
    }

    public function toOptionArray()
    {
        $array = $this->toArray();
        $options = array_map(
            function ($value, $label) {
                return ['value' => $value, 'label' => $label];
            },
            array_keys($array),
            $array
        );
        return $options;
    }
}
