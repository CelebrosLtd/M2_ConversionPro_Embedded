/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

define([
    'jquery',
    'Celebros_ConversionPro/js/query-builder'
], function ($, queryBuilder) {
    'use strict';

    $.widget('mage.celebrosRangeInputs', {
        options: {
            inputMin: "[data-role=inputs-min]",
            minDefaultData: "min-range",
            inputMax: "[data-role=inputs-max]",
            maxDefaultData: "max-range",
            valueTemplate: "_%s%d_%d"
        },

        /**
         * @private
         */
        _create: function () {
            this.actionButton = this.element.find('button.action').first();
            this.inputMin = this.element.find(this.options.inputMin).first();
            this.inputMax = this.element.find(this.options.inputMax).first();
            this.applyActionButtonState(false);
            this._bind();
        },

        /**
         * Bind all events
         * @private
         */
        _bind: function () {
            this._bindElement(this.actionButton, this._bindActionButton);
            this._bindElement(this.inputMin, this._bindInput);
            this._bindElement(this.inputMax, this._bindInput);
        },

        /**
         * Bind per element
         * @private
         */
        _bindElement: function (element, callback) {
            if (element.is('select')) {
                element.on('change', $.proxy(callback, this));
            } else if (element.is('button')) {
                element.on('click', $.proxy(callback, this));
            } else if (element.is('input')) {
                element.on('change', $.proxy(callback, this));
            }
        },

        /**
         * Bind action button
         * @param event
         * @private
         */
        _bindActionButton: function (event) {
            this.arrangeInputs();
            this.apply();
            this.applyActionButtonState(true);
        },

        /**
         * Bind range input element
         * @param event
         * @private
         */
        _bindInput: function (event) {
            this.arrangeInputs();
            this.applyActionButtonState(true);
        },

        /**
         * Apply range changes
         */
        apply: function() {
            var filterValue = this.options.valueTemplate
                .replace('%s', this.element.data('value-suffix'))
                .replace('%d', this.getRangeMinValue())
                .replace('%d', this.getRangeMaxValue());
            window.location = queryBuilder.set(window.location.href, this.element.data('request-var'),  filterValue);
        },

        /**
         * Get ranges minimum value
         * @returns {number}
         */
        getRangeMinValue: function () {
            var min = this.inputMin.val()
                ? parseInt(this.inputMin.val())
                : parseInt(this.inputMin.data(this.options.minDefaultData));
            min = isNaN(min) ? 0 : min;

            if (min < parseInt(this.inputMin.data(this.options.minDefaultData))) {
                min = parseInt(this.inputMin.data(this.options.minDefaultData));
            }

            return min;
        },

        /**
         * Get ranges maximum value
         * @returns {number}
         */
        getRangeMaxValue: function () {
            var max = this.inputMax.val()
                ? parseInt(this.inputMax.val())
                : parseInt(this.inputMax.data(this.options.maxDefaultData));
            max = isNaN(max) ? 0 : max;

            if (max > parseInt(this.inputMax.data(this.options.maxDefaultData))) {
                max = parseInt(this.inputMax.data(this.options.maxDefaultData));
            }

            return max;
        },

        /**
         * Change state of action button
         * @param {boolean} state
         */
        applyActionButtonState: function(state) {
            this.actionButton.prop("disabled", !state);
        },

        /**
         * Arrange range inputs values
         */
        arrangeInputs: function () {
            var min = this.getRangeMinValue();
            if (min < parseInt(this.inputMin.data(this.options.minDefaultData))) {
                this.inputMin.val(min);
            }

            var max = this.getRangeMaxValue();
            if (max > parseInt(this.inputMax.data(this.options.maxDefaultData))) {
                this.inputMax.val(max);
            }
        }

    });

    return $.mage.celebrosRangeInputs;
});
