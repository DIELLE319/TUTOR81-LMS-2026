<?php
/**
 * Created by PhpStorm.
 * User: endrit
 * Date: 2/27/2017
 * Time: 12:19 PM
 */

require_once dirname(__FILE__).'/../config.php';
if (!isset($_SESSION)) {
    @ini_set('session.gc_maxlifetime', 1440);
    @ini_set('session.cookie_lifetime', 0);
    session_start();
}