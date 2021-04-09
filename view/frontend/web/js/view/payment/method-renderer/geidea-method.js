define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Geidea_Payment/js/action/set-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/customer-data'
    ],
    function (
        $, Component, setPaymentMethodAction,
        additionalValidators, customerData) {
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
            },

            continueToGeidea: function () {
                if (additionalValidators.validate()) {
                    //update payment method information if additional data was changed
                    this.selectPaymentMethod();
                    setPaymentMethodAction(this.messageContainer).done(
                        function () {
                            customerData.invalidate(['cart']);
                            $.mage.redirect(
                                window.checkoutConfig.payment.geidea_payment.payUrl
                            );
                        }
                    );

                    return false;
                }
            }
        });
    }
);