define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'ko',
    'Mediact_Smile/js/fetch-data'
], function($, Component, customerData, ko, fetchData) {
    "use strict";

    var smileData = ko.observableArray([]);

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            this._super();
            this.customer = customerData.get('customer');

            fetchData(smileData);
        },

        getAttributes: function() {
            return  {
                'data-channel-api-key' : smileData()[0],
                'data-external-customer-id' : smileData()[1],
                'data-customer-auth-digest' : smileData()[2]
            };
        }
    });
});