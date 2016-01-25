<?php

if (isset($_GET['m_orderid']))
{
	header('Location: ' . $_SERVER['HOST'] . '/viewinvoice.php?id=' . $_GET['m_orderid'] . '&paymentsuccess=true');
}