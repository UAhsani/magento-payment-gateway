define([
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/modal/alert',
        'Geidea_Payment/js/geidea/geidea-sdk'
    ],
    function (
        ko, $, Component, alert,
        geideaSdk) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Geidea_Payment/payment/geidea',
                api: ko.observable(null)
            },

            initialize: function () {
                this._super();

                var self = this;

                geideaSdk(this.clientConfig.geideaSdkUrl).done(function() {
                    self.api(new GeideaApi(self.clientConfig.merchantKey));
                });

                return this;
            },

            getCode: function() {
                return 'geidea_payment';
            },

            isActive: function() {
                return true;
            },

            continueToGeidea: function () {
                console.log(this.api());

                return false;
            },

            addError: function (message, type) {
                type = type || 'error';
                customerData.set('messages', {
                    messages: [{
                        type: type,
                        text: message
                    }],
                    'data_id': Math.floor(Date.now() / 1000)
                });
            },
    
            addAlert: function (message) {
                alert({
                    content: message
                });
            }
        });
    }
);