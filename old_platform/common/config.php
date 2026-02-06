<?php
date_default_timezone_set('Europe/Rome');

if ( !defined('DBNAME') )
	define('DBNAME', 'pro_tutor81');
if ( !defined('USERNAME') )
	define('USERNAME', 'pro_tutor81');
if ( !defined('PASSWORD') )
	define('PASSWORD', getenv('OVH_DB_PASSWORD') ?: '');

define('COMMON_LIBRARY', dirname(__FILE__) . '/lib/');