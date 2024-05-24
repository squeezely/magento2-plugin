define([
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'domReady!',
], function (Component, customerData) {
    'use strict';

    return Component.extend({
        preventListOfProducts: [],

        initialize() {
            this._super();

            window._sqzl = _sqzl || [];

            if (!this.trackAddToCart) return;

            // Track any changes to add or remove items.
            customerData.get('cart').subscribe((data) => {
                const previousCount = +sessionStorage.getItem("sqzlSummaryCount") || 0;
                const currentCount = data.summary_count;

                // AddtoCart event
                if (currentCount > previousCount) this.sqzlAddToCart(data);
                // removeFromCart event
                if (currentCount < previousCount) this.sqzlRemoveFromCart(data);

                this.saveCurrentProducts(data.items);
                this.saveSessionData(currentCount);
            });
        },

        sqzlAddToCart(cartData) {
            let buffer = [];

            if (this.preventListOfProducts.length === 0) {
                this.preventListOfProducts = JSON.parse(sessionStorage.getItem("sqzlProductCart")) || [];
            }

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
                for (let i = 0; i < buffer.length; i++) {
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

            if (this.preventListOfProducts.length === 0) {
                this.preventListOfProducts = JSON.parse(sessionStorage.getItem("sqzlProductCart"));
            }

            cartData.items.forEach((obj) => {
                buffer.push({
                    "id": obj.product_sku,
                    "name": obj.product_name,
                    "price": obj.product_price_value.incl_tax ? +obj.product_price_value.incl_tax : obj.product_price_value,
                    "quantity": obj.qty
                });
            });

            // Option 1: when product fully removed
            if (buffer.length !== this.preventListOfProducts.length) {
                this.preventListOfProducts.forEach((obj) => {
                    if (buffer.findIndex((el) => el.id === obj.id) === -1) {
                        this.sqzlRemoveFromCartObject(obj.id);
                    }
                });
            } else {
                // Option 2: when product decreased qty
                const productId = this.findRemovedProduct(buffer, this.preventListOfProducts)[0];
                this.sqzlRemoveFromCartObject(productId);
            }

            this.preventListOfProducts = buffer;
        },

        findRemovedProduct(cartData, previousData) {
            const map = new Map(previousData.map(item => [item.id, item.quantity]));
            const result = [];

            cartData.forEach((item) => {
                const qty = map.get(item.id);
                if (qty !== undefined && item.quantity !== qty) {
                    result.push(item.id);
                }
            });

            return result;
        },

        sqzlRemoveFromCartObject(productSKU) {
            window._sqzl.push({
                "event": "RemoveFromCart",
                "products": [{"id": productSKU}],
            });
        },

        sqzlAddToCartObject(products) {
            let pushData = {
                "event": "AddToCart",
                "products": products,
            };
            window._sqzl.push(pushData);
        },

        // Fixes behavior when page reloads after adding or deleting product
        saveSessionData(count) {
            sessionStorage.setItem("sqzlProductCart", JSON.stringify(this.preventListOfProducts));
            sessionStorage.setItem("sqzlSummaryCount", count);
        },

        saveCurrentProducts(items) {
            this.preventListOfProducts = [];

            items.forEach((obj) => {
                this.preventListOfProducts.push({
                    "id": obj.product_sku,
                    "name": obj.product_name,
                    "price": obj.product_price_value.incl_tax ? +obj.product_price_value.incl_tax : obj.product_price_value,
                    "quantity": obj.qty
                });
            });
        }
    });
});
