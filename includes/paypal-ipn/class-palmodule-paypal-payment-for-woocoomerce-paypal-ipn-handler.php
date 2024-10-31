<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles responses from PayPal IPN.
 */
class Palmodule_PayPal_Payment_For_Woocoomerce_Paypal_IPN_Handler {

    /**
     * Constructor.
     *
     * @param bool $sandbox
     * @param string $receiver_email
     */
    
    public function __construct() {
        $this->liveurl = 'https://ipnpb.paypal.com/cgi-bin/webscr';
        $this->testurl = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
        $_POST['ipn_track_id'] = '1';
        add_action('valid_paypal_ipn_request', array($this, 'valid_response'));
    }

    /**
     * Check for PayPal IPN Response.
     */
    public function check_response() {
        if (!empty($_POST) && !empty($_POST['ipn_track_id'])) {
            if (!empty($_POST) && $this->validate_ipn()) {
                $posted = wp_unslash($_POST);
                do_action('valid_paypal_ipn_request', $posted);
                exit;
            }
            wp_die('PayPal IPN Request Failure', 'PayPal IPN', array('response' => 500));
        }
    }

    /**
     * There was a valid response.
     * @param  array $posted Post data after wp_unslash
     */
    public function valid_response($posted) {
        if (!empty($posted['custom']) && ( $order = $this->get_paypal_order($posted['custom']) )) {
            $posted['payment_status'] = strtolower($posted['payment_status']);
            if (isset($posted['test_ipn']) && 1 == $posted['test_ipn'] && 'pending' == $posted['payment_status']) {
                $posted['payment_status'] = 'completed';
            }
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $this->log('Found order #' . $order_id);
            $this->log('Payment status: ' . $posted['payment_status']);
            if (method_exists($this, 'payment_status_' . $posted['payment_status'])) {
                call_user_func(array($this, 'payment_status_' . $posted['payment_status']), $order, $posted);
            }
        }
    }

