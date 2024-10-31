<?php

if (!defined('ABSPATH')) {
    exit;
}

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payment;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\ItemList;
use PayPal\Api\RedirectUrls;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\Refund;
use PayPal\Api\Sale;


class WC_Gateway_Palmodule_PayPal_Express_Checkout_API_Handler {

    public $rest_client_id;
    public $rest_secret_id;
    public $sandbox = false;
    public $payer;
    public $order_item;
    public $order_cart_data;
    public $itemlist;
    public $details;
    public $amount;
    public $transaction;
    public $payment;
    public $gateway_calculation;
    public $payment_method;
    public $redirecturls;
    public $invoice_prefix;

    public function getAuth() {
        try {
            if (!class_exists('Palmodule_PayPal_Payment_For_Woocoomerce_Calculations')) {
                require_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/class-palmodule-paypal-payment-for-woocoomerce-calculations.php' );
            }
            $this->gateway_calculation = new Palmodule_PayPal_Payment_For_Woocoomerce_Calculations();
            $this->mode = $this->sandbox == true ? 'SANDBOX' : 'LIVE';
            $auth = new ApiContext(new OAuthTokenCredential($this->rest_client_id, $this->rest_secret_id));
            $auth->setConfig(array('mode' => $this->mode, 'http.headers.PayPal-Partner-Attribution-Id' => 'mbjtechnolabs_SP', 'log.LogEnabled' => true, 'log.LogLevel' => 'DEBUG', 'log.FileName' => wc_get_log_file_path('palmodule_paypal_express'), 'cache.enabled' => true, 'cache.FileName' => wc_get_log_file_path('palmodule_paypal_rest_cache')));
            return $auth;
        } catch (Exception $ex) {
            WC_Gateway_Palmodule_PayPal_Rest::log($ex->getMessage());
        }
    }

    public function palmodule_set_item_list() {
        try {
            $this->order_cart_data = $this->gateway_calculation->cart_calculation();
            $this->itemlist = new ItemList();
            foreach ($this->order_cart_data['order_items'] as $item) {
                $this->item = new Item();
                $this->item->setName($item['name']);
                $this->item->setCurrency(get_woocommerce_currency());
                $this->item->setQuantity($item['qty']);
                $this->item->setPrice($item['amt']);
                $this->itemlist->addItem($this->item);
            }
        } catch (Exception $ex) {
            WC_Gateway_Palmodule_PayPal_Rest::log($ex->getMessage());
        }
    }

    public function set_redirect_url() {
        $this->redirecturls = new RedirectUrls();
        $this->redirecturls->setReturnUrl(esc_url_raw(add_query_arg('palmodule_express_checkout_action', 'return_url', WC()->api_request_url( 'WC_Gateway_Palmodule_PayPal_Express_Checkout' ))));
        $this->redirecturls->setCancelUrl(esc_url_raw(add_query_arg('palmodule_express_checkout_action', 'cancel_url', WC()->api_request_url( 'WC_Gateway_Palmodule_PayPal_Express_Checkout' ))));
    }

    public function palmodule_set_detail_amount() {
        try {
            $this->details = new Details();
            if (!empty($this->order_cart_data['shippingamt'])) {
                $this->details->setShipping($this->order_cart_data['shippingamt']);
            }
            if (!empty($this->order_cart_data['taxamt'])) {
                $this->details->setTax($this->order_cart_data['taxamt']);
            }
            if (!empty($this->order_cart_data['itemamt'])) {
                $this->details->setSubtotal($this->order_cart_data['itemamt']);
            }
            $this->amount = new Amount();
            $this->amount->setCurrency(get_woocommerce_currency());
            $this->amount->setTotal(WC()->cart->total);
            $this->amount->setDetails($this->details);
        } catch (Exception $ex) {
            WC_Gateway_Palmodule_PayPal_Rest::log($ex->getMessage());
        }
    }

