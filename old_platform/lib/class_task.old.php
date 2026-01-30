<?php
require_once 'class_db.php';
require_once 'sanitize.php';

class Task{

	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}
	
	private function taskExist($short_desk_task, $company_id){
		$query = "SELECT COUNT(*) as qta FROM tasks WHERE short_desk_task = '$short_desk_task' AND company_id = $company_id";
		return (bool)$res[0]['qta'];
	}
	
	private function isTaskAssigned($task_id){
		return false;
	}
	
	public function getTasksByCompany($company_id){
		$company_id = sanitize($company_id, INT);
		$query = "SELECT * FROM tasks WHERE company_id = $company_id";
		$res = $this->db_conn->query($query);
		return $res;
	}
	
	public function addTask($short_desc_task, $long_desc_task, $company_id){
		$short_desc_task = $this->db_conn->escapestr($short_desc_task);
		$company_id = sanitize($company_id, INT);
		if ($this->taskExist($short_desk_task, $company_id)) return false;
		$long_desc_task = $this->db_conn->escapestr($long_desc_task);
		$query = "INSERT INTO tasks (short_desc_task, long_desc_task, company_id) VALUES ('$short_desc_task','$long_desc_task',$company_id)";
		$res = $this->db_conn->insert($query);
		return $res;
	}
	
	public function deletTask($id_task){
		$id_task = sanitize($id_task, INT);
		if ($this->isTaskAssigned($id_task)) return false;
		$query = "DELETE FROM tasks WHERE id_task = $id_task";
		$res = $this->db_conn->query($query);
		return $res;		
	}
	
	
}