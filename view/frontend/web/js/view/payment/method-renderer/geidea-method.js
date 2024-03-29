define([
        'ko',
        'jquery',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Geidea_Payment/js/action/set-payment-method',
        'Magento_Vault/js/view/payment/vault-enabler'
    ],
    function (
        ko, $, $t,
        Component, messageList, additionalValidators,
        setPaymentMethodAction, VaultEnabler) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Geidea_Payment/payment/geidea',
                paymentActionError: $t('Something went wrong with your request. Please try again later.'),
                processing: ko.observable(false)
            },

            initialize: function () {
                var self = this;
    
                self._super();
                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
                
                return self;
            },

            getCheckoutImage: function() {
                return this.clientConfig.checkoutIcon;
            },

            getCode: function() {
                return 'geidea_payment';
            },

            isActive: function() {
                return true;
            },

            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': { }
                };
    
                this.vaultEnabler.visitAdditionalData(data);
    
                return data;
            },

            setPaymentMethod: function (reject) {
                var deferred = $.Deferred();

                setPaymentMethodAction(this.messageContainer, this.getData()).done(function () {
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

                $.post(this.clientConfig.reserveUrl, params).done(function (data) {
                    if (data.success)
                        return deferred.resolve(data);
        
                    return reject(new Error(data['error_message']));
                }).fail(function (jqXHR, textStatus, err) {
                    return reject(err);
                }.bind(this));

                return deferred.promise();
            },

            startPayment: function(data, reject) {
                
                var deferred = $.Deferred();
                
                var onSuccess = function(_message, _statusCode) {
                    return deferred.resolve();
                }
        
                var onError = function(error) {
                    return reject(new Error(error.responseMessage));
                }
        
                var onCancel = function() {
                    return reject(new Error($t('Payment canceled')));
                }
        
                var api = new GeideaApi(this.clientConfig.merchantKey, onSuccess, onError, onCancel);
                
                api.configurePayment({
                    callbackUrl: this.clientConfig.callbackUrl,
                    amount: data.amount,
                    currency: data.currency,
                    merchantReferenceId: data.orderId,
                    merchantLogoUrl: this.clientConfig.merchantLogo,
                    isTransactionReceiptEnabled: this.clientConfig.receiptEnabled,
                    language: this.clientConfig.language,
                    cardOnFile: this.isVaultEnabled() && this.vaultEnabler.isActivePaymentTokenEnabler(),
                    styles: { "headerColor": this.clientConfig.headerColor },
                    email: {
                        email: data.customerEmail
                    },
                    address: data.address,
                    integrationType: this.clientConfig.integrationType,
                    name: this.clientConfig.name,
                    version: this.clientConfig.version,
                    pluginVersion: this.clientConfig.pluginVersion,
                    partnerId: this.clientConfig.partnerId
                });

                api.startPayment();

                return deferred.promise();
            },

            authorize: function(resolve, reject) {
                var params = {
                    'quote_id': window.checkoutConfig.quoteData['entity_id']
                };

                var deferred = $.Deferred();

                $.post(this.clientConfig.authorizeUrl, params).done(function (data) {
                    if (data.success)
                        return resolve(data);
        
                    return reject(new Error(data['error_message']));
                }).fail(function (jqXHR, textStatus, err) {
                    return reject(err);
                }.bind(this));

                return deferred.promise();
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
                                return this.startPayment.bind(this, data, deferred.reject)();
                            }.bind(this))
                            .then(this.authorize.bind(this, deferred.resolve, deferred.reject));
                    }.bind(this))
                        .promise()
                        .done(function(data) {
                            $.mage.redirect(data.redirectUrl);
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
            },

            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },
    
            getVaultCode: function () {
                return this.vaultCode;
            }
        });
    }
);