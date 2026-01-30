<?php

// HTTP headers for no cache etc
/*header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
 header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");*/

if (!empty($_FILES)) {
	require_once 'class_course.php';

	// 5 minutes execution time
	@set_time_limit(5 * 60);

	// Uncomment this one to fake upload time
	// usleep(5000);

	// Get parameters
	$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
	$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
	$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

	// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
	if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {

		$owner_user_id = $_REQUEST['user_owner'];
		$course_id = $_REQUEST['course_id'];

		//move_uploaded_file($_FILES['file']['tmp_name'], '../archivio/'.$file_name);
		$course_obj = new iWDCourse();
		$elem = $course_obj->getCourseObjectByID($course_id);
		$course_obj->closeiWDCourse();
		$dest_path = "../media/user_store/".$owner_user_id."/courses/ecommerce_images/thumb/".$elem['ecommerce_image_filename'];
		if (file_exists($dest_path)){
			unlink($dest_path);
		}
		move_uploaded_file($_FILES['file']['tmp_name'], $dest_path);
		echo $course_id;
	}

}
?>
