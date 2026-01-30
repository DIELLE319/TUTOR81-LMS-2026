<?php

error_reporting(0);
require_once '../config.php';
require_once 'class_learning_project.php';
$learn_id = sanitize($_POST['learn_id'], INT);

function gen_filename($name)
{
	$filename = strtolower(trim($name));
	$filename = str_replace(' ', '-', $filename);
	$filename = preg_replace('/[^a-zA-Z0-9\-]/','', $filename);
	$filename = preg_replace('/-+/','-', $filename);
	$filename = trim(substr($filename, 0, 30), '-');
	return $filename;
}

function getUniqueCode($length = "")
{
	$code = md5(uniqid(rand(), true));
	if ($length != "") return substr($code, 0, $length);
	else return $code;
}

function file_extension($filename)
{
	$path_info = pathinfo($filename);
	return $path_info['extension'];
}

// HTTP headers for no cache etc
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


// 5 minutes execution time
@set_time_limit(5 * 60);

// Uncomment this one to fake upload time
// usleep(5000);

// Get parameters
$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

// Clean the fileName for security reasons
$fileName = preg_replace('/[^\w\._]+/', '', $fileName);

// Look for the content type header
if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
	$contentType = $_SERVER["HTTP_CONTENT_TYPE"];

if (isset($_SERVER["CONTENT_TYPE"]))
	$contentType = $_SERVER["CONTENT_TYPE"];

$elem_id = $_REQUEST['id_elem'];

// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
	$learn = new T81LearningProject();
	$learn_dett = $learn->getDetail($learn_id);
	$fileName = getUniqueCode().".".file_extension($_FILES['file']['name']);
	move_uploaded_file($_FILES['file']['tmp_name'], "../{$base_media_path}user_store/{$learn_dett['owner_user_id']}/learning_projects/ecommerce_images/thumb/{$learn_dett['ecommerce_image_filename']}");
	//$learn->addPhoto($elem_id,$fileName);
	//$learn->closeiWDLearningProject();
} else{
	die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
}
die('SUCCESS');