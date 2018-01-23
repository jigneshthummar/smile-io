define([
    'jquery',
    'mage/storage',
    'mage/url',
    'ko'
], function($, storage, url, ko) {
    "use strict";

    return function (smileData) {
        console.log(smileData);

        storage.post(
            url.build('/smileio/customer/index')
        ).done(
            function(response) {
                if (response) {
                    smileData([]);

                    $.each(response, function (i, v) {
                        smileData.push(v);
                    });
                }
            }
        );
    };
});