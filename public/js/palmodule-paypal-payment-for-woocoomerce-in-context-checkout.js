;
(function ($, window, document) {
    paypal.Button.render({
        env: palmodule_in_content_param.ENV, // Or 'sandbox'
        locale: palmodule_in_content_param.LOCALE,
        style: {
            label: 'checkout',
            size: palmodule_in_content_param.SIZE,
            shape: palmodule_in_content_param.SHAPE,
            color: palmodule_in_content_param.COLOR
        },
        payment: function () {
            var get_attributes = function () {
                var select = $('.variations_form').find('.variations select'),
                        data = {},
                        count = 0,
                        chosen = 0;
                select.each(function () {
                    var attribute_name = $(this).data('attribute_name') || $(this).attr('name');
                    var value = $(this).val() || '';
                    if (value.length > 0) {
                        chosen++;
                    }
                    count++;
                    data[ attribute_name ] = value;
                });
                return {
                    'count': count,
                    'chosenCount': chosen,
                    'data': data
                };
            };
            var postdata = {
                'nonce': palmodule_in_content_param.GENERATE_NONCE,
                'qty': $('.quantity .qty').val(),
                'attributes': $('.variations_form').length ? get_attributes().data : [],
                'wc-paypal_express-new-payment-method': $("#wc-paypal_express-new-payment-method").is(':checked'),
                'is_add_to_cart': palmodule_in_content_param.IS_PRODUCT,
                'product_id': palmodule_in_content_param.POST_ID
            };
            return paypal.request.post(palmodule_in_content_param.CREATE_PAYMENT_URL, postdata).then(function (data) {
                console.log(data.id);
                return data.paymentID;
            });
        },
        onAuthorize: function (data, actions) {
            console.log(actions);
            console.log(data);
            return actions.redirect();
        },
        onCancel: function (data, actions) {
            return actions.redirect();
        }
    }, '#palmodule_express_checkout_paypal_button');
    paypal.Button.render({
        env: palmodule_in_content_param.ENV, // Or 'sandbox'
        locale: palmodule_in_content_param.LOCALE,
        style: {
            label: 'credit',
            size: palmodule_in_content_param.SIZE,
            shape: palmodule_in_content_param.SHAPE
        },
        payment: function () {
            var get_attributes = function () {
                var select = $('.variations_form').find('.variations select'),
                        data = {},
                        count = 0,
                        chosen = 0;
                select.each(function () {
                    var attribute_name = $(this).data('attribute_name') || $(this).attr('name');
                    var value = $(this).val() || '';
                    if (value.length > 0) {
                        chosen++;
                    }
                    count++;
                    data[ attribute_name ] = value;
                });
                return {
                    'count': count,
                    'chosenCount': chosen,
                    'data': data
                };
            };
            var postdata = {
                'nonce': palmodule_in_content_param.GENERATE_NONCE,
                'qty': $('.quantity .qty').val(),
                'attributes': $('.variations_form').length ? get_attributes().data : [],
                'wc-paypal_express-new-payment-method': $("#wc-paypal_express-new-payment-method").is(':checked'),
                'is_add_to_cart': palmodule_in_content_param.IS_PRODUCT,
                'product_id': palmodule_in_content_param.POST_ID
            };
            return paypal.request.post(palmodule_in_content_param.CC_CREATE_PAYMENT_URL, postdata).then(function (data) {
                console.log(data.id);
                return data.paymentID;
            });
        },
        onAuthorize: function (data, actions) {
            console.log(actions);
            console.log(data);
            return actions.redirect();
        },
        onCancel: function (data, actions) {
            return actions.redirect();
        }
    }, '#palmodule_express_checkout_paypal_cc_button');
})(jQuery, window, document);