<?php

/*
 * To change this template, choose Tools | Templates
* and open the template in the editor.
*/
require_once 'class_learning_project.php';
require_once 'sanitize.php';
$op_type = $_POST['op_type'];
if($op_type == 'edit_project'){
	$id = $_POST['id'];
	$l_title = $_POST['l_title'];
	$txt_desc_ita = $_POST['txt_desc_ita'];
	$arrCompany = array();
	if(isset($_POST['arrCompany'])){
		$arrCompany = $_POST['arrCompany'];
	}
	$learn = new iWDLearningProject();
	$res = $learn->editProject($id,$l_title,$txt_desc_ita,$arrCompany);
}elseif($op_type == 'change_status'){
	$id = $_POST['id'];
	$status = $_POST['status'];
	$learn = new iWDLearningProject();
	$res = $learn->changeStatus($id,$status);
}elseif($op_type == 'create_learning_project_from_course'){
	$course_id = $_POST['course_id'];
	require_once 'class_course.php';
	$course_obj = new iWDCourse();
	$course = $course_obj->getCourseObjectByID($course_id);
	// creazione codice e titolo corso
	$subcategory_detail = $course_obj->getDetailSubcategory($course['subcategory_id']);
	$type_detail = $course_obj->getDetailType($course['type_id']);
	$custom_categories = $course_obj->getCourseCustomCategories($course_id);
	$title = $subcategory_detail['abrv'].sprintf('%02d',$subcategory_detail['position']).$type_detail['abrv'];
	for ($i = count($custom_categories)-1; $i >= 0; $i--){
		$title .= $custom_categories[$i]['abrv'];
	}
	$title .= " - ".$course['title'];
	// fine creazione codice e titolo corso
	$description = $course['description'];
	$owner_user_id = $course['owner_user_id'];
	$is_published_in_ecommerce = 1;
	$course_cover_image = $course['ecommerce_image_filename'];
	$learn_obj = new iWDLearningProject();
	$res = $learn_obj->create($title, $description, $owner_user_id, $is_published_in_ecommerce, $course_cover_image);
	if($res > 0){
		$res += $learn_obj->addCourseUnities($res, $course_id);
	}
}
echo $res;
?>
