define(
    [
        'jquery',
        'Magento_Customer/js/model/authentication-popup',
        'Magento_Customer/js/customer-data',
        'Magento_Ui/js/modal/alert',
        'Magento_Ui/js/modal/confirm',
        'underscore',
        'jquery-ui-modules/widget',
        'mage/decorate',
        'mage/collapsible',
        'mage/cookies',
        'jquery-ui-modules/effect-fade'
    ],
    function ($, authenticationPopup, customerData, alert, confirm, _) {
        'use strict';
        return function (widget) {
            $.widget('mage.sidebar', widget, {
                _removeItemAfter: function (elem) {
                    var productData = this._getProductById(Number(elem.data('cart-item')));

                    if (!_.isUndefined(productData)) {
                        $(document).trigger('ajax:removeFromCart', {
                            productIds: [productData['product_id']],
                            productSku: productData['product_sku']
                        });

                        if (window.location.href.indexOf(this.shoppingCartUrl) === 0) {
                            window.location.reload();
                        }
                    }
                }
            });

            return $.mage.sidebar;
        }
    }
);