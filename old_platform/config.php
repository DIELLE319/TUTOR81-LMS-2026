<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 14-nov-2014
 * File: config.php
 * Project: Piattaforma Tutor81
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
/** Error reporting  */
error_reporting(E_ALL);

date_default_timezone_set('Europe/Rome');

if (!defined('DEVELOPMENT'))
    define('DEVELOPMENT', FALSE);
if (!defined('DEVELOP_PAYPAL'))
    define('DEVELOP_PAYPAL', TRUE);

ini_set('display_errors', FALSE);
ini_set('display_startup_errors', FALSE);

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for Tutor81 */

if ( !defined('DB_NAME') )
    define( 'DB_NAME', 'pro_tutor81' );
/** MySQL database username */
if ( !defined('DB_USER') )
    define( 'DB_USER', 'pro_tutor81' );
/** MySQL database password */
if ( !defined('DB_PASSWORD') )
    define( 'DB_PASSWORD', getenv('OVH_DB_PASSWORD') ?: '' );
/** MySQL hostname */
if ( !defined('DB_HOST') )
    define( 'DB_HOST', getenv('OVH_DB_HOST') ?: '127.0.0.1' );
/** Database Charset to use in creating database tables. */
if ( !defined('DB_CHARSET') )
    define( 'DB_CHARSET', 'utf8' );

if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

if ( !defined('BASE_WEBSITE_PATH'))
	//define('BASE_WEBSITE_PATH', "/tutor81.com/pre-amm/");
	define('BASE_WEBSITE_PATH', "/");
if (!defined('BASE_ROOT_PATH')) {
    define('BASE_ROOT_PATH', dirname(__FILE__) . '/');
}
if ( !defined('BASE_MEDIA_PATH'))
	define('BASE_MEDIA_PATH', dirname(__FILE__) . "/media/");
if ( !defined('BASE_LIBRARY_PATH'))
	define('BASE_LIBRARY_PATH', dirname(__FILE__) . "/lib/");

if ( !defined('URL_PLAYER'))
	//define('URL_PLAYER', "http://localhost/tutor81.com/sample1/");
	define('URL_PLAYER', "https://avviacorso.tutor81.com/");

$base_media_path = 'media/';

if ( !defined('SESSION_LIFETIME'))
	define('SESSION_LIFETIME', "1440");

if ( !defined('ECOMMERCE_TUTOR81_USERID'))
    define('ECOMMERCE_TUTOR81_USERID', "16953");

if ( !defined('SUPERUSER_TUTOR81_USERID'))
    define('SUPERUSER_TUTOR81_USERID', "6");

if (!defined('HUBMEDIA_URL')) {
    if (DEVELOPMENT)
        define('HUBMEDIA_URL', 'https://pre-media.tutor81.local'); // development
    else
        define('HUBMEDIA_URL', 'https://media.tutor81.com'); // pre production
}

if (!defined('HUB_URL')) {
    if (DEVELOPMENT)
        $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost:8000';
    else
        $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'accedi.tutor81.com';
    
    define('HUB_URL', 'https://' . $server_name);
}

if (!defined('AVVIACORSO_URL')) {
    if (DEVELOPMENT)
        $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost:8000';
    else
        $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'accedi.tutor81.com';
        
    define('AVVIACORSO_URL', 'https://'.$server_name."/ec-avvia-corso.php");
}

if (!defined('COMMON_AVVIACORSO_URL')) {
    define('COMMON_AVVIACORSO_URL', 'https://avviacorso.tutor81.com');
}

if (!defined('AVVIACORSO_OLD_URL')) {
    if (DEVELOPMENT)
        define('AVVIACORSO_OLD_URL', 'https://pre-player.tutor81.local'); // development
    else
        define('AVVIACORSO_OLD_URL', 'https://avviacorso.tutor81.com'); // pre production
}
//
//if (!defined('PAYPAL_URL')) {
//    if (DEVELOP_PAYPAL)
//        define('PAYPAL_URL', 'https://www.sandbox.paypal.com/cgi-bin/webscr'); // development
//    else
//        define('PAYPAL_URL', 'https://www.paypal.com/it/cgi-bin/webscr'); // pre production
//}
//
//if (!defined('PAYPAL_ACCOUNT')) {
//    if (DEVELOP_PAYPAL)
//        define('PAYPAL_ACCOUNT', ''); // development
//    else
//        define('PAYPAL_ACCOUNT', ''); // pre production
//}