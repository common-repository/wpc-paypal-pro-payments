<?php

/**
 * @since      1.0.0
 * @package    WPC_PayPal_Pro_Payments_Gateway
 * @subpackage WPC_PayPal_Pro_Payments_Gateway/includes
 * @author     WPCodelibrary <support@wpcodelibrary.com>
 */
class WPC_PayPal_Pro_Payments_Gateway extends WC_Payment_Gateway {

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    public function __construct() {
        try {
            $this->id = 'wpc_paypal_pro';
            $this->api_version = '119';
            $this->method_title = __('WPC PayPal Pro Payments ', WPC_SLUG);
            $this->method_description = __('WPC PayPal Pro Payments allows you to accept credit cards on your website.', WPC_SLUG);
            $this->has_fields = true;
            $this->liveurl = 'https://api-3t.paypal.com/nvp';
            $this->sanboxurl = 'https://api-3t.sandbox.paypal.com/nvp';
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('wpc_paypal_pro_title');
            $this->description = $this->get_option('wpc_paypal_pro_description');
            $this->enabled = $this->get_option('wpc_paypal_pro_enabled');
            $this->testmode = $this->get_option('wpc_paypal_pro_sandboxmode', "no") === "yes" ? true : false;
            if ($this->testmode) {
                $this->payment_url = "https://api-3t.sandbox.paypal.com/nvp";
                $this->api_username = ($this->get_option('wpc_paypal_pro_sandbox_api_username')) ? trim($this->get_option('wpc_paypal_pro_sandbox_api_username')) : '';
                $this->api_password = ($this->get_option('wpc_paypal_pro_sandbox_api_password')) ? trim($this->get_option('wpc_paypal_pro_sandbox_api_password')) : '';
                $this->api_signature = ($this->get_option('wpc_paypal_pro_sandbox_api_signature')) ? trim($this->get_option('wpc_paypal_pro_sandbox_api_signature')) : '';
            } else {
                $this->payment_url = "https://api-3t.paypal.com/nvp";
                $this->api_username = ($this->get_option('wpc_paypal_pro_live_api_username')) ? trim($this->get_option('wpc_paypal_pro_live_api_username')) : '';
                $this->api_password = ($this->get_option('wpc_paypal_pro_live_api_password')) ? trim($this->get_option('wpc_paypal_pro_live_api_password')) : '';
                $this->api_signature = ($this->get_option('wpc_paypal_pro_live_api_signature')) ? trim($this->get_option('wpc_paypal_pro_live_api_signature')) : '';
            }
            $this->wpc_paypal_pro_invoice_id_prefix = $this->get_option('wpc_paypal_pro_invoice_id_prefix');
            $this->debug = 'yes' === $this->get_option('debug', 'no');
            $this->payment_action = $this->get_option('wpc_paypal_pro_payment_action', 'Sale');
            $this->post_data = array();
            $this->supports = array(
                'products'
            );
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        } catch (Exception $ex) {
            wc_add_notice('<strong>' . __('Payment error', WPC_SLUG) . '</strong>: ' . $ex->getMessage(), 'error');
            return;
        }
    }

    public function init_form_fields() {
        return $this->form_fields = wpc_paypal_pro_payments_setting_field();
    }

    public function is_available() {
        if ($this->enabled === "yes") {
            if (!is_ssl() && !$this->testmode) {
                return false;
            }
        }
        return true;
    }

    public function log($message) {
        if ($this->debug) {
            if (!isset($this->log)) {
                $this->log = new WC_Logger();
            }
            $this->log->add('wpc-paypal-pro-payments', $message);
        }
    }

