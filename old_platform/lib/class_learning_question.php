<?php
require_once 'class_db.php';
require_once 'class_learning_project.php';
require_once 'sanitize.php';

class Tutor81QuestionObj{

	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}

	public function getQuestionDetail($id){
		$id = sanitize($id, INT);
		$query = "SELECT * FROM question_sentences WHERE id = ".$id;
		$res = $this->db_conn->query($query);
		return $res[0];
	}

	public function getSentenceDetail($id){
		$id = sanitize($id, INT);
		$query = "SELECT text FROM question_sentences WHERE id =  ".$id;
		$res = $this->db_conn->query($query);
		return $res[0]['text'];
	}

	public function getAnswersByQuestionID($id){
		$id = sanitize($id, INT);
		$query = "SELECT * FROM answers WHERE question_sentence_id =  ".$id;
		$res = $this->db_conn->query($query);
		return $res;
	}

	public function getAnswerFromQuestion($question_id){
		$question_id = sanitize($question_id, INT);
		$query = "SELECT learning_event_question_answers.*,text,is_correct FROM learning_event_question_answers JOIN answers ON answer_id=answers.id WHERE learning_event_question_id = ".$question_id;
		$res = $this->db_conn->query($query);
		return $res;
	}

	public function countQuestionsVideoID($video_id){
		$video_id = sanitize($video_id, INT);
		$query = "SELECT COUNT(*) as conta FROM video_test_interruption_points JOIN video_test_interruption_point_questions ON video_test_interruption_points.id = video_test_interruption_point_id WHERE learning_object_id = ".$video_id;
		$res = $this->db_conn->query($query);
		return $res[0]['conta'];
	}

	public function countQuestionsSlideID($slide_id){
		$slide_id = sanitize($slide_id, INT);
		$query = "SELECT COUNT(*) as conta FROM slides WHERE is_question = 1 AND learning_object_id = ".$slide_id;
		$res = $this->db_conn->query($query);
		return $res[0]['conta'];
	}
 
 	public function countQuestionsDocID($doc_id){
   	$doc_id = sanitize($doc_id, INT);
   	$query = "SELECT COUNT(*) as conta FROM doc_questions WHERE doc_id = $doc_id";
   	$res = $this->db_conn->query($query);
   	return $res[0]['conta'];
  }

  public function countQuestionsWebID($web_id){
  	$web_id = sanitize($web_id, INT);
  	$query = "SELECT COUNT(*) as conta FROM web_questions WHERE web_id = $web_id";
  	$res = $this->db_conn->query($query);
  	return $res[0]['conta'];
  }
	
	public function countQuestionsAnswered($learning_event_id){
		$learning_event_id = sanitize($learning_event_id, INT);
		$query = "SELECT COUNT(DISTINCT learning_event_questions.question_sentence_id) as conta FROM learning_event_questions WHERE learning_event_id = ".$learning_event_id;
		$res = $this->db_conn->query($query);
		return $res[0]['conta'];
	}

	public function countQuestionsCorrect($learning_event_id){
		$learning_event_id = sanitize($learning_event_id, INT);
		$query = "SELECT COUNT(DISTINCT learning_event_questions.question_sentence_id) as conta FROM learning_event_questions JOIN learning_event_question_answers ON learning_event_question_answers.learning_event_question_id = learning_event_questions.id JOIN answers ON answers.id = learning_event_question_answers.answer_id WHERE learning_event_id = ".$learning_event_id." AND is_correct = 1";
		$res = $this->db_conn->query($query);
		return $res[0]['conta'];
	}

	public function countQuestionsWrong($learning_event_id){
		$learning_event_id = sanitize($learning_event_id, INT);
		$query = "SELECT COUNT(DISTINCT learning_event_questions.question_sentence_id) as conta FROM learning_event_questions JOIN learning_event_question_answers ON learning_event_question_answers.learning_event_question_id = learning_event_questions.id JOIN answers ON answers.id = learning_event_question_answers.answer_id WHERE learning_event_id = ".$learning_event_id." AND is_correct = 0";
		$res = $this->db_conn->query($query);
		return $res[0]['conta'];
	}

