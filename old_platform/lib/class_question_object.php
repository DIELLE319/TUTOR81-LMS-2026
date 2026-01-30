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

class QuestionObject {


	// In OOP classes are usually named starting with a cap letter.

	//private $r_conn;
	//private $conn;
	//var $conn; var $rem_conn;
	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}

	public function getQuestionDetail($id){
		$id = sanitize($id, INT);
		$query = "SELECT * FROM question_sentences WHERE id =  ".$id;
		$res = $this->db_conn->query($query);
		return $res[0];
	}

	public function getSentenceDetail($id){
		$id = sanitize($id, INT);
		$query = "SELECT text FROM question_sentences WHERE id =  ".$id;
		$res = $this->db_conn->query($query);
		return $res[0]['text'];
	}

	public function newQuestion($question_sentence){
		$question_sentence = $this->db_conn->escapestr($question_sentence);
		$query = "INSERT INTO question_sentences(text) VALUES ('".$question_sentence."')";
		$res = $this->db_conn->insert($query);
		return $res;
	}

	public function addAnswersToQuestion($text,$question_sentence_id,$is_correct){
		$text = $this->db_conn->escapestr($text);
		$question_sentence_id = sanitize($question_sentence_id, INT);
		$is_correct = sanitize($is_correct, INT);
		$code = md5($question_sentence_id.$is_correct.$text);
		$query = "INSERT INTO answers(text,question_sentence_id,is_correct,code) VALUES('".$text."',".$question_sentence_id.",".$is_correct.",'".$code."')";
		$res = $this->db_conn->insert($query);
		return $res;
	}

	public function editQuestion($id,$question_sentence){
		$id = sanitize($id, INT);
		$question_sentence = $this->db_conn->escapestr($question_sentence);
		$query = "UPDATE question_sentences SET question_sentences.text = '".$question_sentence."' WHERE id = ".$id;
		$res = $this->db_conn->update($query);
		return $res;
	}

	public function updateAnswer($id,$text,$is_correct,$delete){
		$id = sanitize($id, INT);
		$text = $this->db_conn->escapestr($text);
		$is_correct = sanitize($is_correct, INT);
		$delete = sanitize($delete, INT);
		$query = "UPDATE answers SET answers.text = '".$text."', is_correct = ".$is_correct." WHERE id = ".$id;
		$res = $this->db_conn->update($query);
		return $res;
	}

	public function deleteAnswer($id){
		$id = sanitize($id, INT);
		$query = "DELETE FROM answers WHERE id = ".$id;
		$res = $this->db_conn->update($query);
		return $res;
	}


	public function getAnswersByQuestionID($id){
		$id = sanitize($id, INT);
		$query = "SELECT * FROM answers WHERE question_sentence_id =  ".$id;
		$res = $this->db_conn->query($query);
		return $res;
	}

	public function removeQuestion($question_id){
		$question_id = sanitize($question_id, INT);
		$list = $this->getAnswersByQuestionID($question_id);
		foreach ($list as $single){
			$this->deleteAnswer($single['id']);
		}
		$query = "DELETE FROM question_sentences WHERE id = ".$question_id;
		$res = $this->db_conn->update($query);
		return $res;
	}

	public function closeQuestionObject(){
		//PHP B id=30525
		//@mysql_close($this->conn);
	}
}


?>
