<?php
#ini_set("display_errors", true);
#error_reporting(E_ALL);
header("Content-type:text/html; charset=utf-8");
//	header("HTTP/1.0 200 OK");

    global $mosConfig_absolute_path, $mosConfig_live_site, $mosConfig_lang, $database,
    $mosConfig_mailfrom, $mosConfig_fromname;
   
        /*** access Joomla's configuration file ***/
        $my_path = dirname(__FILE__);
        
        if( file_exists($my_path."/../../../configuration.php")) {
            $absolute_path = dirname( $my_path."/../../../configuration.php" );
            require_once($my_path."/../../../configuration.php");
        }
        elseif( file_exists($my_path."/../../configuration.php")){
            $absolute_path = dirname( $my_path."/../../configuration.php" );
            require_once($my_path."/../../configuration.php");
        }
        elseif( file_exists($my_path."/configuration.php")){
            $absolute_path = dirname( $my_path."/configuration.php" );
            require_once( $my_path."/configuration.php" );
        }
        else {
            die( "Joomla Configuration File not found!" );
        }
        
        $absolute_path = realpath( $absolute_path );
        
        // Set up the appropriate CMS framework
        if( class_exists( 'jconfig' ) ) {
			define( '_JEXEC', 1 );
			define( 'JPATH_BASE', $absolute_path );
			define( 'DS', DIRECTORY_SEPARATOR );
			
			// Load the framework
			require_once ( JPATH_BASE . DS . 'includes' . DS . 'defines.php' );
			require_once ( JPATH_BASE . DS . 'includes' . DS . 'framework.php' );

			// create the mainframe object
			$mainframe = & JFactory::getApplication( 'site' );
			
			// Initialize the framework
			$mainframe->initialise();
			
			// load system plugin group
			JPluginHelper::importPlugin( 'system' );
			
			// trigger the onBeforeStart events
			$mainframe->triggerEvent( 'onBeforeStart' );
			$lang =& JFactory::getLanguage();
			$mosConfig_lang = $GLOBALS['mosConfig_lang'] = strtolower( $lang->getBackwardLang() );
			// Adjust the live site path
			$mosConfig_live_site = str_replace('/administrator/components/com_virtuemart', '', JURI::base());
			$mosConfig_absolute_path = JPATH_BASE;
        } else {
        	define('_VALID_MOS', '1');
        	require_once($mosConfig_absolute_path. '/includes/joomla.php');
        	require_once($mosConfig_absolute_path. '/includes/database.php');
        	$database = new database( $mosConfig_host, $mosConfig_user, $mosConfig_password, $mosConfig_db, $mosConfig_dbprefix );
        	$mainframe = new mosMainFrame($database, 'com_virtuemart', $mosConfig_absolute_path );
        }
	/*** END of Joomla config ***/
    
    
    /*** VirtueMart part ***/
		global $database;        
        require_once($mosConfig_absolute_path.'/administrator/components/com_virtuemart/virtuemart.cfg.php');
        include_once( ADMINPATH.'/compat.joomla1.5.php' );
        require_once( ADMINPATH. 'global.php' );
        require_once( CLASSPATH. 'ps_main.php' );
		require_once( CLASSPATH. 'ps_database.php' );
		require_once( CLASSPATH. 'ps_order.php' );
        require_once( CLASSPATH. 'payment/ps_payu.cfg.php' );
        
		$debug_email_address = $mosConfig_mailfrom;
	    $sess = new ps_session();                        

	/*** END VirtueMart part ***/
	

if (!$_POST) return;

include( PAYU_CLASS_PATH );
$option  = array( 'merchant' => PAYU_MERCHANT, 'secretkey' => PAYU_SECRET_KEY );

$pay = PayU::getInst()->setOptions( $option )->IPN();
$d = array(
			'order_id' => $_POST['REFNOEXT'],
			'notify_customer' => "N",
			'order_total' => $_POST['IPN_TOTALGENERAL'],
			'order_status' => "C",
		);

$ps_order= new ps_order;
$ps_order->order_status_update( $d );
echo $pay;
?>