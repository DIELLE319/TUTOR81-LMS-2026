<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 01-lug-2015
 * File: report/course-detail.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'class_learning_question.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_db.php';

$comp = new T81Company();

$learning_project_user_id = filter_input(INPUT_GET, 'learning_project_user_id',FILTER_SANITIZE_NUMBER_INT);

$learn_prj_event = $comp->getUserLearningProject($learning_project_user_id);

$license = $learn_prj_event['learning_project_pwd'];
$course_id = $learn_prj_event['learning_project_id'];

$db = new MySQLConn();

$query = "SELECT learning_events.* FROM learning_project_users JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id  WHERE learning_project_pwd = '".$license."'";
$res = $db->query($query);

$learning_event_id = $res[0]['id'];

$learn_que = new Tutor81QuestionObj();
$learn_obj = new T81LearningProject();

$unities = $learn_obj->getUnitiesByLearningProject($course_id);
$course = $learn_obj->getCourseDetail($unities[0]['course_id']);
$modules = $learn_obj->getModulesByCourseID($course['id']);
$total_question = 0;

$risposte = 0;
$non_risposte = 0;
echo "<i style='font-size:9px'>Generato il ". date("d/m/Y H:i:s")."</i><br/>";
foreach($modules as $single_mo){
	$lessons = $learn_obj->getLessonsByModule($single_mo['id']);
	foreach($lessons as $single){
		$lo = $learn_obj->getLearningObjByLesson($single['id']);
		foreach($lo as $single_lo){
			echo "<h5>".$single_lo['title']."</h5>";
			echo '<table class="table table-striped" border="1">';
			echo '    <thead>';
			echo '        <th>Domanda</th>';
			echo '        <th>Risposta</th>';
			echo '    </thead>';
			echo '    <tbody>';
			if ($single_lo['learning_object_type_id'] == 1){
				$query = "SELECT * FROM video_test_interruption_points JOIN video_test_interruption_point_questions ON video_test_interruption_points.id = video_test_interruption_point_id WHERE learning_object_id = ".$single_lo['id'];
				$res_video = $db->query($query);

				foreach($res_video as $single_question){
					$qu = $learn_que->getQuestionDetail($single_question['question_sentence_id']);
					$query = "SELECT COUNT(*) as conta FROM learning_event_questions WHERE learning_event_id = ".$learning_event_id." AND question_sentence_id = ".$single_question['question_sentence_id'];
					$conta = $db->query($query);

					if($conta[0]['conta'] == 0){
						$non_risposte++;
					} else {
						echo "<tr>";
						echo "<td>".$qu['text']."</td>";
						echo "<td>RISPOSTO</td>";
						echo "</tr>";
						$risposte++;
					}
				}

			}elseif ($single_lo['learning_object_type_id'] == 2){
				$query = "SELECT * FROM slides WHERE is_question = 1 AND learning_object_id = ".$single_lo['id'];
				$res_slide = $db->query($query);
				foreach($res_slide as $single_question){
					$query = "SELECT * FROM slide_test_questions WHERE slide_id = ".$single_question['id'];
					$res = $db->query($query);
					$qu = $learn_que->getQuestionDetail($res[0]['question_sentence_id']);
					$query = "SELECT COUNT(*) as conta FROM learning_event_questions WHERE learning_event_id = ".$learning_event_id." AND question_sentence_id = ".$res[0]['question_sentence_id'];
					$conta = $db->query($query);
					if($conta[0]['conta'] == 0){
						$non_risposte++;
					} else {					 
						echo "<tr>";
						echo "<td>".$qu['text']."</td>";
						echo "<td>RISPOSTO</td>";
						echo "</tr>";
						$risposte++;
					}
				}
			}elseif ($single_lo['learning_object_type_id'] == 3){
				$query = "SELECT * FROM doc_questions WHERE doc_id = {$single_lo['id']}";
				$res_doc = $db->query($query);
				foreach($res_doc as $single_question){
					$qu = $learn_que->getQuestionDetail($single_question['question_sentence_id']);
					$query = "SELECT COUNT(*) as conta FROM learning_event_questions WHERE learning_event_id = $learning_event_id AND question_sentence_id = {$single_question['question_sentence_id']}";
					$conta = $db->query($query);
					if($conta[0]['conta'] == 0){
						$non_risposte++;
					} else {					 
						echo "<tr>";
						echo "<td>".$qu['text']."</td>";
						echo "<td>RISPOSTO</td>";
						echo "</tr>";
						$risposte++;
					}
				}
			}elseif ($single_lo['learning_object_type_id'] == 4){
				$query = "SELECT * FROM web_questions WHERE web_id = {$single_lo['id']}";
				$res_web = $db->query($query);
				foreach($res_web as $single_question){
					$qu = $learn_que->getQuestionDetail($single_question['question_sentence_id']);
					$query = "SELECT COUNT(*) as conta FROM learning_event_questions WHERE learning_event_id = $learning_event_id AND question_sentence_id = {$single_question['question_sentence_id']}";
					$conta = $db->query($query);
					if($conta[0]['conta'] == 0){
						$non_risposte++;
					} else {					 
						echo "<tr>";
						echo "<td>".$qu['text']."</td>";
						echo "<td>RISPOSTO</td>";
						echo "</tr>";
						$risposte++;
					}
				}
			}
			echo "</tbody>";
			echo "</table>";
		}
	}
}
?>