define([
    'jquery'
], function ($) {
    'use strict';

    var dfd = $.Deferred();

    return function loadGeideaScript(geideaSdkUrl) {
        require.config({
            paths: {
                geideaSdk: geideaSdkUrl
            },
            shim: {
                geideaSdk: {
                    exports: 'geidea'
                }
            }
        });

        if (dfd.state() !== 'resolved') {
            require(['geideaSdk'], function (geideaObject) {
                dfd.resolve(geideaObject);
            });
        }

        return dfd.promise();
    };
});
