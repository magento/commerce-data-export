/**
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */

define([
    'Magento_Ui/js/grid/massactions',
    'uiRegistry'
], function (Massactions, registry) {
    'use strict';

    return Massactions.extend({

        /**
         * Default action callback. Sends selections data via POST request.
         *
         * @param {Object} action - Action data.
         * @param {Object} data - Selections data.
         */
        defaultCallback: function (action, data) {
            // Add current feed parameter from available data sources
            let currentFeed = document.getElementById('feed-selector').value;
            if (currentFeed) {
                if (!data.params) {
                    data.params = {};
                }
                data.params.feed = currentFeed;
            }

            // Call parent implementation with transformed data
            this._super(action, data);
        }
    });
});
