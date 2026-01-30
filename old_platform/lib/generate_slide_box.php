<?php
require_once 'sanitize.php';
require_once 'class_om.php';
require_once 'class_learning_question.php';

if(isset($_GET['slide_id'])){
	$id = sanitize($_GET['slide_id'], INT);
	$learn_obj = new T81DOM();
	$question_obj = new Tutor81QuestionObj();
	$res = $learn_obj->getQuestionSlideDetail($id);
	$question = $question_obj->getSentenceDetail($res['question_sentence_id']);
	$answers_list = $question_obj->getAnswersByQuestionID($res['question_sentence_id']);

	$main_img = imagecreatefrompng("../img/question-box.png");
	$mx = imagesx($main_img)-40;
	$my = imagesy($main_img);

	$main_text = $question;
	$main_text_size = 16;
	$main_text_x = ($mx/2);
	$mt_f = 'calibri.ttf';

	$main_text_color = imagecolorallocate( $main_img, 0, 0, 0 );

	$words = explode(' ', $main_text);
	$lines = array($words[0]);
	$currentLine = 0;
	for($i = 1; $i < count($words); $i++){
		$lineSize = imagettfbbox($main_text_size, 0, $mt_f, $lines[$currentLine] . ' ' . $words[$i]);
		if($lineSize[2] - $lineSize[0] < $mx){
			$lines[$currentLine] .= ' ' . $words[$i];
		}else{
			$currentLine++;
			$lines[$currentLine] = $words[$i];
		}
	}
	$line_count = 1;
	// Loop through the lines and place them on the image
	foreach ($lines as $line){
		$line_box = imagettfbbox($main_text_size, 0, $mt_f, "$line");
		$line_width = $line_box[0]+$line_box[2];
		$line_height = $line_box[1]-$line_box[7];
		$line_margin = 20;//($mx-$line_width)/2;
		$line_y = (60 +($line_height+12) * $line_count);
		imagettftext($main_img, $main_text_size, 0, $line_margin, $line_y, $main_text_color, $mt_f, $line);

		$line_count ++;
	}

	$mx -=20;
	$i = 1;
	foreach($answers_list as $answer){
		if($answer['is_correct'] == 1){
			$main_text_color = imagecolorallocate($main_img, 0, 180, 0);
		} else {

			$main_text_color = imagecolorallocate($main_img, 200, 0, 0);
		}
		$main_text = $i.") ".$answer['text'];

		$words = explode(' ', $main_text);
		$lines = array($words[0]);
		$currentLine = 0;
		for($i = 1; $i < count($words); $i++){
			$lineSize = imagettfbbox($main_text_size, 0, $mt_f, $lines[$currentLine] . ' ' . $words[$i]);
			if($lineSize[2] - $lineSize[0] < $mx){
				$lines[$currentLine] .= ' ' . $words[$i];
			}else{
				$currentLine++;
				$lines[$currentLine] = $words[$i];
			}
		}

		foreach ($lines as $line){
			$line_box = imagettfbbox($main_text_size, 0, $mt_f, "$line");
			$line_width = $line_box[0]+$line_box[2];
			$line_height = $line_box[1]-$line_box[7];
			$line_margin = 30;//($mx-$line_width)/2;
			$line_y = (60 +($line_height+12) * $line_count);
			imagettftext($main_img, $main_text_size, 0, $line_margin, $line_y, $main_text_color, $mt_f, $line);

			// Increment Y so the next line is below the previous line
			$line_count ++;
		}
			
		$i++;
	}
	imageantialias($main_img, true);
	header( "Content-type: image/png" );
	imagepng( $main_img );
	imagecolordeallocate( $main_text_color );
	imagedestroy( $main_img );
} ?>
