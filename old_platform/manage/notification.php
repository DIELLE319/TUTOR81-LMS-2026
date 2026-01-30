<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 28-set-2015
 * File: manage/notification.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_notification.php';

$not_obj = new Tutor81Notification();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);




/**
 * NOTIFICA ISCRIZIONE AL CORSO
 */
if ($op_type === 'notify_subscription') {
    $licenses = filter_input(INPUT_POST, 'licenses', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $licenses = json_decode($licenses, TRUE);
    if (!empty($licenses)) {
        $results = array('success' => array(), 'failed' => array());
        require_once BASE_LIBRARY_PATH . 'class_notification.php';
        $not_obj = new Tutor81Notification();
        foreach($licenses as $license_id) {
            $sending = $not_obj->notifyCourseAssignment($license_id);
            if ($sending['result']) array_push ($results['success'], $sending);
            else array_push ($results['failed'], $sending);
        }
        
        $not_obj->notifyCourseAssignmentResult($_POST['buyer_id'], $_POST['company_id'], $_POST['learning_project_id'], $results['success'], $results['failed']);
    }
    $res = 1;
    
/**
 * INVIO LINK ATTESTATO
 */
} elseif ($op_type === 'send_attestato') {
    $not_obj->notifyAttestato($_POST['license_id'], $_POST['destination_email']);
    $res = 1;
}

echo $res;