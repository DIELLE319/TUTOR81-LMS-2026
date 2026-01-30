<?php
require_once 'class_db.php';
require_once 'sanitize.php';

class Permissions{

	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}

	public function getUserPermissions($user_id){
		$user_id = sanitize($user_id, INT);
		$query = "SELECT * 
							FROM user_roles
							JOIN permissions ON user_roles.role_id = permissions.role_id
							WHERE user_roles.user_id = $user_id";
		$res = $this->db_conn->query($query);

		$pemissions = array();
		foreach ($res as $row){
			if (! key_exists($row["area_id"], $permissions)){
				$permissions[$row["area_id"]] = array("view" => bool($row["view"]), "insert" => bool($row["insert"]), "update" => bool($row["update"]), "delete" => bool($res["delete"]));
			} else {
				if ((! $permissions[$row["area_id"]]["view"]) && $row["view"]) $permissions[$row["area_id"]]["view"] = true;
				if ((! $permissions[$row["area_id"]]["insert"]) && $row["insert"]) $permissions[$row["area_id"]]["insert"] = true;
				if ((! $permissions[$row["area_id"]]["update"]) && $row["update"]) $permissions[$row["area_id"]]["update"] = true;
				if ((! $permissions[$row["area_id"]]["delete"]) && $row["delete"]) $permissions[$row["area_id"]]["delete"] = true;
			}
		}


		return $permissions;
	}
	
	public function getRoles(){
		return $this->db_conn->query("SELECT * FROM roles");
	}
	
	public function getRoleById($id_role){
		$id_role = sanitize($id_role, INT);
		$query = "SELECT * FROM roles WHERE id_role = $id_role";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res[0] : false;
	}
	
	public function getUserRole($user_id){
		$user_id = sanitize($user_id, INT);
		$query = "SELECT roles.* FROM roles JOIN users ON roles.id_role = users.role WHERE users.id = $user_id";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res[0] : false;
	}


}