define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'domReady!',
], function (Component, customerData) {
    'use strict';

    return Component.extend({
        cartCount: null,
        preventListOfProducts: [],
        initialize() {
            this._super();
            const CART = customerData.get('cart')();

            if (!this.trackAddToCart) return;
            if (Object.keys(CART).length) {
                // Get count of product when module initialize
                this.cartCount = CART.summary_count;

                CART.items.forEach((obj) => {
                    this.preventListOfProducts.push({
                        "id": obj.product_sku,
                        "name": obj.product_name,
                        "price": obj.product_price_value.incl_tax ? +obj.product_price_value.incl_tax : obj.product_price_value,
                        "quantity": obj.qty
                    });
                });
            }
            window._sqzl = _sqzl || [];
            // Track any changes to add or remove items.
            customerData.get('cart').subscribe((data) => {
                // AddtoCart event
                if (data.summary_count > this.cartCount) this.sqzlAddToCart(data);
                // removeFromCart event
                if (data.summary_count < this.cartCount) this.sqzlRemoveFromCart(data);
                this.cartCount = data.summary_count;
            });
        },
        sqzlAddToCart(cartData) {
            let buffer = [];

            cartData.items.forEach((obj) => {
                buffer.push({
                    "id": obj.product_sku,
                    "name": obj.product_name,
                    "price": obj.product_price_value.incl_tax ? +obj.product_price_value.incl_tax : obj.product_price_value,
                    "quantity": obj.qty,
                    "language": this.locale
                });
            });

            // If add new product
            if (buffer.length !== this.preventListOfProducts.length) {
                this.sqzlAddToCartObject(buffer[0]);
            } else {
            // If product added but existing
                for(let i = 0; i < buffer.length; i++) {
                    if (buffer[i]["quantity"] !== this.preventListOfProducts[i]["quantity"]) {
                        this.sqzlAddToCartObject({
                            "id": buffer[i]["id"],
                            "name": buffer[i]["name"],
                            "price": buffer[i]["price"].incl_tax ? +buffer[i]["price"].incl_tax : buffer[i]["price"],
                            "quantity": buffer[i]["quantity"] - this.preventListOfProducts[i]["quantity"],
                            "language": this.locale
                        });
                    }
                }
            }

            this.preventListOfProducts = buffer;
        },
        sqzlRemoveFromCart(cartData) {
            let buffer = [];

            cartData.items.forEach((obj) => {
                buffer.push({
                    "id": obj.product_sku,
                    "name": obj.product_name,
                    "price": obj.product_price_value.incl_tax ? +obj.product_price_value.incl_tax : obj.product_price_value,
                    "quantity": obj.qty
                });
            });
            if (buffer.length !== this.preventListOfProducts.length) {
                this.preventListOfProducts.forEach((obj) => {
                    if (buffer.findIndex((el) => el.id === obj.id) === -1) this.sqzlRemoveFromCartObject(obj.id);
                });
            }
            this.preventListOfProducts = buffer;
        },
        sqzlRemoveFromCartObject: function (productSKU) {
            window._sqzl.push({
                "event": "RemoveFromCart",
                "products": [{ "id":  productSKU }],
            });
        },
        sqzlAddToCartObject: function(products) {
            let pushData = {
                "event": "AddToCart",
                "products": products,
            };
            window._sqzl.push(pushData);
        }
    });
});
