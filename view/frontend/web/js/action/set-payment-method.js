define([
    'Magento_Checkout/js/action/set-payment-information'
], function (setPaymentInformation) {
    'use strict';

    return function (messageContainer, paymentData) {
        return setPaymentInformation(messageContainer, paymentData);
    };
});
