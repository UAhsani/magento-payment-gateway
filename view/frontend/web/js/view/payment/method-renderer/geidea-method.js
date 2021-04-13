define([
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Geidea_Payment/js/action/set-payment-method'
    ],
    function (
        ko, $, Component, alert,
        additionalValidators, setPaymentMethodAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Geidea_Payment/payment/geidea'
            },

            getCode: function() {
                return 'geidea_payment';
            },

            isActive: function() {
                return true;
            },

            setPaymentMethod: function () {
                return setPaymentMethodAction(this.messageContainer);
            },

            reserve: function() {
                var params = {
                    'quote_id': window.checkoutConfig.quoteData['entity_id']
                };

                return $.post(window.checkoutConfig.payment.geidea_payment.reserveUrl, params)
            },

            startPayment: function(data) {
                
                var onSuccess = function(_message, _statusCode) {
                    console.log("suc");
                }
        
                var onError = function(error) {
                    console.log(error);
                    console.log("error");
                }
        
                var onCancel = function() {
                    console.log("can");
                }
        
                var api = new GeideaApi(this.clientConfig.merchantKey, onSuccess, onError, onCancel);
                
                api.configurePayment({
                    callbackUrl: "https://magento2.avalab.io/geidea/payment/callback/",// window.checkoutConfig.payment.geidea_payment.callbackUrl.replace("http", "https"),
                    amount: 128,
                    currency: "SAR"
                });

                api.startPayment();
            },
            
            continueToGeidea: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.setPaymentMethod()
                        .then(this.reserve.bind(this))
                        .then(this.startPayment.bind(this))
                        .done(function () {
                            console.log("done xd");
                        });

                    return false;
                }

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