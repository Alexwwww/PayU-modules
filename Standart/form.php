<?php
header("Content-type:text/html; charset=utf-8");

include_once "PayU.cls.php";

# Create form for request
if( !isset($_GET['answer']) )
{
	$forSend = array (
					#'ORDER_REF' => $orderID, # Uniqe order 
					#'ORDER_DATE' => date("Y-m-d H:i:s"), # Date of paying ( Y-m-d H:i:s ) 
					'ORDER_PNAME' => array( "Test_goods", "Тест товар №1", "Test_goods3" ), # Array with data of goods
					'ORDER_PCODE' => array( "testgoods1", "testgoods2", "testgoods3" ), # Array with codes of goods
					'ORDER_PINFO' => array( "", "", "" ), # Array with additional data of goods
					'ORDER_PRICE' => array( "0.10", "0.11", "0.12" ), # Array with prices of goods
					'ORDER_QTY' => array( 1, 2, 1 ), # Array with data of counts of each goods 
					'ORDER_VAT' => array( 0, 0, 0 ), # Array with VAT of each goods
					'ORDER_SHIPPING' => 0, # Shipping cost
					'PRICES_CURRENCY' => "UAH",  # Currency
					'LANGUAGE' => "RU",
					'BILL_FNAME' => "TEST"
				  );

# For debug mode off remove second argument at pushArray method
$pay = PayU::getInst()->setMerch( "MERCHANT", 'SECRET_KEY')->pushArray( $forSend, 1 ); 


#------------
#	Variants for showing form
#	1. echo $pay
#	2. echo $pay->form
#	
#	Show form at another place (when class PayU initialized)
#	1.	$pay2 = PayU::getInst();
#		echo $pay2;
#	2. 	echo PayU::getInst();
#
#-----------
echo $pay; 

}



# Read answer (IPN)
if( isset($_GET['answer']) )
{
	$payansewer = PayU::getInst()->setMerch('MERCHANT', 'SECRET_KEY')->checkSign();
	echo $payansewer;
}




?>