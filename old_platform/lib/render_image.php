<?php

/*
 * To change this template, choose Tools | Templates
* and open the template in the editor.
*/
$file = base64_decode($_GET['file']);
$thumb = 0;
if (isset($_GET['thumb'])){
	$thumb = $_GET['thumb'];
}
$image_stream = file_get_contents($file);
$im = imagecreatefromstring($image_stream);
$original_width = imagesx($im);
$original_height = imagesy($im);

if ($thumb == 1){
	$max_width = 600;
	$max_height = 300;
}else{
	$max_width = $original_width;
	$max_height = $original_height;
}

if (($original_width > $max_width) || ($original_height > $max_height)){
	//original width exceeds, so reduce the original width to maximum limit.
	//calculate the height according to the maximum width.
	if(($original_width > $max_width) && ($original_height <= $max_height))
	{
		$percent = $max_width/$original_width;
		$new_width = $max_width;
		$new_height = round ($original_height * $percent);
	}

	//image height exceeds, recudece the height to maxmimum limit.
	//calculate the width according to the maximum height limit.
	if(($original_width <= $max_width) && ($original_height > $max_height))
	{
		$percent = $max_height/$original_height;
		$new_height = $max_height;
		$new_width = round ($original_width * $percent);
	}

	//both height and width exceeds.
	//but image can be vertical or horizontal.
	if(($original_width > $max_width) && ($original_height > $max_height))
	{
		//if image has more width than height
		//resize width to maximum width.
		if ($original_width > $original_height)
		{
			$percent = $max_width/$original_width;
			$new_width = $max_width;
			$new_height = round ($original_height * $percent );
		}

		//image is vertical or square. More height than width.
		//resize height to maximum height.
		else
		{
			$new_height = $max_height;
			$percent = $max_height/$original_height;
			$new_height = $max_height;
			$new_width = round ($original_width * $percent);
		}
	}
}else{
	$new_width = $original_width;
	$new_height = $original_height;
}
if ($thumb == 1){
	$new_width = 400;
	$new_height = 250;
}
$tmpimg = imagecreatetruecolor($new_width, $new_height);
imagecopyresized( $tmpimg, $im, 0, 0, 0, 0,$new_width, $new_height, $original_width, $original_height );
imagecopyresampled( $tmpimg, $im, 0, 0, 0, 0,$new_width, $new_height, $original_width, $original_height );
header("Content-Type: image/jpeg");
imagejpeg($tmpimg);
?>
