<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 28-gen-2015
 * File: lib/class_user.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once dirname(__FILE__).'/../config.php';
require_once BASE_LIBRARY_PATH . 'class_db.php';
require_once BASE_LIBRARY_PATH . 'sanitize.php';

class T81User {

    var $db_conn;
    
    /**
     * For update this file get it from permalink https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.csv
     * @var location file on server
     */
    private static $comuni_codes_file = BASE_LIBRARY_PATH . 'Elenco-comuni-italiani.csv';
    private static $alphabet_months = array('A' => '01',
                                            'B' => '02',
                                            'C' => '03',
                                            'D' => '04',
                                            'E' => '05',
                                            'H' => '06',
                                            'L' => '07',
                                            'M' => '08',
                                            'P' => '09',
                                            'R' => '10',
                                            'S' => '11',
                                            'T' => '12');
    
    /**
     * return born data from inversion of the taxcode
     * 
     * @param string $tax_code
     * @return array $born_data = array('born_date' => "YYYY-mm-dd", 
     *                                  'born_gender' => 'M/F', 
     *                                  'born_city' => 'city');
     */
    public static function getBornDataFromTaxCode($tax_code){
        $tax_code = filter_var($tax_code, FILTER_SANITIZE_STRING); //TODO: validate tax code
        $born_year = (int) substr($tax_code, 6, 2);
        $born_year += $born_year > date('y') ? 1900 : 2000;
        $born_month = self::$alphabet_months[substr($tax_code, 8,1)];
        $born_day = (int) substr($tax_code, 9, 2);
        $born_gender = $born_day > 40 ? 'F' : 'M'; 
        $born_day -= $born_gender === 'F' ? 40 : 0;
        $born_day = $born_day > 9 ? $born_day : '0'.$born_day;
        $born_data = array('born_date' => "$born_year-$born_month-$born_day", 'born_gender' => $born_gender, 'born_city' => '');
        $born_city = substr($tax_code, 11, 4);
        if (file_exists(self::$comuni_codes_file) && is_readable(self::$comuni_codes_file)) {
            $header = NULL;
            if (($handle = fopen(self::$comuni_codes_file, 'r')) !== FALSE)
            {
                while (($row = fgetcsv($handle, 1000, ';')) !== FALSE)
                {
                    if(!$header)
                        $header = $row;
                    elseif ($row[18] == $born_city) {
                        $born_data['born_city'] = htmlentities($row[5]);
                        break;
                    }
                }
                fclose($handle);
            }
        }
        return $born_data;
    } 

    public function __construct() {
        $this->db_conn = new MySQLConn();
    }

    public function usernameExist($username, $user_id = 0) {
        $username = $this->db_conn->escapestr(trim($username));
        $user_id = sanitize($user_id, INT);
        $query = "SELECT COUNT(id) as conta FROM users WHERE username = '$username' AND id <> $user_id";
        $username_count = $this->db_conn->query($query);
        return $username_count[0]['conta'] > 0;
    }

    public function taxCodeExist($tax_code, $user_id = 0) {
        $tax_code = $this->db_conn->escapestr(trim($tax_code));
        $user_id = sanitize($user_id, INT);
        $query = "SELECT COUNT(id) as qta FROM users WHERE tax_code = '$tax_code' AND id <> $user_id";
        $res = $this->db_conn->query($query);
        return $res[0]['qta'] > 0;
    }

    public function taxCodeOrEmailExist($tax_code, $email, $user_id = 0) {
        $tax_code = $this->db_conn->escapestr(trim($tax_code));
        $email = $this->db_conn->escapestr(trim($email));
        $user_id = sanitize($user_id, INT);
        $query = "SELECT id FROM users WHERE tax_code = '$tax_code' AND id <> $user_id AND email='$email'";
        $res = $this->db_conn->query($query);
        return $res;
    }

