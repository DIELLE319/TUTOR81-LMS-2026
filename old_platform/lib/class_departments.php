<?php

require_once 'class_db.php';
require_once 'sanitize.php';

class Departments {

    var $db_conn;

    public function __construct() {
        $this->db_conn = new MySQLConn();
    }

    /**
     * 
     * @param String $short_desc MUST SANITIZED BEFORE
     * @param Integer $company_id MUST SANITIZED BEFORE
     */
    private function productUnitExist($short_desc, $company_id) {
        $query = "SELECT COUNT(*) as qta FROM product_units WHERE short_desc_pu = '$short_desc' AND company_id = $company_id";
        $res = $this->db_conn->query($query);
        return (bool) $res[0]['qta'];
    }

    /**
     *
     * @param String $short_desc MUST SANITIZED BEFORE
     * @param Integer $company_id MUST SANITIZED BEFORE
     */
    private function departmentTypeExist($short_desc, $company_id) {
        $query = "SELECT COUNT(*) as qta FROM department_types WHERE short_desc_dep_type = '$short_desc' AND company_id = $company_id";
        $res = $this->db_conn->query($query);
        return (bool) $res[0]['qta'];
    }

    /**
     * 
     * @param Integer $dep_type_id MUST SANITIZED BEFORE
     * @return boolean
     */
    private function isDepartmentTypeInUse($dep_type_id) {
        $query = "SELECT COUNT(*) as qta FROM departments WHERE dep_type_id = $dep_type_id";
        $res = $this->db_conn->query($query);
        return (bool) $res[0]['qta'];
    }

    /**
     * 
     * @param Integer $dep_id MUST SANITIZED BEFORE
     * @return boolean
     */
    private function isDepartmentInUse($dep_id) {
        $query = "SELECT COUNT(*) as qta FROM department_employees WHERE dep_id = $dep_id";
        $res = $this->db_conn->query($query);
        return (bool) $res[0]['qta'];
    }

    /**
     *
     * @param Integer $pu_id MUST SANITIZED BEFORE
     * @return boolean
     */
    private function isProductUnitInUse($pu_id) {
        $query = "SELECT COUNT(*) as qta FROM departments WHERE pu_id = $pu_id";
        $res = $this->db_conn->query($query);
        return (bool) $res[0]['qta'];
    }

    /**
     * 
     * @param Integer $dep_type_id MUST SANITIZED BEFORE
     * @param Integer $pu_id MUST SANITIZED BEFORE
     */
    private function departmentExistInProductUnit($dep_type_id, $pu_id) {
        $query = "SELECT COUNT(*) as qta FROM departments WHERE dep_type_id = $dep_type_id AND pu_id = $pu_id";
        $res = $this->db_conn->query($query);
        return (bool) $res[0]['qta'];
    }

    /**
     * 
     * @param Integer $user_id MUST SANITIZED BEFORE
     * @param String $hire_date MUST SANITIZED BEFORE
     */
    private function checkEmployeeInAllDepartments($user_id, $hire_date) {
        $query = "SELECT COUNT(*) as qta FROM department_employees 
                  WHERE user_id = $user_id AND (dismissal_date IS NULL OR dismissal_date >= '$hire_date')";
        $res = $this->db_conn->query($query);
        return (bool) $res[0]['qta'];
    }

