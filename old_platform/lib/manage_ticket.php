<?php
require_once 'sanitize.php';
require_once 'class_ticket.php';

$ticket_obj = new Tutor81Ticket();

$op_type = sanitize($_POST['op_type'], PARANOID);

/*
 * INVIA NUOVO TICKET
 */
if ($op_type === 'send_new_ticket') {
	$license_id = $_POST['license_id'] ? : 0;
	$user_id = $_POST['user_id'] ? : 0;
	$staff_id = 0;
	$ip_address = $_SERVER ['REMOTE_ADDR'];
	$user_agent =  $_SERVER['HTTP_USER_AGENT'];
	
	$id_ticket = $ticket_obj->addTicket($user_id, $_POST['user_email'], $license_id, 
			$_POST['type_help'], $_POST['ticket_topic_id'], $staff_id, $_POST['source'],
			$_POST['object'], $_POST['body'], $ip_address, $user_agent);
	
	if ($id_ticket > 0) {
		require_once 'class_notification.php';
		$not_obj = new Tutor81Notification();
		$not_obj->notifyNewTicket($id_ticket);
		$res = $id_ticket;
	} else {
		$res = 0;
	}
/*
 * INVIA NUOVO MESSAGGIO
 */
} elseif ($op_type === 'send_new_thread') {
	$user_id = $_POST['user_id'] ? : 0;
	$staff_id = $_POST['staff_id'] ? : 0;
	$ip_address = $_SERVER ['REMOTE_ADDR'];
	$user_agent =  $_SERVER['HTTP_USER_AGENT'];
	
	if (isset($_POST['closed']) && $_POST['closed'] != 0) $ticket_obj->setTicketCloseOpen($_POST['ticket_id'], 0); // riapri il ticket
	
	$id_ticket_thread = $ticket_obj->addThread($_POST['ticket_id'], $staff_id, $user_id, 
		$_POST['thread_type'], $_POST['source'], $_POST['object'], $_POST['body'], $ip_address, $user_agent);
	if ($id_ticket_thread > 0) {
		require_once 'class_notification.php';
		$not_obj = new Tutor81Notification();
		$not_obj->notifyNewThread($_POST['ticket_id'], $staff_id);
		$res = $id_ticket_thread;
	} else {
		$res = 0;
	}
/*
 * RECUPERA TICKET PER CODICE
 */
} elseif ($op_type === 'get_ticket_by_code') {
	$ticket = $ticket_obj->getTicketByCode($_POST['code']);
	if ($ticket){
		$threads = $ticket_obj->getTicketThreads($ticket['id_ticket']);
		$res = array('ticket' => $ticket, 'threads' => array());
		foreach ($threads as $thread){
			array_push($res['threads'], $thread);
		}
		$res = json_encode($res);
	} else $res = 0;	
/*
 * ASSEGNA TICKET
 */
} elseif ($op_type === 'assign_ticket') {
	$ticket = $ticket_obj->getTicketById($_POST['id_ticket']);
	if ($ticket['staff_id'] == 0){
		$assigned = $ticket_obj->assignTicket($_POST['id_ticket'], $_POST['staff_id']);
		$res = $assigned ? $_POST['staff_id'] : 0;
	} else $res = $ticket['staff_id'];	
/*
 * CHIUDI/APRI TICKET
 */
} elseif ($op_type === 'close_open_ticket') {
	$closed = $ticket_obj->setTicketCloseOpen($_POST['id_ticket'],$_POST['closed']);
	$res = $closed ? : 0;
}
echo $res;