<?php
require_once 'class_db.php';
require_once 'sanitize.php';

class Risk{

	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}
	
	
	
}