    private function assignUserGroup($user_id) {
        $user_id = sanitize($user_id, INT);
        $query = "INSERT INTO user_system_groups(user_id,system_group_id)VALUES(" . $user_id . ",1)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function createUser($assignto, $role, $name, $surname, $username, $password, $email, $company_id, $tax_code, $func_id=1) {
        $tax_code = strtoupper($this->db_conn->escapestr(trim($tax_code)));

        if ($tax_code != "" && $this->taxCodeExist($tax_code))
            return "UTENTE";

        $name = $this->db_conn->escapestr(trim($name));
        $surname = $this->db_conn->escapestr(trim($surname));
        $username = trim($username);
        $username = strtolower(str_replace(array(" ", "'", '"'), "", $username));
        $username = $this->db_conn->escapestr($username);
        $new_username = $username;
        $suffix = 1;
        while ($this->usernameExist($username)) {
            $username = $new_username . ($suffix++);
        }
        $email = $this->db_conn->escapestr(trim($email));
        $company_id = sanitize($company_id, INT);
        $assignto = sanitize($assignto, INT);
        $role = filter_var($role, FILTER_SANITIZE_NUMBER_INT);
        $func_id = filter_var($func_id, FILTER_SANITIZE_NUMBER_INT) ? : 1;
        $creation_date = date("Y-m-d H:i:s");
        $address = ' ';
        $cap = '';
        $code = sha1($username);
        $password = sha1($password);
        $suspended = 0;
        $deleted = 0;
        $language_id = 1;
        $force_reset = 0;

        $query = "INSERT INTO users(role,name,surname,creation_date,creator_id,address,
                                            cap,code,email,suspended,password,deleted,username,
                                            company_id,language_id,tax_code,force_reset,business_function_id)
                            VALUES($role,'$name','$surname','$creation_date',$assignto,'$address',
                                    '$cap','$code','$email',$suspended,'$password',$deleted,'$username',
                                        $company_id,$language_id,'$tax_code',$force_reset,$func_id)";

