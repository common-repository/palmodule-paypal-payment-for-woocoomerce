<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Palmodule_PayPal_Pro_API_Handler {

    public $gateway_settings;

    public function request_do_payment($order, $card) {
        $pre_wc_30 = version_compare(WC_VERSION, '3.0', '<');
        try {
            $post_data = array(
                'VERSION' => $this->gateway_settings->api_version,
                'SIGNATURE' => $this->gateway_settings->api_signature,
                'USER' => $this->gateway_settings->api_username,
                'PWD' => $this->gateway_settings->api_password,
                'METHOD' => 'DoDirectPayment',
                'PAYMENTACTION' => $this->gateway_settings->paymentaction,
                'IPADDRESS' => $this->get_user_ip(),
                'AMT' => number_format($order->get_total(), 2, '.', ','),
                'INVNUM' => $this->gateway_settings->invoice_prefix . $order->get_order_number(),
                'CURRENCYCODE' => $pre_wc_30 ? $order->get_order_currency() : $order->get_currency(),
                'CREDITCARDTYPE' => $card->type,
                'ACCT' => $card->number,
                'EXPDATE' => $card->exp_month . $card->exp_year,
                'CVV2' => $card->cvc,
                'EMAIL' => $pre_wc_30 ? $order->billing_email : $order->get_billing_email(),
                'FIRSTNAME' => $pre_wc_30 ? $order->billing_first_name : $order->get_billing_first_name(),
                'LASTNAME' => $pre_wc_30 ? $order->billing_last_name : $order->get_billing_last_name(),
                'STREET' => $pre_wc_30 ? trim($order->billing_address_1 . ' ' . $order->billing_address_2) : trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2()),
                'CITY' => $pre_wc_30 ? $order->billing_city : $order->get_billing_city(),
                'STATE' => $pre_wc_30 ? $order->billing_state : $order->get_billing_state(),
                'ZIP' => $pre_wc_30 ? $order->billing_postcode : $order->get_billing_postcode(),
                'COUNTRYCODE' => $pre_wc_30 ? $order->billing_country : $order->get_billing_country(),
                'SHIPTONAME' => $pre_wc_30 ? ( $order->shipping_first_name . ' ' . $order->shipping_last_name ) : ( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() ),
                'SHIPTOSTREET' => $pre_wc_30 ? $order->shipping_address_1 : $order->get_shipping_address_1(),
                'SHIPTOSTREET2' => $pre_wc_30 ? $order->shipping_address_2 : $order->get_shipping_address_2(),
                'SHIPTOCITY' => $pre_wc_30 ? $order->shipping_city : $order->get_shipping_city(),
                'SHIPTOSTATE' => $pre_wc_30 ? $order->shipping_state : $order->get_shipping_state(),
                'SHIPTOCOUNTRYCODE' => $pre_wc_30 ? $order->shipping_country : $order->get_shipping_country(),
                'SHIPTOZIP' => $pre_wc_30 ? $order->shipping_postcode : $order->get_shipping_postcode(),
                'CUSTOM' => apply_filters( 'palmodule_paypal_pro_custom_parameter', json_encode( array( 'order_id' => version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id(), 'order_key' => version_compare( WC_VERSION, '3.0', '<' ) ? $order->order_key : $order->get_order_key() ) ) , $order ),
                'NOTIFYURL' => apply_filters('palmodule_paypal_pro_notifyurl_parameter', add_query_arg('palmodule_ipn_action', 'ipn', WC()->api_request_url( 'Palmodule_PayPal_Payment_For_Woocoomerce_Paypal_IPN_Handler' ))),
                'BUTTONSOURCE' => 'mbjtechnolabs_SP',
            );
            if ($this->gateway_settings->soft_descriptor) {
                $post_data['SOFTDESCRIPTOR'] = $this->gateway_settings->soft_descriptor;
            }
            if ($this->gateway_settings->send_items) {
                $item_loop = 0;
                if (sizeof($order->get_items()) > 0) {
                    $ITEMAMT = 0;
                    $fee_total = 0;
                    foreach ($order->get_items() as $item) {
                        $_product = $order->get_product_from_item($item);
                        if ($item['qty']) {
                            $item_name = $item['name'];
                            if ($pre_wc_30) {
                                $item_meta = new WC_Order_Item_Meta($item);
                                if ($formatted_meta = $item_meta->display(true, true)) {
                                    $item_name .= ' ( ' . $formatted_meta . ' )';
                                }
                            } else {
                                $item_meta = new WC_Order_Item_Product($item);
                                if ($formatted_meta = $item_meta->get_formatted_meta_data()) {
                                    foreach ($formatted_meta as $meta_key => $meta) {
                                        $item_name .= ' ( ' . $meta->display_key . ': ' . $meta->value . ' )';
                                    }
                                }
                            }
                            $post_data['L_NUMBER' . $item_loop] = $item_loop;
                            $post_data['L_NAME' . $item_loop] = $item_name;
                            $post_data['L_AMT' . $item_loop] = $order->get_item_subtotal($item, false);
                            $post_data['L_QTY' . $item_loop] = $item['qty'];
                            $ITEMAMT += $order->get_item_total($item, true) * $item['qty'];
                            $item_loop++;
                        }
                    }
                    foreach ($order->get_fees() as $fee) {
                        $post_data['L_NUMBER' . $item_loop] = $item_loop;
                        $post_data['L_NAME' . $item_loop] = trim(substr($fee['name'], 0, 127));
                        $post_data['L_AMT' . $item_loop] = $fee['line_total'];
                        $post_data['L_QTY' . $item_loop] = 1;
                        $ITEMAMT += $fee['line_total'];
                        $fee_total += $fee['line_total'];
                        $item_loop++;
                    }
                    if (( $order->get_total_shipping() + $order->get_shipping_tax() ) > 0) {
                        $post_data['L_NUMBER' . $item_loop] = $item_loop;
                        $post_data['L_NAME' . $item_loop] = 'Shipping';
                        $post_data['L_AMT' . $item_loop] = round($order->get_total_shipping() + $order->get_shipping_tax(), 2);
                        $post_data['L_QTY' . $item_loop] = 1;
                        $ITEMAMT += round($order->get_total_shipping() + $order->get_shipping_tax(), 2);
                        $item_loop++;
                    }
                    if ($order->get_total_discount() > 0) {
                        $post_data['L_NUMBER' . $item_loop] = $item_loop;
                        $post_data['L_NAME' . $item_loop] = 'Order Discount';
                        $post_data['L_AMT' . $item_loop] = '-' . round($order->get_total_discount(), 2);
                        $post_data['L_QTY' . $item_loop] = 1;
                        $item_loop++;
                    }
                    $ITEMAMT = round($ITEMAMT, 2);
                    if (absint($order->get_total() * 100) !== absint($ITEMAMT * 100)) {
                        $post_data['L_NUMBER' . $item_loop] = $item_loop;
                        $post_data['L_NAME' . $item_loop] = 'Rounding amendment';
                        $post_data['L_AMT' . $item_loop] = ( absint($order->get_total() * 100) - absint($ITEMAMT * 100) ) / 100;
                        $post_data['L_QTY' . $item_loop] = 1;
                    }
                    $post_data['ITEMAMT'] = round(( $order->get_subtotal() + $order->get_total_shipping() + $fee_total ) - $order->get_total_discount(), 2);
                    $post_data['TAXAMT'] = round($order->get_total_tax(), 2);
                }
            }
            if ($this->gateway_settings->debug) {
                $log = $post_data;
                $log['ACCT'] = '****';
                $log['CVV2'] = '****';
                WC_Gateway_Palmodule_PayPal_Pro::log('Do payment request ' . print_r($log, true));
            }
            $response = wp_safe_remote_post($this->gateway_settings->testmode ? $this->gateway_settings->testurl : $this->gateway_settings->liveurl, array(
                'method' => 'POST',
                'headers' => array(
                    'PAYPAL-NVP' => 'Y',
                ),
                'body' => apply_filters('palmodule-paypal-payment-for-woocoomerce_request', $post_data, $order),
                'timeout' => 70,
                'user-agent' => 'WooCommerce',
                'httpversion' => '1.1',
            ));
            if (is_wp_error($response)) {
                WC_Gateway_Palmodule_PayPal_Pro::log('Error ' . print_r($response->get_error_message(), true));
                throw new Exception(__('There was a problem connecting to the payment gateway.', 'palmodule-paypal-payment-for-woocoomerce'));
            }
            WC_Gateway_Palmodule_PayPal_Pro::log('Response ' . print_r($response['body'], true));
            if (empty($response['body'])) {
                WC_Gateway_Palmodule_PayPal_Pro::log('Empty response!');
                throw new Exception(__('Empty Paypal response.', 'palmodule-paypal-payment-for-woocoomerce'));
            }
            parse_str($response['body'], $parsed_response);
            WC_Gateway_Palmodule_PayPal_Pro::log('Parsed Response ' . print_r($parsed_response, true));

            if (!isset($parsed_response['ACK'])) {
                throw new Exception(__('Unexpected response from PayPal.', 'palmodule-paypal-payment-for-woocoomerce'));
            }
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            switch (strtolower($parsed_response['ACK'])) {
                case 'success':
                case 'successwithwarning':
                    $txn_id = (!empty($parsed_response['TRANSACTIONID']) ) ? wc_clean($parsed_response['TRANSACTIONID']) : '';
                    $correlation_id = (!empty($parsed_response['CORRELATIONID']) ) ? wc_clean($parsed_response['CORRELATIONID']) : '';
                    $details = $this->get_transaction_details($txn_id);
                    if ($details && strtolower($details['PAYMENTSTATUS']) === 'pending' && strtolower($details['PENDINGREASON']) === 'authorization') {
                        update_post_meta($order_id, '_paypalpro_charge_captured', 'no');
                        update_post_meta($order_id, '_transaction_id', $txn_id);
                        $order->update_status('on-hold', sprintf(__('PayPal Pro charge authorized (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'palmodule-paypal-payment-for-woocoomerce'), $txn_id));
                        if (version_compare(WC_VERSION, '3.0', '<')) {
                            $order->reduce_order_stock();
                        } else {
                            wc_reduce_stock_levels($order_id);
                        }
                    } else {
                        $order->add_order_note(sprintf(__('PayPal Pro payment completed (Transaction ID: %s, Correlation ID: %s)', 'palmodule-paypal-payment-for-woocoomerce'), $txn_id, $correlation_id));
                        $order->payment_complete($txn_id);
                    }
                    WC()->cart->empty_cart();
                    if (method_exists($order, 'get_checkout_order_received_url')) {
                        $redirect = $order->get_checkout_order_received_url();
                    } else {
                        $redirect = add_query_arg('key', version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key(), add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
                    }
                    return array(
                        'result' => 'success',
                        'redirect' => $redirect
                    );
                    break;
                case 'failure':
                default:
                    if (!empty($parsed_response['L_LONGMESSAGE0'])) {
                        $error_message = $parsed_response['L_LONGMESSAGE0'];
                    } elseif (!empty($parsed_response['L_SHORTMESSAGE0'])) {
                        $error_message = $parsed_response['L_SHORTMESSAGE0'];
                    } elseif (!empty($parsed_response['L_SEVERITYCODE0'])) {
                        $error_message = $parsed_response['L_SEVERITYCODE0'];
                    } elseif ($this->gateway_settings->testmode) {
                        $error_message = print_r($parsed_response, true);
                    }
                    $order->update_status('failed', sprintf(__('PayPal Pro payment failed (Correlation ID: %s). Payment was rejected due to an error: ', 'palmodule-paypal-payment-for-woocoomerce'), $parsed_response['CORRELATIONID']) . '(' . $parsed_response['L_ERRORCODE0'] . ') ' . '"' . $error_message . '"');
                    throw new Exception($error_message);
                    break;
            }
        } catch (Exception $e) {
            wc_add_notice('<strong>' . __('Payment error', 'palmodule-paypal-payment-for-woocoomerce') . '</strong>: ' . $e->getMessage(), 'error');
            return;
        }
    }

    public function get_transaction_details($transaction_id = 0) {
        $url = $this->gateway_settings->testmode ? $this->gateway_settings->testurl : $this->gateway_settings->liveurl;
        $post_data = array(
            'VERSION' => $this->gateway_settings->api_version,
            'SIGNATURE' => $this->gateway_settings->api_signature,
            'USER' => $this->gateway_settings->api_username,
            'PWD' => $this->gateway_settings->api_password,
            'METHOD' => 'GetTransactionDetails',
            'TRANSACTIONID' => $transaction_id
        );
        $response = wp_safe_remote_post($url, array(
            'method' => 'POST',
            'headers' => array(
                'PAYPAL-NVP' => 'Y'
            ),
            'body' => $post_data,
            'timeout' => 70,
            'user-agent' => 'WooCommerce',
            'httpversion' => '1.1'
        ));
        if (is_wp_error($response)) {
            WC_Gateway_Palmodule_PayPal_Pro::log('Error ' . print_r($response->get_error_message(), true));
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'palmodule-paypal-payment-for-woocoomerce'));
        }
        parse_str($response['body'], $parsed_response);
        if (!isset($parsed_response['ACK'])) {
            return false;
        }
        switch (strtolower($parsed_response['ACK'])) {
            case 'success':
            case 'successwithwarning':
                return $parsed_response;
                break;
        }
        return false;
    }

    public function request_process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        if (!$order || !$order->get_transaction_id() || !$this->gateway_settings->api_username || !$this->gateway_settings->api_password || !$this->gateway_settings->api_signature) {
            return false;
        }
        $details = $this->get_transaction_details($order->get_transaction_id());
        if ($details && strtolower($details['PENDINGREASON']) === 'authorization') {
            $order->add_order_note(__('This order cannot be refunded due to an authorized only transaction.  Please use cancel instead.', 'palmodule-paypal-payment-for-woocoomerce'));
            WC_Gateway_Palmodule_PayPal_Pro::log('Refund order # ' . absint($order_id) . ': authorized only transactions need to use cancel/void instead.');
            throw new Exception(__('This order cannot be refunded due to an authorized only transaction.  Please use cancel instead.', 'palmodule-paypal-payment-for-woocoomerce'));
        }
        $post_data = array(
            'VERSION' => $this->gateway_settings->api_version,
            'SIGNATURE' => $this->gateway_settings->api_signature,
            'USER' => $this->gateway_settings->api_username,
            'PWD' => $this->gateway_settings->api_password,
            'METHOD' => 'RefundTransaction',
            'TRANSACTIONID' => $order->get_transaction_id(),
            'REFUNDTYPE' => is_null($amount) ? 'Full' : 'Partial',
        );
        if (!is_null($amount)) {
            $post_data['AMT'] = number_format($amount, 2, '.', '');
            $post_data['CURRENCYCODE'] = ( version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency() );
        }
        if ($reason) {
            if (255 < strlen($reason)) {
                $reason = substr($reason, 0, 252) . '...';
            }
            $post_data['NOTE'] = html_entity_decode($reason, ENT_NOQUOTES, 'UTF-8');
        }
        $response = wp_safe_remote_post($this->gateway_settings->testmode ? $this->gateway_settings->testurl : $this->gateway_settings->liveurl, array(
            'method' => 'POST',
            'headers' => array('PAYPAL-NVP' => 'Y'),
            'body' => $post_data,
            'timeout' => 70,
            'user-agent' => 'WooCommerce',
            'httpversion' => '1.1'
        ));
        if (is_wp_error($response)) {
            WC_Gateway_Palmodule_PayPal_Pro::log('Error ' . print_r($response->get_error_message(), true));
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'palmodule-paypal-payment-for-woocoomerce'));
        }
        parse_str($response['body'], $parsed_response);
        if (!isset($parsed_response['ACK'])) {
            throw new Exception(__('Unexpected response from PayPal.', 'palmodule-paypal-payment-for-woocoomerce'));
        }
        switch (strtolower($parsed_response['ACK'])) {
            case 'success':
            case 'successwithwarning':
                $order->add_order_note(sprintf(__('Refunded %1$s - Refund ID: %2$s', 'palmodule-paypal-payment-for-woocoomerce'), $parsed_response['GROSSREFUNDAMT'], $parsed_response['REFUNDTRANSACTIONID']));
                return true;
            default:
                WC_Gateway_Palmodule_PayPal_Pro::log('Parsed Response (refund) ' . print_r($parsed_response, true));
                break;
        }
        return false;
    }

    public function get_user_ip() {
        return WC_Geolocation::get_ip_address();
    }

}
