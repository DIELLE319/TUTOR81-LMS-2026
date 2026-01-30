<?php
require_once 'class_db.php';
require_once 'sanitize.php';
require_once 'class_learning_project.php';
require_once 'class_attestato.php';

class Tutor81LearningEvt{

	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}

	public function getPurchaseDetailById($license_id){
		$license_id = sanitize($license_id, INT);
		$query = "SELECT learning_project_users.* FROM learning_project_users WHERE id = ".$license_id;
		$res = $this->db_conn->query($query);
		return $res[0];
	}

	public function getPurchaseDetailByCode($license_code){
		$query = "SELECT learning_project_users.* FROM learning_project_users WHERE learning_project_pwd = ".$license_code;
		$res = $this->db_conn->query($query);
		return $res[0];
	}

	public function getQuestionFromPurchase($purchase_id){
		$query = "SELECT learning_event_questions.*,text FROM learning_events JOIN learning_event_questions ON learning_event_questions.learning_event_id = learning_events.id JOIN question_sentences ON question_sentences.id = learning_event_questions. question_sentence_id  WHERE learning_project_user_id = ".$purchase_id." GROUP BY question_sentence_id ORDER BY generation_time";
		$res = $this->db_conn->query($query);
		return $res;
	}

	public function getLearningEventFromPurchase($learning_project_user_id){
            $learning_project_user_id = filter_var($learning_project_user_id, FILTER_SANITIZE_NUMBER_INT);
            $query = "SELECT learning_event_learning_objects.*,title FROM learning_events JOIN learning_event_learning_objects ON learning_events.id = learning_event_learning_objects.learning_event_id JOIN learning_objects ON learning_objects.id = learning_event_learning_objects.learning_object_id  WHERE learning_project_user_id = ".$learning_project_user_id;
            $res = $this->db_conn->query($query);
            return $res;
	}
        
        /**
         * Seleziona gli eventi e le informazioni su licenze, corsi, utenti, ditte, 
         * per le necessità di aggiornamento degli utenti in base ai corsi SICUREZZA
         * seguiti.
         * Limitato ai corsi Sicurezza, Antincendio e Primo Soccorso
         * 
         * @param INTEGER $tutor_company_id id dell'ente formativo 
         */
        public function getUpdateNeedsByTutorCompany($tutor_company_id = 0) {
            $tutor_company_id = filter_var($tutor_company_id, FILTER_VALIDATE_INT);
            $companies = "";
            if ($tutor_company_id) {
                $companies = "AND users.company_id IN (SELECT companies.id
                                             FROM companies 
                                             WHERE companies.owner_user_id IN (SELECT users.id FROM users WHERE users.company_id = '$tutor_company_id'))";
            }
            $query = "SELECT 
                        learning_events.id as learning_event_id, 
                        learning_events.learning_project_user_id, 
                        CONCAT(users.surname, ' ', users.name) as user_name, 
                        users.id as user_id,
                        users.company_id, 
                        companies.business_name,
                        tutor_company.business_name as tutor_business_name,
                        learning_project_users.tutor_purchase_id, 
                        learning_project_users.learning_project_id,
                        learning_project.title,
                        categories.id as category_id,
                        categories.name as category_name,
                        subcategories.id as subcategory_id,
                        subcategories.name as subcategory_name,
                        tutors_purchases.price,  
                        learning_events.end_date_time,
                        learning_events.update_done
                    FROM learning_events 
                    JOIN learning_project_users ON learning_events.learning_project_user_id = learning_project_users.id 
                    JOIN users ON learning_project_users.user_id = users.id
                    JOIN companies ON users.company_id = companies.id 
                    JOIN users as tutor_user ON companies.owner_user_id = tutor_user.id 
                    JOIN companies as tutor_company ON tutor_user.company_id = tutor_company.id 
                    JOIN tutors_purchases ON learning_project_users.tutor_purchase_id = tutors_purchases.id
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    JOIN unities ON  learning_project_users.learning_project_id = unities.learning_project_id
                    JOIN courses ON course_id = courses.id 
                    JOIN subcategories ON courses.subcategory_id = subcategories.id 
                    JOIN categories ON subcategories.category_id = categories.id
                    WHERE learning_events.end_date_time IS NOT NULL
                    AND learning_events.end_date_time!= '0000-00-00 00:00:00' 
                    $companies
                    AND categories.id IN (5,9,10)
                    AND ((subcategories.id IN (8,9,10,11,12,20) AND DATEDIFF(NOW(),learning_events.end_date_time)>5*365) 
                         OR (subcategories.id = 13 AND DATEDIFF(NOW(),learning_events.end_date_time)>1*365)
                         OR (subcategories.id IN (14,15) AND DATEDIFF(NOW(),learning_events.end_date_time)>2*365)
                         )
                    ORDER BY users.id, learning_events.end_date_time, learning_project.title;";
            $res = $this->db_conn->query($query);
            return isset($res[0]) ? $res : false;
        }
        
        public function setUpdateDone($learning_event_id, $update_done){
            $learning_event_id = filter_var($learning_event_id, FILTER_VALIDATE_INT);
            $query = "UPDATE learning_events SET update_done = $update_done WHERE id = $learning_event_id";
            return $this->db_conn->update($query);
        }

	public function alreadyStarted($learning_project_user_id){
		$learning_project_user_id = sanitize($learning_project_user_id, INT);
		$query = "SELECT learning_events.* FROM learning_events WHERE learning_project_user_id = ".$learning_project_user_id;
		$res = $this->db_conn->query($query);
		return $res[0];
	}

	public function setLPUUnlocked($lpu_id, $starting_from, $finish_within) {
            $lpu_id = filter_var($lpu_id, FILTER_SANITIZE_NUMBER_INT);
            $starting_from = date('Y-m-d', strtotime($this->db_conn->escapestr($starting_from)));
            $finish_within = date('Y-m-d', strtotime($this->db_conn->escapestr($finish_within)));
            $query = "UPDATE learning_project_users 
                      SET assigned = 1, 
                        starting_from = '$starting_from', 
                        finish_within = '$finish_within'
                      WHERE id = $lpu_id";
            $res = $this->db_conn->update($query);
            return $res;
	}

	public function createLearningEvent($learning_project_user_id,$learning_prj_id,$user_id){
		$learning_project_user_id = sanitize($learning_project_user_id, INT);
		$learning_project_users = 0;
		$learn_prj = new Tutor81LearningPrj();
		$first_learn_obj = $learn_prj->getNextLearningObject($learning_prj_id, $learning_project_users);
		$query = "INSERT INTO learning_events(unit_id,start_date_time,last_lesson_id,last_learning_object_id,last_course_module_id,learning_project_user_id)VALUES(
				'".$first_learn_obj['unit_id']."',
						'".  date("Y-m-d H:i:s")."',
								'".$first_learn_obj['lesson_id']."',
										'".$first_learn_obj['learning_object_id']."',
												'".$first_learn_obj['module_id']."',
														'".$learning_project_user_id."')";
		$res = $this->db_conn->insert($query);
		return $res;
	}

	public function alreadyStartedObject($learning_event_id,$learning_object_id,$course_module_id,$lesson_id){
		$learning_event_id = sanitize($learning_event_id, INT);
		$learning_object_id = sanitize($learning_object_id, INT);
		$course_module_id = sanitize($course_module_id, INT);
		$lesson_id = sanitize($lesson_id, INT);
		$query = "SELECT learning_event_learning_objects.* FROM learning_event_learning_objects WHERE
				learning_event_id = ".$learning_event_id." AND
						learning_object_id = ".$learning_object_id." AND
								course_module_id = ".$course_module_id." AND
										lesson_id = ".$lesson_id;
		$res = $this->db_conn->query($query);
		return $res[0];
	}

	public function addStartEvent($learning_event_id,$learning_object_id,$course_module_id,$lesson_id){
		$learning_event_id = sanitize($learning_event_id, INT);
		$learning_object_id = sanitize($learning_object_id, INT);
		$course_module_id = sanitize($course_module_id, INT);
		$lesson_id = sanitize($lesson_id, INT);
		$res = $this->alreadyStartedObject($learning_event_id,$learning_object_id,$course_module_id,$lesson_id);
		if(count($res) == 0){
			$query = "INSERT learning_event_learning_objects(learning_event_id,learning_object_id,start_date_time,course_module_id,lesson_id)VALUES(
					'".$learning_event_id."',
							'".$learning_object_id."',
									'".  date("Y-m-d H:i:s")."',
											'".$course_module_id."',
													'".$lesson_id."')";
			$res = $this->db_conn->insert($query);
			$_SESSION['learning_event_learning_objects'] = $res;

			$query = "UPDATE learning_events SET last_video_test_time = 0, last_learning_object_id = ".$learning_object_id.", last_slide_test_position = 0 WHERE id = ".$learning_event_id;
			$res1 = $this->db_conn->update($query);
		}else{
			$_SESSION['learning_event_learning_objects'] = $res['id'];
		}
		return $res;
	}

	public function updateSlidePosEvent($learning_event_learning_objects_id,$position){
		$learning_event_learning_objects_id = sanitize($learning_event_learning_objects_id, INT);
		$position = sanitize($position, INT);
		$query = "UPDATE learning_events SET last_slide_test_position = '".$position."' WHERE id = ".$learning_event_learning_objects_id;
		$res = $this->db_conn->update($query);
		return $res;
	}

	public function restoreSlidePosition($learning_event_learning_objects_id,$learning_object_id){
		$learning_event_learning_objects_id = sanitize($learning_event_learning_objects_id, INT);
		$learning_object_id = sanitize($learning_object_id, INT);
		$query = "SELECT last_slide_test_position FROM learning_events WHERE id = ".$learning_event_learning_objects_id." AND last_learning_object_id = ".$learning_object_id;
		$res = $this->db_conn->query($query);
		return $res[0]['last_slide_test_position'];
	}

	public function restoreVideoPosition($learning_event_learning_objects_id,$learning_object_id){
		$learning_event_learning_objects_id = sanitize($learning_event_learning_objects_id, INT);
		$learning_object_id = sanitize($learning_object_id, INT);
		$query = "SELECT last_video_test_time FROM learning_events WHERE id = ".$learning_event_learning_objects_id." AND last_learning_object_id = ".$learning_object_id;
		$res = $this->db_conn->query($query);
		return $res[0]['last_video_test_time'];
	}

	public function addEndEvent($learning_event_learning_objects_id){
		$learning_event_learning_objects_id = sanitize($learning_event_learning_objects_id, INT);
		$query = "UPDATE learning_event_learning_objects SET end_date_time = '".  date("Y-m-d H:i:s")."' WHERE id = ".$learning_event_learning_objects_id;
		$this->db_conn->update($query);
		return $res;
	}

	public function updateVideoTime($learn_event_id,$time){
		$learn_event_id = sanitize($learn_event_id, INT);
		$time = sanitize($time, INT);
		$query = "UPDATE learning_events SET last_video_test_time = '".  $time."' WHERE id = ".$learn_event_id;
		$this->db_conn->update($query);
	}
        
        /**
         * Set number of total learning object of the course in learning_events table
         * 
         * @param integer $learning_event_id
         * @param integer $total_num_lo
         * @return integer number of afflicted row
         */
        public function setTotalNumLO($learning_event_id, $total_num_lo){
            $learning_event_id = filter_var($learning_event_id, FILTER_SANITIZE_NUMBER_INT);
            $total_num_lo = filter_var($total_num_lo, FILTER_SANITIZE_NUMBER_INT);
            $query = "UPDATE learning_events SET total_num_lo = $total_num_lo WHERE id = $learning_event_id";
            return $this->db_conn->update($query);
        }
        
        /**
         * Set percentage of course completion in learning_events table
         * 
         * @param integer $learning_event_id
         * @param integer $progress_rate
         * @return integer number of afflicted row
         */
        public function setProgressRate($learning_event_id, $progress_rate){
            $learning_event_id = filter_var($learning_event_id, FILTER_SANITIZE_NUMBER_INT);
            $progress_rate = filter_var($progress_rate, FILTER_SANITIZE_NUMBER_INT);
            $query = "UPDATE learning_events SET progress_rate = $progress_rate WHERE id = $learning_event_id";
            return $this->db_conn->update($query);
        }

	public function closeLearningEvent($learning_event_id,$license_id){
		$learning_event_id = sanitize($learning_event_id, INT);
		$query = "SELECT * FROM learning_events WHERE id = ".$learning_event_id;
		$res_detail = $this->db_conn->query($query);
		if($res_detail['end_date_time'] == "0000-00-00 00:00:00" || !isset($res_detail['end_date_time'])){
			$query = "UPDATE learning_events SET end_date_time = '".  date("Y-m-d H:i:s")."' WHERE id = ".$learning_event_id;
			$res = $this->db_conn->update($query);
			if($res > 0){
				$attestato = new Tutor81Attestato();
				$attestato->generatePDF($license_id);
			}
		}else{
			$res = 1;
		}
		return $res;
	}
        
        public function purgeHistoryLearningEvents ($learning_event_id){
            $learning_event_id = filter_var($learning_event_id, FILTER_SANITIZE_NUMBER_INT);
            $query = "DELETE learning_event_questions,
                        learning_event_question_answers
                    FROM learning_event_questions
                        LEFT JOIN learning_event_question_answers ON learning_event_questions.id = learning_event_question_answers.learning_event_question_id
                    WHERE learning_event_questions.learning_event_id = $learning_event_id";
            $res = $this->db_conn->delete($query);
            $query = "DELETE learning_event_learning_objects FROM learning_event_learning_objects WHERE learning_event_learning_objects.learning_event_id = $learning_event_id";
            $res .= " - " . $this->db_conn->delete($query);
            $query = "DELETE learning_events FROM learning_events WHERE learning_events.id = $learning_event_id";
            $res .= " - " . $this->db_conn->delete($query);
            return $res;
        }

	public function closeTutor81Tutor81LearningEvt(){
		//PHP B id=30525
		//@mysql_close($this->conn);
	}
}
