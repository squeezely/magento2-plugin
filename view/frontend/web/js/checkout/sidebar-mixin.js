define(
    [
        'jquery',
        'underscore',
    ],
    function ($, _) {
        'use strict';
        return function (widget) {
            return $.widget('mage.sidebar', $.mage.sidebar, {
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
                    return this._super(elem);
                }
            });
        }
    }
);