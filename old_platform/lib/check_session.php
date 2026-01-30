<?php

require_once "set_session.php";

if((!isset($_SESSION['admin_logged'])) || ($_SESSION['admin_logged'] != true) || (session_id() != $_SESSION['admin_sessionid']) || $_SESSION['last_access'] < (time() - SESSION_LIFETIME)){
	
	?>
	
		<script>window.location.href = "<?=BASE_WEBSITE_PATH?>logout.php";</script>
		<h3>Sessione scaduta. effettua il <a href="<?=BASE_WEBSITE_PATH?>ec-login.php">login</a> prma di procedere.</h3>
				
	<?php
	
	exit();
		
	//require_once ABSPATH."logout.php";


} else {

	require_once "get_session.php";
	
}