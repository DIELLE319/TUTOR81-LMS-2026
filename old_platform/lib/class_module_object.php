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

require_once 'class_db.php';
require_once 'sanitize.php';

class ModuleObject {


	// In OOP classes are usually named starting with a cap letter.

	//private $r_conn;
	//private $conn;
	//var $conn; var $rem_conn;
	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}

	public function getModuleDetailByID($module_id){
		$module_id = sanitize($module_id, INT);
		$query = "SELECT * FROM course_modules WHERE id = ". $module_id;
		$res = $this->db_conn->query($query);
		return $res[0];
	}

	public function createNewModule($title,$description,$duration,$max_execution_time){
		$title =  $this->db_conn->escapestr($title);
		$description =  $this->db_conn->escapestr($description);
		$duration = sanitize($duration, INT);
		$max_execution_time = sanitize($max_execution_time, INT);
		$query = "INSERT INTO course_modules(title,description,duration,max_execution_time) VALUES(
				'".$title."',
						'".$description."',
								'".$duration."',
										'".$max_execution_time."')";
		$res = $this->db_conn->insert($query);
		return $res;
	}

	public function editModule($module_id,$title_module,$description_module,$duration_module,$max_duration_module){
		$title_module =  $this->db_conn->escapestr($title_module);
		$description_module =  $this->db_conn->escapestr($description_module);
		$duration_module = sanitize($duration_module, INT);
		$max_duration_module = sanitize($max_duration_module, INT);
		$module_id = sanitize($module_id, INT);
		$query = "UPDATE course_modules SET
				title = '".$title_module."',
						description = '".$description_module."',
								duration = '".$duration_module."',
										max_execution_time = '".$max_duration_module."' WHERE id = ".$module_id;
		$res = $this->db_conn->update($query);
		return $res;
	}

	public function getLessonMaxPosition($course_module_id){
		$query = "SELECT MAX(position) as max_pos FROM course_module_lessons WHERE course_module_id = ".$course_module_id;
		$res = $this->db_conn->query($query);
		return $res[0]['max_pos'];
	}

	public function addNewLessonObjectToModule($lesson_id,$course_module_id,$position = 1){
		$lesson_id = sanitize($lesson_id, INT);
		$course_module_id = sanitize($course_module_id, INT);
		$position = sanitize($position, INT);
		$get_last_position = $this->getLessonMaxPosition($course_module_id);
		if($get_last_position == 0){
			$get_last_position = 1;
		}else{
			$get_last_position++;
		}
		$query = "INSERT INTO course_module_lessons(lesson_id,course_module_id,position) VALUES(
				'".$lesson_id."',
						'".$course_module_id."',
								'".$get_last_position."')";
		$res = $this->db_conn->insert($query);
		return $res;
	}

	public function getMaxPosition($course_id,$module_id){
		$course_id = sanitize($course_id, INT);
		$module_id = sanitize($module_id, INT);
		$query = "SELECT COUNT(*) as max_position FROM course_course_modules WHERE course_id = ".$course_id;
		$res = $this->db_conn->query($query);
		return $res[0]['max_position'];
	}
	 
	public function associateModuleToCourse($course_id,$module_id){
		$course_id = sanitize($course_id, INT);
		$module_id = sanitize($module_id, INT);
		$position = $this->getMaxPosition($course_id,$module_id);
		$position += 1;
		$query = "INSERT INTO course_course_modules(course_module_id,course_id,position) VALUES(
				'".$module_id."',
						'".$course_id."',
								'".$position."')";
		$res = $this->db_conn->insert($query);
		return $res;
	}


	public function removeLesson($course_module_lesson_id){
		$course_module_lesson_id = sanitize($course_module_lesson_id, INT);
		$query = "DELETE FROM course_module_lessons WHERE id = ".$course_module_lesson_id;
		$res = $this->db_conn->update($query);
		return $res;
	}


	public function closeModuleObject(){
		//PHP B id=30525
		//@mysql_close($this->conn);
	}
}


?>