    public function palmodule_set_transaction($order) {
        try {
            $order_key = version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key();
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $this->transaction = new Transaction();
            $this->transaction->setAmount($this->amount)
                    ->setItemList($this->itemlist)
                    ->setDescription("Payment description")
                    ->setInvoiceNumber(uniqid())
                    ->setNotifyUrl(apply_filters('palmodule_palmodule_paypal_express_url', add_query_arg('palmodule_ipn_action', 'ipn', WC()->api_request_url( 'Palmodule_PayPal_Payment_For_Woocoomerce_Paypal_IPN_Handler' ))))
                    ->setCustom(json_encode(array('order_id' => $order_id, 'order_key' => $order_key)));
        } catch (Exception $ex) {
            WC_Gateway_Palmodule_PayPal_Rest::log($ex->getMessage());
        }
    }

    public function palmodule_cart_set_transaction() {
        try {
            $this->transaction = new Transaction();
            $this->transaction->setAmount($this->amount)
                    ->setNotifyUrl( apply_filters('palmodule_paypal_express_ipn_url', add_query_arg('palmodule_ipn_action', 'ipn', WC()->api_request_url( 'Palmodule_PayPal_Payment_For_Woocoomerce_Paypal_IPN_Handler' ))))
                    ->setItemList($this->itemlist)
                    ->setDescription("Payment description");
        } catch (Exception $ex) {
            WC_Gateway_Palmodule_PayPal_Rest::log($ex->getMessage());
        }
    }

    public function palmodule_set_payer() {
        $this->payer = new Payer();
        $this->payer->setPaymentMethod("paypal");
        if( !empty($_REQUEST['is_palmodule_cc']) &&  $_REQUEST['is_palmodule_cc'] == 'yes') {
            $this->payer->setExternalSelectedFundingInstrumentType('CREDIT');
        }
        
    }

    public function palmodule_set_payment() {
        try {
            $this->payment = new Payment();
            $this->payment->setIntent("sale")
                    ->setPayer($this->payer)
                    ->setRedirectUrls($this->redirecturls)
                    ->setTransactions(array($this->transaction));
        } catch (Exception $ex) {
            WC_Gateway_Palmodule_PayPal_Rest::log($ex->getMessage());
        }
    }

    public function checkout_payment_url() {
        if (!empty($_POST['paymentID']) && !empty($_POST['payerID'])) {
            $paymentID = $_POST['paymentID'];
            $payerID = $_POST['payerID'];
            palmodule_set_session('paymentID', $paymentID);
            palmodule_set_session('payerID', $payerID);

            wp_redirect(wc_get_checkout_url());
            exit();
        }
    }

