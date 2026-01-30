<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 18-ago-2015
 * File: manage/safety.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_safety.php';
$safe_obj = new Safety();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


// SET USER FUNCTION
if ($op_type == "set_user_function") {
    $res = $safe_obj->setBusinessFunctionId($_REQUEST['user_id'], $_REQUEST['business_function_id']);



// ADD USER ASSIGNMENT
} elseif ($op_type == "add_user_assignment") {
    $assign_start_date = DateTime::createFromFormat('d/m/Y', $_REQUEST['assign_start_date']);
    $assign_start_date = $assign_start_date->format('Y-m-d');
    $res = $safe_obj->addUserAssignment($_REQUEST['user_id'], $_REQUEST['assign_id'], $assign_start_date);



// EDIT USER ASSIGNMENT
} elseif ($op_type == "edit_user_assignment") {
    $assign_start_date = DateTime::createFromFormat('d/m/Y', $_REQUEST['assign_start_date']);
    $assign_end_date = DateTime::createFromFormat('d/m/Y', $_REQUEST['assign_end_date']);
    if ($assign_start_date) {
        $assign_start_date = $assign_start_date->format('Y-m-d');
        $assign_end_date = $assign_end_date ? $assign_end_date->format('Y-m-d') : '';
        $res = $safe_obj->editUserAssignment($_REQUEST['id_user_assign'], $assign_start_date, $assign_end_date);
    } else {
        if (!$assign_end_date)
            $res = $safe_obj->deleteUserAssignment($_REQUEST['id_user_assign']);
        else
            $res = "Data fine assegnazione non vuota";
    }



// ADD COURSE TYPE
} elseif ($op_type == "add_learning_need") {
    $creation_date = date('Y-m-d H:i:s');
    $code = sha1($_REQUEST['short_desc_learning_need'] . $creation_date);
    $category_id = 5;
    $deleted = 0;
    $learning_need_id = $safe_obj->addLearningNeed($_REQUEST['short_desc_learning_need'], $_REQUEST['long_desc_learning_need'], $_REQUEST['expiration_time'], $code, $_REQUEST['creator_id'], $creation_date, $_REQUEST['company_id'], $category_id, $deleted);

    $res = $learning_need_id;
    if (is_int($res) && $res > 0) {
        if ($_REQUEST['biz_func_id'])
            $res += $safe_obj->addLearningNeedBizFunc($learning_need_id, $_REQUEST['biz_func_id']);
        if ($_REQUEST['assign_id'])
            $res += $safe_obj->addLearningNeedAssign($learning_need_id, $_REQUEST['assign_id']);
        if ($_REQUEST['ateco_risk_id'])
            $res += $safe_obj->addLearningNeedAtecoRisk($learning_need_id, $_REQUEST['ateco_risk_id']);

        $ccat_list = $_REQUEST['ccat_list'];
        foreach ($ccat_list as $ccat) {
            if ($ccat)
                $res += $safe_obj->addLearningNeedCCat($learning_need_id, $ccat);
        }
    }



// SUSEPEND COURSE TYPE
} elseif ($op_type == "suspend_learning_need") {
    $res = $safe_obj->suspendLearningNeed($_REQUEST['id_learning_need'], $_REQUEST['creator_id']);




// ADD USER COURSE TYPE
} elseif ($op_type == "add_user_learning_need") {
    $execution_date = DateTime::createFromFormat('d/m/Y', $_REQUEST['execution_date']);
    $execution_date = $execution_date->format('Y-m-d');
    $res = $safe_obj->addUserLearningNeed($_REQUEST['user_id'], $_REQUEST['learning_need_id'], $execution_date, $_REQUEST['tutor_id']);



// ADD TUTOR
} elseif ($op_type == "add_tutor") {
    $res = $safe_obj->addTutor($_REQUEST['desc_tutor'], $_REQUEST['company_id']);
}

echo $res;