<?php

/* iWebDev di Thomas Orlandi
 * -----------------------------------------------------------------------------------------
* This software contains confidential proprietary information belonging
* to iWebDev di Thomas Orlandi. No part of this information may be used, reproduced,
* or stored without prior written consent of iWebDev di Thomas Orlandi.
* -----------------------------------------------------------------------------------------/
* 3-lug-2012
* File: class_db.php
* Project: tutor81
*
* Author: Thomas Orlandi :: info@iwebdev.it
*
*/

class MySQLConn {

	var $conn;

	public function __construct(){
		$this->conn = new mysqli(DB_HOST,DB_USER,DB_PASSWORD, DB_NAME);
		if (!$this->conn) {
			die($this->conn->error);
		}else{
			$db_selected = $this->conn->select_db(DB_NAME);
			$sql = "SET NAMES 'utf8'";
			$this->conn->query($sql);
			if (!$db_selected) {
				die($this->conn->error);
			}
		}
	}

	public function connect(){
		$this->conn = new mysqli(DB_HOST,DB_USER,DB_PASSWORD);
		//$this->conn=@mysql_connect(SERVER,USERNAME,PASSWORD,true);
		if (!$this->conn) {
			//new EventLog(2,'MySQL', $this->conn->error);
			die('Check the log for more details.');
		}else{
			$db_selected = $this->conn->select_db(DB_NAME);
			$sql = "SET NAMES 'utf8'";
			$this->conn->query($sql);
			if (!$db_selected) {
				die ('Check the log for more details.');
			}
		}
	}

	public function rem_connect($ip,$username,$password){
		$this->conn = new mysqli($ip,$username,$password);
		if (!$this->conn) {
			die('Check the log for more details.');
		}else{
			$db_selected = $this->conn->select_db(DB_NAME);
			$sql = "SET NAMES 'utf8'";
			$this->conn->query($sql);
			if (!$db_selected) {
				die ('Check the log for more details.');
			}
		}
	}

	public function Login($username,$password){
		$username = mysqli_escape_string($this->conn, $username);
		$password = sha1($password);
		$query = "SELECT * FROM utenti WHERE username = '".$username."' AND password = '".$password."'";
		$res = $this->conn->query($query);
		if (!$res) {
			echo $this->conn->error;
			return 0;
		}else{
			$line = mysqli_fetch_array($res, MYSQLI_NUM);
			return $line;
		}

	}

	public function escapestr($str){
		return mysqli_escape_string($this->conn, $str);
	}

	public function countaffectrow($query){
		$res = $this->conn->query($query, $this->conn);
		if (!$res) {
			die('Check the log for more details.');
		}else{
			return mysqli_num_rows($res);
		}
	}

	public function countrow(){
		return mysqli_affected_rows($this->conn);
	}

	public function lastInsertID(){
		return $this->conn->insert_id;
	}

	public function insert($query){
		$res = $this->conn->query($query);
		if (!$res) {
			echo $this->conn->error;
		}else{
			return $this->conn->insert_id;
		}
	}

	public function update($query){
		$res = $this->conn->query($query);
		if (!$res) {
			die('Check the log for more details.');
		}else{
			return mysqli_affected_rows($this->conn);
		}
	}

	public function delete($query){
		$res = $this->conn->query($query);
		if (!$res) {
			die('Check the log for more details.');
		}else{
			return mysqli_affected_rows($this->conn);
		}
	}

	public function query($query){
		$res = $this->conn->query($query);
		if (!$res) {
			die('Check the log for more details.');
		}else{
			$got = array();
			while($line = mysqli_fetch_array($res, MYSQLI_ASSOC)){
				array_push ($got, $line);
			}
			return $got;
		}
	}

	public function getIdByUser($username){
		$query = "SELECT id FROM users WHERE username = '".$username."'";
		$res = $this->conn->query($query);
		if (!$res) {
			//new EventLog(2,'MySQL', $this->conn->error);
			die('Check the log for more details.');
		}else{
			$line = mysqli_fetch_array($res, MYSQLI_ASSOC);
			return $line['id'];
		}
	}


	public function close(){
		//PHP B id=30525
		//@mysql_close($this->conn);
	}
}


?>
