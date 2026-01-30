<?php
require_once 'sanitize.php';
require_once 'class_messages.php';

$message_obj = new Tutor81Messages();

$op_type = sanitize($_POST['op_type'], PARANOID);

/* **** CREAZIONE NUOVO MESSAGGIO ****/
if ($op_type === 'create_new_message'){
	$res = $message_obj->addMessage($_POST['from_user_id'], $_POST['object'], $_POST['message_text'],$_POST['recipients']);
	$res = $res ? 1 : 0;
	
	/* **** CREAZIONE NUOVO TICKER ****/
} elseif ($op_type === 'create_new_ticker'){
	$res = $message_obj->addMessage($_POST['from_user_id'], $_POST['object'], $_POST['message_text'],array(),$_POST['companies'],$_POST['in_news_ticker']);
	$res = $res ? 1 : 0;
	
	/* **** IMPOSTA IN NEWS TICKER ****/
} elseif ($op_type === 'set_in_news_ticker'){
	$res = $message_obj->setInNewsTicker($_POST['id_message'], $_POST['in_news_ticker']);
	$res = $res ? : 0;
	
	/* **** LETTURA MESSAGGIO ****/
} elseif ($op_type === 'message_read'){
	$res = $message_obj->setMessageReadByRecipient($_POST['id_recipient']);
	$res = $res ? : 0;
}

echo $res;
