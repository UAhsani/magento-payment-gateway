/*browser:true*/
/*global define*/
define([
    'jquery',
    'Magento_Vault/js/view/payment/method-renderer/vault'
], function ($, VaultComponent) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'Magento_Vault/payment/form'
        },

        getMaskedCard: function () {
            return this.details.maskedCC;
        },

        getExpirationDate: function () {
            return this.details.expirationDate;
        },

        getCardType: function () {
            return this.details.type;
        },

        getToken: function () {
            return this.publicHash;
        },

        getIcons: function (type) {
            if (this.details.type == "MADA") {
                var icon = {
                    'url': require.toUrl('Geidea_Payment/images/mada-logo.png'),
                    'width': 46,
                    'height': 30,
                    'title': 'icon_mada'
                };

                return icon;
            } else {
                return window.checkoutConfig.payment.ccform.icons[type];
            }
        }
    });
});