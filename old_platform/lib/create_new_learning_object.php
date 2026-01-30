<?php
require_once '../config.php';
require_once 'class_om.php';

// HTTP headers for no cache etc
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!empty($_FILES)) {

	ini_set('upload_max_filesize', '100M');
	ini_set('post_max_size', '120M');
	ini_set('max_input_time', 300);
	//ini_set('max_execution_time', 300);
	// 5 minutes execution time
	@set_time_limit(5 * 60);

	// Uncomment this one to fake upload time
	// usleep(5000);

	if (empty($_FILES) || $_FILES['file']['error']) {
		die('{"OK": 0, "info": "Failed to move uploaded file."}');
	}

	// Get parameters
	$chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
	$chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
	$fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

	// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
	if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {

		$path_parts = pathinfo($_FILES["file"]["name"]);
		$extension = $path_parts['extension'];

		$owner_user_id = $_REQUEST['user_owner'];
		$deleted = 0;
		$learning_object_type_id = $_REQUEST['obj_type'];
		$object_id = $_REQUEST['obj_id'];


		if ($object_id > 0){
			 
			$title = "OM".$object_id."-".$path_parts['filename'];

			$base_filename = $title.".".$extension;
			 
			if($learning_object_type_id == 1){
				if($extension == "mp4"){
					$dest_path = $base_media_path."video/mp4/".$base_filename;
				}else{
					$dest_path = $base_media_path."video/web/".$base_filename;
				}
			}elseif($learning_object_type_id == 2){
				$dest_path = $base_media_path."user_store/".$owner_user_id."/learning_objects/slide_test/".$base_filename;
			}elseif($learning_object_type_id == 3){
				$dest_path = $base_media_path."user_store/".$owner_user_id."/learning_objects/documents/".$base_filename;
			}
			 
			move_uploaded_file($_FILES['file']['tmp_name'], $dest_path);
			 
			$learn_obj = new T81DOM();
			if($learning_object_type_id == 1){
				//VIDEO
				$learn_obj->updateVideoPath($object_id,$title,$base_filename,$title);
			}elseif($learning_object_type_id == 2){
				//SLIDE
				$dir_images = $base_media_path."user_store/".$owner_user_id."/learning_objects/slide_test/images_of_".$title."/";
				mkdir($dir_images);
				$gs_out_path = $dir_images.'image%06d.png';
				$command = "gs -sDEVICE=png16m -dGraphicsAlphaBits=4 -dTextAlphaBits=4 -dDOINTERPOLATE -sOutputFile=".$gs_out_path." -dSAFER -dBATCH -dNOPAUSE ".$dest_path;
				exec($command, $output, $return_var);
				if($return_var == 0){
					$file_list = scandir($dir_images);
					$i = 0;
					// Loop all files created
					foreach($file_list as $filename){
						$file_prefix = substr($filename,0,strlen('image'));
						if($file_prefix == 'image'){
							$i++;
							$learn_obj->addImageSlide($i,$object_id,$filename);

							$new_width = 800;
							$new_height = 600;
							 
							$im = imagecreatefrompng($dir_images.$filename);
							 
							$original_width = imagesx($im);
							$original_height = imagesy($im);
							 
							$tmpimg = imagecreatetruecolor($new_width, $new_height);
							imagecopyresized( $tmpimg, $im, 0, 0, 0, 0,$new_width, $new_height, $original_width, $original_height );
							imagecopyresampled($tmpimg, $im, 0, 0, 0, 0,$new_width, $new_height, $original_width, $original_height );
							imagepng($tmpimg, $dir_images.$filename);
						}
					}
				}
				$learn_obj->updateSlidePath($object_id,$title,$base_filename,$title);
			}elseif($learning_object_type_id == 3){
				//DOCUMENT
				$learn_obj->updateDocumentPath($object_id,$title,$base_filename,$title);
			}
		}
		$learn_obj->closeiWDOM();
		echo $object_id;
	}

}
?>
