<?php
#ini_set( "display_errors", true );
#error_reporting( E_ALL );

if ($_SERVER["REQUEST_METHOD"] !== "POST") die();
if(!require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php")) die('prolog_before.php not found!');
if (CModule::IncludeModule('sale'))
{
	$ord = $_POST['REFNOEXT'];
	$ordArray = explode( "_", $ord );
	$ORDER_ID = $ordArray[1];
	$User_ID = $ordArray[2];

	$arOrder = CSaleOrder::GetByID($ORDER_ID);
	$payID = $arOrder['PAY_SYSTEM_ID'];
	$payData = CSalePaySystemAction::GetByID( $payID );

	include  $_SERVER['DOCUMENT_ROOT'].$payData['ACTION_FILE']."/payu.cls.php"; 
	
	$b = unserialize( $payData['PARAMS'] );
	
	foreach ( $b as $k => $v ) $payuOpt[$k] = $v['VALUE'];
	
	$PayU = new PayU( $payuOpt["MERCHANT"], $payuOpt["SECURE_KEY"] );
	$check = $PayU->getPostData()->checkHashSignature();
	if ( !$check )  die( "Incorrect signature" );


	$answer = $PayU->createAnswer();
	$stmp = strtotime( $_POST['SALEDATE'] );
	$arFields = array(
				"PS_STATUS" => "Y", 
				"PS_STATUS_CODE" => $_POST['ORDERSTATUS'] ,
				"PS_STATUS_DESCRIPTION" => $_POST['ORDERSTATUS']. " " . $_POST['PAYMETHOD'] ,
				"PS_STATUS_MESSAGE" => " - ",
				"PS_SUM" => $_POST['IPN_TOTALGENERAL'],
				"PS_CURRENCY" =>$_POST['CURRENCY'],
				"PS_RESPONSE_DATE" => date( "d.m.Y H:i:s" ),
			);
		CSaleOrder::Update( $ORDER_ID, $arFields );
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>