public function countQuestions($learning_prj_id){
            $learning_prj_id = sanitize($learning_prj_id, INT);
            $learn_obj = new T81LearningProject();
            $unities = $learn_obj->getUnitiesByLearningProject($learning_prj_id);
            $course = $learn_obj->getCourseDetail($unities[0]['course_id']);
            $modules = $learn_obj->getModulesByCourseID($course['id']);
            $total_question = 0;
            $total_question_video = 0;
            $total_question_slide = 0;
            $total_question_doc = 0;
            $total_question_web = 0;
            foreach($modules as $single_mo){
                $lessons = $learn_obj->getLessonsByModule($single_mo['id']);
                foreach($lessons as $single){
                    $lo = $learn_obj->getLearningObjByLesson($single['id']);
                    foreach($lo as $single_lo){
                        if ($single_lo['learning_object_type_id'] == 1){
                           $total_question_video += $this->countQuestionsVideoID($single_lo['id']);
                        }elseif ($single_lo['learning_object_type_id'] == 2){
                           $total_question_slide += $this->countQuestionsSlideID($single_lo['id']);
                        }elseif($single_lo['learning_object_type_id'] == 3){
                        	$total_question_doc += $this->countQuestionsDocID($single_lo['id']);
                        }elseif($single_lo['learning_object_type_id'] == 4){
                        	$total_question_web += $this->countQuestionsWebID($single_lo['id']);
                        }
                    }
                }
            }
            $total_question = $total_question_slide + $total_question_video + $total_question_doc + $total_question_web;
            return $total_question;
        }

	// ---- Conteggio domande del corso NON DUPLICATE ----------------------------------------------------
	public function countDistinctQuestions($course_id){
		//$learning_prj_id = sanitize($learning_prj_id, INT);
		$learn_obj = new T81LearningProject();
		//$unities = $learn_obj->getUnitiesByLearningProject($learning_prj_id);
		//$course = $learn_obj->getCourseDetail($unities[0]['course_id']);
		$course_id = sanitize($course_id, INT);
		$modules = $learn_obj->getModulesByCourseID($course_id);
		$total_question = 0;
		$total_question_video = 0;
		$total_question_slide = 0;
		$obj_list = array();
		foreach($modules as $single_mo){
			$lessons = $learn_obj->getLessonsByModule($single_mo['id']);
			foreach($lessons as $single){
				$lo = $learn_obj->getLearningObjByLesson($single['id']);
				foreach($lo as $single_lo){
					if (!in_array($single_lo['id'], $obj_list)){
						array_push($obj_list, $single_lo['id']);
						if ($single_lo['learning_object_type_id'] == 1){
							$total_question_video += $this->countQuestionsVideoID($single_lo['id']);
						} elseif ($single_lo['learning_object_type_id'] == 2){
							$total_question_slide += $this->countQuestionsSlideID($single_lo['id']);
						}
					}
				}
			}
		}
		$total_question = $total_question_slide + $total_question_video;
		return $total_question;
	}
	// ---- ----------------------------------------- ----------------------------------------------------

	public function storeAnswer($learning_event_id,$question_id,$answer_id){
		$learning_event_id = sanitize($learning_event_id, INT);
		$question_id = sanitize($question_id, INT);
		$answer_id = sanitize($answer_id, INT);

		$query = "INSERT INTO learning_event_questions(question_sentence_id,learning_event_id,generation_time)VALUES(
				'".$question_id."',
						'".$learning_event_id."',
								'".  date("Y-m-d H:i:s")."')";
		$res = $this->db_conn->insert($query);
		if($res > 0){
			$query = "INSERT INTO learning_event_question_answers(learning_event_question_id,answer_id,user_answer_time)VALUES(
					'".$res."',
							'".$answer_id."',
									'".  date("Y-m-d H:i:s")."')";
			$res2 = $this->db_conn->insert($query);
		}
		return $res + $res2;
	}


	public function closeTutor81QuestionObj(){
		//PHP B id=30525
		//@mysql_close($this->conn);
	}
}
?>
