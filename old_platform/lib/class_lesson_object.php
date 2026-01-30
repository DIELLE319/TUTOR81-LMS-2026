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

    class LessonObject {
		
	
	// In OOP classes are usually named starting with a cap letter.
	 
	//private $r_conn;
	//private $conn;
	//var $conn; var $rem_conn;
	var $db_conn;
	
	public function __construct(){
            $this->db_conn = new MySQLConn();
	}
	
         public function getLessonDetailByID($lesson_id){
            $lesson_id = sanitize($lesson_id, INT);
            $query = "SELECT * FROM lessons WHERE id = ". $lesson_id;
            $res = $this->db_conn->query($query);
            return $res[0];
        }
        
        public function isObjectInLesson($learning_object_id, $lesson_id){
        	$learning_object_id = sanitize($learning_object_id, INT);
        	$lesson = sanitize($lesson_id, INT);
        	$query = "SELECT COUNT(id) as qta FROM lesson_learning_objects WHERE learning_object_id = $learning_object_id AND lesson_id = ".$lesson_id;
        	$res = $this->db_conn->query($query);
        	return (bool)$res[0]['qta'];
        }
        
        
        public function createNewLesson($title,$description,$duration,$percentage_correct_answer_to_pass,$owner_user_id){
            $title =  $this->db_conn->escapestr($title);
            $description =  $this->db_conn->escapestr($description);
            $duration = sanitize($duration, INT);
            $percentage_correct_answer_to_pass = sanitize($percentage_correct_answer_to_pass, INT);
            $owner_user_id = sanitize($owner_user_id, INT);
            $code = sha1($title.$description);
            $query = "INSERT INTO lessons(title,description,duration,percentage_correct_answer_to_pass,code,owner_user_id) VALUES(
                '".$title."',
                '".$description."',
                '".$duration."',
                '".$percentage_correct_answer_to_pass."',
                '".$code."',
                '".$owner_user_id."')";
            $res = $this->db_conn->insert($query);
            return $res;
        }
        
        public function editLesson($lesson_id,$title, $description, $duration, $percentage_correct_answer_to_pass){
            $lesson_id = sanitize($lesson_id, INT);
            $title =  $this->db_conn->escapestr($title);
            $description =  $this->db_conn->escapestr($description);
            $duration = sanitize($duration, INT);
            $percentage_correct_answer_to_pass = sanitize($percentage_correct_answer_to_pass, INT);
            $query = "UPDATE lessons SET
                title = '".$title."',
                description = '".$description."',
                duration = '".$duration."',
                percentage_correct_answer_to_pass = '".$percentage_correct_answer_to_pass."' WHERE id = ".$lesson_id;
            $res = $this->db_conn->update($query);
            return $res;
        }
        
        public function removeAllLearningObjectToLesson($lesson_id){
            $lesson_id = sanitize($lesson_id, INT);
            $query = "DELETE FROM lesson_learning_objects WHERE lesson_id = ".$lesson_id;
            $res = $this->db_conn->update($query);
            return $res;
        }
        
        public function removeLearningObjectToLesson($lesson_id, $learning_object_id){
        	$learning_object_id = sanitize($learning_object_id, INT);
        	$lesson_id = sanitize($lesson_id, INT);
        	$query = "DELETE FROM lesson_learning_objects WHERE lesson_id = $lesson_id AND learning_object_id = $learning_object_id";
        	$res = $this->db_conn->update($query);
        	return $res;
        }
        
        public function addNewLearningObjectToLesson($learnining_obj_id,$lesson_id,$position = 1){
            $learnining_obj_id = sanitize($learnining_obj_id, INT);
            $lesson_id = sanitize($lesson_id, INT);
            $position = sanitize($position, INT);
            $query = "INSERT INTO lesson_learning_objects(learning_object_id,lesson_id,position) VALUES(
                '".$learnining_obj_id."',
                '".$lesson_id."',
                '".$position."')";
            $res = $this->db_conn->insert($query);
            return $res;
        }
        
        public function updatePositionLearningObjectInLesson($learning_object_id, $lesson_id, $position){
        	$learning_object_id = sanitize($learning_object_id, INT);
        	$lesson_id = sanitize($lesson_id, INT);
        	$position = sanitize($position, INT);
        	$query = "UPDATE lesson_learning_objects SET position = $position WHERE learning_object_id = $learning_object_id AND lesson_id = $lesson_id";
        	$res = $this->db_conn->update($query);
        	return $res;
        }
        
        
	public function closeLessonObject(){
		//PHP B id=30525
		//@mysql_close($this->conn);
	}	
}
	
	
?>
