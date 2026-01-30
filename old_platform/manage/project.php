<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 18-ago-2015
 * File: manage/project.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'sanitize.php';

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

if ($op_type == 'edit_project') {
    $id = $_POST['id'];
    $l_title = $_POST['l_title'];
    $txt_desc_ita = $_POST['txt_desc_ita'];
    $arrCompany = array();
    if (isset($_POST['arrCompany'])) {
        $arrCompany = $_POST['arrCompany'];
    }
    $learn = new T81LearningProject();
    $res = $learn->editProject($id, $l_title, $txt_desc_ita, $arrCompany);
} elseif ($op_type == 'change_status') {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $learn = new T81LearningProject();
    $res = $learn->changeStatus($id, $status);
} elseif ($op_type == 'create_learning_project_from_course') {
    $course_id = $_POST['course_id'];
    require_once BASE_LIBRARY_PATH . 'class_course.php';
    $course_obj = new iWDCourse();
    $course = $course_obj->getCourseObjectByID($course_id);
    // creazione codice e titolo corso
    $subcategory_detail = $course_obj->getDetailSubcategory($course['subcategory_id']);
    $type_detail = $course_obj->getDetailType($course['type_id']);
    $custom_categories = $course_obj->getCourseCustomCategories($course_id);
    $title = $subcategory_detail['abrv'] . sprintf('%02d', $subcategory_detail['position']) . $type_detail['abrv'];
    for ($i = count($custom_categories) - 1; $i >= 0; $i--) {
        $title .= $custom_categories[$i]['abrv'];
    }
    $title .= " - " . $course['title'];
    // fine creazione codice e titolo corso
    $description = $course['description'];
    $owner_user_id = $course['owner_user_id'];
    $is_published_in_ecommerce = 1;
    $course_cover_image = $course['ecommerce_image_filename'];
    $learn_obj = new T81LearningProject();
    $res = $learn_obj->create($title, $description, $owner_user_id, $is_published_in_ecommerce, $course_cover_image);
    if ($res > 0) {
        $res += $learn_obj->addCourseUnities($res, $course_id);
    }
    
/**
 * Calcola la percentuale di esecuzione del corso
 */
} elseif ($op_type === 'calc_progress_rate') {
    $learn_obj = new T81LearningProject();
    $learning_project_users = json_decode($_POST['learning_project_users']);
    $learning_project_users = !is_array($learning_project_users) ? array($learning_project_users) : $learning_project_users;
    $learn_obj = new T81LearningProject();
    $execution_percentage = array();
    foreach ($learning_project_users as $lpu){
        $num_lo = $learn_obj->get_num_learning_objects($lpu->learning_project_id);
        $num_exe_lo = $learn_obj->get_num_lo_executed($lpu->id);
        $execution_percentage[$lpu->id] = ($num_exe_lo != 0 ? intval(floor(($num_exe_lo * 100) / $num_lo)) : 0);
        /* update learning_events table with the number of lo in course and calculated progress rate */
        require_once BASE_LIBRARY_PATH . 'class_learning_event.php';
        $learning_event_obj = new Tutor81LearningEvt();
        $updated = $learning_event_obj->setTotalNumLO($lpu->learning_event_id, $num_lo);
        $updated = $learning_event_obj->setProgressRate($lpu->learning_event_id, $execution_percentage[$lpu->id]);
    }
    $res = !empty($execution_percentage) ? json_encode($execution_percentage) : 0;
}


echo $res;