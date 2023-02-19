/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

define([
    'jquery'
], function ($) {
    return function (options) {
        var self = this;
        this.filters = options.filters;
        Object.keys(this.filters).forEach( function(filter) {
            var data = self.filters[filter];
            if (data.status) {
                require([
                    'conversionpro/filter/'+filter
                ], function(f) {
                    var filterVar = new f(data.selectors, data.options);
                });
            }
        });
    };
});
