<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
include  dirname(__FILE__)."/payu.cls.php"; 

$ORDER_ID = $arResult['ORDER_ID'];

#------------------------------------------------
# Recive all items data
#------------------------------------------------

$arBasketItems = array();

$dbBasketItems = CSaleBasket::GetList(
          array(
                "NAME" => "ASC",
                "ID" => "ASC"
              ),
          array(
                "LID" => SITE_ID,
                "ORDER_ID" => $ORDER_ID
              ),
          false,
          false,
          array("ID", "CALLBACK_FUNC", "MODULE", 
                "PRODUCT_ID", "QUANTITY", "DELAY", 
                "CAN_BUY", "PRICE", "WEIGHT")
    );
while ($arItems = $dbBasketItems->Fetch())
{
    if (strlen($arItems["CALLBACK_FUNC"]) > 0)
    {
        CSaleBasket::UpdatePrice($arItems["ID"], 
                                 $arItems["CALLBACK_FUNC"], 
                                 $arItems["MODULE"], 
                                 $arItems["PRODUCT_ID"], 
                                 $arItems["QUANTITY"]);
        $arItems = CSaleBasket::GetByID($arItems["ID"]);
    }
    $arBasketItems[] = $arItems;
}


#--------------------------------------------
$arOrder = CSaleOrder::GetByID($ORDER_ID);
$db_res = CSaleOrderPropsValue::GetList(($b=""), ($o=""), array("ORDER_ID"=>$ORDER_ID));

while ($ar_res = $db_res->Fetch())
   $arCurOrderProps[(strlen($ar_res["CODE"])>0) ? $ar_res["CODE"] : $ar_res["ID"]] = $ar_res["VALUE"];

$PayU = new PayU( CSalePaySystemAction::GetParamValue("MERCHANT"), CSalePaySystemAction::GetParamValue("SECURE_KEY") );
$PayU->url = CSalePaySystemAction::GetParamValue("LU_URL");

$orderID = "TESTPAYU_".$ORDER_ID."_".CSaleBasket::GetBasketUserID()."_". md5( "payuOrder_".time() );

  $forSend = array (
          'ORDER_REF' => $orderID, # Uniqe order 
          'ORDER_DATE' => date("Y-m-d H:i:s"), # Date of paying ( Y-m-d H:i:s ) 
          );


foreach ( $arBasketItems as $val )
{
  $forSend['ORDER_PNAME'][] = $val['NAME'];
  $forSend['ORDER_PCODE'][] = $val['PRODUCT_ID'];
  $forSend['ORDER_PINFO'][] = "";
  $forSend['ORDER_PRICE'][] = $val['PRICE'];
  $forSend['ORDER_QTY'][] = $val['QUANTITY'];
  $forSend['ORDER_VAT'][] = $val['VAT_RATE'];
}
 
  
  $forSend += array(
                    'ORDER_SHIPPING' => $arOrder['PRICE_DELIVERY'], # Shipping cost
                    'PRICES_CURRENCY' => CSalePaySystemAction::GetParamValue("PRICE_CURRENCY"), # Currency
                    'DISCOUNT' => $arOrder['DISCOUNT_VALUE']
                  );

  $PayU->update( $forSend )->debug( CSalePaySystemAction::GetParamValue("DEBUG_MODE") );
  $PayU->data['LANGUAGE'] = CSalePaySystemAction::GetParamValue("LANGUAGE");

  $backref = CSalePaySystemAction::GetParamValue("BACK_REF");
  if ( $backref != "" ) $PayU->data['BACK_REF'] = $backref;


  $form = $PayU->getForm();

  echo $form;