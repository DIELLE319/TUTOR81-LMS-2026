<?php
require_once 'config.php';
require_once ABSPATH.'lib/reset_session.php';
header("location:".BASE_WEBSITE_PATH."ec-login.php");
exit();