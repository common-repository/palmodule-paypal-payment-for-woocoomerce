<?php

/**
 * WC_Gateway_Palmodule_Braintree class.
 *
 * @extends WC_Payment_Gateway_CC
 */
class WC_Gateway_Palmodule_Braintree extends WC_Payment_Gateway_CC {

    /**
     * Constructor
     */
    public $result;
    public $order_id;
    public $current_user_id;
    public $redirect_endpoint;
    public $return_result;
    public $request;
    public $order;

    function __construct() {
        $this->id = 'palmodule_braintree';
        $this->icon = $this->get_option('card_icon', '');
        if (is_ssl()) {
            $this->icon = preg_replace("/^http:/i", "https:", $this->icon);
        }
        $this->icon = apply_filters('woocommerce_palmodule_braintree_icon', $this->icon);
        $this->method_title = 'Braintree';
        $this->method_description = __('Credit Card payments Powered by PayPal / Braintree.', 'palmodule-paypal-payment-for-woocoomerce');
        $this->supports = array(
            'products',
            'refunds'
        );
        $this->init_form_fields();
        $this->init_settings();
        $this->enable_tokenized_payments = $this->get_option('enable_tokenized_payments', 'yes');
        if ($this->enable_tokenized_payments == 'yes') {
            $this->supports = array(
                'subscriptions',
                'products',
                'refunds',
                'subscription_cancellation',
                'subscription_reactivation',
                'subscription_suspension',
                'subscription_amount_changes',
                'subscription_payment_method_change', // Subs 1.n compatibility.
                'subscription_payment_method_change_customer',
                'subscription_payment_method_change_admin',
                'subscription_date_changes',
                'multiple_subscriptions',
                'add_payment_method',
                'tokenization'
            );
        }
        $this->merchant_account_id = NULL;
        $this->braintree_customer_id = NULL;
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->sandbox = 'yes' === $this->get_option('sandbox', 'yes');
        $this->environment = $this->sandbox == false ? 'production' : 'sandbox';
        $this->merchant_id = $this->sandbox == false ? $this->get_option('live_merchant_id') : $this->get_option('sandbox_merchant_id');
        $this->private_key = $this->sandbox == false ? $this->get_option('live_private_key') : $this->get_option('sandbox_private_key');
        $this->public_key = $this->sandbox == false ? $this->get_option('live_public_key') : $this->get_option('sandbox_public_key');
        $this->debug = 'yes' === $this->get_option('debug', 'yes');
        $this->invoice_prefix = $this->get_option('invoice_prefix');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('palmodule_braintree_before_process_payment', array($this, 'palmodule_braintree_before_process_payment'), 10, 1);
        add_action('palmodule_before_add_payment_method', array($this, 'palmodule_before_add_payment_method'), 10);
        if ($this->is_available()) {
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'), 0);
        }
        $this->result;
        $this->order_id = NULL;
        $this->current_user_id;
        $this->redirect_endpoint = 'account';
        $this->return_result;
        $this->request;
        $this->order;
    }

    /**
     * Admin Panel Options
     */
    public function admin_options() {
        ?>
        <h3><?php _e('Braintree', 'palmodule-paypal-payment-for-woocoomerce'); ?></h3>
        <p><?php _e($this->method_description, 'palmodule-paypal-payment-for-woocoomerce'); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table> <?php
    }

    /**
     * Check if this gateway is enabled
     */
    public function is_available() {
        if ('yes' != $this->enabled) {
            return false;
        }
        if (!$this->merchant_id || !$this->public_key || !$this->private_key) {
            return false;
        }
        return true;
    }

