<?php
require_once 'class_db.php';
require_once 'sanitize.php';

class CustomCategory{

	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}
	
	public function getFirstLevelCustomCategory($lev_1){
		$lev_1 = sanitize($lev_1, INT);
		$query = "SELECT * FROM custom_categories WHERE lev_1 = $lev_1 AND lev_2 = 0 AND lev_3 = 0";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res[0] : false;
	}
	
	public function getCustomCategoriesByLev1($lev_1){
		$lev_1 = sanitize($lev_1, INT);
		$query = "SELECT * FROM custom_categories WHERE lev_1 = $lev_1 ORDER BY lev_2, lev_3";
		$res = $this->db_conn->query($query);
		return $res;
	}
	
}