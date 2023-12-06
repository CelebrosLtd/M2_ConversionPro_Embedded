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
    'Celebros_ConversionPro/js/query-builder',
    'Celebros_ConversionPro/js/filter/range/formatter',
    'jquery-ui-modules/slider'
], function ($, queryBuilder, rangeFormatter) {
    'use strict';

    $.widget('mage.celebrosRangeSlider', {
        options: {
            sliderElement: "[data-role=slider]",
            sliderAmount: "[data-role=slider-amount]",
            minDefaultData: "min-range",
            maxDefaultData: "max-range",
            rangeTemplate: "${ $.min } - ${ $.max }",
            valueTemplate: "_%s%d_%d"
        },

        /**
         * @private
         */
        _create: function () {
            this.formatter = new rangeFormatter(this.element.data("type"), this.options.rangeTemplate);
            this.sliderElement = this.element.find(this.options.sliderElement).first();
            this.sliderAmount = this.element.find(this.options.sliderAmount).first();
            this.actionButton = this.element.find('button.action').first();
            this.applyActionButtonState(false);
            this._bind();
            this._init();
        },

        /**
         * Bind all events
         * @private
         */
        _bind: function () {
            this.actionButton.on('click', $.proxy(this._bindActionButton, this));
        },

        /**
         * Bind action button
         * @param event
         * @private
         */
        _bindActionButton: function (event) {
            this.apply();
        },

        /**
         * Init slider
         * @private
         */
        _init: function () {
            this.sliderElement.slider({
                range: true,
                min: this.getRangeMinValue(),
                max: this.getRangeMaxValue(),
                values: [ this.getRangeMinValue(), this.getRangeMaxValue() ],
                slide: function(event, ui) {
                    this.sliderAmount.html(this.formatter.process(ui.values));
                    this.applyActionButtonState(true);
                }.bind(this)
            });

            this.sliderAmount.html(
                this.formatter.process(this.sliderElement.slider('option', 'values'))
            );
        },

        /**
         * Get ranges minimum value
         * @returns {number}
         */
        getRangeMinValue: function () {
            var min = this.element.data(this.options.minDefaultData);
            min = isNaN(min) ? 0 : min;

            return min;
        },

        /**
         * Get ranges maximum value
         * @returns {number}
         */
        getRangeMaxValue: function () {
            var max = this.element.data(this.options.maxDefaultData);
            max = isNaN(max) ? 0 : max;

            return max;
        },

        /**
         * Format string
         * @param string
         * @param args
         * @returns {string}
         */
        format: function(string, args) {
            for (var k in args) {
                string = string.replace("{" + k + "}", args[k])
            }
            return string;
        },

        /**
         * Change state of action button
         * @param {boolean} state
         */
        applyActionButtonState: function(state) {
            this.actionButton.prop("disabled", !state);
        },

        /**
         * Apply slider changes
         */
        apply: function() {
            var minValue = this.sliderElement.slider("values", 0);
            var maxValue = this.sliderElement.slider("values", 1);
            if (minValue < maxValue) {
                var filterValue = this.options.valueTemplate
                    .replace('%s', this.element.data('value-suffix'))
                    .replace('%d', minValue)
                    .replace('%d', maxValue);
                window.location = queryBuilder.set(window.location.href, this.element.data('request-var'),  filterValue);
            }
        }
    });

    return $.mage.celebrosRangeSlider;
});
