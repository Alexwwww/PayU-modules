<?php
class PayU
{	
	var $form = "", 
		$url = "https://secure.payu.ua/order/lu.php", 
		$button = "<input type='submit'>";
	private static $Inst = false, $key, $merchant;
	private $cells = array( 'MERCHANT',	'ORDER_REF', 'ORDER_DATE', 'ORDER_PNAME', 'ORDER_PCODE', 
							'ORDER_PINFO', 'ORDER_PRICE', 'ORDER_QTY', 'ORDER_VAT', 'ORDER_SHIPPING', 'PRICES_CURRENCY');
	

	private function __construct(){}
	private function __clone(){}
	public function __toString()
	{ 
		return ( $this->form === "" ) ? "Form are not exists" : $this->form;  
	}

	public static function getInst()
	{	
		if( self::$Inst === false ) self::$Inst = new PayU();
		return self::$Inst;
	}

	function setMerch( $merch = null, $key = null )
	{
		if( $merch == null || $key == null  ) return false;
		self::$merchant = $merch;
		self::$key = $key;
		return $this;
	}
#----------------------------------------------------------------------
# System methods	
#----------------------------------------------------------------------
	function md5_hmac($key = null, $data) 
	{	
		if ( $key == null ) $key = self::$key;
  		$b = 64;
 		if (strlen($key) > $b) $key = pack("H*",md5($key));
  		$key  = str_pad($key, $b, chr(0x00));
  		$ipad = str_pad('', $b, chr(0x36));
  		$opad = str_pad('', $b, chr(0x5c));
  		$k_ipad = $key ^ $ipad;
  		$k_opad = $key ^ $opad;
  		return md5($k_opad  . pack("H*", md5($k_ipad . $data)));
	}

	function Signature( $data = null ) 
	{		
		$str = "";
		if ( $data == null ) $data = &$this->data;
		$this->checkArray( $data );
		foreach ( $this->cells as $v ) $str .= $this->convData( $data[$v] );
		return $this->md5_hmac( self::$key, $str );
	}


#----------------------------------------------------------------------
# Create request
#----------------------------------------------------------------------
	function pushArray( $arr, $debug = 0 )
	{	
		$this->data['MERCHANT'] = self::$merchant;
		if( !isset($arr['ORDER_REF']) ) $arr['ORDER_REF'] = $_SERVER['HTTP_HOST']."_". md5( rand(1,1000).time() );
		if( !isset($arr['ORDER_DATE']) ) $arr['ORDER_DATE'] = date("Y-m-d H:i:s");
		$this->data += $arr;
		$this->debug( $debug )->finish();
		return $this;
	}

	function debug( $deb = 0 )
	{
		$this->data['TESTORDER'] = ( $deb == 1 ) ? "TRUE" : "FALSE";
		$this->data['DEBUG'] = $deb;
		return $this;
	}

# Create signature. 
	function finish()
	{
		$hash = $this->Signature();
		if ( !$hash ) die( 'Incorrect data' );
		$this->data['ORDER_HASH'] = $hash;
		$this->form = $this->getForm();
		return $this;
	}

# Is all data exists
	function checkArray( $data = null )
	{
		if ( $data == null ) $data = &$this->data;
		foreach ( $this->cells as $v ) 
		{ 
			if ( !isset($data[$v]) ) die("$v is not set");
		}
		return true;
	}

#Outputs a string for hmac format. For a string like 'aa' it will return '2aa'.
	function convString($string) 
	{
		return mb_strlen($string, '8bit') . $string;
	}

# The same as convString except that it receives
# an array of strings and returns the string from all values within the array.
	function convArray($array) 
	{
  		$return = '';
  		foreach ($array as $v) $return .= $this->convString( $v );
  		return $return;
	}


	function convData( $val )
	{
		return ( is_array( $val ) ) ? $this->convArray( $val ) : $this->convString( $val );
	}

# Make inputs for form
	function makeString ( $name, $val )
	{
		$str = "";
		if ( !is_array( $val ) ) return '<input type="hidden" name="'.$name.'" value="'.$val.'">'."\n";
		foreach ($val as $v) $str .= $this->makeString( $name.'[]', $v );
		return $str;
	}

#Method which create a form
	function getForm()
	{	
		$form = '<form method="post" action="'.$this->url.'">';
		foreach ( $this->data as $k => $v ) $form .= $this->makeString( $k, $v );
		return $form . $this->button."</form>";
	}

#---------------------------------------------------------------------------------------
# Read answer
#---------------------------------------------------------------------------------------

	function getPostData()
	{
		$this->post = $_POST;
		$array = array( "IPN_PID", "IPN_PNAME", "IPN_DATE", "ORDERSTATUS" );
		foreach ( $array as $name ) if ( !isset( $this->post[ $name ] ) ) die( "Incorrect data" );
		$this->datetime = date("YmdHis");
		return $this;
	}

	function checkSign()
	{	
		$check = $this->getPostData()->getSignature();
		if ( !$check )  die( "Incorrect signature" );
		return $this;
	}

	function getSignature()
	{	
		$post = &$this->post;
		$hash = $post["HASH"];  
		unset( $post["HASH"] );
		$sign = $this->Signature( $post );
		return ( $hash != $sign ) ? false : true ;
	}

	function createAnswer()
	{	
		$datetime = &$this->datetime;
		$post = &$this->post;
		$data = array(
				   "IPN_PID" => $post[ "IPN_PID" ], 
				   "IPN_PNAME" => $post[ "IPN_PNAME" ], 
				   "IPN_DATE" => $post[ "IPN_DATE" ], 
				   "DATE" => $datetime
					);

		$sign = $this->Signature( $data );
		$this->form = "<!-- <EPAYMENT>$datetime|$sign</EPAYMENT> -->";
		return $this;
	}

}
?>