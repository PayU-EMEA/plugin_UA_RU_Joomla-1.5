<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); 
/**
*
* @version $Id: ps_payu.php 1000 2012-07-18 00:00:00Z payu.ua $
* @package VirtueMart
* @subpackage payment
* @copyright Copyright (C) 2004-2007 soeren - All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
*
*/


class ps_payu {

    var $classname = "ps_payu";
    var $payment_code = "PayU";
	
	
	
function show_configuration() {
	$db = new ps_DB();
	include_once(CLASSPATH ."payment/".$this->classname.".cfg.php"); // Read current Configuration
	$clsUrl = dirname(__FILE__)."/payu_class.php";
	?>
<style>
.boxesEcomm{padding:10px 20px;}
.boxesEcomm div{ margin:5px; border-bottom:1px dotted #ffffff; font-weight:bold;}
.boxesEcomm input,select{ margin-right:80px; width:200px; float:right;}
.boxesEcomm span{  background-color: #FFEE99; border: 1px dotted #AEAEAE; color: #5A5A5A; display: none; font-size: 10px; 
				   font-weight: normal; padding: 5px; position: absolute; right: 10px; }
.boxesEcomm div:hover span{display: block;}
#config-page strong textarea{ margin:5px 10px;}
#config-page{margin:5px;}
</style>

<div class="boxesEcomm"> 

	<div>ID мерчанта : <input type="text" name="PAYU_MERCHANT" value="<?= PAYU_MERCHANT ?>" size="30" /></div>
	<div>Секретный ключ :<input type="text" name="PAYU_SECRET_KEY" value="<?= PAYU_SECRET_KEY ?>" size="60" />
		
	</div>
	
	<? 
	$on = $off = "";
	if ( PAYU_DEBUG == 1 ) $on = "selected='selected'";
		else $off = "selected='selected'"; 
	?>
	<div>Режим отладки :
			<select name="PAYU_DEBUG">
				<option value='1' <?= $on ?> >Включен</option>
				<option value='0' <?= $off ?> >Выключен</option>
			</select>
	</div>

	<div>Ссылка LiveUpdate :
			<input type="text" name="PAYU_LU_URL" value="<?= PAYU_LU_URL ?>" size="60" />
			<span>по умолчанию : https://secure.payu.ua/order/lu.php</span>
	</div>

	<div>Валюта мерчанта :
			<input type="text" name="PAYU_CURRENCY" value="<?= PAYU_CURRENCY ?>" size="60" />
	</div>
	
	<div>Ссылка возврата клиента :
			<input type="text" name="PAYU_BACK_REF" value="<?= PAYU_BACK_REF ?>" size="120" />
			<span>Если оставить пустым,<br>то клиент останется в системе PayU</span>
	</div>

	<div>Процент НДС :
			<input type="text" name="PAYU_VAT" value="<?= PAYU_VAT ?>" size="2" />
			<span>Если указать 0 - без НДС.<br>Для указания НДС в размере 18% - 19</span>
	</div>
	
	<div>Язык страницы PayU :
			<input type="text" name="PAYU_LANGUAGE" value="<?= PAYU_LANGUAGE ?>" size="2" />
			<span>Доступны ( RU, EN, RO, DE, FR, IT, ES )</span>
	</div>
	<? echo ( PAYU_CLASS_PATH == "" ) ? "<p>Нет всех нужных параметров. необходимо сохранить форму</p>" : ""; ?>
	<input type="hidden" name="PAYU_CLASS_PATH" value="<?= ( PAYU_CLASS_PATH == "" ) ? $clsUrl : PAYU_CLASS_PATH ?>" />

</div>

<h3>Ссылка для IPN</h3>
<div style='font-weight:bold; background-color: #FFEE99; border: 1px dotted #AEAEAE; color: #5A5A5A;'>
http://{ АДРЕС САЙТА }/administrator/components/com_virtuemart/notify_payu.php
</div>


<h3 >Код, который добавить ниже</h3>
<div style='font-weight:bold; background-color: #FFEE99; border: 1px dotted #AEAEAE; color: #5A5A5A;'>
&lt?php<br>
include('<?= $clsUrl ?>');<br>
$nuser = ( isset($user) ) ? $user : null ;<br>
$generate = new PayUGen( $order_id, $db, $nuser ); <br>
?&gt;
</div>
<?php
}
    
	function has_configuration() 
	{
		return true;
	}
   
  	function configfile_writeable() 
  	{
		return is_writeable( CLASSPATH."payment/".$this->classname.".cfg.php" );
	}
   
  	function configfile_readable() 
  	{
		return is_readable( CLASSPATH."payment/".$this->classname.".cfg.php" );
	}
   
  	function write_configuration( &$d ) 
  	{
		$my_config_array = array(	"PAYU_MERCHANT" 	=> $d['PAYU_MERCHANT'],
									"PAYU_SECRET_KEY" 	=> $d['PAYU_SECRET_KEY'],
									"PAYU_DEBUG" 		=> $d['PAYU_DEBUG'],
									"PAYU_LU_URL" 		=> $d['PAYU_LU_URL'],
									"PAYU_CURRENCY" 	=> $d['PAYU_CURRENCY'],
									"PAYU_BACK_REF" 	=> $d['PAYU_BACK_REF'],
									"PAYU_VAT" 			=> $d['PAYU_VAT'],
									"PAYU_LANGUAGE" 	=> $d['PAYU_LANGUAGE'],
									"PAYU_CLASS_PATH" 	=> $d['PAYU_CLASS_PATH']
                            	);
		$config = "<?php\n";
		$config .= "if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); \n\n";
		foreach( $my_config_array as $key => $value ) $config .= "define ('$key', '$value');\n";
		$config .= "?>";
  
		if ($fp = fopen(CLASSPATH ."payment/".$this->classname.".cfg.php", "w")) 
		{
			fputs($fp, $config, strlen($config));
			fclose ($fp);
			return true;
		} else return false;
	}

	function process_payment($order_number, $order_total, &$d) 
	{	
      return true;
    }
  
}