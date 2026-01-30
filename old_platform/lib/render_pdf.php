<?php

$file = base64_decode($_GET['file']);
header('Content-type: application/pdf');
header('Content-Disposition: inline; filename="the.pdf"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($file));
@readfile($file);

/*
if(!isset($_GET['file']))die('LOGGED! no file specified');

$file_path=$_SERVER['DOCUMENT_ROOT'].'/'.strip_tags(htmlentities($_GET['file'])).'.pdf';

if ($fp = fopen ($file_path, "r")) {
	$file_info = pathinfo($file_path);
	$file_name = $file_info["basename"];
	$file_size = filesize($file_path);
	$file_extension = strtolower($file_info["extension"]);

	if($file_extension!='pdf') {
		die('LOGGED! bad extension');
	}

	ob_start();
	header('Content-type: application/pdf');
	header('Content-Disposition: attachment; filename="'.$file_name.'"');
	header("Content-length: $file_size");
	ob_end_flush();

	while(!feof($fp)) {
		$file_buffer = fread($fp, 2048);
		echo $file_buffer;
	}

	fclose($fp);
	exit;
	exit();
} else {
	die('LOGGED! bad file '.$file_path);
}
*/