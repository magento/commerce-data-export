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
    'underscore',
    'mage/translate',
    'Magento_Ui/js/grid/columns/column',
], function (_, $t, Column) {
    'use strict';

    return Column.extend({

        getOrigStatus: function (row) {
            return row['status_orig'];
        },
        /**
         * Retrieves label associated with a provided value.
         *
         * @returns {String}
         */
        getLabel: function () {
            var options = this.options || [],
                values = this._super(),
                label = [];

            if (_.isString(values)) {
                values = values.split(',');
            }

            if (!_.isArray(values)) {
                values = [values];
            }

            values = values.map(function (value) {
                return value + '';
            });

            values.forEach(function (value) {
                let index = _.findIndex(options, {value: value + ''})
                if (index !== -1 ) {
                    label.push(options[index].label);
                } else {
                    label.push($t('Error') + ' ' + value);
                }
            });


            return label.join(', ');
        }
    });
});