        $res = $this->db_conn->insert($query);
        $user_id = $res;
        $this->assignUserGroup($user_id);
        return $res;
    }

    public function editUser($user_id, $role, $name, $surname, $email, $tax_code, $username, $func_id) {
        $user_id = sanitize($user_id, INT);
        $tax_code = strtoupper($this->db_conn->escapestr(trim($tax_code)));

        if ($this->taxCodeExist($tax_code, $user_id))
            return "UTENTE";

        $username = $this->db_conn->escapestr(trim($username));
        $new_username = $username;
        $suffix = 1;
        while ($this->usernameExist($username, $user_id)) {
            $username = $new_username . ($suffix++);
        }

        $role = sanitize($role, INT);
        $name = $this->db_conn->escapestr(trim($name));
        $surname = $this->db_conn->escapestr(trim($surname));
        $email = $this->db_conn->escapestr(trim($email));
        $func_id = sanitize($func_id, INT);
        $query = "UPDATE users SET
								role = $role,	
								name = '$name', 
								surname = '$surname',	
								email = '$email',	
								username = '$username',	
								tax_code = '$tax_code',	
								business_function_id = $func_id 
							WHERE id = $user_id";

        $res = $this->db_conn->update($query);

        if (is_string($res)) {
            $res .= "',$user_id,$role,$name,$surname,$email,$username,$tax_code,$func_id";
        }
        return $res;
    }

    public function disableUser($id) {
        $id = sanitize($id, INT);
        $query = "UPDATE users SET deleted = 1 WHERE id = " . $id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function enableUser($id) {
        $id = sanitize($id, INT);
        $query = "UPDATE users SET deleted = 0 WHERE id = " . $id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function deleteUser($id) {
        $id = sanitize($id, INT);
        $query = "DELETE FROM users WHERE id = " . $id;
        $res = $this->db_conn->delete($query);
        return $res;
    }

    public function setRole($id, $role, $password = "") {
        $id = sanitize($id, INT);
        $role = sanitize($role, INT);
        if (trim($password) != "") {
            $query = "UPDATE users SET role = " . $role . ", password = '" . sha1($password) . "' WHERE id = " . $id;
        } else {
            $query = "UPDATE users SET role = " . $role . " WHERE id = " . $id;
        }
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function loginWithUsernameAndTaxCode($username, $tax_code) {
        $username = $this->db_conn->escapestr($username);
        $tax_code = $this->db_conn->escapestr($tax_code);
        $query = "SELECT * FROM users WHERE tax_code = '$tax_code' AND username = '$username' AND deleted = 0";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function loginWithUsernameAndPassword($username, $password) {
        $username = $this->db_conn->escapestr($username);
        $password = $this->db_conn->escapestr($password);
        $query = "SELECT * FROM users WHERE password = '" . sha1($password) . "' AND username = '$username' AND deleted = 0";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    /**
     * Restituisce l'utente in base al codice fiscale
     * 
     * @param type $tax_code
     * @return type
     */
    public function getUserByTaxCode($tax_code) {
        $tax_code = $this->db_conn->escapestr($tax_code);
        $query = "SELECT * FROM users WHERE tax_code = '$tax_code'";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getUserByEmailAndTaxCode($email, $tax_code) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $tax_code = $this->db_conn->escapestr($tax_code);
        $query = "SELECT * FROM users WHERE email = '$email' AND tax_code = '$tax_code' AND deleted = 0";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getDetail($user_id) {
        $user_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT users.*, 
                companies.business_name,  
                business_functions.name as business_function 
                FROM users 
                JOIN companies ON users.company_id = companies.id 
                JOIN business_functions ON users.business_function_id = business_functions.id 
                WHERE users.id = $user_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getUserLicenseById($license_id) {
        $license_id = filter_var($license_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT * FROM learning_project_users WHERE id = $license_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getUserLearningActivity($user_id, $started = -1, $completed = -1) {
        $user_id = sanitize($user_id, INT);
        if ($started === 0) {
            $started = " AND learning_events.start_date_time IS NULL";
        } elseif ($started === 1) {
            $started = " AND learning_events.start_date_time IS NOT NULL";
        } else {
            $started = "";
        }
        if ($completed === 0) {
            $completed = " AND (learning_events.end_date_time IS NULL OR learning_events.end_date_time = '0000-00-00 00:00:00')";
        } elseif ($completed === 1) {
            $completed = " AND (learning_events.end_date_time IS NOT NULL AND learning_events.end_date_time <> '0000-00-00 00:00:00')";
        } else {
            $completed = "";
        }
        $query = "SELECT learning_project_users.*,
								learning_project.title,
								learning_events.id as learning_event_id,
								learning_events.end_date_time,
								learning_events.start_date_time
				  		FROM learning_project_users 
								JOIN learning_project ON learning_project_id = learning_project.id
								LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
							WHERE user_id = $user_id $started $completed";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function removeLicense($license_id) {
        $license_id = sanitize($license_id, INT);
        $query = "DELETE FROM learning_project_users WHERE id = " . $license_id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function getUserCompany($user_id) {
        $user_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
        //FIXME: JOIN provinces ON companies.province_id = provinces.id
        $query = "SELECT companies.* FROM companies JOIN users ON users.company_id = companies.id WHERE users.id = " . $user_id;
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getUserCompanyOwner($company_id) {
        $company_id = sanitize($company_id, INT);
        //FIXME: JOIN provinces ON companies.province_id = provinces.id
        $query = "SELECT companies.owner_user_id FROM companies WHERE companies.id = " . $company_id;
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0]['owner_user_id'] : false;
    }

    public function getBusinessFunctions() {
        $query = "SELECT * FROM business_functions";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function setUserPassword($user_id, $password) {
        $user_id = sanitize($user_id, INT);
        $password = sha1(trim($password));
        $query = "UPDATE users SET password = '$password' WHERE id = $user_id";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function addTosUserAuthorization($user_id) {
        $user_id = sanitize($user_id, INT);
        $query = "SELECT * FROM tos_user_auth WHERE user_id = $user_id";
        $res = $this->db_conn->query($query);
        if (!isset($res[0])) {
            $now = date('Y-m-d H:i:s');
            $query = "INSERT INTO tos_user_auth (user_id,creation_date,last_update,authorized) 
								VALUES ($user_id,'$now','$now',1)";
            $res = $this->db_conn->insert($query);
            return $res > 0 ? : false;
        } else {
            return false;
        }
    }

    public function getTosUserAuthorization($user_id) {
        $user_id = sanitize($user_id, INT);
        $query = "SELECT * FROM tos_user_auth WHERE user_id = $user_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function setTosUserAuthorizationByUser($user_id, $authorized) {
        $user_id = sanitize($user_id, INT);
        $authorized = sanitize($authorized, INT);
        $now = date('Y-m-d H:i:s');
        $query = "UPDATE tos_user_auth SET last_update = '$now', authorized = $authorized WHERE user_id = $user_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function createRecoverPasswordCode($user_id) {
        $user_id = sanitize($user_id, INT);
        $code = uniqid('', true);
        $creation_date = date("Y-m-d H:i:s");
        $query = "INSERT INTO recover_password (code,creation_date,user_id) VALUES ('$code','$creation_date',$user_id)";
        $res = $this->db_conn->insert($query);
        return $res > 0 ? $code : false;
    }

    public function checkRecoverPasswordCode($code) {
        $code = $this->db_conn->escapestr($code);
        $now = date("Y-m-d H:i:s");
        $query = "DELETE FROM recover_password WHERE code = '$code' AND creation_date < DATE_SUB('$now', INTERVAL 24 HOUR)";
        $this->db_conn->update($query);
        $query = "SELECT * FROM recover_password WHERE code = '$code'";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }
    
    public function searchUser($term){
        $term = filter_var($term, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $query = "SELECT users.id,
                    CONCAT(users.surname, ' ', users.name) as value,
                    CONCAT('#', users.id, ' - ', users.surname, ' ', users.name, ' - ', companies.business_name) as label
                  FROM users 
                  JOIN companies ON users.company_id = companies.id
                  WHERE users.name LIKE '%$term%' 
                    OR users.surname LIKE '%$term%' 
                    OR users.username LIKE '%$term%'
                    OR users.tax_code LIKE '%$term%'
                  ORDER BY users.surname, users.name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function searchUserInAllTutorCompanies($term, $tutor_id, $is_superadmin = FALSE){
        $term = filter_var($term, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $tutor_id = filter_var($tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $is_superadmin = filter_var($is_superadmin, FILTER_VALIDATE_BOOLEAN);
        $LABEL_tutor = $is_superadmin ? ", ' (', companies_tutor.business_name, ')' " : "";
        $JOIN_tutor = $is_superadmin ? " JOIN users as users_tutor ON companies.owner_user_id = users_tutor.id JOIN companies as companies_tutor ON users_tutor.company_id = companies_tutor.id " : "";
        $AND_tutor_is = $is_superadmin ? "" : " AND companies.owner_user_id in (SELECT users.id from users where users.company_id = $tutor_id)" ;
        $query = "SELECT users.*,
                    CONCAT(users.surname, ' ', users.name) as value,
                    CONCAT('#', users.id, ' - ', users.surname, ' ', users.name, ' - ', companies.business_name $LABEL_tutor) as label
                  FROM users 
                  JOIN companies ON users.company_id = companies.id 
                  $JOIN_tutor 
                  WHERE (users.name LIKE '%$term%' 
                    OR users.surname LIKE '%$term%' 
                    OR users.username LIKE '%$term%'
                    OR users.tax_code LIKE '%$term%')
                  $AND_tutor_is 
                  ORDER BY users.surname, users.name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function searchUserInCompany($term, $companyid){
        $term = filter_var($term, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $query = "SELECT users.id,
                    CONCAT(users.surname, ' ', users.name) as value,
                    CONCAT('#', users.id, ' - ', users.surname, ' ', users.name, ' - ', companies.business_name) as label
                  FROM users 
                  JOIN companies ON users.company_id = companies.id
                  WHERE 
                    (users.name LIKE  '%$term%' 
                     OR users.surname LIKE '%$term%'
                     OR users.username LIKE '%$term%'
                     OR users.tax_code LIKE '%$term%')
                     AND users.company_id = $companyid
                  ORDER BY users.surname, users.name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function searchEmployeesInCompany($term, $companyid, $unita_id, $reparto_id, $learning_project_id){
        $term = filter_var($term, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $companyid = sanitize($companyid, INT);
        $unita_id = sanitize($unita_id, INT);
        $reparto_id = sanitize($reparto_id, INT);
        $learning_project_id = sanitize($learning_project_id, INT);
        $filter_unita = $unita_id > 0 ? "AND departments.pu_id = ".$unita_id." " : "";
        $filter_reparto = $reparto_id > 0 ? "AND department_employees.dep_id = ".$reparto_id." " : "";
        $query = "SELECT 
                users.name as name,
                users.surname as surname,
                users.tax_code as tax_code,
                business_functions.name as business_function,
                users.email as email,
                users.id as user_id,
                users.deleted as deleted
            FROM users
                JOIN companies ON users.company_id = companies.id
                JOIN business_functions ON users.business_function_id = business_functions.id
                LEFT JOIN department_employees ON users.id = department_employees.user_id
                LEFT JOIN departments ON department_employees.dep_id = departments.id_dep
            WHERE 
                users.company_id = $companyid
                $filter_unita
                $filter_reparto
            ORDER BY users.surname, users.name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    /**
     * Restituisce l'elenco degli utenti dell'azienda <code>$company_id</code>
     * che non hanno seguito o in corso un learning project
     * 
     * @param integer $company_id
     * @param integer $learning_project_id
     * @return type array multidimensionale di utenti
     */
    public function searchEmployeesFreeInCompany($company_id, $unita_id, $reparto_id, $learning_project_id) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $learning_project_where_clause = "learning_project_users.learning_project_id = " . filter_var($learning_project_id, FILTER_SANITIZE_NUMBER_INT);
        $unita_id = filter_var($unita_id, FILTER_SANITIZE_NUMBER_INT);
        $reparto_id = filter_var($reparto_id, FILTER_SANITIZE_NUMBER_INT);
        $filter_unita = $unita_id > 0 ? "AND departments.pu_id = ".$unita_id." " : "";
        $filter_reparto = $reparto_id > 0 ? "AND department_employees.dep_id = ".$reparto_id." " : "";
        $query = "SELECT 
                    users.name as name,
                    users.surname as surname,
                    users.tax_code as tax_code,
                    business_functions.name as business_function,
                    users.email as email,
                    users.id as user_id
                  FROM users
                    JOIN business_functions ON users.business_function_id = business_functions.id
                    LEFT JOIN department_employees ON users.id = department_employees.user_id
                    LEFT JOIN departments ON department_employees.dep_id = departments.id_dep
                  WHERE deleted = 0 
                    AND users.company_id = $company_id 
                    $filter_unita 
                    $filter_reparto 
                    AND users.id NOT IN 
                    (
                        SELECT user_id 
                        FROM learning_project_users
                        WHERE $learning_project_where_clause
                    ) 
                  ORDER BY surname, name";
        $res = $this->db_conn->query($query);
        return !empty($res) ? $res : false;
    }

    public function searchAttestatiInCompany($company_id, $unita_id, $reparto_id){
        $company_id = sanitize($company_id, INT);
        $unita_id = sanitize($unita_id, INT);
        $reparto_id = sanitize($reparto_id, INT);
        $filter_unita = $unita_id > 0 ? "AND departments.pu_id = ".$unita_id." " : "";
        $filter_reparto = $reparto_id > 0 ? "AND department_employees.dep_id = ".$reparto_id." " : "";
        $query = "SELECT 
                    CONCAT(users.surname, ' ', users.name) as user_name,
                    learning_project.title as learning_project_title,
                    DATE(learning_events.end_date_time) as end_date,
                    learning_project_users.id as license_id
                FROM learning_project_users 
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
                    JOIN users ON learning_project_users.user_id = users.id
                    JOIN companies ON users.company_id = companies.id
                    LEFT JOIN department_employees ON users.id = department_employees.user_id
                    LEFT JOIN departments ON department_employees.dep_id = departments.id_dep
                WHERE companies.id = $company_id
                    AND (learning_events.end_date_time IS NOT NULL AND learning_events.end_date_time <> '0000-00-00 00:00:00')
                    AND users.deleted = 0
                    $filter_unita
                    $filter_reparto
                ORDER BY users.surname, users.name, learning_events.end_date_time";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function searchEmployeesInCompanyFilterByDepAndRep($company_id, $dep_id, $rep_id) {
        $company_id = sanitize($company_id, INT);
        $query = "SELECT * 
                  FROM department_types 
                    LEFT JOIN departments ON id_dep_type = dep_type_id
                  WHERE company_id = $company_id
                  ORDER BY short_desc_dep_type";
        $res = $this->db_conn->query($query);
        return $res;
    }


    public function searchUserInCompanyBySurname($term, $companyid, $learningprojectid){
        $term = filter_var($term, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $query = "SELECT users.*,
                    CONCAT(users.surname, ' ', users.name) as value,
                    CONCAT('#', users.id, ' - ', users.surname, ' ', users.name, ' - ', companies.business_name, ' - ', users.tax_code) as label,
                    users.name as name,
                    users.surname as surname,
                    users.tax_code as tax_code,
                    users.business_function_id as typeid,
                    users.email as email
                  FROM users 
                  JOIN companies ON users.company_id = companies.id
                  WHERE
                    users.surname LIKE '%$term%'
                    AND users.company_id = $companyid
                    AND users.id NOT IN (SELECT
							user_id 
						FROM learning_project_users
						WHERE learning_project_users.learning_project_id = $learningprojectid
					) 
                  ORDER BY users.surname, users.name";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function closeiWDUser() {
        //PHP B id=30525
        //@mysql_close($this->conn);
    }

    public function setUserCompany($user_id, $company_id) {
        $user_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "UPDATE users SET company_id = '$company_id' WHERE id = $user_id";
        $res = $this->db_conn->update($query);
        return $res;
    }

    /*
     * Check if exist in database the user searched by username or course code
     */
    public function getTaxCode($user_or_course){
        // Check in learning_project_user if course code have a user_id
        // With that user id get the user tax code on users
        $query = "SELECT
                users.tax_code
            FROM users
            JOIN learning_project_users ON learning_project_users.user_id = users.id
            WHERE 
                learning_project_users.learning_project_pwd = '$user_or_course'
                OR users.username = '$user_or_course'";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0]['tax_code'] : false;
    }

    public function loginByUsername($username) {
        $username = filter_var(trim($username), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $query = "SELECT users.*
                  FROM users
                  WHERE LOWER(username) = '" . strtolower($username) . "'";
        $res = $this->db_conn->query($query);
        return isset($res [0]) ? $res[0] : false;
    }

    public function getUsernameFromLearningProjectPassword($learning_password) {
        $query = "SELECT users.username
                    FROM learning_project_users as LP
                    JOIN users ON LP.user_id = users.id
                    WHERE LP.learning_project_pwd = '$learning_password'";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getUserByUsername($username){
        $username = filter_var(trim($username), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $query = "SELECT users.*
                  FROM users
                  JOIN learning_project_users ON learning_project_users.user_id = users.id
                  WHERE (users.username = '$username' OR learning_project_users.learning_project_pwd = '$username')";
        $res = $this->db_conn->query($query);
        return isset($res [0]) ? $res[0] : false;

    }

    public function getUserByCourseCodeOrUsername($loginstring){
        $loginstring = filter_var(trim($loginstring), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $query = "SELECT lpu.id as lpu_id, lpu.user_id, users.*
                  FROM learning_project_users as lpu
                  LEFT JOIN users ON lpu.user_id = users.id
                  WHERE users.username = '$loginstring' OR lpu.learning_project_pwd = '$loginstring'";
        $res = $this->db_conn->query($query);
        return isset($res [0]) ? $res[0] : false;

    }

}