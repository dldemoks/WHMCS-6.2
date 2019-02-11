<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function payeer_config() {
	
	$configarray = array(
		'FriendlyName' => array(
			'Type' => 'System',
			'Value' => 'Payeer'
		),
		'payeer_url' => array(
			'FriendlyName' => 'Merchant URL',
			'Type' => 'text',
			'Size' => '100',
			'Default' => 'https://payeer.com/merchant/',
			'Description' => 'The URL for the payment'
		),
		'payeer_shop' => array(
			'FriendlyName' => 'ID store',
			'Type' => 'text',
			'Size' => '50',
			'Description' => 'The store identifier registered in Payeer'
		),
		'payeer_secret_key' => array(
			'FriendlyName' => 'Secret key',
			'Type' => 'text',
			'Size' => '100',
			'Description' => 'The secret key of merchant'
		),
		'payeer_logfile' => array(
			'FriendlyName' => 'Path to the log file for payments (for example, /payeer_orders.log)',
			'Type' => 'text',
			'Size' => '100',
			'Description' => 'If path is not specified, the log is not written'
		),
		'payeer_ipfilter' => array(
			'FriendlyName' => 'IP - filter',
			'Type' => 'text',
			'Size' => '100',
			'Description' => 'The list of trusted ip addresses, you can specify the mask'
		),
		'payeer_email_error' => array(
			'FriendlyName' => 'Email for errors',
			'Type' => 'text',
			'Size' => '100',
			'Description' => 'Email to send payment errors'
		)
	);

	return $configarray;
}

function payeer_link($params) {
	
	global $_LANG;

	$m_url = $params['payeer_url'];
	$m_shop = $params['payeer_shop'];
	$m_orderid = $params['invoiceid'];
	$m_amount = number_format($params['amount'], 2, '.', '');
	$m_curr = ($params['currency'] == 'RUR') ? 'RUB' : $params['currency'];
	$m_desc = base64_encode($params['description']);
	$m_key = $params['payeer_secret_key'];
	$m_lang = $params['clientdetails']['language'] == 'russian' ? 'ru' : 'en';
	
	$arHash = array(
		$m_shop,
		$m_orderid,
		$m_amount,
		$m_curr,
		$m_desc,
		$m_key
	);
	$sign = strtoupper(hash('sha256', implode(':', $arHash)));

	$code = '<form id = "form_payment_payeer" method="GET" action="' . $m_url . '">
		<input type="hidden" name="m_shop" value="' . $m_shop . '">
		<input type="hidden" name="m_orderid" value="' . $m_orderid . '">
		<input type="hidden" name="m_amount" value="' . $m_amount . '">
		<input type="hidden" name="m_curr" value="' . $m_curr . '">
		<input type="hidden" name="m_desc" value="' . $m_desc . '">
		<input type="hidden" name="m_sign" value="' . $sign . '">
		<input type="hidden" name="lang" value="' . $m_lang . '">
		<input type="submit" name="m_process" value="' . $_LANG['invoicespaynow'] . '" /></form>';

	return $code;
}