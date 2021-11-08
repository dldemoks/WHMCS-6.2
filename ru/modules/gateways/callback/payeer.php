<?php

require_once __DIR__ . '/../../../init.php';

$whmcs->load_function('gateway');
$whmcs->load_function('invoice');

$gatewayModuleName = basename(__FILE__, '.php');
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die('Module Not Activated');
}

if (isset($_POST['m_operation_id']) && isset($_POST['m_sign'])) {
	
	$err = false;
	$message = '';
	
	// запись логов
	
	$log_text = "--------------------------------------------------------\n".
		"operation id       " . $_POST['m_operation_id'] . "\n".
		"operation ps       " . $_POST['m_operation_ps'] . "\n".
		"operation date     " . $_POST['m_operation_date'] . "\n".
		"operation pay date " . $_POST['m_operation_pay_date'] . "\n".
		"shop               " . $_POST['m_shop'] . "\n".
		"order id           " . $_POST['m_orderid'] . "\n".
		"amount             " . $_POST['m_amount'] . "\n".
		"currency           " . $_POST['m_curr'] . "\n".
		"description        " . base64_decode($_POST['m_desc']) . "\n".
		"status             " . $_POST['m_status'] . "\n".
		"sign               " . $_POST['m_sign'] . "\n\n";

	$log_file = $gatewayParams['payeer_logfile'];
	
	if (!empty($log_file)) {
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . $log_file, $log_text, FILE_APPEND);
	}
	
	$order_id = preg_replace('/[^a-zA-Z0-9_-]/', '', substr($_POST['m_orderid'], 0, 32));
	checkCbInvoiceID($order_id, $gatewayParams['name']);
	
	// проверка цифровой подписи и ip

	$sign_hash = strtoupper(hash('sha256', implode(":", array(
		$_POST['m_operation_id'],
		$_POST['m_operation_ps'],
		$_POST['m_operation_date'],
		$_POST['m_operation_pay_date'],
		$_POST['m_shop'],
		$_POST['m_orderid'],
		$_POST['m_amount'],
		$_POST['m_curr'],
		$_POST['m_desc'],
		$_POST['m_status'],
		$gatewayParams['payeer_secret_key']
	))));
	
	$valid_ip = true;
	$sIP = str_replace(' ', '', $gatewayParams['payeer_ipfilter']);
	
	if (!empty($sIP)) {
		
		$arrIP = explode('.', $_SERVER['REMOTE_ADDR']);
		if (!preg_match('/(^|,)(' . $arrIP[0] . '|\*{1})(\.)' .
		'(' . $arrIP[1] . '|\*{1})(\.)' .
		'(' . $arrIP[2] . '|\*{1})(\.)' .
		'(' . $arrIP[3] . '|\*{1})($|,)/', $sIP)) {
			$valid_ip = false;
		}
	}
	
	if (!$valid_ip) {
		
		$message .= " - IP-адрес сервера не является доверенным\n" . 
		"   доверенные IP: " . $sIP . "\n" .
		"   IP текущего сервера: " . $_SERVER['REMOTE_ADDR'] . "\n";
		$err = true;
	}

	if ($_POST["m_sign"] != $sign_hash) {
		
		$message .= " - не совпадают цифровые подписи\n";
		$err = true;
	}
	
	if (!$err) {
		
		switch ($_POST['m_status']) {
			
			case 'success':
				echo $_POST['m_orderid'] . '|success';
				checkCbTransID($_POST['m_operation_id']);
				addInvoicePayment($order_id, $_POST['m_operation_id'], $_POST['m_amount'], '', $gatewayModuleName);
				logTransaction($gatewayParams['name'], $_POST, 'Successful');
				break;
				
			default:
				$message .= " - статус платежа не является success\n";
				logTransaction($gatewayParams['name'], $_POST, 'Unsuccessful');
				$err = true;
				break;
		}
	}

	if ($err) {
		
		$to = $gatewayParams['payeer_email_error'];

		if (!empty($to)) {
			
			$message = "Не удалось провести платёж через систему Payeer по следующим причинам:\n\n" . $message . "\n" . $log_text;
			$headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" . 
			"Content-type: text/plain; charset=utf-8 \r\n";
			mail($to, 'Ошибка оплаты', $message, $headers);
		}
		
		echo $_POST['m_orderid'] . '|error';
	}
}