    public function getProductUnits($company_id) {
        $company_id = sanitize($company_id, INT);
        $query = "SELECT * FROM product_units WHERE company_id = $company_id ORDER BY short_desc_pu";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    public function getProductUnitByShortDescription($short_desc_pu, $company_id){
        $short_desc_pu = $this->db_conn->escapestr(trim($short_desc_pu));
        $company_id = sanitize($company_id, INT);
        $query = "SELECT * FROM product_units WHERE short_desc_pu = '$short_desc_pu' AND company_id = $company_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getProductUnitDetail($id_pu) {
        $id_pu = sanitize($id_pu, INT);
        $query = "SELECT * FROM product_units WHERE id_pu = $id_pu";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getDepartmentDetail($id_dep) {
        $id_dep = sanitize($id_dep, INT);
        $query = "SELECT * FROM departments JOIN department_types ON dep_type_id = id_dep_type WHERE id_dep = $id_dep";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }
    
    public function getRisksList(){
        $res = $this->db_conn->query("SeleCT * FROM risks ORDER BY position");
        return isset($res[0]) ? $res : false;
    }

    public function getDepartmentTypes($company_id) {
        $company_id = sanitize($company_id, INT);
        $query = "SELECT * FROM department_types WHERE company_id = $company_id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getDepartmentsTypeById($id_dep_type) {
        $id_dep_type = sanitize($id_dep_type, INT);
        $query = "SELECT * FROM department_types WHERE id_dep_type = $id_dep_type";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getDepartmentsTypeByShortDescription($short_desc_dep_type, $company_id) {
        $short_desc_dep_type = $this->db_conn->escapestr(trim($short_desc_dep_type));
        $company_id = sanitize($company_id, INT);
        $query = "SELECT * FROM department_types WHERE short_desc_dep_type = '$short_desc_dep_type' AND company_id = $company_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getDepartmentsByProductUnit($pu_id) {
        $pu_id = sanitize($pu_id, INT);
        $query = "SELECT * FROM departments
                    JOIN department_types ON dep_type_id = id_dep_type
                  WHERE pu_id = $pu_id 
                  ORDER BY short_desc_dep_type";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getDepartmentsByDepartmentType($dep_type_id) {
        $dep_type_id = sanitize($dep_type_id, INT);
        $query = "SELECT * FROM departments WHERE dep_type_id = $dep_type_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    public function getIdDepartmentByDepartmentTypeAndProductUnit($dep_type_id, $pu_id){
        $dep_type_id = sanitize($dep_type_id,INT);
        $pu_id = sanitize($pu_id, INT);
        $query = "SELECT * FROM departments WHERE dep_type_id = $dep_type_id AND pu_id = $pu_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0]['id_dep']: false;
    }

    public function getDepartmentsByCompany($company_id) {
        $company_id = sanitize($company_id, INT);
        $query = "SELECT * 
                  FROM department_types 
                    LEFT JOIN departments ON id_dep_type = dep_type_id
                  WHERE company_id = $company_id
                  ORDER BY short_desc_dep_type";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getEmployeesByDepartments($dep_id) {
        $dep_id = sanitize($dep_id, INT);
        $query = "SELECT * 
                  FROM users 
                    JOIN department_employees ON users.id = user_id 
                  WHERE dep_id = $dep_id AND dismissal_date IS NULL 
                  ORDER BY surname, name";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getEmployeesByProductUnit($pu_id) {
        $pu_id = sanitize($pu_id, INT);
        $query = "SELECT * FROM users 
                    JOIN department_employees ON users.id = user_id
                    JOIN departments ON dep_id = id_dep 
                  WHERE pu_id = $pu_id AND dismissal_date IS NULL 
                  ORDER BY short_desc_dep, surname, name";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getEmployeesByCompany($company_id, $all = true) {
        $company_id = sanitize($company_id, INT);
        if (filter_var($all, FILTER_VALIDATE_BOOLEAN)) {
            $query = "SELECT * FROM users
                        LEFT JOIN 
                            (
                                department_employees 
                                JOIN departments ON dep_id = id_dep
                                JOIN product_units ON pu_id = id_pu 
                                JOIN department_types ON dep_type_id = id_dep_type
                            ) 
                        ON users.id = user_id
                      WHERE users.company_id = $company_id
                      ORDER BY surname, name, hire_date DESC, short_desc_pu, short_desc_dep_type";
        } else {
            $query = "SELECT * FROM users 
                        JOIN department_employees ON users.id = user_id
                        JOIN departments ON dep_id = id_dep
                        JOIN product_units ON pu_id = id_pu 
                        JOIN department_types ON dep_type_id = id_dep_type
                      WHERE users.company_id = $company_id AND dismissal_date IS NULL 
                      ORDER BY short_desc_pu, short_desc_dep_type, surname, name";
        }
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getEmployeeDetail($user_id) {
        $user_id = sanitize($user_id, INT);
        $query = "SELECT *, users.company_id as user_company_id FROM users 
                    LEFT JOIN department_employees ON users.id = user_id
                    LEFT JOIN departments ON dep_id = id_dep
                    LEFT JOIN product_units ON pu_id = id_pu 
                    LEFT JOIN department_types ON dep_type_id = id_dep_type
                  WHERE users.id = $user_id 
                  ORDER BY hire_date DESC, short_desc_pu, short_desc_dep_type";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getProductUnitCustomCategories($pu_id) {
        $pu_id = sanitize($pu_id, INT);
        $query = "SELECT * FROM product_unit_custom_categories JOIN custom_categories ON id = ccat_id WHERE pu_id = $pu_id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getProductUnitSpecificCustomCategories($pu_id, $lev_1) {
        $pu_id = sanitize($pu_id, INT);
        $lev_1 = sanitize($lev_1, INT);
        $query = "SELECT * FROM product_unit_custom_categories JOIN custom_categories ON id = ccat_id WHERE pu_id = $pu_id AND lev_1 = $lev_1";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }
    
    public function getProductUnitAtecoRisk($pu_id) {
        $pu_id = sanitize($pu_id, INT);
        $query = "SELECT * FROM product_unit_ateco_risk JOIN ateco_risks ON ateco_risk_id = id_ateco_risk WHERE pu_id = $pu_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getProductUnitAteco($pu_id) {
        $pu_id = sanitize($pu_id, INT);
        $query = "SELECT * FROM product_unit_ateco JOIN ateco_sectors ON ateco_id = ateco_sectors.id WHERE pu_id = $pu_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getAtecoRisks() {
        $query = "SELECT * FROM ateco_risks";
        $res = $this->db_conn->query($query);
        return $res;
    }
    
    public function getAtecoList() {
        $query = "SELECT * FROM ateco_sectors ORDER BY name";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function addProductUnit($short_desc, $long_desc, $company_id) {
        $short_desc = $this->db_conn->escapestr($short_desc);
        $company_id = sanitize($company_id, INT);
        if ($this->productUnitExist($short_desc, $company_id))
            return false;
        $long_desc = $this->db_conn->escapestr($long_desc);
        $query = "INSERT INTO product_units (short_desc_pu, long_desc_pu, company_id) VALUE ('$short_desc','$long_desc',$company_id)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function addDepartmentType($short_desc, $long_desc, $company_id) {
        $short_desc = $this->db_conn->escapestr($short_desc);
        $company_id = sanitize($company_id, INT);
        if ($this->departmentTypeExist($short_desc, $company_id))
            return false;
        $long_desc = $this->db_conn->escapestr($long_desc);
        $query = "INSERT INTO department_types (short_desc_dep_type, long_desc_dep_type, company_id) VALUE ('$short_desc','$long_desc', $company_id)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function addDepartmentInProductUnit($dep_type_id, $pu_id) {
        $dep_type_id = sanitize($dep_type_id, INT);
        if ($this->departmentExistInProductUnit($dep_type_id, $pu_id))
            return false;
        $pu_id = sanitize($pu_id, INT);
        $query = "INSERT INTO departments (dep_type_id, pu_id) VALUES ($dep_type_id, $pu_id)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function addEmployeeInDepartment($user_id, $dep_id, $hire_date) {
        $hire_date = $this->db_conn->escapestr($hire_date);
        $d = DateTime::createFromFormat('Y-m-d', $hire_date);
        if (!($d && $d->format('Y-m-d') == $hire_date))
            return false;
        $user_id = sanitize($user_id, INT);
        if ($this->checkEmployeeInAllDepartments($user_id, $hire_date))
            return false;
        $dep_id = sanitize($dep_id, INT);
        $query = "INSERT INTO department_employees (dep_id, user_id, hire_date) VALUES ($dep_id, $user_id, '$hire_date')";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function addAtecoSectorInProductUnit($pu_id, $ateco_id) {
        $pu_id = sanitize($pu_id, INT);
        $ateco_id = sanitize($ateco_id, INT);
        $query = "INSERT INTO product_unit_ateco (pu_id, ateco_id) VALUES ($pu_id,$ateco_id)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function editAtecoSectorInProductUnit($id_pu_ateco, $ateco_id) {
        $id_pu_ateco = sanitize($id_pu_ateco, INT);
        $ateco_id = sanitize($ateco_id, INT);
        $query = "UPDATE product_unit_ateco SET ateco_id = $ateco_id WHERE id_pu_ateco = $id_pu_ateco";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function addAtecoRiskInProductUnit($pu_id, $ateco_risk_id) {
        $pu_id = sanitize($pu_id, INT);
        $ateco_risk_id = sanitize($ateco_risk_id, INT);
        $query = "INSERT INTO product_unit_ateco_risk (pu_id, ateco_risk_id) VALUES ($pu_id,$ateco_risk_id)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function editAtecoRiskInProductUnit($id_pu_ateco_risk, $ateco_risk_id) {
        $id_pu_ateco_risk = sanitize($id_pu_ateco_risk, INT);
        $ateco_risk_id = sanitize($ateco_risk_id, INT);
        $query = "UPDATE product_unit_ateco_risk SET ateco_risk_id = $ateco_risk_id WHERE id_pu_ateco_risk = $id_pu_ateco_risk";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function addProductUnitCustomCategory($pu_id, $ccat_id) {
        $pu_id = sanitize($pu_id, INT);
        $ccat_id = sanitize($ccat_id, INT);
        $query = "INSERT INTO product_unit_custom_categories (pu_id, ccat_id) VALUES ($pu_id,$ccat_id)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function setProductUnit($id_pu, $short_desc_pu, $long_desc_pu) {
        $id_pu = sanitize($id_pu, INT);
        $short_desc_pu = $this->db_conn->escapestr($short_desc_pu);
        $long_desc_pu = $this->db_conn->escapestr($long_desc_pu);
        $query = "UPDATE product_units 
							SET short_desc_pu = '$short_desc_pu', 
									long_desc_pu = '$long_desc_pu'
							WHERE id_pu = $id_pu";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function setDepartmentType($id_dep_type, $short_desc_dep_type, $long_desc_dep_type) {
        $id_dep_type = sanitize($id_dep_type, INT);
        $short_desc_dep_type = $this->db_conn->escapestr($short_desc_dep_type);
        $long_desc_dep_type = $this->db_conn->escapestr($long_desc_dep_type);
        $query = "UPDATE department_types 
							SET short_desc_dep_type = '$short_desc_dep_type', 
									long_desc_dep_type = '$long_desc_dep_type'
							WHERE id_dep_type = $id_dep_type";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function setDepartment($id_dep, $dep_type_id) {
        $id_dep = sanitize($id_dep, INT);
        $dep_type_id = sanitize($dep_type_id, INT);
        $query = "UPDATE departments 
                  SET dep_type_id = $dep_type_id
                  WHERE id_dep = $id_dep";
        $res = $this->db_conn->update($query);
        return $res;
    }
    
    public function setDepartmentRisks($id_dep, $risks, $other_risk){
        $id_dep = filter_var($id_dep, FILTER_SANITIZE_NUMBER_INT);
        if (is_string($risks)) $risks = explode (',', $risks);
        $risks = filter_var_array($risks, FILTER_SANITIZE_NUMBER_INT);
        $risks = !empty($risks) ? implode(',', $risks) : '';
        $other_risk = $this->db_conn->escapestr(htmlentities(trim($other_risk), ENT_QUOTES));
        $query = "UPDATE departments 
                  SET risks = '$risks',
                      other_risk = '$other_risk'
                  WHERE id_dep = $id_dep";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function editProductUnitCustomCategory($id_pu_ccat, $ccat_id) {
        $id_pu_ccat = sanitize($id_pu_ccat, INT);
        $ccat_id = sanitize($ccat_id, INT);
        $query = "UPDATE product_unit_custom_categories SET ccat_id = $ccat_id WHERE id_pu_ccat = $id_pu_ccat";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function deleteDepartmentType($id_dep_type) {
        $id_dep_type = sanitize($id_dep_type, INT);
        if ($this->isDepartmentTypeInUse($id_dep_type))
            return false;
        $query = "DELETE FROM department_types WHERE id_dep_type = $id_dep_type";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function deleteDepartment($id_dep) {
        $id_dep = sanitize($id_dep, INT);
        if ($this->isDepartmentInUse($id_dep))
            return false;
        $query = "DELETE FROM departments WHERE id_dep = $id_dep";
        $res = $this->db_conn->delete($query);
        return $res;
    }

    public function deleteProductUnit($id_pu) {
        $id_pu = sanitize($id_pu, INT);
        if ($this->isProductUnitInUse($id_pu))
            return false;
        $query = "DELETE FROM product_units WHERE id_pu = $id_pu;";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function deleteEmployeeInDepartment($id_dep_empl) {
        $id_dep_empl = sanitize($id_dep_empl, INT);
        $query = "DELETE FROM department_employees WHERE id_dep_empl = $id_dep_empl";
        $res = $this->db_conn->delete($query);
        return $res;
    }

    public function deleteHistoryEmployeeDepartment($user_id) {
        $user_id = sanitize($user_id, INT);
        $query = "DELETE FROM department_employees WHERE user_id = $user_id";
        $res = $this->db_conn->delete($query);
        return $res;
    }

    public function dismissEmployeeByIdDepEmplId($id_dep_empl, $dismissal_date) {
        $id_dep_empl = sanitize($id_dep_empl, INT);
        $dismissal_date = $this->db_conn->escapestr($dismissal_date);
        $d = DateTime::createFromFormat('Y-m-d', $dismissal_date);
        if (!($d && $d->format('Y-m-d') == $dismissal_date))
            return false;
        $query = "UPDATE department_employees SET dismissal_date = '$dismissal_date' WHERE id_dep_empl = $id_dep_empl AND dismissal_date IS NULL";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function dismissEmployeeByUserId($user_id, $dismissal_date) {
        $user_id = sanitize($user_id, INT);
        $dismissal_date = $this->db_conn->escapestr($dismissal_date);
        $d = DateTime::createFromFormat('Y-m-d', $dismissal_date);
        if (!($d && $d->format('Y-m-d') == $dismissal_date))
            return false;
        $query = "UPDATE department_employees SET dismissal_date = '$dismissal_date' WHERE user_id = $user_id AND dismissal_date IS NULL";
        $res = $this->db_conn->update($query);
        return $res;
    }

}
