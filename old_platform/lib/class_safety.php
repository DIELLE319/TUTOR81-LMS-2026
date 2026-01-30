<?php
require_once 'class_db.php';
require_once 'sanitize.php';

class Safety{

	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}
	
	/**
	 * Verifica se esiste già un assegnazione in corso per l'utente
	 * 
	 * @param Integer $user_id
	 * @param Integer $assign_id
	 * @param Date $assign_start_date
	 * @return boolean
	 */
	private function userAssignmentExist($user_id, $assign_id, $assign_start_date){
		$query = "SELECT COUNT(*) as qta FROM user_assignments WHERE user_id = $user_id AND assign_id = $assign_id AND (assign_end_date IS NULL OR assign_end_date >= '$assign_start_date')";
		$res = $this->db_conn->query($query);
		return (bool)$res[0]['qta'];
	}
	
	public function getBusinessFunctions(){
		return $this->db_conn->query("SELECT * FROM business_functions");
	}
	
	public function getAssignments(){
		return $this->db_conn->query("SELECT * FROM assignments ORDER BY position");
	}
	
	public function getAtecoRisks(){
		return $this->db_conn->query("SELECT * FROM ateco_risks");
	}
	
	public function getSafetyCategories(){
		return $this->db_conn->query("SELECT * FROM custom_categories WHERE category_id = 0 OR category_id = 5 ORDER BY lev_1, lev_2, lev_3");
	}
	
	public function getSafetyLearningNeeds(){
		return $this->db_conn->query("SELECT * FROM learning_needs WHERE category_id = 5");
	}

	public function getLearningNeedBizFunc($learning_need_id){
		$learning_need_id = sanitize($learning_need_id, INT);
		$res = $this->db_conn->query("SELECT * FROM learning_need_biz_func WHERE learning_need_id = $learning_need_id");
		return isset($res[0]) ? $res[0] :false;
	}

	public function getLearningNeedAssign($learning_need_id){
		$learning_need_id = sanitize($learning_need_id, INT);
		$res = $this->db_conn->query("SELECT * FROM learning_need_assign WHERE learning_need_id = $learning_need_id");
		return isset($res[0]) ? $res[0] :false;
	}

	public function getLearningNeedAtecoRisk($learning_need_id){
		$learning_need_id = sanitize($learning_need_id, INT);
		$res = $this->db_conn->query("SELECT * FROM learning_need_ateco_risk WHERE learning_need_id = $learning_need_id");
		return isset($res[0]) ? $res[0] :false;
	}
	
	public function getLearningNeedCustomCategory($learning_need_id, $lev_1 = 0){
		$learning_need_id = sanitize($learning_need_id, INT);
		$lev_1 = sanitize($lev_1, INT);
		$and_lev_1 = $lev_1 ? "AND lev_1 = $lev_1" : "";
		$query = "SELECT * FROM learning_need_custom_categories JOIN custom_categories ON id = ccat_id WHERE learning_need_id = $learning_need_id $and_lev_1";
		$res = $this->db_conn->query($query);
		if (isset($res[0])) return $lev_1 ? $res[0] : $res;
		else return false;
	}
	
	public function getLearningNeedNewVersion($learning_need_id){
		$learning_need_id = sanitize($learning_need_id, INT);
		$biz_func_id = $this->getLearningNeedBizFunc($learning_need_id);
		$assign_id = $this->getLearningNeedAssign($learning_need_id);
		if ($biz_func_id) {
			$query = "SELECT learning_needs.* FROM learning_needs
								JOIN learning_need_biz_func ON id_learning_need = learning_need_biz_func.learning_need_id 
								JOIN learning_need_custom_categories ON id_learning_need = learning_need_custom_categories.learning_need_id
								WHERE id_learning_need = $learning_need_id AND ccat_id = 1";
		} elseif ($assign_id) {
			$query = "SELECT learning_needs.* FROM learning_needs
								JOIN learning_need_assign ON id_learning_need = learning_need_assign.learning_need_id 
								JOIN learning_need_custom_categories ON id_learning_need = learning_need_custom_categories.learning_need_id
								WHERE id_learning_need = $learning_need_id AND ccat_id = 1";
		} else {
			return false;
		}
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res[0] : false;
		
	}
	
	public function getUserLearningNeeds($user_id, $learning_need_id = 0){
		$user_id = sanitize($user_id, INT);
		$learning_need_id = sanitize($learning_need_id, INT);
		$and_learning_need = $learning_need_id ? "AND  learning_need_id = $learning_need_id": "";
		$query= "SELECT * FROM user_learning_needs
						JOIN learning_needs ON learning_need_id = id_learning_need
						JOIN tutors ON tutor_id = id_tutor
						WHERE user_id = $user_id $and_learning_need ORDER BY execution_date DESC";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res: false;
	}
	
	public function getUserAssignments($user_id, $assign_id = 0){
		$user_id = sanitize($user_id, INT);
		$assign_id = sanitize($assign_id, INT);
		$and_assign_id = $assign_id ? "AND assign_id = $assign_id" : "";
		$query = "SELECT * FROM user_assignments
							JOIN assignments ON assign_id = id_assign
							WHERE user_id = $user_id $and_assign_id 
							ORDER BY assign_id, assign_start_date DESC";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res : false;
	}
	
        public function getUserAssgnmentsFromCompany($company_id){
            $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
            $query = "SELECT user_assignments.assign_id as assign_id,
                        assignments.very_short_desc_assign as very_short_desc_assign,
                        assignments.short_desc_assign as short_desc_assign,
                        users.surname as surname,
                        users.name as name,
                        user_assignments.id_user_assign as id_user_assign,
                        user_assignments.assign_start_date as assign_start_date,
                        user_assignments.assign_end_date as assign_end_date
                      FROM user_assignments
                        JOIN users ON users.id = user_assignments.user_id
                        JOIN assignments ON assignments.id_assign = user_assignments.assign_id
                      WHERE assign_start_date <= CURDATE() 
                        AND (assign_end_date IS NULL OR assign_end_date >= CURDATE()) 
                        AND company_id = '$company_id'
                      ORDER BY assignments.position, users.surname, users.name";
            $res = $this->db_conn->query($query);
            return isset($res[0]) ? $res : false;
        }
        
        public function getUserAssgnmentsFromCompanyAndAssignId($company_id, $assign_id){
            $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
            $assign_id = filter_var($assign_id, FILTER_SANITIZE_NUMBER_INT);
            $query = "SELECT user_assignments.assign_id as assign_id,
                        assignments.very_short_desc_assign as very_short_desc_assign,
                        assignments.short_desc_assign as short_desc_assign,
                        users.surname as surname,
                        users.name as name,
                        user_assignments.id_user_assign as id_user_assign,
                        user_assignments.assign_start_date as assign_start_date,
                        user_assignments.assign_end_date as assign_end_date
                      FROM user_assignments
                        JOIN users ON users.id = user_assignments.user_id
                        JOIN assignments ON assignments.id_assign = user_assignments.assign_id
                      WHERE assign_start_date <= CURDATE() 
                        AND (assign_end_date IS NULL OR assign_end_date >= CURDATE()) 
                        AND users.company_id = '$company_id'
                        AND user_assignments.assign_id = $assign_id
                      ORDER BY users.surname, users.name";
            $res = $this->db_conn->query($query);
            return isset($res[0]) ? $res : false;
        }
        
	public function getTutorsByCompany($company_id){
		$company = sanitize($company_id, INT);
		$query = "SELECT * FROM tutors WHERE company_id = $company";
		$res = $this->db_conn->query($query);
		return isset($res[0]) ? $res : false;
	}
	
	public function addTutor($desc_tutor, $company_id){
		return $this->db_conn->insert("INSERT INTO tutors (desc_tutor, company_id) VALUES ('$desc_tutor', $company_id)");
	}
	
	public function addUserAssignment($user_id, $assign_id, $assign_start_date){
		$assign_start_date = $this->db_conn->escapestr($assign_start_date);
		$d = DateTime::createFromFormat('Y-m-d', $assign_start_date);
		if (!($d && $d->format('Y-m-d') == $assign_start_date)) return false;
		$user_id = sanitize($user_id, INT);
		$assign_id = sanitize($assign_id, INT);
		if ($this->userAssignmentExist($user_id, $assign_id, $assign_start_date)) return false;
		$query = "INSERT INTO user_assignments (user_id, assign_id, assign_start_date) VALUES ($user_id, $assign_id, '$assign_start_date')";
		$res = $this->db_conn->insert($query);
		return $res;
	}
	
	public function addLearningNeed($short_desc_learning_need,$long_desc_learning_need,$expiration_time,$code,$creator_id,$creation_date,$company_id,$category_id,$deleted){
		$short_desc_learning_need = $this->db_conn->escapestr($short_desc_learning_need);
		$long_desc_learning_need = $this->db_conn->escapestr($long_desc_learning_need);
		$expiration_time = sanitize($expiration_time, INT);
		$code = $this->db_conn->escapestr($code);
		$creator_id = sanitize($creator_id, INT);
		$creation_date = $this->db_conn->escapestr($creation_date);
		$d = DateTime::createFromFormat('Y-m-d H:i:s', $creation_date);
		if (!($d && $d->format('Y-m-d H:i:s') == $creation_date)) $creation_date = date("Y-m-d H:i:s");
		$company_id = sanitize($company_id, INT);
		$category_id = sanitize($category_id, INT);
		$deleted = sanitize($deleted, INT);
		$query = "INSERT INTO learning_needs (short_desc_learning_need, long_desc_learning_need, expiration_time, code, creator_id, creation_date, company_id, category_id, deleted)
							VALUES('$short_desc_learning_need','$long_desc_learning_need', $expiration_time, '$code',$creator_id,'$creation_date',$company_id,$category_id,$deleted)";
		$res = $this->db_conn->insert($query);
		return $res;
	}
	
	public function addLearningNeedBizFunc($learning_need_id,$biz_func_id){
		$learning_need_id = sanitize($learning_need_id, INT);
		$biz_func_id = sanitize($biz_func_id, INT);
		$query = "INSERT INTO learning_need_biz_func (learning_need_id,biz_func_id) VALUES ($learning_need_id,$biz_func_id)";
		$res = $this->db_conn->insert($query);
		return $res;
	}
	
	public function addLearningNeedAssign($learning_need_id,$assign_id){
		$learning_need_id = sanitize($learning_need_id, INT);
		$assign_id = sanitize($assign_id, INT);
		$query = "INSERT INTO learning_need_assign (learning_need_id,assign_id) VALUES ($learning_need_id,$assign_id)";
		$res = $this->db_conn->insert($query);
		return $res;
	}
	
	public function addLearningNeedAtecoRisk($learning_need_id,$ateco_risk_id){
		$learning_need_id = sanitize($learning_need_id, INT);
		$ateco_risk_id = sanitize($ateco_risk_id, INT);
		$query = "INSERT INTO learning_need_ateco_risk (learning_need_id,ateco_risk_id) VALUES ($learning_need_id,$ateco_risk_id)";
		$res = $this->db_conn->insert($query);
		return $res;
	}
	
	public function addLearningNeedCCat($learning_need_id,$ccat_id){
		$learning_need_id = sanitize($learning_need_id, INT);
		$ccat_id = sanitize($ccat_id, INT);
		$query = "INSERT INTO learning_need_custom_categories (learning_need_id,ccat_id) VALUES ($learning_need_id,$ccat_id)";
		$res = $this->db_conn->insert($query);
		return $res;
	}
	
	public function addUserLearningNeed($user_id, $learning_need_id, $execution_date, $tutor_id){
		$user_id = sanitize($user_id, INT);
		$learning_need_id = sanitize($learning_need_id, INT);
		if (!$user_id || !$learning_need_id) return false;
		$execution_date = $this->db_conn->escapestr($execution_date);
		$d = DateTime::createFromFormat('Y-m-d', $execution_date);
		if (!($d && $d->format('Y-m-d') == $execution_date)) $execution_date = date('Y-m-d');
		$tutor_id = sanitize($tutor_id, INT);
		return $this->db_conn->insert("INSERT INTO user_learning_needs (user_id, learning_need_id, execution_date,tutor_id) VALUES ($user_id,$learning_need_id,'$execution_date',$tutor_id)");
	}
	
	public function editUserAssignment($id_user_assign, $assign_start_date, $assign_end_date){
		$assign_start_date = $this->db_conn->escapestr($assign_start_date);
		$d = DateTime::createFromFormat('Y-m-d', $assign_start_date);
		if (!($d && $d->format('Y-m-d') == $assign_start_date)) return false;
		$assign_end_date = $this->db_conn->escapestr($assign_end_date);
		$d = DateTime::createFromFormat('Y-m-d', $assign_end_date);
		if (!($d && $d->format('Y-m-d') == $assign_end_date)) $assign_end_date = "NULL";
		else $assign_end_date = "'$assign_end_date'";
		$id_user_assign = sanitize($id_user_assign, INT);
		$query = "UPDATE user_assignments
							SET assign_start_date = '$assign_start_date',
									assign_end_date = $assign_end_date
							WHERE id_user_assign = $id_user_assign";
		$res = $this->db_conn->update($query);
		return $res;
	}
	
	public function setBusinessFunctionId($user_id, $business_function_id){
		$user_id = sanitize($user_id, INT);
		$business_function_id = sanitize($business_function_id, INT);
		$query = "UPDATE users SET business_function_id = $business_function_id WHERE id = $user_id";
		$res = $this->db_conn->update($query);
		return $res;
	}
	
	public function suspendLearningNeed($id_learning_need, $creator_id){
		$id_learning_need = sanitize($id_learning_need, INt);
		$creator_id = sanitize($creator_id, INT);
		$query = "UPDATE learning_needs SET deleted = 1 WHERE id_learning_need = $id_learning_need AND creator_id = $creator_id";
		$res = $this->db_conn->update($query);
		return $res;
	}
	
	public function deleteUserAssignment($id_user_assign){
		$id_user_assign = sanitize($id_user_assign, INT);
		$query = "DELETE FROM user_assignments WHERE id_user_assign = $id_user_assign";
		$res = $this->db_conn->delete($query);
		return $res;
	}
	
}