    public function palmodule_update_payments($order) {
        $this->getAuth();
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        $order_key = version_compare(WC_VERSION, '3.0', '<') ? $order->order_key : $order->get_order_key();
        $this->order_cart_data = $this->gateway_calculation->order_calculation($order_id);
        $payment = new Payment();
        $payment->setId(palmodule_get_session('paymentID'));
        $patchReplace = new \PayPal\Api\Patch();
        $patchReplace->setOp('replace')
                ->setPath('/transactions/0/amount')
                ->setValue(json_decode('{
                    "total": "' . palmodule_number_format($order->get_total(), $order) . '",
                    "currency": "' . get_woocommerce_currency() . '",
                    "details": {
                        "subtotal": "' . $this->order_cart_data['itemamt'] . '",
                        "shipping": "' . $this->order_cart_data['shippingamt'] . '",
                        "tax":"' . $this->order_cart_data['taxamt'] . '"
                    }
                }'));

        $patchRequest = new \PayPal\Api\PatchRequest();
        $invoice_number = preg_replace("/[^a-zA-Z0-9]/", "", $order->get_order_number());
        $patchAdd_custom = new \PayPal\Api\Patch();
        $patchAdd_custom->setOp('add')->setPath('/transactions/0/custom')->setValue(json_encode(array('order_id' => $order_id, 'order_key' => $order_key)));
        $shipping_first_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_first_name : $order->get_shipping_first_name();
        $shipping_last_name = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_last_name : $order->get_shipping_last_name();
        $shipping_address_1 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_1 : $order->get_shipping_address_1();
        $shipping_address_2 = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_address_2 : $order->get_shipping_address_2();
        $shipping_city = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_city : $order->get_shipping_city();
        $shipping_state = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_state : $order->get_shipping_state();
        $shipping_postcode = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_postcode : $order->get_shipping_postcode();
        $shipping_country = version_compare(WC_VERSION, '3.0', '<') ? $order->shipping_country : $order->get_shipping_country();
        $item_lists = array();
        foreach ($this->order_cart_data['order_items'] as $key => $item) {
            $item_lists[$key]['name'] = $item['name'];
            $item_lists[$key]['currency'] = version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency();
            $item_lists[$key]['quantity'] = $item['qty'];
            $item_lists[$key]['price'] = $item['amt'];
        }
        $patchupdateitem = new \PayPal\Api\Patch();
        $patchupdateitem->setOp('replace')
                ->setPath('/transactions/0/item_list/items')
                ->setValue(json_decode(json_encode($item_lists)));
        if (!empty($shipping_country)) {
            $patchAdd = new \PayPal\Api\Patch();
            $patchAdd->setOp('add')
                    ->setPath('/transactions/0/item_list/shipping_address')
                    ->setValue(json_decode('{
                    "recipient_name": "' . $shipping_first_name . ' ' . $shipping_last_name . '",
                    "line1": "' . $shipping_address_1 . '",
                    "line2": "' . $shipping_address_2 . '",
                    "city": "' . $shipping_city . '",
                    "state": "' . $shipping_state . '",
                    "postal_code": "' . $shipping_postcode . '",
                    "country_code": "' . $shipping_country . '"
                }'));
            $patchAddone = new \PayPal\Api\Patch();
            $patchAddone->setOp('add')->setPath('/transactions/0/invoice_number')->setValue($this->invoice_prefix . $invoice_number);
            $patchRequest->setPatches(array($patchAdd, $patchReplace, $patchAddone, $patchAdd_custom, $patchupdateitem));
        } else {
            $patchAdd = new \PayPal\Api\Patch();
            $patchAdd->setOp('add')->setPath('/transactions/0/invoice_number')->setValue($this->invoice_prefix . $invoice_number);
            $patchRequest->setPatches(array($patchAdd, $patchReplace, $patchAdd_custom, $patchupdateitem));
        }
        try {
            $result = $payment->update($patchRequest, $this->getAuth());
            if ($result == true) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $ex) {
            WC_Gateway_Palmodule_PayPal_Rest::log($ex->getMessage());
            return false;
        }
    }

    public function palmodule_execute_payments($order) {
        try {
            $return = $this->palmodule_update_payments($order);
            if ($return == false) {
                return false;
            }
            $paymentID = palmodule_get_session('paymentID');
            $payerID = palmodule_get_session('payerID');
            if (!empty($paymentID) && !empty($payerID)) {
                $execution = new PaymentExecution();
                $execution->setPayerId($payerID);
                $payment = Payment::get($paymentID, $this->getAuth());
                $payment->execute($execution, $this->getAuth());
                $transactions = $payment->getTransactions();
                $relatedResources = $transactions[0]->getRelatedResources();
                $sale = $relatedResources[0]->getSale();
                $saleId = $sale->getId();
                if ($payment->state == "approved") {
                    $response = array('id' => $saleId, 'state' => $payment->state);
                    return $response;
                } else {
                    return array(
                        'result' => 'fail',
                        'redirect' => ''
                    );
                   
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function palmodule_return_url() {
        try {
            $this->getAuth();
            if (!empty($_REQUEST['paymentId']) && !empty($_REQUEST['PayerID']) && !empty($_REQUEST['token'])) {
                $paymentID = $_REQUEST['paymentId'];
                $payerID = $_REQUEST['PayerID'];
                $token = $_REQUEST['token'];
                palmodule_set_session('paymentID', $paymentID);
                palmodule_set_session('payerID', $payerID);
                palmodule_set_session('token', $token);
                $payment = Payment::get($paymentID, $this->getAuth());
                $payer = $payment->getPayer();
                $this->palmodule_get_shipping_address($payer);
                wp_redirect(wc_get_checkout_url());
                exit();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function palmodule_get_shipping_address($payer) {
        $shipping_address = array();
        $PayerInfo = $payer->getPayerInfo();
        $shipping_address['first_name'] = $PayerInfo->getFirstName();
        $shipping_address['last_name'] = $PayerInfo->getLastName();
        $shipping_address['email'] = $PayerInfo->getEmail();
        $ship_address = $PayerInfo->getShippingAddress();
        $shipping_address['recipient_name'] = $ship_address->recipient_name;
        $shipping_address['address_1'] = $ship_address->line1;
        $shipping_address['city'] = $ship_address->city;
        $shipping_address['state'] = $ship_address->state;
        $shipping_address['postcode'] = $ship_address->postal_code;
        $shipping_address['country'] = $ship_address->country_code;
        palmodule_set_session('palmodule_express_checkout_shipping_address', $shipping_address);
    }

    public function palmodule_cancel_url() {
        try {
            $cart_page_url = wc_get_page_permalink('cart');
            wp_safe_redirect($cart_page_url);
        } catch (Exception $ex) {
            
        }
    }

    public function create_payment_url($bool = true) {
         
        $this->getAuth();
        try {
            if (!WC()->cart->is_empty()) {
                $this->palmodule_set_item_list();
                $this->set_redirect_url();
                $this->palmodule_set_detail_amount();
                $this->palmodule_cart_set_transaction();
                $this->palmodule_set_payer();
                $this->palmodule_set_payment();
                $this->payment->create($this->getAuth());
                if ($this->payment->state == "created" && $bool == true) {
                    $response = array('paymentID' => $this->payment->id);
                    echo wp_send_json($response);
                    exit();
                } else {
                    return $this->payment->getApprovalLink();
                }
            }
        } catch (Exception $ex) {
            WC_Gateway_Palmodule_PayPal_Rest::log($ex->getMessage());
        }
    }

    public function create_refund_request($order_id, $amount, $reason = '') {
        $this->getAuth();
        $order = wc_get_order($order_id);
        $sale = Sale::get($order->get_transaction_id(), $this->getAuth());
        $this->amount = new Amount();
        $this->amount->setCurrency(version_compare(WC_VERSION, '3.0', '<') ? $order->get_order_currency() : $order->get_currency());
        $this->amount->setTotal(palmodule_number_format($amount, $order));
        $refund = new Refund();
        $refund->setAmount($this->amount);
        try {
            $refundedSale = $sale->refund($refund, $this->getAuth());
            if ($refundedSale->state == 'completed') {
                $order->add_order_note('Refund Transaction ID:' . $refundedSale->getId());
                if (isset($reason) && !empty($reason)) {
                    $order->add_order_note('Reason for Refund :' . $reason);
                }
                $max_remaining_refund = wc_format_decimal($order->get_total() - $order->get_total_refunded());
                if (!$max_remaining_refund > 0) {
                    $order->update_status('refunded');
                }
                return true;
            }
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            $error_data = json_decode($ex->getData());
            if (is_object($error_data) && !empty($error_data)) {
                $error_message = ($error_data->message) ? $error_data->message : $error_data->information_link;
                return new WP_Error('paypal_credit_card_rest_refund-error', $error_message);
            } else {
                return new WP_Error('paypal_credit_card_rest_refund-error', $ex->getData());
            }
        } catch (Exception $ex) {
            return new WP_Error('paypal_credit_card_rest_refund-error', $ex->getMessage());
        }
    }

    public function palmodule_add_payment_method($card_data) {


        if ($this->card->getState() == 'ok') {
            $result = 'success';
        } else {
            $result = 'fail';
        }
        return array(
            'result' => $result,
            'redirect' => wc_get_account_endpoint_url('payment-methods')
        );
    }

    public function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }

    public function is_renewal($order_id) {
        return ( (function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_renewal($order_id) )) || $this->used_payment_token );
    }

    public function palmodule_return_checkout_order_received_url($order) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        if ($this->is_renewal($order_id)) {
            return;
        }
        WC()->cart->empty_cart();
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_order_received_url(),
        );
    }

}
