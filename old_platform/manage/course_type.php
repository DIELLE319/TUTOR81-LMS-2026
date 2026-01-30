<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 4-set-2015
 * File: manage/course_type.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';

$course_type_obj = new T81CourseType();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


/* AGGIUNGE UN NUOVO CORSO TIPO PER LE AULE */
if ($op_type === "add_course_type") {
    $res = $course_type_obj->addCourseType($_POST) ? : 0;

    
/* RESTITUISCE I DATI DEL CORSO TIPO */   
} elseif ($op_type === "get_course_detail"){
    $course_type = $course_type_obj->getCourseTypeDetail($_POST['id_course_type']);
    $res = $course_type ? json_encode($course_type) : 0;
}

echo $res;