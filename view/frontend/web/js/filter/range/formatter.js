/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

define([
    'mage/utils/template',
    'underscore',
    'priceUtils'
], function (mageTemplate, _, priceUtils) {
    'use strict';

    /**
     * @param {String} valueType
     * @param {String} template
     */
    function RangeFormatter(valueType, template) {
        /**
         * @protected
         * @type {String}
         */
        this._valueType = 'Range';

        /**
         * @protected
         * @type {String}
         */
        this._template = '${ $.min } - ${ $.max }';

        if (valueType) {
            this._valueType = valueType;
        }

        if (template) {
            this._template = template;
        }
    }

    /**
     * @param {String} value
     * @returns {String}
     * @private
     */
    RangeFormatter.prototype._processValue = function (value) {
        switch (this._valueType) {
            case "price":
                value = priceUtils.formatPrice(value);
                break;
        }

        return value;
    }

    /**
     * @param {object} values
     * @returns {String}
     */
    RangeFormatter.prototype.process = function (values) {
        return mageTemplate.template(this._template, {
            min: this._processValue(_.first(values)),
            max: this._processValue(_.first(_.rest(values)))
        });
    };

    return RangeFormatter;
});