    public function payment_fields() {
        global $woocommerce;
        if ($this->description) {
            echo '<p>' . wp_kses_post($this->description) . '</p>';
        }
        $wpc_credircard = isset($_REQUEST['wpc_credircard']) ? esc_attr($_REQUEST['wpc_credircard']) : '';
        ?>
        <p class="form-row validate-required">
            <label><?php _e('Credit Card Number', WPC_SLUG); ?> <span class="required">*</span></label>
            <input class="input-text" type="text" size="19" maxlength="19" name="wpc_credircard" value="<?php echo $wpc_credircard; ?>" />
        </p>         
        <p class="form-row form-row-first">
            <label><?php _e('Card Type', WPC_SLUG); ?> <span class="required">*</span></label>
            <select name="wpc_cardtype" id="wpc_cardtype" >
                <option value="Visa" selected="selected">Visa</option>
                <option value="MasterCard">MasterCard</option>
                <option value="Discover">Discover</option>
                <option value="Amex">American Express</option>
            </select>
        </p>       
        <div class="clear"></div>
        <p class="form-row form-row-first">
            <label><?php _e('Expiration Date', WPC_SLUG); ?> <span class="required">*</span></label>
            <select name="wpc_expdatemonth" id="wpc_expdatemonth">
                <option value=1>01</option>
                <option value=2>02</option>
                <option value=3>03</option>
                <option value=4>04</option>
                <option value=5>05</option>
                <option value=6>06</option>
                <option value=7>07</option>
                <option value=8>08</option>
                <option value=9>09</option>
                <option value=10>10</option>
                <option value=11>11</option>
                <option value=12>12</option>
            </select>
            <select name="wpc_expdateyear" id="wpc_expdateyear">
                <?php
                $today = (int) date('Y', time());
                for ($i = 0; $i < 8; $i++) {
                    ?>
                    <option value="<?php echo $today; ?>"><?php echo $today; ?></option>
                    <?php
                    $today++;
                }
                ?>
            </select>            
        </p>

        <p class="form-row form-row-first validate-required">
            <label><?php _e('Card Verification Number (CVV)', WPC_SLUG); ?> <span class="required">*</span></label>
            <input class="input-text" type="text" size="4" maxlength="4" name="wpc_ccvnumber" value="" />
        </p>
        <div class="clear"></div>
    <?php
    }

    public function validate_fields() {
        try {
            $is_card_valid = wpc_paypal_pro_payments_is_card_vallid($_POST);
            $is_card_type = wpc_paypal_pro_payments_is_card_type($_POST);
            $is_card_expire = wpc_paypal_pro_payments_is_card_expire($_POST);
            $is_card_cvv = wpc_paypal_pro_payments_is_card_cvv($_POST);
            if (!$is_card_valid) {
                wc_add_notice(__('Credit card number you entered is invalid.', WPC_SLUG), 'error');
            }


            if (!$is_card_type) {
                wc_add_notice(__('Card type is not valid.', WPC_SLUG), 'error');
            }
            if (!$is_card_expire) {
                wc_add_notice(__('Card expiration date is not valid.', WPC_SLUG), 'error');
            }
            if (!$is_card_cvv) {
                wc_add_notice(__('Card verification number is not valid.', WPC_SLUG), 'error');
            }
            return true;
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return false;
        }
    }

    public function process_payment($order_id) {
        global $woocommerce;
        $order = wc_get_order($order_id);
        $this->wpc_paypal_pro_payments_log_write('Processing order:', $order_id);
        return $this->wpc_paypal_pro_payments_do_payment($order);
    }

