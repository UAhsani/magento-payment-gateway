define([
        'ko',
        'jquery',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Geidea_Payment/js/action/set-payment-method'
    ],
    function (
        ko, $, $t,
        Component, messageList, additionalValidators,
        setPaymentMethodAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Geidea_Payment/payment/geidea',
                paymentActionError: $t('Something went wrong with your request. Please try again later.'),
                processing: ko.observable(false)
            },

            getCode: function() {
                return 'geidea_payment';
            },

            isActive: function() {
                return true;
            },

            setPaymentMethod: function (reject) {
                var deferred = $.Deferred();

                setPaymentMethodAction(this.messageContainer).done(function () {
                    return deferred.resolve();
                }).fail(function (response) {
                    var error;

                    try {
                        error = JSON.parse(response.responseText);
                    } catch (exception) {
                        error = this.paymentActionError;
                    }

                    return reject(new Error(error));
                }.bind(this));

                return deferred.promise();
            },

            reserve: function(reject) {
                var params = {
                    'quote_id': window.checkoutConfig.quoteData['entity_id']
                };

                this.processing(true);

                var deferred = $.Deferred();

                $.post(window.checkoutConfig.payment.geidea_payment.reserveUrl, params).done(function (data) {
                    if (data.success)
                        return deferred.resolve(data);
        
                    return reject(new Error(data['error_message']));
                }).fail(function (jqXHR, textStatus, err) {
                    return reject(err);
                }.bind(this));

                return deferred.promise();
            },

            startPayment: function(data, resolve, reject) {
                
                var onSuccess = function(_message, _statusCode) {
                    return resolve();
                }
        
                var onError = function(error) {
                    return reject(new Error(error.responseMessage));
                }
        
                var onCancel = function() {
                    return reject(new Error($t('Payment canceled')));
                }
        
                var api = new GeideaApi(this.clientConfig.merchantKey, onSuccess, onError, onCancel);
                
                api.configurePayment({
                    callbackUrl: "https://magento2.avalab.io/geidea/payment/callback/",// window.checkoutConfig.payment.geidea_payment.callbackUrl.replace("http", "https"),
                    amount: data.amount,
                    currency: "SAR"//data.currency
                });

                api.startPayment();
            },
            
            continueToGeidea: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    
                    $.Deferred(function (deferred) {
                        this.setPaymentMethod.bind(this, deferred.reject)()
                            .then(this.reserve.bind(this, deferred.reject))
                            .then(function(data) {
                                return this.startPayment.bind(this, data, deferred.resolve, deferred.reject)();
                            }.bind(this));
                    }.bind(this))
                        .promise()
                        .done(function() {
                            this.processing(false);
                            console.log("done xd");
                        }.bind(this))
                        .fail(function(err) {
                            this.processing(false);
                            this.addError(err.message);
                        }.bind(this));

                    return false;
                }

                return false;
            },

            addError: function (message) {
                messageList.addErrorMessage({
                    message: message
                });
            }
        });
    }
);