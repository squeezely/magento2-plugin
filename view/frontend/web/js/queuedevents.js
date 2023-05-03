define([
        'jquery',
        'uiComponent',
        'domReady!',
    ], function ($, Component) {
        'use strict';
        return Component.extend({
            initialize: function () {
                this._super();
                window._sqzl = _sqzl || [];
                var events = [];
                $.ajax({
                    url: this.queuedevents.url,
                    type: 'get',
                    global: false,
                    success: function (data) {
                        events = $.parseJSON(data);
                        $.each(events, function(i, event) {
                            _sqzl.push(event);
                        });
                    }
                });

            },
        });
    }
);
