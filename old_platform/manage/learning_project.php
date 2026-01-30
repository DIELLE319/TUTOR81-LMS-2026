<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 4-set-2015
 * File: manage/learning_project.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

$learning_project_obj = new T81LearningProject();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


/* AGGIUNGE UN NUOVO CORSO TIPO PER LE AULE */
if ($op_type === "get_learning_project_detail") {
    $learning_project = $learning_project_obj->getCourseDetailFromLearningProject($_POST['learning_project_id']);
    $res = $learning_project ? json_encode($learning_project) : 0;
    
    
/* RESTITUISCE I PREZZI DEL CORSO */
} elseif ($op_type === 'get_learning_project_price') {
    $prices = $learning_project_obj->getPrices($_POST['learning_project_id']);
    $res = $prices ? json_encode($prices) : 0;
}

echo $res;