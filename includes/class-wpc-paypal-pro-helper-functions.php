<?php

function wpc_paypal_pro_payments_setting_field() {
    return array(
        'wpc_paypal_pro_enabled' => array(
            'title' => __('Enable/Disable', WPC_SLUG),
            'label' => __('Enable WPC PayPal Pro', WPC_SLUG),
            'type' => 'checkbox',
            'description' => '',
            'default' => 'no'
        ),
        'wpc_paypal_pro_title' => array(
            'title' => __('Title', WPC_SLUG),
            'type' => 'text',
            'description' => __('Display the title during checkout.', WPC_SLUG),
            'default' => __('Credit card', WPC_SLUG)
        ),
        'wpc_paypal_pro_description' => array(
            'title' => __('Description', WPC_SLUG),
            'type' => 'textarea',
            'description' => __('Display the description during checkout.', WPC_SLUG),
            'default' => __('Pay with your credit card', WPC_SLUG)
        ),
        'wpc_paypal_pro_sandboxmode' => array(
            'title' => __('Sandbox Mode', WPC_SLUG),
            'label' => __('Enable PayPal Sandbox/Test Mode', WPC_SLUG),
            'type' => 'checkbox',
            'description' => __('Enables the payment gateway in Sandbox/Test mode.', WPC_SLUG),
            'default' => 'no'
        ),
        'wpc_paypal_pro_invoice_id_prefix' => array(
            'title' => __('Invoice ID Prefix', WPC_SLUG),
            'type' => 'text',
            'description' => __('Add a prefix to the invoice ID sent to PayPal.', WPC_SLUG),
        ),
        'wpc_paypal_pro_sandbox_api_username' => array(
            'title' => __('Sandbox API Username', WPC_SLUG),
            'type' => 'text',
            'description' => __('Create sandbox accounts and obtain API credentials from within your
									<a href="http://developer.paypal.com">PayPal developer account</a>.', WPC_SLUG),
            'default' => ''
        ),
        'wpc_paypal_pro_sandbox_api_password' => array(
            'title' => __('Sandbox API Password', WPC_SLUG),
            'type' => 'password',
            'default' => ''
        ),
        'wpc_paypal_pro_sandbox_api_signature' => array(
            'title' => __('Sandbox API Signature', WPC_SLUG),
            'type' => 'password',
            'default' => ''
        ),
        'wpc_paypal_pro_live_api_username' => array(
            'title' => __('Live API Username', WPC_SLUG),
            'type' => 'text',
            'description' => __('Get your live account API credentials from your PayPal account profile under the API Access section <br />or by using
									<a target="_blank" href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run">this tool</a>.', WPC_SLUG),
            'default' => ''
        ),
        'wpc_paypal_pro_live_api_password' => array(
            'title' => __('Live API Password', WPC_SLUG),
            'type' => 'password',
            'default' => ''
        ),
        'wpc_paypal_pro_live_api_signature' => array(
            'title' => __('Live API Signature', WPC_SLUG),
            'type' => 'password',
            'default' => ''
        ),
        'wpc_paypal_pro_payment_action' => array(
            'title' => __('Payment Action', WPC_SLUG),
            'label' => __('Whether to process as a Sale or Authorization.', WPC_SLUG),
            'type' => 'select',
            'options' => array(
                'Sale' => 'Sale',
                'Authorization' => 'Authorization',
            ),
            'default' => 'Sale'
        ),
        
        'debug' => array(
            'title' => __('Debug Log', WPC_SLUG),
            'type' => 'checkbox',
            'label' => __('Enable logging', WPC_SLUG),
            'default' => 'no',
            'description' => sprintf(__('Log PayPal events, inside <code>%s</code>', WPC_SLUG), wc_get_log_file_path(WPC_SLUG))
        ),
    );
}

function wpc_paypal_pro_payments_is_card_type($posted) {
    $validcard = array(
        "Visa",
        "MasterCard",
        "Discover",
        "Amex"
    );


    return $posted AND in_array($posted['wpc_cardtype'], $validcard);
}

function wpc_paypal_pro_payments_is_card_expire($posted) {
    $now = time();
    $thisYear = (int) date('Y', $now);
    $thisMonth = (int) date('m', $now);
    $month = $posted['wpc_expdatemonth'];
    $year = $posted['wpc_expdateyear'];

    if (is_numeric($year) && is_numeric($month)) {
        $thisDate = mktime(0, 0, 0, $thisMonth, 1, $thisYear);
        $expireDate = mktime(0, 0, 0, $month, 1, $year);

        return $thisDate <= $expireDate;
    }

    return false;
}

function wpc_paypal_pro_payments_is_card_cvv($posted) {
    $length = strlen($posted['wpc_ccvnumber']);
    return is_numeric($posted['wpc_ccvnumber']) AND $length > 2 AND $length < 5;
}

function wpc_paypal_pro_payments_is_card_vallid($posted) {
    if (!is_numeric($posted['wpc_credircard']))
        return false;

    $number = preg_replace('/[^0-9]+/', '', $posted['wpc_credircard']);
    $strlen = strlen($number);
    $sum = 0;

    if ($strlen < 13)
        return false;

    for ($i = 0; $i < $strlen; $i++) {
        $digit = substr($number, $strlen - $i - 1, 1);
        if ($i % 2 == 1) {
            $sub_total = $digit * 2;
            if ($sub_total > 9) {
                $sub_total = 1 + ($sub_total - 10);
            }
        } else {
            $sub_total = $digit;
        }
        $sum += $sub_total;
    }

    if ($sum > 0 AND $sum % 10 == 0) {
        return true;
    }
    return false;
}