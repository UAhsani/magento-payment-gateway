define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'geidea_payment',
                component: 'Geidea_Payment/js/view/payment/method-renderer/geidea-method',
                config: window.checkoutConfig.payment.geidea_payment
            }
        );

        return Component.extend({});
    });
