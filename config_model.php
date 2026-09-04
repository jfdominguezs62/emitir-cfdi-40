<?php
if ( !class_exists('Constants') ) {
  include( 'constants.php');
}	

define( 'ROOT_PATH',  Constants::getpath_root() ); 
define( 'TWEB_PATH',  Constants::getpath_tweb() ); 
define( 'LIBS_PATH',  Constants::getpath_tweb() . 'libs/mylibs/' );
define( 'IMAGE_PATH', Constants::getpath_images() );
define( 'JS_PATH', 	  Constants::getpath_js() );
define( 'CSS_PATH', 	Constants::getpath_css() );

define( "NAME_EMPRESA", 'Emisión CFDI' );
define( 'APP_SESSION' , 'APP_EMITIR_CFDI');
define( 'LOGIN_PHP'   , 'index.php' );
define( 'COPYRIGHT'   , "© Emisión CFDI" );
define( 'APP_TITLE'   , "SISTEMA DE EMISIÓN CFDI 4.0" );
define( "TWINDOW_CLR" , "#0d47a1" );
define( 'BACKGROUND'	, '#f0f2f5' );
define( 'TBAR_COLOR'  , '#e9ecef' );
define( "FONT_FAMILY",  "Verdana, Segoe UI" );

include( Constants::getpath_tweb() . 'core.session.php' );

$oSessionCfg = new TSession( APP_SESSION );
$oSessionCfg->nTimeOut = 3600;
$oSessionCfg->lExeError = false;

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ERROR | E_PARSE);
?>
