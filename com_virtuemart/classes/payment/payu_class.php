<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); 
/**
*
* Additional class for PayU payment module
*
*/

class PayU
{
	var $luUrl = "https://secure.payu.ua/order/lu.php", 
		$button = "<input type='submit'>",
		$debug = 0,
		$showinputs = "hidden";

	private static $Inst = false, $merchant, $key;

	private $data = array(), $dataArr = array(), $answer = ""; 
	private $LUcell = array( 'MERCHANT' => 1, 'ORDER_REF' => 0, 'ORDER_DATE' => 1, 'ORDER_PNAME' => 1, 'ORDER_PGROUP' => 0,
							'ORDER_PCODE' => 1, 'ORDER_PINFO' => 0, 'ORDER_PRICE' => 1, 'ORDER_QTY' => 1, 'ORDER_VAT' => 1, 
							'ORDER_SHIPPING' => 1, 'PRICES_CURRENCY' => 1);

	private $IPNcell = array( "IPN_PID", "IPN_PNAME", "IPN_DATE", "ORDERSTATUS" );

	private function __construct(){}
	private function __clone(){}
	public function __toString()
	{ 
		return ( $this->answer === "" ) ? "<!-- Answer are not exists -->" : $this->answer;  
	}
	public static function getInst()
	{	
		if( self::$Inst === false ) self::$Inst = new PayU();
		return self::$Inst;
	}
	function setOptions( $opt = array() )
	{
		if ( !isset( $opt['merchant'] ) || !isset( $opt['secretkey'] )) die("No params");
		self::$merchant = $opt['merchant'];
		self::$key = $opt['secretkey'];
		unset( $opt['merchant'], $opt['secretkey'] );
		if ( count($opt) === 0 ) return $this;
		foreach ( $opt as $k => $v) $this->$k = $v;
		return $this;
	}

	function setData( $array = null )
	{	
		if ($array === null ) die("No data");
		$this->dataArr = $array;
		return $this;
	}
	function Signature( $data = null ) 
	{		
		$str = "";
		foreach ( $data as $v ) $str .= $this->convData( $v );
		return hash_hmac("md5",$str, self::$key);
	}
	private function convString($string) 
	{	
		return mb_strlen($string, '8bit') . $string;
	}
	private function convArray($array) 
	{
  		$return = '';
  		foreach ($array as $v) $return .= $this->convString( $v );
  		return $return;
	}
	private function convData( $val )
	{
		return ( is_array( $val ) ) ? $this->convArray( $val ) : $this->convString( $val );
	}
#====================== LU GENERETE FORM =================================================
	public function LU()
	{	
		$arr = &$this->dataArr;
		$arr['MERCHANT'] = self::$merchant;
		if( !isset($arr['ORDER_DATE']) ) $arr['ORDER_DATE'] = date("Y-m-d H:i:s");
		$arr['TESTORDER'] = ( $this->debug == 1 ) ? "TRUE" : "FALSE";
		$arr['DEBUG'] = $this->debug;
		$arr['ORDER_HASH'] = $this->Signature( $this->checkArray( $arr ) );
		$this->answer = $this->genereteForm( $arr );
		return $this;
	}
	private function checkArray( $data )
	{
		$this->cells = array();
		$ret = array();
		foreach ( $this->LUcell as $k => $v ) 
		{ 	
			if ( isset($data[$k]) ) $ret[$k] = $data[$k];
			 elseif ( $v == 1 ) die("$k is not set");
		}
		return $ret;
	}
	private function genereteForm( $data )
	{	
		$form = '<form method="post" id="PayUForm" action="'.$this->luUrl.'">';
		foreach ( $data as $k => $v ) $form .= $this->makeString( $k, $v );
		return $form . $this->button."</form>";
	}	
	private function makeString ( $name, $val )
	{
		$str = "";
		if ( !is_array( $val ) ) return '<input type="'.$this->showinputs.'" name="'.$name.'" value="'.htmlspecialchars($val).'">'."\n";
		foreach ($val as $v) $str .= $this->makeString( $name.'[]', $v );
		return $str;
	}
#======================= IPN READ ANSWER ============================