    public function wpc_paypal_pro_payments_do_payment($order, $card) {
        try {
            $this->post_data = array(
                'VERSION' => $this->api_version,
                'SIGNATURE' => $this->api_signature,
                'USER' => $this->api_username,
                'PWD' => $this->api_password,
                'METHOD' => 'DoDirectPayment',
                'PAYMENTACTION' => $this->wpc_paypal_pro_payment_action,
                'AMT' => number_format($order->get_total(), 2, '.', ','),
                'INVNUM' => $this->invoice_prefix . str_replace("#", "", $order->get_order_number()),
                'IPADDRESS' => $_SERVER['REMOTE_ADDR'],
                'CURRENCYCODE' => $order->get_order_currency(),
                'CREDITCARDTYPE' => $_POST['wpc_cardtype'],
                'ACCT' => $_POST['wpc_credircard'],
                'EXPDATE' => sprintf('%s%s', $_POST['wpc_expdatemonth'], $_POST['wpc_expdateyear']),
                'CVV2' => $_POST['wpc_ccvnumber'],
                'EMAIL' => $order->billing_email,
                'FIRSTNAME' => $order->billing_first_name,
                'LASTNAME' => $order->billing_last_name,
                'STREET' => trim($order->billing_address_1 . ' ' . $order->billing_address_2),
                'CITY' => $order->billing_city,
                'STATE' => $order->billing_state,
                'ZIP' => $order->billing_postcode,
                'COUNTRYCODE' => $order->billing_country,
                'SHIPTONAME' => $order->shipping_first_name . ' ' . $order->shipping_last_name,
                'SHIPTOSTREET' => $order->shipping_address_1,
                'SHIPTOSTREET2' => $order->shipping_address_2,
                'SHIPTOCITY' => $order->shipping_city,
                'SHIPTOSTATE' => $order->shipping_state,
                'SHIPTOCOUNTRYCODE' => $order->shipping_country,
                'SHIPTOZIP' => $order->shipping_postcode,
                'BUTTONSOURCE' => 'WPCodelibrary_SP_EC_PRO'
            );
            $this->wpc_paypal_pro_payments_log_write('Do payment request ', $this->post_data);
            $response = $this->wpc_paypal_pro_payments_make_payment($order);
            if (is_wp_error($response)) {
                $this->wpc_paypal_pro_payments_log_write('Error ', $response->get_error_message());
                throw new Exception(__('Somehing went wrong connecting to the payment gateway.', WPC_SLUG));
            }

            if (empty($response['body'])) {
                $this->wpc_paypal_pro_payments_log_write('Empty response', $response->get_error_message());
                throw new Exception(__('Empty response.', WPC_SLUG));
            }
            parse_str($response['body'], $parsed_response);
            $this->wpc_paypal_pro_payments_log_write('Response:', $parsed_response);
            return $this->wpc_paypal_pro_payments_update_notes($parsed_response, $order);
        } catch (Exception $e) {
            wc_add_notice('<strong>' . __('Payment error', WPC_SLUG) . '</strong>: ' . $e->getMessage(), 'error');
            return;
        }
    }

    public function wpc_paypal_pro_payments_make_payment($order) {
        return wp_safe_remote_post($this->payment_url, array(
            'method' => 'POST',
            'headers' => array(
                'PAYPAL-NVP' => 'Y'
            ),
            'body' => $this->post_data,
            'timeout' => 60,
            'user-agent' => WPC_SLUG,
            'httpversion' => '1.1'
        ));
    }

    public function wpc_paypal_pro_payments_update_notes($parsed_response, $order) {

        switch (strtolower($parsed_response['ACK'])) {
            case 'success':
            case 'successwithwarning':
                $transaction_id = (!empty($parsed_response['TRANSACTIONID']) ) ? wc_clean($parsed_response['TRANSACTIONID']) : '';
                $correlation_id = (!empty($parsed_response['CORRELATIONID']) ) ? wc_clean($parsed_response['CORRELATIONID']) : '';
                $order->add_order_note(sprintf(__('Payment completed (Transaction ID: %s, Correlation ID: %s)', WPC_SLUG), $transaction_id, $correlation_id));
                $order->payment_complete($transaction_id);
                WC()->cart->empty_cart();
                if (method_exists($order, 'get_checkout_order_received_url')) {
                    $redirect = $order->get_checkout_order_received_url();
                } else {
                    $redirect = add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(get_option('woocommerce_thanks_page_id'))));
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
                } elseif ($this->testmode) {
                    $error_message = print_r($parsed_response, true);
                }
                $order->update_status('failed', sprintf(__('Payment Failed (Correlation ID: %s). Payment was rejected due to an error: ', WPC_SLUG), $parsed_response['CORRELATIONID']) . '(' . $parsed_response['L_ERRORCODE0'] . ') ' . '"' . $error_message . '"');
                throw new Exception($error_message);
                break;
        }
    }

    public function wpc_paypal_pro_payments_log_write($text = null, $message) {
        if ($this->debug) {
            if (empty($this->log)) {
                $this->log = new WC_Logger();
            }
            if (is_array($message) && count($message) > 0) {
                $message = $this->wpc_secure_detail($message);
            }
            $this->log->add('wpc_paypal_pro', $text . ' ' . print_r($message, true));
        }
    }

    public function wpc_secure_detail($message) {
        foreach ($message as $key => $value) {
            if ($key == "USER" || $key == "PWD" || $key == "SIGNATURE" || $key == "ACCT" || $key == "EXPDATE" || $key == "CVV2") {
                $str_length = strlen($value);
                $ponter_data = "";
                for ($i = 0; $i <= $str_length; $i++) {
                    $ponter_data .= '*';
                }
                $message[$key] = $ponter_data;
            }
        }

        return $message;
    }

}