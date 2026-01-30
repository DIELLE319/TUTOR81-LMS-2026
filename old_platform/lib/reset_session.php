<?php
session_start();
$_SESSION['admin_logged'] = false;
$_SESSION['admin_sessionid'] = "";
session_unset();
session_destroy();
session_write_close();
setcookie(session_name(),'',0,'/');