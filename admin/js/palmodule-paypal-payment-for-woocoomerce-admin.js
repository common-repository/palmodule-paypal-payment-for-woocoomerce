(function ($) {
    'use strict';
    $(window).load(function () {
        $('#woocommerce_palmodule_braintree_sandbox').change(function () {
            var sandbox = $('#woocommerce_palmodule_braintree_sandbox_public_key, #woocommerce_palmodule_braintree_sandbox_private_key, #woocommerce_palmodule_braintree_sandbox_merchant_id').closest('tr');
            var production = $('#woocommerce_palmodule_braintree_live_public_key, #woocommerce_palmodule_braintree_live_private_key, #woocommerce_palmodule_braintree_live_merchant_id').closest('tr');
            if ($(this).is(':checked')) {
                sandbox.show();
                production.hide();
            } else {
                sandbox.hide();
                production.show();
            }
        }).change();

        $('#woocommerce_palmodule_paypal_rest_sandbox').change(function () {
            var sandbox = $('#woocommerce_palmodule_paypal_rest_rest_client_id_sandbox, #woocommerce_palmodule_paypal_rest_rest_secret_id_sandbox').closest('tr');
            var production = $('#woocommerce_palmodule_paypal_rest_rest_client_id_live, #woocommerce_palmodule_paypal_rest_rest_secret_id_live').closest('tr');
            if ($(this).is(':checked')) {
                sandbox.show();
                production.hide();
            } else {
                sandbox.hide();
                production.show();
            }
        }).change();

        $('#woocommerce_palmodule_paypal_express_sandbox').change(function () {
            var sandbox = jQuery('#woocommerce_palmodule_paypal_express_rest_client_id_sandbox, #woocommerce_palmodule_paypal_express_rest_secret_id_sandbox').closest('tr');
            var production = jQuery('#woocommerce_palmodule_paypal_express_rest_client_id_live, #woocommerce_palmodule_paypal_express_rest_secret_id_live').closest('tr');
            if ($(this).is(':checked')) {
                sandbox.show();
                production.hide();
                jQuery('#woocommerce_palmodule_paypal_express_sandbox_api_credentials').show();
                jQuery('#woocommerce_palmodule_paypal_express_api_credentials').hide();
            } else {
                sandbox.hide();
                jQuery('#woocommerce_palmodule_paypal_express_api_credentials').show();
                jQuery('#woocommerce_palmodule_paypal_express_sandbox_api_credentials').hide();
                production.show();
            }
        }).change();
        
        $('#woocommerce_palmodule_paypal_pro_testmode').change(function () {
            var sandbox = $('#woocommerce_palmodule_paypal_pro_sandbox_api_username, #woocommerce_palmodule_paypal_pro_sandbox_api_password, #woocommerce_palmodule_paypal_pro_sandbox_api_signature').closest('tr');
            var production = $('#woocommerce_palmodule_paypal_pro_api_username, #woocommerce_palmodule_paypal_pro_api_password, #woocommerce_palmodule_paypal_pro_api_signature').closest('tr');
            if ($(this).is(':checked')) {
                sandbox.show();
                production.hide();
            } else {
                sandbox.hide();
                production.show();
            }
        }).change();
        
        $('#woocommerce_palmodule_paypal_pro_payflow_testmode').change(function () {
            var sandbox = $('#woocommerce_palmodule_paypal_pro_payflow_sandbox_paypal_vendor, #woocommerce_palmodule_paypal_pro_payflow_sandbox_paypal_password, #woocommerce_palmodule_paypal_pro_payflow_sandbox_paypal_user, #woocommerce_palmodule_paypal_pro_payflow_sandbox_paypal_partner').closest('tr');
            var production = $('#woocommerce_palmodule_paypal_pro_payflow_paypal_vendor, #woocommerce_palmodule_paypal_pro_payflow_paypal_password, #woocommerce_palmodule_paypal_pro_payflow_paypal_user, #woocommerce_palmodule_paypal_pro_payflow_paypal_partner').closest('tr');
            if ($(this).is(':checked')) {
                sandbox.show();
                production.hide();
            } else {
                sandbox.hide();
                production.show();
            }
        }).change();
        
        $('#woocommerce_palmodule_paypal_advanced_testmode').change(function () {
            var sandbox = $('#woocommerce_palmodule_paypal_advanced_sandbox_paypal_vendor, #woocommerce_palmodule_paypal_advanced_sandbox_paypal_password, #woocommerce_palmodule_paypal_advanced_sandbox_paypal_user, #woocommerce_palmodule_paypal_advanced_sandbox_paypal_partner').closest('tr');
            var production = $('#woocommerce_palmodule_paypal_advanced_paypal_vendor, #woocommerce_palmodule_paypal_advanced_paypal_password, #woocommerce_palmodule_paypal_advanced_paypal_user, #woocommerce_palmodule_paypal_advanced_paypal_partner').closest('tr');
            if ($(this).is(':checked')) {
                sandbox.show();
                production.hide();
            } else {
                sandbox.hide();
                production.show();
            }
        }).change();

    });
})(jQuery);
