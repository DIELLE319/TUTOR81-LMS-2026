<?php
require_once 'class_question_object.php';
require_once 'class_om.php';
require_once 'sanitize.php';
require_once 'function.php';
$op_type = $_POST['op_type'];
  
$learn_obj = new iWDOM();
if($op_type == "creat_new"){
	$title = sanitize($_POST['obj_title'], PARANOID);
	
	$owner_user_id = $_POST['owner_user_id'];
	$learning_object_type_id = $_POST['obj_type'];
	$arg = $_POST['obj_arg'];
	$language_id = $_POST['obj_lang'];
	$duration = $_POST['obj_dura'];
	$percentage_correct_answer_to_pass = $_POST['obj_perc'];
	$description = $_POST['obj_descr'];
	$published_in_ecommerce = 0;
	$obj_cat = $_POST['obj_cat'];
	$level_id = $_POST['obj_level'];
	$custom = $_POST['custom'];
	$type_id = $_POST['type_id'];

	$obj_lesson = 0;
	$obj_module = 0;

	$object_id = $learn_obj->createNewLearningObject($owner_user_id,$learning_object_type_id,$language_id,
			$percentage_correct_answer_to_pass,$description,$duration,$published_in_ecommerce,$obj_cat,$arg,$obj_lesson,$obj_module,$level_id, $title, $custom,$type_id);
	if ($object_id > 0){
		$title = trim($title);
		$code = sha1($title.date("y-m-d h:m:i"));
		$learn_obj->updateTitle($object_id, $title);
		$learn_obj->updateCode($object_id, $code);
		$obj_om = $_POST['obj_om'];
		$temp_path = "../media/user_store/$owner_user_id/learning_objects/temp";

		//$new_name = preg_replace('/\s/', '_', $title);
		$new_name = toAscii($title);
		$new_name = "OM$object_id-$new_name";
		
		if($learning_object_type_id == 1){
			
			foreach ($obj_om as $om){
				$filename = "$temp_path/$om";
				$path_parts = pathinfo($filename);
				$video_path = $path_parts['extension']=='mp4'?'mp4':'web';
				chmod($filename, 0777);
				rename($filename, "../media/video/$video_path/$new_name.{$path_parts['extension']}");
				$learn_obj->updateVideoPath($object_id,$new_name,$new_name);
			}
			
		}elseif($learning_object_type_id == 2){
			
			$filename = "$temp_path/$obj_om[0]";
			$dest_path = "../media/user_store/$owner_user_id/learning_objects/slide_test/$new_name.pdf";
			chmod($filename, 0755);
			rename($filename, $dest_path);
			$dir_images = "../media/user_store/$owner_user_id/learning_objects/slide_test/images_of_$new_name/";
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
			$learn_obj->updateSlidePath($object_id,"$new_name.pdf",$new_name);
			
		}elseif($learning_object_type_id == 3){

			$filename = "$temp_path/$obj_om[0]";
			chmod($filename, 0755);
			rename($filename, "../media/user_store/$owner_user_id/learning_objects/documents/$new_name.pdf");
			$learn_obj->updateDocumentPath($object_id,"$new_name.pdf","$new_name.pdf");

		}elseif($learning_object_type_id == 4){
			$dirname = "$temp_path/$obj_om[0]";
			unlink("$dirname/edit.php");
			unlink("$dirname/update.php");
			//chmod($dirname, 0755);
			recurseChmod($dirname);
			rename($dirname, "../media/user_store/$owner_user_id/learning_objects/web/$new_name");
			$learn_obj->updateWebPath($object_id,"$new_name","$new_name");

		}
		
	}
	
	
	
	$res = $object_id;
	
} elseif ($op_type == 'disable_it'){
	$id = sanitize($_POST['id'],INT);
	$res = $learn_obj->disableByID($id);
}elseif ($op_type == 'enable_it'){
	$id = sanitize($_POST['id'],INT);
	$res = $learn_obj->enableByID($id);
}elseif($op_type == "edit_learn"){
	$id = $_POST['id'];
	$language_id = $_POST['language_id'];
	$percentage_correct_answer_to_pass = $_POST['percentage'];
	$description = $_POST['description'];
	$duration = $_POST['duration'];
	$title = $_POST['title'];
	$argument_id = $_POST['argument_id'];
	$level_id = $_POST['level_id'];
	$obj_cat = $_POST['obj_cat'];
	$type_id = $_POST['type_id'];
	$custom = $_POST['custom'];
	$res = $learn_obj->editLearningObject($id, $language_id,
			$percentage_correct_answer_to_pass, $description, $duration, $title, $argument_id, $level_id, $obj_cat, $type_id, $custom);
}elseif($op_type == "edit_learn_tags"){
	$id = $_POST['id'];
	$argument_id = $_POST['argument_id'];
	$level_id = $_POST['level_id'];
	$obj_cat = $_POST['obj_cat'];
	$type_id = $_POST['type_id'];
	$custom = $_POST['custom'];
	$res = $learn_obj->editLearnTags($id, $argument_id, $level_id, $obj_cat, $type_id, $custom);
}elseif($op_type == "new_question"){
	$learn_obj_type = $_POST['learn_obj_type'];
	$learn_obj_id = $_POST['learn_obj_id'];
	$question_txt = $_POST['question_txt'];
	$question_time = sanitize($_POST['question_time'],INT);
	$answers_txt = $_POST['answers_txt'];
	$answers_correct_txt = $_POST['answers_correct_txt'];

	$question_obj = new QuestionObject();
	$question_id = $question_obj->newQuestion($question_txt);
	$x = 0;
	foreach($answers_txt as $single){
		$question_obj->addAnswersToQuestion($single, $question_id, $answers_correct_txt[$x]);
		$x++;
	}
	if ($learn_obj_type == 1){
		$id = $learn_obj->newInterruptPoint($learn_obj_id,$question_time);
		$res = $learn_obj->newInterruptPointQuestion($id,$question_id);
	}elseif ($learn_obj_type == 2){
		$id = $learn_obj->newSlide($learn_obj_id,$question_time);
		$res = $learn_obj->newSlideTestQuestion($id, $question_id);
		$learn_obj->updateSlidePosition($learn_obj_id);
	}elseif($learn_obj_type == 3){
		$res = $learn_obj->addDocQuestion($question_id, $learn_obj_id);
	}elseif($learn_obj_type == 4){
		$res = $learn_obj->addWebQuestion($question_id, $learn_obj_id);
	}
}elseif($op_type == "update_question_time"){
	$id = $_POST['quest_id'];
	$time_question = $_POST['time_question'];
	$res = $learn_obj->editQuestionTime($id,$time_question);
}elseif($op_type == "update_slide_question_position"){
	$slide_id = $_POST['slide_id'];
	$position = $_POST['position'];
	$res = $learn_obj->editSlideQuestionPosition($slide_id, $position);
}elseif($op_type == "load_web_template"){
	$dirname = sanitize($_POST['dirname'], PARANOID);
	$user_id = sanitize($_POST['user_id'], INT);
	$path = "../media/template/web_objects/$dirname";
	if (is_dir($path) && $user_id > 0){
		$new_dir = uniqid("web_$dirname");
		$dest = "../media/user_store/$user_id/learning_objects/temp/$new_dir";
		
		//recurseCopy($path, "../media/user_store/$user_id/learning_objects/temp/$new_dir");
		
		$dest = "../media/user_store/$user_id/learning_objects/temp/$new_dir";
		shell_exec(" mkdir $dest; cp -R -a $path/* $dest/ 2>&1; chmod -R 0777 $dest/assets ");
		$res = $new_dir;
		
	} else {
		$res = 0;
	}
}
echo $res;
    
?>