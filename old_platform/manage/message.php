<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 18-ago-2015
 * File: manage/message.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'sanitize.php';
require_once BASE_LIBRARY_PATH . 'class_messages.php';

$message_obj = new Tutor81Messages();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

/* * *** CREAZIONE NUOVO MESSAGGIO *** */
if ($op_type === 'create_new_message') {
    $res = $message_obj->addMessage($_POST['from_user_id'], $_POST['object'], $_POST['message_text'], $_POST['recipients']);
    $res = $res ? 1 : 0;

    /*     * *** CREAZIONE NUOVO TICKER *** */
} elseif ($op_type === 'create_new_ticker') {
    $res = $message_obj->addMessage($_POST['from_user_id'], $_POST['object'], $_POST['message_text'], array(), $_POST['companies'], $_POST['in_news_ticker']);
    $res = $res ? 1 : 0;

    /*     * *** IMPOSTA IN NEWS TICKER *** */
} elseif ($op_type === 'set_in_news_ticker') {
    $res = $message_obj->setInNewsTicker($_POST['id_message'], $_POST['in_news_ticker']);
    $res = $res ? : 0;

    /*     * *** LETTURA MESSAGGIO *** */
} elseif ($op_type === 'message_read') {
    $res = $message_obj->setMessageReadByRecipient($_POST['id_recipient']);
    $res = $res ? : 0;
}

echo $res;