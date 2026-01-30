<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 01-01-2025
 * File: manage/event.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_learning_event.php';

$learning_event_obj = new Tutor81LearningEvt();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


/**
 * GET UPDATE NEEDS OF SECURITY COURSES
 */
if ($op_type === 'get_update_needs') {
    $tutor_company_id = filter_input(INPUT_POST, 'tutor_company_id', FILTER_SANITIZE_NUMBER_INT);
    $res = $learning_event_obj->getUpdateNeedsByTutorCompany($tutor_company_id);
    
/**
 * CHANGE VALUE OF UPDATE_DONE
 */    
} elseif ($op_type === 'change update_done') {
    $learning_event_id = filter_input(INPUT_POST, 'learning_event_id', FILTER_SANITIZE_NUMBER_INT);
    $update_done = filter_input(INPUT_POST, 'update_done', FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    $res = $learning_event_obj->setUpdateDone($learning_event_id, $update_done);
}

echo $res;