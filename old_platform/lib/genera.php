<?php
if (key_exists('license_id', $_GET)){
	require_once 'class_license.php';
	$license = new Tutor81License();
	$license_id = $_GET['license_id'];
	$license->generateLicense($license_id);
} elseif (key_exists('course_id', $_GET)){
	require_once 'class_attestato.php';
	$attestato = new Tutor81Attestato();
	$license_id = $_GET['course_id'];
	$attestato->generatePDF($license_id);
}
?>