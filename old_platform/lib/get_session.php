<?php
/**
 * Created by PhpStorm.
 * User: endrit
 * Date: 2/27/2017
 * Time: 12:11 PM
 */


if(!key_exists('HTTPS', $_SESSION) || (key_exists('HTTPS', $_SERVER) && $_SESSION['HTTPS']!=$_SERVER['HTTPS']))
{
    session_regenerate_id(true);
    $_SESSION['admin_sessionid'] = session_id();
    $_SESSION['HTTPS']=$_SERVER['HTTPS'];
}

$_SESSION['last_access'] = time();