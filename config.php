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

ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);	

if ( Constants::is_filejs() ) {
  createVarsPathJs();
}

function createVarsPathJs() {
  $oSession = new TSession( APP_SESSION );
  $rol = $oSession->GetVar('rol') ?: 'operador';

  $cHtml  = "<script>"; 
  $cHtml .= 'const _SUCCESS = "success";' . PHP_EOL;
  $cHtml .= 'const _ERROR   = "error";'   . PHP_EOL;
  $cHtml .= 'const USER_ROL = "' . $rol . '";' . PHP_EOL;
  $cHtml .= "	var path = {" . PHP_EOL;
  $cHtml .= '		tweb   : "' . Constants::getpath_tweb() . '",' 		. PHP_EOL;
  $cHtml .= '		images : "' . Constants::getpath_images() . '",' 	. PHP_EOL;
  $cHtml .= '		sound  : "' . Constants::getpath_sound() . '",' 	. PHP_EOL;
  $cHtml .= '		js     : "' . Constants::getpath_js() . '",' 			. PHP_EOL;
  $cHtml .= '		css    : "' . Constants::getpath_css() . '",' 		. PHP_EOL;
  $cHtml .= '		model  : "' . Constants::getpath_model() . '",' 	. PHP_EOL;
  $cHtml .= '		view   : "' . Constants::getpath_view() . '",' 		. PHP_EOL;
  $cHtml .= '		root   : "' . Constants::getpath_root() . '"' 		. PHP_EOL;
  $cHtml .= "	};" . PHP_EOL;
  $cHtml .= "</script>";
  echo $cHtml;
}
?>
