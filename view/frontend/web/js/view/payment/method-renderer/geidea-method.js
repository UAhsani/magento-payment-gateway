define([
        'jquery',
        'Magento_Checkout/js/view/payment/default'
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