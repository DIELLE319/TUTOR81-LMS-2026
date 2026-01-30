<?php
    require_once 'check_session.php';
    require_once 'sanitize.php';
    if (key_exists('course_id', $_GET)){
    	$course_id = sanitize($_GET['course_id'], INT);
    } elseif (key_exists('course_id', $_POST)){
    	$course_id = sanitize($_POST['course_id'], INT);
    }
    /*
    require_once 'lib/class_learning_project.php';
    require_once 'lib/class_learning_event.php';
    $learn_prj = new iWDLearningProject();
    $learning_project_detail = $learn_prj->getDetail($learning_prj_id);
    */
    require_once 'class_course.php';
    $course_obj = new iWDCourse();
    $course = $course_obj->getCourseObjectByID($course_id);
    require_once 'class_om.php';
    $om_obj = new iWDOM();
    
	// ---- DOMANDE  ----------------------------------------------------------
	require_once 'class_learning_question.php';
	$learn_que = new Tutor81QuestionObj();
	
	$total_question = $learn_que->countDistinctQuestions($course_id);
/*	
    	$unities = $learn_prj->getUnitiesByLearningProject($learning_prj_id);
    	$course = $learn_prj->getCourseDetail($unities[0]['course_id']);
*/
    	$modules = $course_obj->getModulesByCourseID($course_id);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
  	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <title>Tutor81</title>
</head>
<body>
	<div id="questions_box">
		<h1 class="title_detail">Corso: <?=$course['title']?></h1>	
		
		
		<?php
		$num_q = 0;
    	foreach($modules as $single_mo){
    		$lessons = $course_obj->getLessonsByModule($single_mo['id']);
       		foreach($lessons as $single){
        		$lo = $course_obj->getLearningObjByLesson($single['id']);
            
            	foreach($lo as $single_lo){
                  
            		if ($single_lo['learning_object_type_id'] == 1){

                   		$obj_questions = $om_obj->getVideoQuestions($single_lo['id']);
            	
            		} elseif ($single_lo['learning_object_type_id'] == 2){
                    
                    	$obj_questions = $om_obj->getSlideQuestions($single_lo['id']);
					
								} elseif ($single_lo['learning_object_type_id'] == 3){
                    
                    	$obj_questions = $om_obj->getDocQuestions($single_lo['id']);
					
								} elseif ($single_lo['learning_object_type_id'] == 4){
                    
                    	$obj_questions = $om_obj->getWebQuestions($single_lo['id']);
					
								} else {

											$obj_questions = "";

								}
					
								if (!empty($obj_questions)){
									foreach($obj_questions as $single_question){
										$question = $learn_que->getQuestionDetail($single_question['question_sentence_id']);?>
							
										<div class="question_box">
    									<h5><?=++$num_q.'. '.$question['text']?></h5>
											<ol class="answer_box">
								
										<?php $answers= $learn_que->getAnswersByQuestionID($single_question['question_sentence_id']);
			
										foreach($answers as $single_answer){?>
								
												<li><?=$single_answer["text"]?></li>
								
										<?php }?>
								
											</ol>
										</div>
					
									<?php }
								}
				
            	}
       		}
    	}
    
    	?>
	</div>
</body>