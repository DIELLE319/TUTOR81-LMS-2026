<?php

/* iWebDev di Thomas Orlandi
 * -----------------------------------------------------------------------------------------
* This software contains confidential proprietary information belonging
* to iWebDev di Thomas Orlandi. No part of this information may be used, reproduced,
* or stored without prior written consent of iWebDev di Thomas Orlandi.
* -----------------------------------------------------------------------------------------/
* 3-lug-2012
* File: class_learning_object.php
* Project: tutor81
*
* Author: Thomas Orlandi :: info@iwebdev.it
*
*/
require_once dirname(__FILE__).'/../config.php';

require_once BASE_LIBRARY_PATH . 'class_db.php';
require_once BASE_LIBRARY_PATH . 'sanitize.php';

class T81LearningProject{


	// In OOP classes are usually named starting with a cap letter.

	//private $r_conn;
	//private $conn;
	//var $conn; var $rem_conn;
	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}


	public function getAvailableList($comp_id = 0){
		$comp_id = sanitize($comp_id, INT);
		$query = "SELECT * FROM learning_project WHERE is_published_in_ecommerce = 1 ORDER BY title";
		$res = $this->db_conn->query($query);
		$res_final = array();
		foreach($res as $single){
			if($single['reserved_to'] == ""){
				array_push($res_final,$single);
			}else{
				if (in_array($comp_id, explode(",",$single['reserved_to']))) {
					array_push($res_final,$single);
				}
			}

		}
		return $res_final;
	}

	public function getList($comp_id = 0){
		$query = "SELECT * FROM learning_project ORDER BY title";
		$res = $this->db_conn->query($query);
		return $res;
	}

	public function getFilteredList($filter = 0){
		if($filter == 0){
			$query = "SELECT * FROM learning_project ORDER BY title";
		}elseif($filter == 1){
			$query = "SELECT * FROM learning_project WHERE is_published_in_ecommerce = 1 ORDER BY title";
		}elseif($filter == 2){
			$query = "SELECT * FROM learning_project WHERE is_published_in_ecommerce = 0 ORDER BY title";
		}elseif($filter == 3){
			$query = "SELECT * FROM learning_project WHERE is_published_in_ecommerce = 2 ORDER BY title";
		}elseif($filter == 4){
			$query = "SELECT * FROM learning_project WHERE is_published_in_ecommerce = 1 and reserved_to <> '' ORDER BY title";
		}
		$res = $this->db_conn->query($query);
		return $res;
	}

	public function getDetail($id){
		$id = sanitize($id, INT);
		$query = "SELECT * FROM learning_project WHERE id = ".$id;
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res[0] : false;
	}

	public function getLessonType($lesson_id){
		$lesson_id = sanitize( $lesson_id, INT);
		$query = "SELECT learning_object_types.* FROM lesson_learning_objects JOIN learning_objects ON lesson_learning_objects.learning_object_id = learning_objects.id JOIN learning_object_types ON learning_objects.learning_object_type_id = learning_object_types.id WHERE lesson_id = ".$lesson_id;
		$res = $this->db_conn->query($query);
		return $res[0]['name'];
	}

	private function get_module_lessons_course_detail($module_id){
		$query = "SELECT lessons.* FROM course_module_lessons JOIN lessons ON course_module_lessons.lesson_id = lessons.id WHERE course_module_id = ".$module_id." ORDER BY position";
		$res = $this->db_conn->query($query);
		return $res;
	}


	private function get_module_course_detail($course_id){
		$query = "SELECT course_modules.* FROM course_course_modules JOIN course_modules ON course_course_modules.course_module_id = course_modules.id WHERE course_course_modules.course_id = ".$course_id." ORDER BY course_course_modules.position";
		$res = $this->db_conn->query($query);
		return $res;
	}

	private function get_lesson_chapter_detail($lesson_id){
		$query = "SELECT learning_objects.*,lesson_learning_objects.position as position  FROM lesson_learning_objects JOIN learning_objects ON learning_object_id = learning_objects.id WHERE lesson_id = ".$lesson_id. " ORDER BY lesson_learning_objects.position";
		$res = $this->db_conn->query($query);
		return $res;

	}

	private function get_lesson_detail($lesson_id){
		$query = "SELECT lessons.*  FROM lessons WHERE id = ".$lesson_id;
		$res = $this->db_conn->query($query);
		return $res[0];
	}

	private function get_unities($learning_project_id){
		$query = "SELECT unities.*  FROM unities WHERE learning_project_id = ".$learning_project_id." ORDER BY position";
		$res = $this->db_conn->query($query);
		return $res;
	}

	public function get_num_learning_objects($learning_project_id){
		$learning_project_id = sanitize($learning_project_id, INT);
		$lo_counter = 0;
		$learning_project_unities = $this->get_unities($learning_project_id);
		foreach($learning_project_unities as $item){
			if ($item['unit_type_id'] == 1){
				$lo_counter++;
			}elseif($item['unit_type_id'] == 2){
				$element = $this->get_lesson_detail($item['lesson_id']);
				$chapter_list = $this->get_lesson_chapter_detail($item['lesson_id']);
				foreach($chapter_list as $chapter){
					$lo_counter++;
				}
			}elseif($item['unit_type_id'] == 3){
				$module_list = $this->get_module_course_detail($item['course_id']);
				foreach($module_list as $module){
					$module_lesson_list = $this->get_module_lessons_course_detail($module['id']);
					foreach($module_lesson_list as $lessons){
						$chapter_list = $this->get_lesson_chapter_detail($lessons['id']);
						foreach($chapter_list as $chapter){
							$lo_counter++;
						}
					}
				}
			}
		}
		return $lo_counter;
	}

	public function get_num_lo_executed($learning_project_user_id){
            $num_result = 0;
            $query = "SELECT * FROM learning_events WHERE learning_project_user_id = ".$learning_project_user_id;
            $res = $this->db_conn->query($query);
            if(count($res) == 0) return 0;
            $learning_event_id = $res[0]['id'];

            $query = "SELECT COUNT(id) as qta "
                . "FROM learning_event_learning_objects "
                . "WHERE learning_event_id = $learning_event_id "
                . "AND end_date_time IS NOT NULL "
                . "AND end_date_time <> '0000-00-00 00:00:00'";
            $res = $this->db_conn->query($query);
            return isset($res[0]) ? $res[0]['qta']  : 0;
	}

	public function getModulesByCourseID($course_id){
		$course_id = sanitize( $course_id, INT);
		$query = "SELECT course_modules.* FROM course_course_modules JOIN course_modules ON course_course_modules.course_module_id = course_modules.id WHERE course_course_modules.course_id = ".$course_id." ORDER BY position";
		$res = $this->db_conn->query($query);
		return $res;
	}

	public function getUnitiesByLearningProject($learning_prj_id,$type = 3){
		$learning_prj_id = sanitize( $learning_prj_id, INT);
		$query = "SELECT unities.*  FROM unities WHERE unit_type_id = 3 AND learning_project_id = ".$learning_prj_id." ORDER BY position";
		$res = $this->db_conn->query($query);
		return $res;
	}

	public function getCourseDetail($course_id){
		$course_id = sanitize( $course_id, INT);
		$query = "SELECT courses.*  FROM courses WHERE id = ".$course_id;
		$res = $this->db_conn->query($query);
		return $res[0];
	}

	public function getLessonsByModule($module_id){
		$module_id = sanitize( $module_id, INT);
		$query = "SELECT lessons.* FROM course_module_lessons JOIN lessons ON course_module_lessons.lesson_id = lessons.id WHERE course_module_id = ".$module_id." ORDER BY position";
		$res = $this->db_conn->query($query);
		return $res;
	}

	public function getLearningObjByLesson($lesson_id){
		$lesson_id = sanitize( $lesson_id, INT);
		$query = "SELECT learning_objects.* FROM lesson_learning_objects JOIN learning_objects ON learning_objects.id = lesson_learning_objects.learning_object_id WHERE lesson_id = ".$lesson_id." ORDER BY position";
		$res = $this->db_conn->query($query);
		return $res;
	}

	function create($title,$description,$owner_user_id,$is_published_in_ecommerce,$course_cover_image){
		require dirname(__FILE__) . '/../config.php';
		$title = $this->db_conn->escapestr($title);
		$description = $this->db_conn->escapestr($description);
		$owner_user_id = sanitize($owner_user_id, INT);
		$is_published_in_ecommerce = sanitize($is_published_in_ecommerce, INT);
		$code = sha1($title.date("Y-m-d H:i:s"));
		$ecommerce_image_filename = $code;
		$query = "INSERT INTO learning_project(
				title,
				description,
				owner_user_id,
				creation_date,
				ecommerce_image_filename,
				code,
				is_published_in_ecommerce)VALUES(
				'".$title."',
						'".$description."',
								".$owner_user_id.",
										'".date("Y-m-d H:i:s")."',
												'".$ecommerce_image_filename."',
														'".$code."',
																".$is_published_in_ecommerce.")";
		$res = $this->db_conn->insert($query);
		if($res > 0){
			$source_path = "../".$base_media_path."user_store/".$owner_user_id."/courses/ecommerce_images/thumb/".$course_cover_image;
			if (file_exists($source_path)){
				$dest_path = "../".$base_media_path."user_store/".$owner_user_id."/learning_projects/ecommerce_images/thumb/".$ecommerce_image_filename;
				copy($source_path, $dest_path);
			}
		}
		return $res;
	}

	function addCourseUnities($learning_project_id,$course_id,$pos = 1){
		$learning_project_id = sanitize($learning_project_id,INT);
		$course_id = sanitize($course_id,INT);
		$pos = sanitize($pos,INT);
		$query = "INSERT INTO unities(
				course_id,
				position,
				unit_type_id,
				learning_project_id)VALUES(
				".$course_id.",
						".$pos.",
						3,
						$learning_project_id)";
		$res = $this->db_conn->insert($query);
		return $res;
	}



	public function editProject($id,$l_title,$txt_desc_ita,$arrCompany){
		$id = sanitize( $id, INT);
		$l_title =  $this->db_conn->escapestr($l_title);
		$txt_desc_ita = $this->db_conn->escapestr($txt_desc_ita);
		if(count($arrCompany) > 0){
			$arr_txt = implode(",", $arrCompany);
		}else{
			$arr_txt = "";
		}
		$query = "UPDATE learning_project SET title = '".$l_title."', description = '".$txt_desc_ita."', reserved_to = '".$arr_txt."' WHERE id = ".$id;
		$res = $this->db_conn->update($query);
		return $res;
	}

	public function changeStatus($id,$status){
		$id = sanitize( $id, INT);
		$status = sanitize( $status, INT);
		$query = "UPDATE learning_project SET is_published_in_ecommerce = '".$status."' WHERE id = ".$id;
		$res = $this->db_conn->update($query);
		return $res;
	}


	public function addPhoto($elem_id,$fileName){
		$elem_id = sanitize( $elem_id, INT);
		$fileName = sanitize( $fileName, PARANOID);
		$query = "UPDATE learning_project SET ecommerce_image_filename = '".$fileName."' WHERE id = ".$elem_id;
		$res = $this->db_conn->update($query);
		return $res;
	}

	public function getCourseDetailFromLearningProject($learning_prj_id){
		$learning_prj_id = filter_var( $learning_prj_id, FILTER_SANITIZE_NUMBER_INT);
		$query = "SELECT learning_project.id as learning_project_id,
                            learning_project.title as learning_project_title,
                            learning_project.description as learning_project_description,
                            courses.* 
                          FROM learning_project
                            JOIN unities ON unities.learning_project_id = learning_project.id
                            JOIN courses ON course_id = courses.id
                          WHERE learning_project.id = '$learning_prj_id' AND unit_type_id = 3";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res[0] : false;
	}

	public function getCourseEcommerceDetailFromLearningProject($learning_prj_id){
            $learning_prj_id = filter_var( $learning_prj_id, FILTER_SANITIZE_NUMBER_INT);
            $query = "SELECT
                        learning_project.id as learning_project_id,
                        learning_project.description as lp_description,
                        learning_project.ecommerce_image_filename as ecommerce_image_filename,
                        courses.id as course_id,
                        learning_project.title as title,
                        categories.name as category,
                        subcategories.name as subcategory,
                        courses.subcategory_id as subcategory_id,
                        types.description as type,
                        courses.total_elearning as duration,
                        courses.owner_user_id as owner_user_id,
                        courses.video_link as video,
                        courses.max_execution_time as execution_time,
                        courses.description as single_description,
                        courses.law_reference as reference_law,
                        courses.video_link as video_courses,
                        courses.didactics as didactics_course,
                        courses.percentage_correct_answer_to_pass as percentage_answer_to_pass,
                        courses.customers as destinatari,
                        courses.course_validity as course_validita,
                        courses.external_integration,
                        courses.targets,
                        courses.requirements
                    FROM learning_project
                        JOIN unities ON unities.learning_project_id = learning_project.id
                        JOIN courses ON course_id = courses.id
                        JOIN subcategories ON courses.subcategory_id = subcategories.id
                        JOIN categories ON subcategories.category_id = categories.id
                        JOIN types ON courses.type_id = types.id

                    WHERE learning_project.id = '$learning_prj_id'";

            $res = $this->db_conn->query($query);
            return isset($res[0]) ? $res[0] : false;
	}

    public function getMaxExecutionTime($learning_prj_id){
        $learning_prj_id = sanitize( $learning_prj_id, INT);
        $query = "SELECT learning_project.id as learning_project_id,
                            learning_project.title as learning_project_title,
                            learning_project.description as learning_project_description,
                            courses.* 
                          FROM learning_project
                            JOIN unities ON unities.learning_project_id = learning_project.id
                            JOIN courses ON course_id = courses.id
                          WHERE learning_project.id = '$learning_prj_id' AND unit_type_id = 3";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

	public function getListLessons($learning_project_id){
		$lesson_list = "";
		$learning_project_unities = $this->getUnitiesByLearningProject($learning_project_id);
		foreach($learning_project_unities as $item){
			if ($item['unit_type_id'] == 1){
				$lesson_list .= $item['title']."<br/>";
			}elseif($item['unit_type_id'] == 2){
				$chapter_list = $this->get_lesson_chapter_detail($item['lesson_id']);
				foreach($chapter_list as $chapter){
					$lesson_list .= $chapter['title']."<br/>";
				}
			}elseif($item['unit_type_id'] == 3){
				$module_list = $this->getModulesByCourseID($item['course_id']);
				foreach($module_list as $module){
					$module_lesson_list = $this->getLessonsByModule($module['id']);
					foreach($module_lesson_list as $lessons){
						$chapter_list = $this->getLearningObjByLesson($lessons['id']);
						foreach($chapter_list as $chapter){
							$lesson_list .= $chapter['description']."<br/>";
						}
					}
				}
			}
		}
		return $lesson_list;
	}

	function getTabledCourses($comp_id = 0){
		$list = $this->getAvailableList($comp_id);
		$courses = array();
		foreach ($list as $single){
			$end_code = strpos($single['title'], ' ');
			$is_update = substr($single['title'], $end_code-1, 1) == 'a';
			if (!$is_update) {
				$index = substr($single['title'],0,$end_code);
				$courses[$index]['new'] = $single;
			} else {
				$index = substr($single['title'],0,$end_code-1);
				$courses[$index]['update'] = $single;
			}
		}
		return $courses;
	}
        
    public function getPrices($learning_project_id){
        $learning_project_id = filter_var($learning_project_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT IFNULL(course_price_range_sequences.price, 0) as price
              FROM unities 
                LEFT JOIN course_price_range_sequences ON unities.course_id = course_price_range_sequences.course_id
                LEFT JOIN ranges ON course_price_range_sequences.range_id = ranges.id 
                LEFT JOIN price_range_sequences ON ranges.price_range_sequence_id = price_range_sequences.id
              WHERE unities.learning_project_id = '$learning_project_id' 
              ORDER BY lower_limit";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

	public function closeiWDLearningProject(){
		//PHP B id=30525
		//@mysql_close($this->conn);
	}


    public function getLearningProjectUserFromPassword($learning_password) {
        $query = "SELECT LPU.*
                    FROM learning_project_users as LPU
                    WHERE LPU.learning_project_pwd = '$learning_password'";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function changeLearningProjectUserUserID($id, $user_id){
        $id = sanitize( $id, INT);
        $user_id = sanitize( $user_id, INT);
        $query = "UPDATE learning_project_users SET user_id = $user_id WHERE id = $id";
        $res = $this->db_conn->update($query);
        return $res;
    }
    
    public static function formatTitle($title){
        $title = filter_var($title, FILTER_SANITIZE_STRING);
        $code_pos = strpos($title, ' - ');
        return $code_pos > 0 ? substr($title, $code_pos + 3) : $title;
    }


}