    /**
     * Check PayPal IPN validity.
     */
    public function validate_ipn() {
        $this->log('Checking IPN response is valid');
        $validate_ipn = array('cmd' => '_notify-validate');
        $validate_ipn += wp_unslash($_POST);
        $params = array(
            'body' => $validate_ipn,
            'timeout' => 60,
            'httpversion' => '1.1',
            'compress' => false,
            'decompress' => false,
            'user-agent' => 'WooCommerce/' . WC()->version
        );
        
        $is_sandbox = (isset($_POST['test_ipn'])) ? 'yes' : 'no';
        if ('yes' == $is_sandbox) {
            $paypal_adr = $this->testurl;
        } else {
            $paypal_adr = $this->liveurl;
        }
        
        $response = wp_safe_remote_post($paypal_adr, $params);
        $this->log('IPN Request: ' . print_r($params, true));
        if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && strstr($response['body'], 'VERIFIED')) {
            $this->log('Received valid response from PayPal');
            return true;
        }
        $this->log('Received invalid response from PayPal');
        if (is_wp_error($response)) {
            $this->log('Error response: ' . $response->get_error_message());
        }
        return false;
    }

    /**
     * Check for a valid transaction type.
     * @param string $txn_type
     */
    public function validate_transaction_type($txn_type) {
        $accepted_types = array('cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money');
        if (!in_array(strtolower($txn_type), $accepted_types)) {
            $this->log('Aborting, Invalid type:' . $txn_type);
            exit;
        }
    }

    /**
     * Check currency from IPN matches the order.
     * @param WC_Order $order
     * @param string $currency
     */
    public function validate_currency($order, $currency) {
        $order_currency = version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency();
        if ($order_currency != $currency) {
            $this->log('Payment error: Currencies do not match (sent "' . $order_currency . '" | returned "' . $currency . '")');
            $order->update_status('on-hold', sprintf(__('Validation error: PayPal currencies do not match (code %s).', 'palmodule-paypal-payment-for-woocoomerce'), $currency));
            exit;
        }
    }

    /**
     * Check payment amount from IPN matches the order.
     * @param WC_Order $order
     * @param int $amount
     */
    public function validate_amount($order, $amount) {
        if (number_format($order->get_total(), 2, '.', '') != number_format($amount, 2, '.', '')) {
            $this->log('Payment error: Amounts do not match (gross ' . $amount . ')');
            $order->update_status('on-hold', sprintf(__('Validation error: PayPal amounts do not match (gross %s).', 'palmodule-paypal-payment-for-woocoomerce'), $amount));
            exit;
        }
    }

    /**
     * Handle a completed payment.
     * @param WC_Order $order
     * @param array $posted
     */
    public function payment_status_completed($order, $posted) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        if ($order->has_status('completed')) {
            $this->log('Aborting, Order #' . $order_id . ' is already complete.');
            exit;
        }
        $this->validate_transaction_type($posted['txn_type']);
        $this->validate_currency($order, $posted['mc_currency']);
        $this->validate_amount($order, $posted['mc_gross']);
        $this->save_paypal_meta_data($order, $posted);
        if ('completed' === $posted['payment_status']) {
            $this->payment_complete($order, (!empty($posted['txn_id']) ? wc_clean($posted['txn_id']) : ''), __('IPN payment completed', 'palmodule-paypal-payment-for-woocoomerce'));
            if (!empty($posted['mc_fee'])) {
                update_post_meta($order_id, 'PayPal Transaction Fee', wc_clean($posted['mc_fee']));
            }
        } else {
            $this->payment_on_hold($order, sprintf(__('Payment pending: %s', 'palmodule-paypal-payment-for-woocoomerce'), $posted['pending_reason']));
        }
    }

    /**
     * Handle a pending payment.
     * @param WC_Order $order
     * @param array $posted
     */
    public function payment_status_pending($order, $posted) {
        $this->payment_status_completed($order, $posted);
    }

    /**
     * Handle a failed payment.
     * @param WC_Order $order
     * @param array $posted
     */
    public function payment_status_failed($order, $posted) {
        $order->update_status('failed', sprintf(__('Payment %s via IPN.', 'palmodule-paypal-payment-for-woocoomerce'), wc_clean($posted['payment_status'])));
    }

    /**
     * Handle a denied payment.
     * @param WC_Order $order
     * @param array $posted
     */
    public function payment_status_denied($order, $posted) {
        $this->payment_status_failed($order, $posted);
    }

    /**
     * Handle an expired payment.
     * @param WC_Order $order
     * @param array $posted
     */
    public function payment_status_expired($order, $posted) {
        $this->payment_status_failed($order, $posted);
    }

    /**
     * Handle a voided payment.
     * @param WC_Order $order
     * @param array $posted
     */
    public function payment_status_voided($order, $posted) {
        $this->payment_status_failed($order, $posted);
    }

    /**
     * Handle a refunded order.
     * @param WC_Order $order
     * @param array $posted
     */
    public function payment_status_refunded($order, $posted) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        if ($order->get_total() == ( $posted['mc_gross'] * -1 )) {
            $order->add_order_note(sprintf(__('Payment %s via IPN.', 'palmodule-paypal-payment-for-woocoomerce'), strtolower($posted['payment_status'])));
            $order->update_status('refunded', sprintf(__('Payment %s via IPN.', 'palmodule-paypal-payment-for-woocoomerce'), strtolower($posted['payment_status'])));
            $this->send_ipn_email_notification(
                    sprintf(__('Payment for order %s refunded', 'palmodule-paypal-payment-for-woocoomerce'), '<a class="link" href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '">' . $order->get_order_number() . '</a>'), sprintf(__('Order #%s has been marked as refunded - PayPal reason code: %s', 'palmodule-paypal-payment-for-woocoomerce'), $order->get_order_number(), $posted['reason_code'])
            );
        }
    }

    /**
     * Handle a reveral.
     * @param WC_Order $order
     * @param array $posted
     */
    public function payment_status_reversed($order, $posted) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $order->add_order_note(sprintf(__('Payment %s via IPN.', 'palmodule-paypal-payment-for-woocoomerce'), wc_clean($posted['payment_status'])));
        $order->update_status('on-hold', sprintf(__('Payment %s via IPN.', 'palmodule-paypal-payment-for-woocoomerce'), wc_clean($posted['payment_status'])));
        $this->send_ipn_email_notification(
                sprintf(__('Payment for order %s reversed', 'palmodule-paypal-payment-for-woocoomerce'), '<a class="link" href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '">' . $order->get_order_number() . '</a>'), sprintf(__('Order #%s has been marked on-hold due to a reversal - PayPal reason code: %s', 'palmodule-paypal-payment-for-woocoomerce'), $order->get_order_number(), wc_clean($posted['reason_code']))
        );
    }

    /**
     * Handle a cancelled reveral.
     * @param WC_Order $order
     * @param array $posted
     */
    public function payment_status_canceled_reversal($order, $posted) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $order->add_order_note(sprintf(__('Payment %s via IPN.', 'palmodule-paypal-payment-for-woocoomerce'), wc_clean($posted['payment_status'])));
        $this->send_ipn_email_notification(
                sprintf(__('Reversal cancelled for order #%s', 'palmodule-paypal-payment-for-woocoomerce'), $order->get_order_number()), sprintf(__('Order #%s has had a reversal cancelled. Please check the status of payment and update the order status accordingly here: %s', 'palmodule-paypal-payment-for-woocoomerce'), $order->get_order_number(), esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')))
        );
    }

    /**
     * Save important data from the IPN to the order.
     * @param WC_Order $order
     * @param array $posted
     */
    public function save_paypal_meta_data($order, $posted) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        if (!empty($posted['payer_email'])) {
            update_post_meta($order_id, 'Payer PayPal address', wc_clean($posted['payer_email']));
        }
        if (!empty($posted['first_name'])) {
            update_post_meta($order_id, 'Payer first name', wc_clean($posted['first_name']));
        }
        if (!empty($posted['last_name'])) {
            update_post_meta($order_id, 'Payer last name', wc_clean($posted['last_name']));
        }
        if (!empty($posted['payment_type'])) {
            update_post_meta($order_id, 'Payment type', wc_clean($posted['payment_type']));
        }
    }

    /**
     * Send a notification to the user handling orders.
     * @param string $subject
     * @param string $message
     */
    public function send_ipn_email_notification($subject, $message) {
        $new_order_settings = get_option('woocommerce_new_order_settings', array());
        $mailer = WC()->mailer();
        $message = $mailer->wrap_message($subject, $message);
        $mailer->send(!empty($new_order_settings['recipient']) ? $new_order_settings['recipient'] : get_option('admin_email'), strip_tags($subject), $message);
    }

    /**
     * Get the order from the PayPal 'Custom' variable.
     * @param  string $raw_custom JSON Data passed back by PayPal
     * @return bool|WC_Order object
     */
    public function get_paypal_order($raw_custom) {
        if (( $custom = json_decode($raw_custom) ) && is_object($custom)) {
            $order_id = $custom->order_id;
            $order_key = $custom->order_key;
        } elseif (preg_match('/^a:2:{/', $raw_custom) && !preg_match('/[CO]:\+?[0-9]+:"/', $raw_custom) && ( $custom = maybe_unserialize($raw_custom) )) {
            $order_id = $custom[0];
            $order_key = $custom[1];
        } else {
            $this->log('Error: Order ID and key were not found in "custom".');
            return false;
        }
        if (!$order = wc_get_order($order_id)) {
            $order_id = wc_get_order_id_by_order_key($order_key);
            $order = wc_get_order($order_id);
        }
        $order_key_value = version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key();
        if (!$order || $order_key_value !== $order_key) {
            $this->log('Error: Order Keys do not match.');
            return false;
        }
        return $order;
    }

    /**
     * Complete order, add transaction ID and note.
     * @param  WC_Order $order
     * @param  string   $txn_id
     * @param  string   $note
     */
    public function payment_complete($order, $txn_id = '', $note = '') {
        $order->add_order_note($note);
        $order->payment_complete($txn_id);
    }

    /**
     * Hold order and add note.
     * @param  WC_Order $order
     * @param  string   $reason
     */
    public function payment_on_hold($order, $reason = '') {
        $order->update_status('on-hold', $reason);
        $order->reduce_order_stock();
        WC()->cart->empty_cart();
    }
    
    public function log($message, $level = 'info') {
        if (empty($this->log)) {
            $this->log = wc_get_logger();
        }
        $this->log->log($level, $message, array('source' => 'palmodule_paypal_ipn'));
    }
}
