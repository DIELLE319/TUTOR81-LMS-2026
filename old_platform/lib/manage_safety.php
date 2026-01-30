<?php
require_once 'class_safety.php';
$safe_obj = new Safety();

$op_type = $_REQUEST['op_type'];


// SET USER FUNCTION
if ($op_type == "set_user_function"){
	$res = $safe_obj->setBusinessFunctionId($_REQUEST['user_id'], $_REQUEST['business_function_id']);
	
	
	
// ADD USER ASSIGNMENT
} elseif ($op_type == "add_user_assignment"){
	$assign_start_date = DateTime::createFromFormat('d/m/Y', $_REQUEST['assign_start_date']);
	$assign_start_date = $assign_start_date->format('Y-m-d');
	$res = $safe_obj->addUserAssignment($_REQUEST['user_id'], $_REQUEST['assign_id'], $assign_start_date);
	
	
	
// EDIT USER ASSIGNMENT
}	elseif ($op_type == "edit_user_assignment"){
	$assign_start_date = DateTime::createFromFormat('d/m/Y', $_REQUEST['assign_start_date']);
	$assign_end_date = DateTime::createFromFormat('d/m/Y', $_REQUEST['assign_end_date']);
	if ($assign_start_date) {
		$assign_start_date = $assign_start_date->format('Y-m-d');
		$assign_end_date = $assign_end_date ? $assign_end_date->format('Y-m-d') : '';
		$res = $safe_obj->editUserAssignment($_REQUEST['id_user_assign'], $assign_start_date, $assign_end_date);
	} else {
		if (!$assign_end_date) $res = $safe_obj->deleteUserAssignment($_REQUEST['id_user_assign']);
		else $res = "Data fine assegnazione non vuota";
	}

	

// ADD COURSE TYPE
} elseif ($op_type == "add_course_type"){
	$creation_date = date('Y-m-d H:i:s');
	$code = sha1($_REQUEST['short_desc_course_type'].$creation_date);
	$category_id = 5;
	$deleted = 0;
	$course_type_id = $safe_obj->addCourseType($_REQUEST['short_desc_course_type'], $_REQUEST['long_desc_course_type'],
			 $_REQUEST['expiration_time'], $code, $_REQUEST['creator_id'], $creation_date, $_REQUEST['company_id'], $category_id, $deleted);
	
	$res = $course_type_id;
	if (is_int($res) && $res > 0) {
		if ($_REQUEST['biz_func_id']) $res += $safe_obj->addCourseTypeBizFunc($course_type_id, $_REQUEST['biz_func_id']);
		if ($_REQUEST['assign_id']) $res += $safe_obj->addCourseTypeAssign($course_type_id, $_REQUEST['assign_id']);
		if ($_REQUEST['ateco_risk_id']) $res += $safe_obj->addCourseTypeAtecoRisk($course_type_id, $_REQUEST['ateco_risk_id']);
	
		$ccat_list = $_REQUEST['ccat_list'];
		foreach ($ccat_list as $ccat){
			if ($ccat) $res += $safe_obj->addCourseTypeCCat($course_type_id, $ccat);
		}
	}
	

	
// SUSEPEND COURSE TYPE
} elseif ($op_type == "suspend_course_type"){
	$res = $safe_obj->suspendCourseType($_REQUEST['id_course_type'], $_REQUEST['creator_id']);

	
	
	
// ADD USER COURSE TYPE
} elseif ($op_type == "add_user_course_type"){
	$execution_date = DateTime::createFromFormat('d/m/Y', $_REQUEST['execution_date']);
	$execution_date = $execution_date->format('Y-m-d');
	$res = $safe_obj->addUserCourseType($_REQUEST['user_id'], $_REQUEST['course_type_id'], $execution_date, $_REQUEST['tutor_id']);
	
	

// ADD TUTOR
} elseif ($op_type == "add_tutor"){
	$res = $safe_obj->addTutor($_REQUEST['desc_tutor'], $_REQUEST['company_id']);
		
	
	
	
}

echo $res;