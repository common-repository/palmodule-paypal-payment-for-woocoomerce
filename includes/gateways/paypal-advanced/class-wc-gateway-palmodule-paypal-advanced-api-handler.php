<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Palmodule_PayPal_Advanced_API_Handler {

    public $gateway_settings;

    public function get_token($order, $post_data, $force_new_token = false) {
        $pre_wc_30 = version_compare(WC_VERSION, '3.0', '<');
        $order_id = $pre_wc_30 ? $order->id : $order->get_id();
        if (!$force_new_token && get_post_meta($order_id, '_SECURETOKENHASH', true) == md5(json_encode($post_data))) {
            return array(
                'SECURETOKEN' => get_post_meta($order_id, '_SECURETOKEN', true),
                'SECURETOKENID' => get_post_meta($order_id, '_SECURETOKENID', true)
            );
        }
        $post_data['SECURETOKENID'] = uniqid() . md5($pre_wc_30 ? $order->order_key : $order->get_order_key());
        $post_data['CREATESECURETOKEN'] = 'Y';
        $post_data['SILENTTRAN'] = 'TRUE';
        $post_data['ERRORURL'] = WC()->api_request_url('WC_Gateway_Palmodule_PayPal_Advanced');
        $post_data['RETURNURL'] = WC()->api_request_url('WC_Gateway_Palmodule_PayPal_Advanced');
        $post_data['URLMETHOD'] = 'POST';
        $response = wp_remote_post($this->gateway_settings->testmode ? $this->gateway_settings->testurl : $this->gateway_settings->liveurl, array(
            'method' => 'POST',
            'body' => urldecode(http_build_query(apply_filters('palmodule-paypal-payment-for-woocoomerce_payflow_request', $post_data, $order), null, '&')),
            'timeout' => 70,
            'user-agent' => 'WooCommerce',
            'httpversion' => '1.1'
        ));
        if (is_wp_error($response)) {
            wc_add_notice(__('There was a problem connecting to the payment gateway.', 'palmodule-paypal-payment-for-woocoomerce'));
            return false;
        }
        if (empty($response['body'])) {
            wc_add_notice(__('Empty Paypal response.', 'palmodule-paypal-payment-for-woocoomerce'));
            return false;
        }
        parse_str($response['body'], $parsed_response);
        if (isset($parsed_response['RESULT']) && in_array($parsed_response['RESULT'], array(160, 161, 162))) {
            return $this->get_token($order, $post_data, $force_new_token);
        } elseif (isset($parsed_response['RESULT']) && $parsed_response['RESULT'] == 0 && !empty($parsed_response['SECURETOKEN'])) {
            update_post_meta($order_id, '_SECURETOKEN', $parsed_response['SECURETOKEN']);
            update_post_meta($order_id, '_SECURETOKENID', $parsed_response['SECURETOKENID']);
            update_post_meta($order_id, '_SECURETOKENHASH', md5(json_encode($post_data)));
            return array(
                'SECURETOKEN' => $parsed_response['SECURETOKEN'],
                'SECURETOKENID' => $parsed_response['SECURETOKENID']
            );
        } else {
            $order->update_status('failed', __('PayPal Pro (Payflow) token generation failed: ', 'palmodule-paypal-payment-for-woocoomerce') . '(' . $parsed_response['RESULT'] . ') ' . '"' . $parsed_response['RESPMSG'] . '"');
            wc_add_notice(__('Payment error:', 'palmodule-paypal-payment-for-woocoomerce') . ' ' . $parsed_response['RESPMSG'], 'error');
            return false;
        }
    }

    protected function _get_post_data($order) {
        $post_data = array();
        $post_data['USER'] = $this->gateway_settings->paypal_user;
        $post_data['VENDOR'] = $this->gateway_settings->paypal_vendor;
        $post_data['PARTNER'] = $this->gateway_settings->paypal_partner;
        $post_data['PWD'] = $this->gateway_settings->paypal_password;
        $post_data['TENDER'] = 'C';
        $post_data['TRXTYPE'] = $this->gateway_settings->paymentaction;
        $post_data['AMT'] = $order->get_total();
        $post_data['CURRENCY'] = ( version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency() );
        $post_data['CUSTIP'] = $this->get_user_ip();
        $post_data['EMAIL'] = ( version_compare(WC_VERSION, '3.0', '<') ? $order->billing_email : $order->get_billing_email() );
        $post_data['INVNUM'] = $this->gateway_settings->invoice_prefix . $order->get_order_number();
        $post_data['BUTTONSOURCE'] = 'mbjtechnolabs_SP';
        $post_data['CUSTOM'] = apply_filters( 'palmodule_paypal_advanced_custom_parameter', json_encode( array( 'order_id' => version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id(), 'order_key' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->order_key : $order->get_order_key() ) ) , $order );
        $post_data['NOTIFYURL'] = apply_filters('palmodule_paypal_advanced_notifyurl_parameter', add_query_arg('palmodule_ipn_action', 'ipn', WC()->api_request_url( 'Palmodule_PayPal_Payment_For_Woocoomerce_Paypal_IPN_Handler' )));
        if ($this->gateway_settings->soft_descriptor) {
            $post_data['MERCHDESCR'] = $this->gateway_settings->soft_descriptor;
        }
        $item_loop = 0;
        if (sizeof($order->get_items()) > 0) {
            $ITEMAMT = 0;
            foreach ($order->get_items() as $item) {
                $_product = $order->get_product_from_item($item);
                if ($item['qty']) {
                    $post_data['L_NAME' . $item_loop] = $item['name'];
                    $post_data['L_COST' . $item_loop] = $order->get_item_total($item, true);
                    $post_data['L_QTY' . $item_loop] = $item['qty'];
                    if ($_product->get_sku()) {
                        $post_data['L_SKU' . $item_loop] = $_product->get_sku();
                    }
                    $ITEMAMT += $order->get_item_total($item, true) * $item['qty'];
                    $item_loop++;
                }
            }
            if (( $order->get_total_shipping() + $order->get_shipping_tax() ) > 0) {
                $post_data['L_NAME' . $item_loop] = 'Shipping';
                $post_data['L_DESC' . $item_loop] = 'Shipping and shipping taxes';
                $post_data['L_COST' . $item_loop] = $order->get_total_shipping() + $order->get_shipping_tax();
                $post_data['L_QTY' . $item_loop] = 1;
                $ITEMAMT += $order->get_total_shipping() + $order->get_shipping_tax();
                $item_loop++;
            }
            if ($order->get_total_discount(false) > 0) {
                $post_data['L_NAME' . $item_loop] = 'Order Discount';
                $post_data['L_DESC' . $item_loop] = 'Discounts including tax';
                $post_data['L_COST' . $item_loop] = '-' . $order->get_total_discount(false);
                $post_data['L_QTY' . $item_loop] = 1;
                $item_loop++;
            }
            $ITEMAMT = round($ITEMAMT, 2);
            if (absint($order->get_total() * 100) !== absint($ITEMAMT * 100)) {
                $post_data['L_NAME' . $item_loop] = 'Rounding amendment';
                $post_data['L_DESC' . $item_loop] = 'Correction if rounding is off (this can happen with tax inclusive prices)';
                $post_data['L_COST' . $item_loop] = ( absint($order->get_total() * 100) - absint($ITEMAMT * 100) ) / 100;
                $post_data['L_QTY' . $item_loop] = 1;
            }
            $post_data['ITEMAMT'] = $order->get_total();
        }
        $pre_wc_30 = version_compare(WC_VERSION, '3.0', '<');
        $post_data['ORDERDESC'] = 'Order ' . $order->get_order_number() . ' on ' . wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $post_data['FIRSTNAME'] = $pre_wc_30 ? $order->billing_first_name : $order->get_billing_first_name();
        $post_data['LASTNAME'] = $pre_wc_30 ? $order->billing_last_name : $order->get_billing_last_name();
        $post_data['STREET'] = $pre_wc_30 ? ( $order->billing_address_1 . ' ' . $order->billing_address_2 ) : ( $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() );
        $post_data['CITY'] = $pre_wc_30 ? $order->billing_city : $order->get_billing_city();
        $post_data['STATE'] = $pre_wc_30 ? $order->billing_state : $order->get_billing_state();
        $post_data['COUNTRY'] = $pre_wc_30 ? $order->billing_country : $order->get_billing_country();
        $post_data['ZIP'] = $pre_wc_30 ? $order->billing_postcode : $order->get_billing_postcode();
        if ($pre_wc_30 ? $order->shipping_address_1 : $order->get_shipping_address_1()) {
            $post_data['SHIPTOFIRSTNAME'] = $pre_wc_30 ? $order->shipping_first_name : $order->get_shipping_first_name();
            $post_data['SHIPTOLASTNAME'] = $pre_wc_30 ? $order->shipping_last_name : $order->get_shipping_last_name();
            $post_data['SHIPTOSTREET'] = $pre_wc_30 ? $order->shipping_address_1 : $order->get_shipping_address_1();
            $post_data['SHIPTOCITY'] = $pre_wc_30 ? $order->shipping_city : $order->get_shipping_city();
            $post_data['SHIPTOSTATE'] = $pre_wc_30 ? $order->shipping_state : $order->get_shipping_state();
            $post_data['SHIPTOCOUNTRY'] = $pre_wc_30 ? $order->shipping_country : $order->get_shipping_country();
            $post_data['SHIPTOZIP'] = $pre_wc_30 ? $order->shipping_postcode : $order->get_shipping_postcode();
        }
        return $post_data;
    }

    public function get_transaction_details($transaction_id = 0) {
        $url = $this->gateway_settings->testmode ? $this->gateway_settings->testurl : $this->gateway_settings->liveurl;
        $post_data = array();
        $post_data['USER'] = $this->gateway_settings->paypal_user;
        $post_data['VENDOR'] = $this->gateway_settings->paypal_vendor;
        $post_data['PARTNER'] = $this->gateway_settings->paypal_partner;
        $post_data['PWD'] = $this->gateway_settings->paypal_password;
        $post_data['TRXTYPE'] = 'I';
        $post_data['ORIGID'] = $transaction_id;

        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'body' => urldecode(http_build_query(apply_filters('palmodule-paypal-payment-for-woocoomerce_payflow_transaction_details_request', $post_data, null, '&'))),
            'timeout' => 70,
            'user-agent' => 'WooCommerce',
            'httpversion' => '1.1',
        ));

        if (is_wp_error($response)) {
            WC_Gateway_Palmodule_PayPal_Advanced::log('Error ' . print_r($response->get_error_message(), true));

            throw new Exception(__('There was a problem connecting to the payment gateway.', 'palmodule-paypal-payment-for-woocoomerce'));
        }

        parse_str($response['body'], $parsed_response);
        
        WC_Gateway_Palmodule_PayPal_Advanced::log('transaction_details ' . print_r($parsed_response, true));

        if (isset($parsed_response['RESULT']) && '0' === $parsed_response['RESULT']) {
            return $parsed_response;
        }

        return false;
    }

    public function request_process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        $url = $this->gateway_settings->testmode ? $this->gateway_settings->testurl : $this->gateway_settings->liveurl;
        if (!$order || !$order->get_transaction_id() || !$this->gateway_settings->paypal_user || !$this->gateway_settings->paypal_vendor || !$this->gateway_settings->paypal_password) {
            return false;
        }
        $details = $this->get_transaction_details($order->get_transaction_id());
        if ($details && strtolower($details['TRANSSTATE']) === '3') {
            $order->add_order_note(__('This order cannot be refunded due to an authorized only transaction.  Please use cancel instead.', 'palmodule-paypal-payment-for-woocoomerce'));
            WC_Gateway_Palmodule_PayPal_Advanced::log('Refund order # ' . $order_id . ': authorized only transactions need to use cancel/void instead.');
            throw new Exception(__('This order cannot be refunded due to an authorized only transaction.  Please use cancel instead.', 'palmodule-paypal-payment-for-woocoomerce'));
        }
        $post_data = array();
        $post_data['USER'] = $this->gateway_settings->paypal_user;
        $post_data['VENDOR'] = $this->gateway_settings->paypal_vendor;
        $post_data['PARTNER'] = $this->gateway_settings->paypal_partner;
        $post_data['PWD'] = $this->gateway_settings->paypal_password;
        $post_data['TRXTYPE'] = 'C';
        $post_data['ORIGID'] = $order->get_transaction_id();
        if (!is_null($amount)) {
            $post_data['AMT'] = number_format($amount, 2, '.', '');
            $post_data['CURRENCY'] = ( version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency() );
        }
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }
            $post_data['COMMENT1'] = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'body' => urldecode(http_build_query(apply_filters('palmodule-paypal-payment-for-woocoomerce_payflow_refund_request', $post_data, null, '&'))),
            'timeout' => 70,
            'user-agent' => 'WooCommerce',
            'httpversion' => '1.1',
        ));
        parse_str($response['body'], $parsed_response);
        if (is_wp_error($response)) {
            WC_Gateway_Palmodule_PayPal_Advanced::log('Error ' . print_r($response->get_error_message(), true));
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'palmodule-paypal-payment-for-woocoomerce'));
        }
        if (!isset($parsed_response['RESULT'])) {
            throw new Exception(__('Unexpected response from PayPal.', 'palmodule-paypal-payment-for-woocoomerce'));
        }
        if ('0' !== $parsed_response['RESULT']) {
            WC_Gateway_Palmodule_PayPal_Advanced::log('Parsed Response (refund) ' . print_r($parsed_response, true));
        } else {
            $order->add_order_note(sprintf(__('Refunded %1$s - PNREF: %2$s', 'palmodule-paypal-payment-for-woocoomerce'), wc_price(number_format($amount, 2, '.', '')), $parsed_response['PNREF']));
            return true;
        }
        return false;
    }

    public function request_return_handler() {
        @ob_clean();
        header('HTTP/1.1 200 OK');
        $result = isset($_POST['RESULT']) ? absint($_POST['RESULT']) : null;
        $INVOICE = $_POST['INVOICE'];
        $INVOICE = str_replace($this->gateway_settings->invoice_prefix, '', $INVOICE);
        $order_id = isset($INVOICE) ? absint(ltrim($INVOICE, '#')) : 0;
        if (is_null($result) || empty($order_id)) {
            echo "Invalid request.";
            exit;
        }
        $order = new WC_Order($order_id);
        switch ($result) {
            case 0 :
            case 127 :
                $txn_id = (!empty($_POST['PNREF']) ) ? wc_clean($_POST['PNREF']) : '';
                $details = $this->get_transaction_details($txn_id);
                if ($details && strtolower($details['TRANSSTATE']) === '3') {
                    update_post_meta(version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id(), '_paypalpro_charge_captured', 'no');
                    add_post_meta(version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id(), '_transaction_id', $txn_id, true);
                    $order->update_status('on-hold', sprintf(__('PayPal Pro (PayFlow) charge authorized (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'palmodule-paypal-payment-for-woocoomerce'), $txn_id));
                    if (version_compare(WC_VERSION, '3.0', '<')) {
                        $order->reduce_order_stock();
                    } else {
                        wc_reduce_stock_levels($order->get_id());
                    }
                } else {
                    $order->add_order_note(sprintf(__('PayPal Pro (Payflow) payment completed (PNREF: %s)', 'palmodule-paypal-payment-for-woocoomerce'), $txn_id));
                    $order->payment_complete($txn_id);
                }
                WC()->cart->empty_cart();
                $redirect = $order->get_checkout_order_received_url();
                break;
            case 126 :
                $order->add_order_note($_POST['RESPMSG']);
                $order->add_order_note($_POST['PREFPSMSG']);
                $order->update_status('on-hold', __('The payment was flagged by a fraud filter. Please check your PayPal Manager account to review and accept or deny the payment and then mark this order "processing" or "cancelled".', 'palmodule-paypal-payment-for-woocoomerce'));
                WC()->cart->empty_cart();
                $redirect = $order->get_checkout_order_received_url();
                break;
            default :
                $order->update_status('failed', $_POST['RESPMSG']);
                $redirect = $order->get_checkout_payment_url(true);
                $redirect = add_query_arg('wc_error', urlencode(wp_kses_post($_POST['RESPMSG'])), $redirect);
                if (is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes') {
                    $redirect = str_replace('http:', 'https:', $redirect);
                }
                break;
        }
        wp_redirect($redirect);
        exit;
    }

    public function request_receipt_page($order_id) {
        wp_enqueue_script('jquery-payment');
        $order = new WC_Order($order_id);
        $url = $this->gateway_settings->testmode ? 'https://pilot-payflowlink.paypal.com' : 'https://payflowlink.paypal.com';
        $post_data = $this->_get_post_data($order);
        $token = $this->get_token($order, $post_data);
        if (!$token) {
            wc_print_notices();
            return;
        }
        echo wpautop(__('Enter your payment details below and click "Confirm and pay" to securely pay for your order.', 'palmodule-paypal-payment-for-woocoomerce'));
        ?>
        <form method="POST" action="<?php echo $url; ?>">
            <div id="payment">
                <label style="padding:10px 0 0 10px;display:block;"><?php echo $this->gateway_settings->title . ' ' . '<div style="vertical-align:middle;display:inline-block;margin:2px 0 0 .5em;">' . $this->gateway_settings->get_icon() . '</div>'; ?></label>
                <div class="payment_box">
                    <p><?php echo $this->gateway_settings->description . ( $this->gateway_settings->testmode ? ' ' . __('TEST/SANDBOX MODE ENABLED. In test mode, you can use the card number 4111111111111111 with any CVC and a valid expiration date.', 'palmodule-paypal-payment-for-woocoomerce') : '' ); ?></p>
                    <fieldset id="paypal_pro_payflow-cc-form">
                        <p class="form-row form-row-wide">
                            <label for="paypal_pro_payflow-card-number"><?php _e('Card Number ', 'palmodule-paypal-payment-for-woocoomerce'); ?><span class="required">*</span></label>
                            <input type="text" id="paypal_pro_payflow-card-number" class="input-text wc-credit-card-form-card-number" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="CARDNUM" />
                        </p>
                        <p class="form-row form-row-first">
                            <label for="paypal_pro_payflow-card-expiry"><?php _e('Expiry (MM/YY) ', 'palmodule-paypal-payment-for-woocoomerce'); ?><span class="required">*</span></label>
                            <input type="text" id="paypal_pro_payflow-card-expiry" class="input-text wc-credit-card-form-card-expiry" autocomplete="off" placeholder="MM / YY" name="EXPDATE" />
                        </p>
                        <p class="form-row form-row-last">
                            <label for="paypal_pro_payflow-card-cvc"><?php _e('Card Code ', 'palmodule-paypal-payment-for-woocoomerce'); ?><span class="required">*</span></label>
                            <input type="text" id="paypal_pro_payflow-card-cvc" class="input-text wc-credit-card-form-card-cvc" autocomplete="off" placeholder="CVC" name="CVV2" />
                        </p>
                        <input type="hidden" name="SECURETOKEN" value="<?php echo esc_attr($token['SECURETOKEN']); ?>" />
                        <input type="hidden" name="SECURETOKENID" value="<?php echo esc_attr($token['SECURETOKENID']); ?>" />
                        <input type="hidden" name="SILENTTRAN" value="TRUE" />
                    </fieldset>
                </div>
                <input type="submit" value="<?php _e('Confirm and pay', 'palmodule-paypal-payment-for-woocoomerce'); ?>" class="submit buy button" style="float:right;"/>
            </div>
            <script type="text/javascript">
                jQuery(function ($) {
                    $('.wc-credit-card-form-card-number').payment('formatCardNumber');
                    $('.wc-credit-card-form-card-expiry').payment('formatCardExpiry');
                    $('.wc-credit-card-form-card-cvc').payment('formatCardCVC');
                });
            </script>
        </form>
        <?php
    }

    public function get_user_ip() {
        return WC_Geolocation::get_ip_address();
    }

}