    public function init_form_fields() {
        $this->form_fields = include( 'settings-palmodule-braintree.php' );
    }

    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        $clientToken = $this->palmodule_braintree_get_client_token();
        ?>
        <div id="braintree-cc-form" class="wc-payment-form">
            <fieldset>
                <div id="palmodule_dropin-container"></div>
            </fieldset>
        </div>
        <?php
        if (is_ajax() || is_add_payment_method_page()) {
            $this->palmodule_braintree_dropin_script($clientToken);
        }
    }

    public function palmodule_braintree_dropin_script($clientToken) {
        ?>
        <script type="text/javascript">
            var init_braintree = function () {
                var authorization = '<?php echo $clientToken; ?>';
                var submitButton = document.querySelector('#place_order');
                var ccForm = jQuery('form.checkout, #order_review, #add_payment_method');
                var $form = jQuery('form.checkout, #order_review, #add_payment_method');
                var braintree_form = jQuery('#braintree-cc-form');
                braintree.dropin.create({
                    authorization: authorization,
                    container: '#palmodule_dropin-container',
                    paypal: {
                        flow: 'vault'
                    },
                    paypalCredit: {
                        flow: 'vault'
                    }
                }, function (err, dropinInstance) {
                    if (err) {
                        console.log("configuration error " + err);
                        jQuery('.woocommerce-error, .palmodule_braintree_token', ccForm).remove();
                        ccForm.prepend('<ul class="woocommerce-error"><li>' + err + '</li></ul>');
                        ccForm.unblock();
                        console.error(err);
                        return;
                    }
                    dropinInstance.on('paymentMethodRequestable', function () {
                        submitButton.removeAttribute('disabled');
                    });
                    dropinInstance.on('noPaymentMethodRequestable', function () {
                        submitButton.setAttribute('disabled', true);
                    });
                    if (jQuery('#payment_method_palmodule_braintree').is(':checked')) {
                    submitButton.addEventListener('click', function (event) {
                        event.preventDefault();
                        dropinInstance.requestPaymentMethod(function (err, payload) {
                            if (err) {
                                jQuery('.woocommerce-error, .braintree-token', ccForm).remove();
                                ccForm.prepend('<ul class="woocommerce-error"><li>' + err + '</li></ul>');
                                console.log("configuration error " + err);
                                ccForm.unblock();
                                return;
                            }
                            if (payload.nonce) {
                                braintree_form.find('input.palmodule_braintree_token').remove();
                                braintree_form.append('<input type="hidden" class="palmodule_braintree_token" name="palmodule_braintree_token" value="' + payload.nonce + '"/>');
                                $form.submit();
                            }
                        });
                    }, false);
                    }
                });
            };
            jQuery(window).ready(function () {
                init_braintree();
            });
            jQuery('form.checkout').on('checkout_place_order_palmodule_braintree', function () {
                return braintreeFormHandler();
            });
            function braintreeFormHandler() {
                if (jQuery('#payment_method_palmodule_braintree').is(':checked')) {
                    if (0 === jQuery('input.palmodule_braintree_token').size()) {
                        return false;
                    }
                } else {
                    return false;
                }
                //return true;
            }
        </script>
        <?php
    }

    public function palmodule_braintree_before_process_payment() {
        if ($this->palmodule_braintree_is_user_logged_in()) {
            $this->palmodule_braintree_create_customer();
            $this->palmodule_before_add_payment_method($redirect_endpoint = 'checkout');
        }
    }

    public function palmodule_braintree_create_customer() {
        $this->palmodule_braintree_get_braintree_customer_id();
        if ($this->braintree_customer_id == NULL) {
            $create_customer_request = $this->palmodule_braintree_create_customer_request();
            $this->palmodule_braintree_request('create_customer', $create_customer_request);
        }
    }

    public function palmodule_braintree_request($request_name = NULL, $request_param = NULL) {
        $this->palmodule_init_braintree_lib();
        if ($request_name == NULL) {
            return false;
        }
        try {
            switch ($request_name) {
                case 'create_customer':
                    $this->add_log('Braintree_Customer::create request' . print_r($request_param, true));
                    $this->result = Braintree_Customer::create(apply_filters('palmodule_braintree_create_customer_request_args', $request_param));
                    $this->add_log('Braintree_Customer::create response' . print_r($this->result, true));
                    $this->palmodule_braintree_response('create_customer');
                    break;
                case "create_payment_method":
                    $this->add_log('Braintree_PaymentMethod::create request' . print_r($request_param, true));
                    $this->result = Braintree_PaymentMethod::create(apply_filters('palmodule_braintree_create_payment_method_request_args', $request_param));
                    $this->add_log('Braintree_PaymentMethod::create response' . print_r($this->result, true));
                    $this->palmodule_braintree_response('create_payment_method');
                    break;
                case "payment_request":
                    $this->add_log('Braintree_Transaction::sale request' . print_r($request_param, true));
                    $this->result = Braintree_Transaction::sale(apply_filters('palmodule_braintree_create_payment_request_args', $request_param));
                    $this->add_log('Braintree_Transaction::sale response' . print_r($this->result, true));
                    $this->palmodule_braintree_response('payment_request');
                    break;
                case 'create_client_token':
                    $this->add_log('Braintree_ClientToken::generate request' . print_r($request_param, true));
                    return $clientToken = Braintree_ClientToken::generate($request_param);
                    $this->add_log('Braintree_ClientToken::generate response' . print_r($clientToken, true));
                    break;
                case 'get_merchant_account_id':
                    $this->result = Braintree_Configuration::gateway();
                    return $this->palmodule_braintree_response('get_merchant_account_id');
                    break;
            }
        } catch (Braintree_Exception_Authentication $e) {
            $this->add_log($request_name . "Braintree_Exception_Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
            wc_add_notice(__($request_name . "Braintree_Exception_Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.", 'palmodule-paypal-payment-for-woocoomerce'), 'error');
            wp_redirect(wc_get_cart_url());
            exit;
        } catch (Braintree_Exception_Authorization $e) {
            $this->add_log($request_name . "Braintree_Exception_Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
            wc_add_notice(__($request_name . "Braintree_Exception_Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.", 'palmodule-paypal-payment-for-woocoomerce'), 'error');
            wp_redirect(wc_get_cart_url());
            exit;
        } catch (Braintree_Exception_DownForMaintenance $e) {
            $this->add_log($request_name . "Braintree_Exception_DownForMaintenance: Request times out.");
            wc_add_notice(__($request_name . "Braintree_Exception_DownForMaintenance: Request times out.", 'palmodule-paypal-payment-for-woocoomerce'), 'error');
            wp_redirect(wc_get_cart_url());
            exit;
        } catch (Braintree_Exception_ServerError $e) {
            $this->add_log($request_name . "Braintree_Exception_ServerError " . $e->getMessage());
            wc_add_notice(__($request_name . "Braintree_Exception_ServerError " . $e->getMessage(), 'palmodule-paypal-payment-for-woocoomerce'), 'error');
            wp_redirect(wc_get_cart_url());
            exit;
        } catch (Braintree_Exception_SSLCertificate $e) {
            $this->add_log($request_name . "Braintree_Exception_SSLCertificate " . $e->getMessage());
            wc_add_notice(__($request_name . "Braintree_Exception_SSLCertificate " . $e->getMessage(), 'palmodule-paypal-payment-for-woocoomerce'), 'error');
            wp_redirect(wc_get_cart_url());
            exit;
        } catch (Braintree_Exception_NotFound $e) {
            if ($request_name == 'create_client_token' && $e->getMessage() == 'Customer specified by customer_id does not exist') {
                if ($this->palmodule_braintree_is_user_logged_in()) {
                    delete_user_meta($this->current_user_id, 'braintree_customer_id');
                    return $clientToken = Braintree_ClientToken::generate();
                }
            } else {
                $this->add_log($request_name . "Braintree_Exception_NotFound " . $e->getMessage());
                wc_add_notice(__($request_name . "Braintree_Exception_NotFound " . $e->getMessage(), 'palmodule-paypal-payment-for-woocoomerce'), 'error');
                wp_redirect(wc_get_cart_url());
                exit;
            }
        } catch (Exception $e) {
            $this->add_log('Error: Unable to complete request. Reason: ' . $e->getMessage());
            wc_add_notice(__('Error: Unable to complete request. Reason: ' . $e->getMessage(), 'palmodule-paypal-payment-for-woocoomerce'), 'error');
            wp_redirect(wc_get_cart_url());
            exit;
        }
    }

    public function palmodule_braintree_response($request_name = NULL) {
        if ($request_name == NULL) {
            return false;
        }
        if ((!empty($this->result->success) && $this->result->success == true) || ($this->result->config && is_object($this->result->config))) {
            switch ($request_name) {
                case 'create_customer':
                    if (!empty($this->result->customer->id)) {
                        $this->palmodule_braintree_save_braintree_customer_id($this->result->customer->id);
                    }
                    break;
                case 'create_payment_method':
                    if (!empty($this->result->paymentMethod)) {
                        $this->palmodule_braintree_save_payment_method();
                    }
                    break;
                case "payment_request":
                    $this->palmodule_braintree_order_status_manage();
                    break;
                case 'get_merchant_account_id':
                    if (is_null($this->order_id)) {
                        $currencycode = get_woocommerce_currency();
                    } else {
                        $currencycode = version_compare(WC_VERSION, '3.0', '<') ? $this->order->get_order_currency() : $this->order->get_currency();
                    }
                    $merchantAccountIterator = $this->result->merchantAccount()->all();
                    foreach ($merchantAccountIterator as $merchantAccount) {
                        if ($currencycode == $merchantAccount->currencyIsoCode) {
                            $this->merchant_account_id = $merchantAccount->id;
                            return $this->merchant_account_id;
                        }
                    }
                    break;
            }
        } else {
            $notice = '';
            foreach ($this->result->errors->deepAll() AS $error) {
                $notice .= $error->code . ": " . $error->message . "\n";
            }
            wc_add_notice($notice);
            $this->return_result = array(
                'result' => 'faild',
                'redirect' => ''
            );
        }
    }

    public function palmodule_braintree_order_status_manage() {
        $maybe_settled_later = array(
            'settling',
            'settlement_pending',
            'submitted_for_settlement',
        );
        if (in_array($this->result->transaction->status, $maybe_settled_later)) {
            $this->order->payment_complete($this->result->transaction->id);
            $this->order->add_order_note(sprintf(__('%s payment approved! Trnsaction ID: %s', 'palmodule-paypal-payment-for-woocoomerce'), $this->title, $this->result->transaction->id));
            WC()->cart->empty_cart();
        } else {
            $this->add_log(sprintf('Info: unhandled transaction id = %s, status = %s', $this->result->transaction->id, $this->result->transaction->status));
            $this->order->update_status('on-hold', sprintf(__('Transaction was submitted to PayPal Braintree but not handled by WooCommerce order, transaction_id: %s, status: %s. Order was put in-hold.', 'palmodule-paypal-payment-for-woocoomerce'), $this->result->transaction->id, $this->result->transaction->status));
        }
        $this->return_result = array(
            'result' => 'success',
            'redirect' => $this->get_return_url($this->order)
        );
    }

    public function palmodule_braintree_create_customer_request() {
        $customer = new WC_Customer($this->current_user_id);
        return $create_customer_request = array('firstName' => $customer->get_billing_first_name(),
            'lastName' => $customer->get_billing_last_name(),
            'company' => $customer->get_billing_company(),
            'email' => $customer->get_billing_email(),
            'phone' => $customer->get_billing_phone(),
            'fax' => '',
            'website' => ''
        );
    }

    public function palmodule_braintree_save_payment_token($palmodule_braintree_payment_tokens) {
        if (!empty($palmodule_braintree_payment_tokens)) {
            update_post_meta($this->order_id, '_palmodule_braintree_payment_tokens', $palmodule_braintree_payment_tokens);
        }
    }

    public function palmodule_braintree_save_payment_method() {
        if (!empty($this->result->paymentMethod->token)) {
            if (empty($this->result->paymentMethod->cardType) && !empty($this->result->paymentMethod->billingAgreementId)) {
                update_user_meta($this->current_user_id, 'billing_agreement_id', $this->result->paymentMethod->billingAgreementId);
            }
            if ($this->redirect_endpoint == 'checkout') {
                $this->palmodule_braintree_save_payment_token($this->result->paymentMethod->token);
                $this->return_result = array(
                    'result' => 'success',
                    'redirect' => ''
                );
            } else {
                $this->return_result = array(
                    'result' => 'success',
                    'redirect' => wc_get_endpoint_url('add-payment-method')
                );
            }
        }
    }

    public function palmodule_before_add_payment_method($redirect_endpoint = NULL) {
        if (!empty($redirect_endpoint)) {
            $this->redirect_endpoint = $redirect_endpoint;
        }
        $payment_method_nonce = self::get_posted_variable('palmodule_braintree_token');
        if ($this->redirect_endpoint == 'account' && !empty($payment_method_nonce)) {
            $this->palmodule_braintree_create_customer();
        }
        if (!empty($this->braintree_customer_id)) {
            $payment_method_request = array('customerId' => $this->braintree_customer_id, 'paymentMethodNonce' => $payment_method_nonce, 'options' => array('failOnDuplicatePaymentMethod' => true));
            if ($this->palmodule_braintree_get_merchant_account_id() != NULL) {
                $this->merchant_account_id = $this->palmodule_braintree_get_merchant_account_id();
                if (!empty($this->merchant_account_id)) {
                    $payment_method_request['options']['verificationMerchantAccountId'] = $this->merchant_account_id;
                }
            }
            $this->palmodule_braintree_request('create_payment_method', $payment_method_request);
        }
    }

    public function add_payment_method() {
        do_action('palmodule_before_add_payment_method');
        if (!empty($this->return_result['result'] && 'success' == $this->return_result['result'])) {
            return $this->return_result;
        }
    }

    /**
     * Process the payment
     */
    public function process_payment($order_id) {
        $this->order_id = $order_id;
        $this->order = new WC_Order($this->order_id);
        do_action('palmodule_braintree_before_process_payment');
        $this->palmodule_braintree_payment_request();
        return $this->return_result;
    }

    public function palmodule_braintree_payment_request() {
        $this->request = array();
        $this->palmodule_braintree_payment_request_set_billing();
        $this->palmodule_braintree_payment_request_set_shipping();
        $this->palmodule_braintree_payment_request_set_payment_method_nonce();
        $this->palmodule_braintree_payment_request_set_customer();
        $this->palmodule_braintree_payment_request_set_other_params();
        if (!empty($this->request)) {
            $this->palmodule_braintree_request('payment_request', $this->request);
        }
    }

    public function palmodule_braintree_payment_request_set_billing() {
        $billing_company = version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_company : $this->order->get_billing_company();
        $billing_first_name = version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_first_name : $this->order->get_billing_first_name();
        $billing_last_name = version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_last_name : $this->order->get_billing_last_name();
        $billing_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_address_1 : $this->order->get_billing_address_1();
        $billing_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_address_2 : $this->order->get_billing_address_2();
        $billing_city = version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_city : $this->order->get_billing_city();
        $billing_postcode = version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_postcode : $this->order->get_billing_postcode();
        $billing_country = version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_country : $this->order->get_billing_country();
        $billing_state = version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_state : $this->order->get_billing_state();
        $this->request['billing'] = array(
            'firstName' => $billing_first_name,
            'lastName' => $billing_last_name,
            'company' => $billing_company,
            'streetAddress' => $billing_address_1,
            'extendedAddress' => $billing_address_2,
            'locality' => $billing_city,
            'region' => $billing_state,
            'postalCode' => $billing_postcode,
            'countryCodeAlpha2' => $billing_country,
        );
    }

    public function palmodule_braintree_payment_request_set_shipping() {
        $this->request['shipping'] = array(
            'firstName' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->shipping_first_name : $this->order->get_shipping_first_name(),
            'lastName' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->shipping_last_name : $this->order->get_shipping_last_name(),
            'company' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->shipping_company : $this->order->get_shipping_company(),
            'streetAddress' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->shipping_address_1 : $this->order->get_shipping_address_1(),
            'extendedAddress' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->shipping_address_2 : $this->order->get_shipping_address_2(),
            'locality' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->shipping_city : $this->order->get_shipping_city(),
            'region' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->shipping_state : $this->order->get_shipping_state(),
            'postalCode' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->shipping_postcode : $this->order->get_shipping_postcode(),
            'countryCodeAlpha2' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->shipping_country : $this->order->get_shipping_country(),
        );
    }

    public function palmodule_braintree_payment_request_set_payment_method_nonce() {
        $palmodule_braintree_payment_tokens = get_post_meta($this->order_id, '_palmodule_braintree_payment_tokens', true);
        if (!empty($palmodule_braintree_payment_tokens)) {
            $this->request['paymentMethodToken'] = $palmodule_braintree_payment_tokens;
        } else {
            $this->request['paymentMethodNonce'] = self::get_posted_variable('palmodule_braintree_token');
        }
    }

    public function palmodule_braintree_payment_request_set_customer() {
        if (!empty($this->braintree_customer_id)) {
            $this->request['customerId'] = $this->braintree_customer_id;
        } else {
            $this->request['customer'] = array(
                'firstName' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_first_name : $this->order->get_billing_first_name(),
                'lastName' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_last_name : $this->order->get_billing_last_name(),
                'company' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_company : $this->order->get_billing_company(),
                'phone' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_phone : $this->order->get_billing_phone(),
                'email' => version_compare(WC_VERSION, '3.0', '<') ? $this->order->billing_email : $this->order->get_billing_email(),
            );
        }
    }

    public function palmodule_braintree_payment_request_set_other_params() {
        $this->request['amount'] = number_format($this->order->get_total(), 2, '.', '');
        if (empty($this->merchant_account_id)) {
            if ($this->palmodule_braintree_get_merchant_account_id() != NULL) {
                $this->merchant_account_id = $this->palmodule_braintree_get_merchant_account_id();
            }
        }
        if (!empty($this->merchant_account_id)) {
            $this->request['merchantAccountId'] = $this->merchant_account_id;
        }
        $invoice_number = preg_replace("/[^a-zA-Z0-9]/", "", $this->order->get_order_number());
        $this->request['orderId'] = $this->invoice_prefix . $invoice_number;
        $this->request['options'] = array('submitForSettlement' => true, 'storeInVaultOnSuccess' => 'true');
        $this->request['channel'] = 'mbjtechnolabs_SP';
    }

    public function palmodule_init_braintree_lib() {
        try {
            if(!class_exists('Braintree_Configuration')) {
                require_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/php-library/braintree/lib/Braintree.php' );
            }
            Braintree_Configuration::environment($this->environment);
            Braintree_Configuration::merchantId($this->merchant_id);
            Braintree_Configuration::publicKey($this->public_key);
            Braintree_Configuration::privateKey($this->private_key);
        } catch (Exception $ex) {
            $this->add_log('Error: Unable to Load Braintree. Reason: ' . $ex->getMessage());
            WP_Error(404, 'Error: Unable to Load Braintree. Reason: ' . $ex->getMessage());
        }
    }

    public function palmodule_braintree_is_user_logged_in() {
        if (is_user_logged_in()) {
            return $this->current_user_id = get_current_user_id();
        } else {
            return false;
        }
    }

    public function palmodule_braintree_get_braintree_customer_id() {
        if ($this->palmodule_braintree_is_user_logged_in()) {
            $this->braintree_customer_id = get_user_meta($this->current_user_id, 'braintree_customer_id', true);
        }
        return $this->braintree_customer_id;
    }

    public function palmodule_braintree_save_braintree_customer_id($braintree_customer_id) {
        if ($this->palmodule_braintree_is_user_logged_in()) {
            $is_success = update_user_meta($this->current_user_id, 'braintree_customer_id', $braintree_customer_id);
            if ($is_success != false) {
                $this->braintree_customer_id = $braintree_customer_id;
                return $this->braintree_customer_id;
            }
        }
        return $this->braintree_customer_id;
    }

    public function palmodule_braintree_get_client_token() {
        try {
            if ($this->palmodule_braintree_get_braintree_customer_id() != NULL) {
                $this->braintree_customer_id = $this->palmodule_braintree_get_braintree_customer_id();
            }
            if ($this->palmodule_braintree_get_merchant_account_id() != NULL) {
                $this->merchant_account_id = $this->palmodule_braintree_get_merchant_account_id();
            }
            return $this->palmodule_braintree_generate_client_token($this->braintree_customer_id, $this->merchant_account_id);
        } catch (Exception $ex) {
            
        }
    }

    public function palmodule_braintree_generate_client_token($braintree_customer_id = NULL, $merchant_account_id = NULL) {
        $this->palmodule_init_braintree_lib();
        if (!empty($braintree_customer_id) && !empty($merchant_account_id)) {
            $clientToken = $this->palmodule_braintree_request('create_client_token', array('customerId' => $braintree_customer_id, 'merchantAccountId' => $this->merchant_account_id));
        } else if (!empty($braintree_customer_id)) {
            $clientToken = $this->palmodule_braintree_request('create_client_token', array('customerId' => $braintree_customer_id));
        } else {
            $clientToken = $this->palmodule_braintree_request('create_client_token', array());
        }
        return $clientToken;
    }

    public function palmodule_braintree_get_merchant_account_id() {
        return $this->merchant_account_id = $this->palmodule_braintree_request('get_merchant_account_id', array());
    }

    public function add_log($message) {
        if ($this->debug == 'yes') {
            if (empty($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add($this->id, $message);
        }
    }

    public function payment_scripts() {
        if (!$this->is_available()) {
            return;
        }
        wp_enqueue_script($this->id . 'js', 'https://js.braintreegateway.com/web/dropin/1.3.1/js/dropin.min.js', array(), WC_VERSION, false);
    }

    public static function get_posted_variable($variable, $default = '') {
        return ( isset($_POST[$variable]) ? $_POST[$variable] : $default );
    }
    
    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        if (!$order || !$order->get_transaction_id()) {
            return false;
        }
        $this->palmodule_init_braintree_lib();
        try {
            $transaction = Braintree_Transaction::find($order->get_transaction_id());
        } catch (Braintree_Exception_NotFound $e) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_NotFound" . $e->getMessage());
            return new WP_Error(404, $e->getMessage());
        } catch (Braintree_Exception_Authentication $e) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_Authentication: API keys are incorrect, Please double-check that you haven't accidentally tried to use your sandbox keys in production or vice-versa.");
            return new WP_Error(404, $e->getMessage());
        } catch (Braintree_Exception_Authorization $e) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_Authorization: The API key that you're using is not authorized to perform the attempted action according to the role assigned to the user who owns the API key.");
            return new WP_Error(404, $e->getMessage());
        } catch (Braintree_Exception_DownForMaintenance $e) {
            $this->add_log("Braintree_Transaction::find Braintree_Exception_DownForMaintenance: Request times out.");
            return new WP_Error(404, $e->getMessage());
        } catch (Exception $e) {
            $this->add_log($e->getMessage());
            return new WP_Error(404, $e->getMessage());
        }
        if (isset($transaction->status) && $transaction->status == 'submitted_for_settlement') {
            if ($amount == $order->get_total()) {
                try {
                    $result = Braintree_Transaction::void($order->get_transaction_id());
                    if ($result->success) {
                        $order->add_order_note(sprintf(__('Refunded %s - Transaction ID: %s', 'palmodule-paypal-payment-for-woocoomerce'), wc_price(number_format($amount, 2, '.', '')), $result->transaction->id));
                        return true;
                    } else {
                        $error = '';
                        foreach (($result->errors->deepAll()) as $error) {
                            return new WP_Error(404, 'ec_refund-error', $error->message);
                        }
                    }
                } catch (Braintree_Exception_NotFound $e) {
                    $this->add_log("Braintree_Transaction::void Braintree_Exception_NotFound: " . $e->getMessage());
                    return new WP_Error(404, $e->getMessage());
                } catch (Exception $ex) {
                    $this->add_log("Braintree_Transaction::void Exception: " . $e->getMessage());
                    return new WP_Error(404, $e->getMessage());
                }
            } else {
                return new WP_Error(404, 'braintree_refund-error', __('Oops, you cannot partially void this order. Please use the full order amount.', 'palmodule-paypal-payment-for-woocoomerce'));
            }
        } elseif (isset($transaction->status) && ($transaction->status == 'settled' || $transaction->status == 'settling')) {
            try {
                $result = Braintree_Transaction::refund($order->get_transaction_id(), $amount);
                if ($result->success) {
                    $order->add_order_note(sprintf(__('Refunded %s - Transaction ID: %s', 'palmodule-paypal-payment-for-woocoomerce'), wc_price(number_format($amount, 2, '.', '')), $result->transaction->id));
                    return true;
                } else {
                    $error = '';
                    foreach (($result->errors->deepAll()) as $error) {
                        return new WP_Error(404, 'ec_refund-error', $error->message);
                    }
                }
            } catch (Braintree_Exception_NotFound $e) {
                $this->add_log("Braintree_Transaction::refund Braintree_Exception_NotFound: " . $e->getMessage());
                return new WP_Error(404, $e->getMessage());
            } catch (Exception $ex) {
                $this->add_log("Braintree_Transaction::refund Exception: " . $e->getMessage());
                return new WP_Error(404, $e->getMessage());
            }
        } else {
            $this->add_log("Error: The transaction cannot be voided nor refunded in its current state: state = {$transaction->status}");
            return new WP_Error(404, "Error: The transaction cannot be voided nor refunded in its current state: state = {$transaction->status}");
        }
    }
}