	public function IPN()
	{	
		$arr = &$this->dataArr;
		$arr = $_POST;
		$this->cells = $this->IPNcell;
		foreach ( $this->IPNcell as $name ) if ( !isset( $arr[ $name ] ) ) die( "Incorrect data" );

		$hash = $arr["HASH"];  
		unset( $arr["HASH"] );
		$sign = $this->Signature( $arr );

		if ( $hash != $sign ) return $this;
		$datetime = date("YmdHis");
		$sign = $this->Signature(  array(
				   						"IPN_PID" => $arr[ "IPN_PID" ][0], 
				  						"IPN_PNAME" => $arr[ "IPN_PNAME" ][0], 
				   						"IPN_DATE" => $arr[ "IPN_DATE" ], 
				   						"DATE" => $datetime
										)
								);

		$this->answer = "<!-- <EPAYMENT>$datetime|$sign</EPAYMENT> -->";
		return $this;
	}
#======================= Check BACK_REF =====================
	function checkBackRef( $type = "http")
	{
		$path = $type.'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$tmp = explode("?", $path);
		$url = $tmp[0].'?';
		$params = array();
		foreach ($_GET as $k => $v)
		{
			if ( $k != "ctrl" ) $params[] = $k.'='.rawurlencode($v);
		}
		$url = $url.implode("&", $params);
		$arr = array($url);
		$sign = $this->Signature( $arr );
		$this->answer = ( $sign === $_GET['ctrl'] ) ? true : false;
		return $this->answer;
	}
}

class PayUGen
{
	function __construct( $order_id, $db, $user)
	{	
		$button = "<style>
					.PayU_shadow{ position:fixed; top:0px; left:0px; right:0px; bottom:0px; background-color:#000000; opacity: 0.4;}
					.PayU_BG{position:fixed; top:50%; left:50%; margin:-40px 0px 0px -60px; background-color:#ffffff; }
					</style>
					<div class='PayU_shadow'>&nbsp;</div><div class='PayU_BG'>".
		  		"<div><img src='http://www.payu.ua/sites/ukraine/files/logo-payu.png' width='120px' style='margin:0px 5px;'></div>".
				"</div>".
		  		"<script>
		  			setTimeout( subform, 3000 );
		  			function subform(){ document.getElementById('PayUForm').submit(); }
		  		</script>";

		$option  = array( 
							'merchant' => PAYU_MERCHANT, 
							'secretkey' => PAYU_SECRET_KEY, 
							'debug' => PAYU_DEBUG,
							'button' => $button
						);
		if ( PAYU_LU_URL != '' ) $option['luUrl'] = PAYU_LU_URL;

		$d = array(
					'ORDER_REF' => $order_id, # Uniqe order 
					'ORDER_SHIPPING' => $db->f('order_shipping') + $db->f('order_shipping_tax'),
					'PRICES_CURRENCY' => PAYU_CURRENCY,
					'LANGUAGE' => PAYU_LANGUAGE
		  			);

		if ( PAYU_BACK_REF != '' ) $d['BACK_REF'] = PAYU_BACK_REF;
	
		$d += $this->getBill( $user );
		$d += $this->getProducts($order_id, $db);
		$pay = PayU::getInst()->setOptions( $option )->setData( $d )->LU();
		echo $pay;
	}

	function getProducts($order_id, $db)
	{	
		$d = array();
			$q  = "SELECT * FROM #__{vm}_order_item WHERE order_id='$order_id'"; 
			$db->query($q);
		while ( $db->next_record() ) 
		{
			$d['ORDER_PNAME'][] = $db->f('order_item_name'); 
			$d['ORDER_QTY'][] = $db->f('product_quantity');
			$d['ORDER_PRICE'][] = $db->f('product_item_price');
			$d['ORDER_VAT'][] = PAYU_VAT;
			$d['ORDER_PCODE'][] = $db->f('product_id'); 
			$d['ORDER_PINFO'][] = ""; 
		}

		if ( PAYU_BACK_REF != '' ) $d['BACK_REF'] = PAYU_BACK_REF;
		return $d;
	}

	function getBill( $user = null)
	{
		if ( $user != null )
		{
			return array(
						'BILL_FNAME' => $user->first_name,
						'BILL_LNAME' => $user->last_name,
						'BILL_EMAIL' => $user->user_email,
						'BILL_PHONE' => $user->phone_1,
						'BILL_ADDRESS' => $user->address_1,
						'BILL_ADDRESS2' => $user->address_2,
						'BILL_ZIPCODE' => $user->zip,
						'BILL_CITY' => $user->city,
						'BILL_COUNTRYCODE' => $user->country,
						);
		}
		return array();
	}
}