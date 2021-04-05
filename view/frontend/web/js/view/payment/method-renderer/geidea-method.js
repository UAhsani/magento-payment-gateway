define([
        'jquery',
        'Magento_Payment/js/view/payment/cc-form'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Geidea_Payment/payment/geidea'
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'geidea_payment';
            },

            isActive: function() {
                return true;
            }
        });
    }
);