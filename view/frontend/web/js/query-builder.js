/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

define([], function () {
    var baseUrl = '';

    var queryBuilder = {

        /**
         * Extracts value of specified 'GET' parameter
         * from a provided url string.
         *
         * @param {String} url - Url to be processed.
         * @param {String} key - Name of parameter to be extracted.
         * @returns {String|Undefined} Parameters' value.
         */
        get: function (url, key) {
            var regExp,
                value;

            key = key
                .replace(/[\[]/, '\\\[')
                .replace(/[\]]/, '\\\]');

            regExp = new RegExp('[\\?&]' + key + '=([^&#]*)');
            value  = regExp.exec(url);

            if (value) {
                return value[1];
            }
        },

        /**
         * Adds 'GET' parameter to the url.
         *
         * @param {String} url - Url to be processed.
         * @param {String} key - Name of parameter that will be added.
         * @param {*} value - Parameters' value.
         * @returns {String} Modified string.
         */
        set: function (url, key, value) {
            var hashIndex   = url.indexOf('#'),
                hasHash     = !~hashIndex,
                hash        = hasHash ? '' : url.substr(hashIndex),
                regExp      = new RegExp('([?&])' + key + '=.*?(&|$)', 'i'),
                separator;

            value = encodeURIComponent(value);
            url = hasHash ? url : url.substr(0, hashIndex);

            if (url.match(regExp)) {
                url = url.replace(regExp, '$1' + key + '=' + value + '$2');
            } else {
                separator = ~url.indexOf('?') ? '&' : '?';
                url += separator + key + '=' + value;
            }

            return url + hash;
        },

        /**
         * Removes specified 'GET' parameter from a provided url.
         *
         * @param {String} url - Url to be processed.
         * @param {String} key - Name of parameter that will be removed.
         * @returns {String} Modified string.
         */
        remove: function (url, key) {
            var urlParts = url.split('?'),
                baseUrl  = urlParts[0],
                query    = urlParts[1],
                regExp;

            if (!query) {
                return url;
            }

            query = query.split('#');

            regExp = new RegExp('&' + key + '(=[^&]*)?|^' + key + '(=[^&]*)?&?');
            query[0] = query[0].replace(regExp, '');

            if (query[0]) {
                baseUrl += '?' + query.join('#');
            }

            return baseUrl;
        }
    };

    return queryBuilder;
});
