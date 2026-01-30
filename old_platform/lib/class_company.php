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
require_once dirname(__FILE__).'/../config.php';
require_once 'class_db.php';
require_once 'sanitize.php';

class T81Company {
    
    var $db_conn;

    public function __construct() {
        $this->db_conn = new MySQLConn();
    }
    
    /**
     * Return path of logo company image
     * 
     * @param array $company = array('id' => 2, 'business_name' => 'Tutor81')
     * @return string path of logo image
     */
    public static function getEcommerceLogo($company = array('id' => 2, 'business_name' => 'Tutor81')) {
        if (file_exists(BASE_MEDIA_PATH . "img/company/" . $company["id"] . "-white.png")) {
            return "/media/img/company/" . $company["id"] . "-white.png";        
        } elseif (file_exists(BASE_MEDIA_PATH . "img/company/" . $company["id"] . ".png")) {
            return "/media/img/company/" . $company["id"] . ".png";
        } else {
            return strtoupper($company['business_name']);
        }
    }

    public function getUserByPassword($username, $password) {
        $username = $this->db_conn->escapestr($username);
        $query = "SELECT * FROM users WHERE password = sha1('" . $password . "') AND username = '" . $username . "' AND deleted = 0";
        $res = $this->db_conn->query($query);
        return $res[0];
    }

    public function getBusiness() {
        $query = "SELECT * FROM companies WHERE deleted = 0 ORDER BY business_name";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getBusinessDetail($comp_id, $list = 0) {
        $comp_id = filter_var($comp_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT companies.*, contracts.name as contract_name "
                . "FROM companies "
                . "LEFT JOIN contracts ON companies.contract_id = contracts.id "
                . "WHERE companies.id = " . $comp_id;
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? ($list == 0 ? $res[0] : $res) : FALSE;
    }

    public function getTimezone() {
        $query = "SELECT * FROM gmt";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getProvinces() {
        $query = "SELECT * FROM provinces ORDER BY name";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getAteco() {
        $query = "SELECT * FROM ateco_sectors ORDER BY name";
        $res = $this->db_conn->query($query);
        return $res;
    }

    /**
     * Restituisce i contratti tipo ordinati per nome
     * 
     * @return array
     */
    public function getContracts() {
        $query = "SELECT * FROM contracts ORDER BY name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    public function getContractDetail($contract_id){
        $contract_id = filter_var($contract_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT * FROM contracts WHERE id = $contract_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    /**
     * Restituisce l'elenco di tutto gli enti formativi attivi
     * 
     * @return mixed array
     */
    public function getBusinessTutor() {
        $query = "SELECT companies.*, 
                    plans.short_desc_plan as short_desc_plan,
                    company_plans.validity_end,
                    company_plans.suspended
                  FROM companies 
                  LEFT JOIN company_plans ON companies.id = company_plans.company_id 
                  LEFT JOIN plans ON company_plans.plan_id = plans.id 
                  WHERE deleted = 0 AND is_tutor = 1 AND suspended = 0 ORDER BY business_name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;;
    }

    public function getAllCompanies($deleted = 0) {
        $deleted = filter_var($deleted, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        $query = "SELECT companies.*, 
                    plans.short_desc_plan as short_desc_plan,
                    company_plans.validity_end,
                    company_plans.suspended
                  FROM companies 
                  LEFT JOIN company_plans ON companies.id = company_plans.company_id 
                  LEFT JOIN plans ON company_plans.plan_id = plans.id 
                  WHERE companies.deleted = $deleted ORDER BY business_name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    public function getCompanyByVatCode($vat_code){
        $vat_code = filter_var($vat_code, FILTER_SANITIZE_STRING);
        $query = "SELECT companies.*,
                    users.company_id as tutor_id
                  FROM companies 
                  JOIN users ON companies.owner_user_id = users.id 
                  WHERE companies.vat = '$vat_code'";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getCompanyByTutorCompany($company_id) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT companies.*, 
                    users.name as user_name, 
                    users.surname as user_surname, 
                    plans.short_desc_plan as short_desc_plan,
                    cp.validity_end,
                    cp.suspended
                  FROM companies 
                  JOIN users ON companies.owner_user_id  = users.id
                  LEFT JOIN (
                    SELECT *
                    FROM company_plans as cp1
                    WHERE validity_end = (
                        SELECT MAX(validity_end)
                        FROM company_plans AS cp2
                        WHERE cp1.company_id = cp2.company_id
                    )
                  ) as cp ON companies.id = cp.company_id 
                  LEFT JOIN plans ON cp.plan_id = plans.id
                  WHERE owner_user_id IN (
                    SELECT id 
                    FROM users 
                    WHERE company_id = $company_id
                  ) 
                  ORDER BY business_name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    /**
     * Restituisce la lista delle aziende che hanno corsi completati
     * 
     * @param integer $company_id
     * @return type array
     */
    public function getCompanyWithCompletedCoursesByTutorCompany($company_id) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT companies.*, 
                    users.name as user_name, 
                    users.surname as user_surname, 
                    contracts.name as contract_name
                  FROM companies 
                  JOIN users ON companies.owner_user_id  = users.id
                  LEFT JOIN contracts ON companies.contract_id = contracts.id
                  WHERE owner_user_id IN (SELECT id 
                                          FROM users 
                                          WHERE company_id = " . $company_id . ") 
                  AND companies.id IN (SELECT users.company_id
                                       FROM users
                                       JOIN learning_project_users ON users.id = learning_project_users.user_id 
                                       JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
                                       WHERE learning_events.end_date_time IS NOT NULL
                                       AND learning_events.end_date_time!= '0000-00-00 00:00:00')
                  ORDER BY business_name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    /**
     * Restituisce la lista delle aziende del tutor con corsi attivi
     * 
     * @param integer $company_id
     * @return type array
     */
    public function getCompanyWithActiveCoursesByTutorCompany($company_id) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT companies.*, 
                    users.name as user_name, 
                    users.surname as user_surname, 
                    contracts.name as contract_name
                  FROM companies 
                  JOIN users ON companies.owner_user_id  = users.id
                  LEFT JOIN contracts ON companies.contract_id = contracts.id
                  WHERE owner_user_id IN (SELECT id 
                                          FROM users 
                                          WHERE company_id = " . $company_id . ") 
                  AND companies.id IN (SELECT users.company_id
                                       FROM users
                                       JOIN learning_project_users ON users.id = learning_project_users.user_id 
                                       LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
                                       WHERE learning_events.end_date_time IS NULL
                                       OR learning_events.end_date_time = '0000-00-00 00:00:00')
                  ORDER BY business_name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getCompanyIDsByTutorCompany($comp_id) {
        $comp_id = sanitize($comp_id, INT);
        $query = "SELECT companies.id FROM companies JOIN users ON users.id = owner_user_id WHERE owner_user_id IN (SELECT id FROM users WHERE company_id = " . $comp_id . ")";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getCompanyByServerAlias($serveralias) {
        $query = "SELECT companies.* FROM companies WHERE ecommerce = '" . $serveralias . "'";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getCompanyByTutorAdmin($tutor_admin_id) {
        $tutor_admin_id = sanitize($tutor_admin_id, INT);
        $query = "SELECT *
                  FROM companies
                  WHERE owner_user_id = $tutor_admin_id
                  ORDER BY business_name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getMainAdminOfCompany($company_id) {
        $company_id = sanitize($company_id, INT);
        $query = "SELECT id FROM users WHERE company_id = " .$company_id. " AND role = 1 ORDER BY creation_date LIMIT 1";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0]["id"] : false;
    }

    public function getCompanyByID($comp_id) {
        $comp_id = sanitize($comp_id, INT);
        $query = "SELECT companies.*,
                    ateco_sectors.name as ateco_sector, 
                    provinces.name as province_label, 
                    users.name as user_name, 
                    users.surname as user_surname, 
                    gmt.description as gmt_description, 
                    gmt.offset as gmt_offset 
                  FROM companies 
                  LEFT JOIN gmt ON companies.gmt = gmt.id 
                  LEFT JOIN users ON users.id = owner_user_id 
                  LEFT JOIN provinces ON provinces.id = companies.province_id 
                  LEFT JOIN ateco_sectors ON ateco_sectors.id = companies.ateco_sector_id 
                  WHERE companies.id = " . $comp_id;
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getUserLicense($user_id) {
        $user_id = sanitize($user_id, INT);
        $query = "SELECT title,learning_project_pwd,learning_project_id FROM `learning_project_users` JOIN learning_project ON learning_project_id = learning_project.id WHERE user_id = " . $user_id;
        $res = $this->db_conn->query($query);
        return $res;
    }
    
    /**
     * Restituisce tutti gli utenti che hanno una sessione
     * 
     * @param integer $compay_id l'id dell'ente formativo 
     */
    public function getUsersHavingSessions(){
        $query = "SELECT users.*
                  FROM users_login_session
                    JOIN users ON users_login_session.user_id = users.id
                    JOIN companies ON users.company_id = companies.id
                  WHERE companies.owner_user_id IN (
                        SELECT tutor_users.id 
                        FROM users as tutor_users 
                        WHERE 1) 
                  GROUP BY users.id
                  ORDER BY users.surname, users.name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    /**
     * Restituisce tutti gli utenti di tutte le aziende dell'ente formativo
     * 
     * @param integer $compay_id l'id dell'ente formativo 
     */
    public function getUsersHaveSessionsByTutorCompany($company_id){
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT users.*
                  FROM users_login_session
                    JOIN users ON users_login_session.user_id = users.id
                    JOIN companies ON users.company_id = companies.id
                  WHERE companies.owner_user_id IN (
                        SELECT tutor_users.id 
                        FROM users as tutor_users 
                        WHERE tutor_users.company_id = '$company_id') 
                  GROUP BY users.id
                  ORDER BY users.surname, users.name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    /**
     * Restituisce gli utenti di un azienda in base al ruolo
     * con $roles (opzionale) impostato per tutti gli utenti
     * 
     * @param type $comp_id
     * @param type $roles -1 --> tutti i ruoli
     *                    integer > 0 per un singolo ruolo
     *                    array per più ruoli;
     * @return type
     */
    public function getUsersCompanyByID($comp_id, $roles = -1, $deleted = 0) {
        $comp_id = filter_var($comp_id, FILTER_SANITIZE_NUMBER_INT);
        $deleted = filter_var($deleted, FILTER_SANITIZE_NUMBER_INT);
        $and_roles = '';
        if (is_array($roles)){
            $roles_filtered = array_filter($roles, function($value){
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT) ? : false;
            });
            $num_roles = count($roles_filtered);
            if ($num_roles > 0){
                $and_roles  .= " AND ";
                foreach ($roles_filtered as $role){
                    $and_roles  .= --$num_roles > 0 ? "role = $role OR " : "role = $role";
                }
            }
        } elseif (($role = filter_var($roles, FILTER_SANITIZE_NUMBER_INT)) > 0) {
            $and_roles  .= " AND role = $role";
        }
        $and_deleted = ' AND deleted = 0';
        if ($deleted == -1){
            $and_deleted = '';
        } elseif ( $deleted > 0 ) {
            $and_deleted = ' AND deleted = 1';
        }
        $query = "SELECT users.*, business_functions.name as function
                    FROM users
                        LEFT JOIN business_functions ON users.business_function_id = business_functions.id
                    WHERE company_id = $comp_id 
                        $and_roles     
                        $and_deleted
                    ORDER BY surname, name";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    /**
     * restituisce i centri di costo della compagnia
     * 
     * @param integer $company_id
     * @return Ambigous <boolean, multitype:>
     */
    public function getCostCentre($company_id) {
        $company_id = sanitize($company_id, INT);
        $query = "SELECT * FROM cost_centre WHERE company_id = $company_id ORDER BY position";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function countUsersCompanyByBusinessFunction($comp_id, $business_function_id = 0) {
        $comp_id = sanitize($comp_id, INT);
        $business_function_id = sanitize($business_function_id, INT);
        if ($business_function_id > 0) {
            $query = "SELECT COUNT(users.id) as qta
								FROM users
								WHERE deleted = 0
								AND company_id = $comp_id AND business_function_id = $business_function_id";
        } else {
            $query = "SELECT COUNT(users.id) as qta
								FROM users
								WHERE deleted = 0
								AND company_id = $comp_id";
        }
        $res = $this->db_conn->query($query);
        return $res[0]['qta'];
    }

    public function createCompany($business_name,$vat,$address,$postal_code,$city,$province_id,
            $is_tutor,$is_partner,$owner_user_id,$discount,$ateco_sector_id,
            $telephone,$email,$gmt,$contract_id,$test_in_the_presence,$risk_evaluation, $iban="", $regional_authorization = "", $ateco = "", $trainer = ""){
        $business_name = $this->db_conn->escapestr($business_name);
        $vat = $this->db_conn->escapestr($vat);
        $address = $this->db_conn->escapestr($address);
        $postal_code = $this->db_conn->escapestr($postal_code);
        $city = $this->db_conn->escapestr($city);
        $province_id = filter_var($province_id, FILTER_SANITIZE_NUMBER_INT);
        $telephone = $this->db_conn->escapestr($telephone);
        $email = $this->db_conn->escapestr(filter_var($email, FILTER_SANITIZE_EMAIL));
        $gmt = filter_var($gmt, FILTER_SANITIZE_NUMBER_INT);
        $discount = filter_var($discount, FILTER_SANITIZE_NUMBER_INT);
        $is_tutor = filter_var($is_tutor, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $is_partner = filter_var($is_partner, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $owner_user_id = filter_var($owner_user_id, FILTER_SANITIZE_NUMBER_INT);
        $ateco_sector_id = filter_var($ateco_sector_id, FILTER_SANITIZE_NUMBER_INT);
        $contract_id = filter_var($contract_id, FILTER_SANITIZE_NUMBER_INT);
        $test_in_the_presence = $this->db_conn->escapestr($test_in_the_presence);
        $test_in_the_presence = $test_in_the_presence === "UPLOADABLE" ||
                                $test_in_the_presence === "IMPLEMENTED_IN_PLAYER" ? $test_in_the_presence : "NO";
        $risk_evaluation = filter_var($risk_evaluation, FILTER_SANITIZE_NUMBER_INT);
        $iban = trim(filter_var($iban, FILTER_SANITIZE_STRING));
        $regional_authorization = trim(filter_var($regional_authorization, FILTER_SANITIZE_STRING));
        $trainer = $this->db_conn->escapestr($trainer);
        $ateco = trim(filter_var($ateco, FILTER_SANITIZE_STRING));

        $query = "SELECT COUNT(*) as conta FROM companies WHERE vat = '$vat'";
        $que = $this->db_conn->query($query);
        if ($que[0]['conta'] == 0) {
            $query = "INSERT INTO companies(business_name,vat,city,address,province_id,is_tutor,is_partner,owner_user_id,discount,deleted,ateco_sector_id,telephone,email,gmt, contract_id, test_in_the_presence,risk_evaluation, iban, regional_authorization, trainer, ateco)
                      VALUES('$business_name','$vat','$city','$address - $city - $postal_code',$province_id,$is_tutor,$is_partner,$owner_user_id,$discount,0,$ateco_sector_id,'$telephone','$email','$gmt',$contract_id,'$test_in_the_presence',$risk_evaluation,'$iban', '$regional_authorization', '$trainer', '$ateco')";
            $res = $this->db_conn->insert($query);
        } else {
            $res = "PIVA";
        }
        return $res;
    }

    public function getDetail($user_id) {
        $user_id = sanitize($user_id, INT);
        $query = "SELECT users.*, business_name FROM users JOIN companies ON users.company_id = companies.id WHERE users.id = " . $user_id;
        $res = $this->db_conn->query($query);
        return $res[0];
    }

    public function getUsersCourses($user_id) {
        $user_id = sanitize($user_id, INT);
        $query = "SELECT learning_project_users.*,title  FROM `learning_project_users` JOIN learning_project ON learning_project_id = learning_project.id WHERE user_id = " . $user_id;
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getUserLearningProject($learning_project_user_id) {
        $learning_project_user_id = sanitize($learning_project_user_id, INT);
        $query = "SELECT learning_project_users.* FROM learning_project_users WHERE id = $learning_project_user_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function editCompany($comp_id, $role_ref, $sociale, $iva, $city, 
            $indirizzo, $provincia, $telefono, $email, $regional_authorization, 
            $discount, $timezone, $ateco_sector_id, $contract_id, 
            $test_in_the_presence, $iban, $url_ecommerce, $ateco, $site_url,$trainer) {
        $comp_id = sanitize($comp_id, INT);
        $soc = $this->db_conn->escapestr($sociale);
        $iva = $this->db_conn->escapestr($iva);
        $city = $this->db_conn->escapestr($city);
        $iban = $this->db_conn->escapestr($iban);
        $url_ecommerce = filter_var($url_ecommerce, FILTER_SANITIZE_URL);
        $indirizzo = $this->db_conn->escapestr($indirizzo);
        $provincia = sanitize($provincia, INT);
        $telefono = $this->db_conn->escapestr($telefono);
        $email = $this->db_conn->escapestr($email);
        $regional_authorization = $this->db_conn->escapestr($regional_authorization);
        $timezone = sanitize($timezone, INT);
        $discount = sanitize($discount, INT);
        $ateco_sector_id = sanitize($ateco_sector_id, INT);
        $contract_id = sanitize($contract_id, INT);
        $owner_user_id = sanitize($role_ref, INT);
        $ateco = filter_var($ateco, FILTER_SANITIZE_STRING);
        $site_url = filter_var($site_url, FILTER_SANITIZE_URL);
        $test_in_the_presence = $this->db_conn->escapestr($test_in_the_presence);
        $test_in_the_presence = $test_in_the_presence === "UPLOADABLE" ||
                                $test_in_the_presence === "IMPLEMENTED_IN_PLAYER" ? $test_in_the_presence : "NO";
        $trainer = $this->db_conn->escapestr($trainer);

        $query = "SELECT COUNT(*) as conta FROM companies WHERE vat = '" . $iva . "' AND id <> " . $comp_id;
        $que = $this->db_conn->query($query);
        if ($que[0]['conta'] == 0) {
            $query = "UPDATE companies
                      SET business_name = '" . $soc . "',
			vat = '" . $iva . "',
                        city = '$city',
			address = '" . $indirizzo . "',
			province_id = " . $provincia . ",
			discount = " . $discount . ",
			ateco_sector_id = " . $ateco_sector_id . ",
			owner_user_id = " . $owner_user_id . ",
			telephone = '" . $telefono . "',
			email = '" . $email . "',
                        regional_authorization = '$regional_authorization',
			gmt = " . $timezone . ",
			contract_id = " . $contract_id . ",
			iban = '" . $iban . "',
			ecommerce = '" . $url_ecommerce . "',
			test_in_the_presence = '$test_in_the_presence',
                        ateco = '$ateco' , 
                        site_url = '$site_url',
                        trainer = '$trainer'
                      WHERE id = " . $comp_id;
            $res = $this->db_conn->update($query);
        } else {
            $res = "PIVA";
        }
        return $res;
    }

    private function assignUserGroup($user_id) {
        $user_id = sanitize($user_id, INT);
        $query = "INSERT INTO user_system_groups(user_id,system_group_id)VALUES(" . $user_id . ",1)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function createUser($role, $nome, $cognome, $username, $password, $email, $company_id, $cod_fisc, $indirizzo, $cap, $citta, $force_reset) {
        $role = $this->db_conn->escapestr($role);
        $nome = $this->db_conn->escapestr($nome);
        $cognome = $this->db_conn->escapestr($cognome);
        $username = $this->db_conn->escapestr($username);
        $username = strtolower($username);
        $password = $password;
        $email = $this->db_conn->escapestr($email);
        $company_id = $this->db_conn->escapestr($company_id);
        $indirizzo = $this->db_conn->escapestr($indirizzo);
        $cap = $this->db_conn->escapestr($cap);
        $citta = $this->db_conn->escapestr($citta);
        $cod_fisc = $this->db_conn->escapestr($cod_fisc);
        $query = "SELECT COUNT(*) as conta FROM users WHERE username = '" . $username . "'";
        $que = $this->db_conn->query($query);
        if ($que[0]['conta'] == 0) {
            $query = "INSERT INTO users(role,name,surname,creation_date,creator_id,address,cap,code,email,suspended,password,deleted,username,company_id,language_id,tax_code,force_reset)VALUES(
					'" . $role . "',
							'" . $nome . "',
									'" . $cognome . "',
											'" . date("Y-m-d h:i:s") . "',6,
													'" . $indirizzo . " - " . $citta . "',
															'" . $cap . "',
																	'" . sha1($username) . "',
																			'" . $email . "',0,
																					'" . sha1($password) . "',0,
																							'" . $username . "',
																									'" . $company_id . "',1,'" . $cod_fisc . "','" . $force_reset . "')";
            $res = $this->db_conn->insert($query);
            $user_id = $res;
            $this->assignUserGroup($user_id);
            $not = new Tutor81Notification();
            $res = $not->notifyUserCreation($user_id, $password);
        } else {
            $res = "UTENTE";
        }
        return $res;
    }

    public function changeCompanyTutor($comp_id, $new_status) {
        $comp_id = sanitize($comp_id, INT);
        $new_status = sanitize($new_status, INT);
        $query = "UPDATE companies SET is_tutor = " . $new_status . " WHERE id = " . $comp_id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function removeUser($user_id) {
        $user_id = sanitize($user_id, INT);
        $query = "DELETE FROM users WHERE id = " . $user_id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    /**
     * Restituisce la lista dei corsi acquistati dall'azienda <code>$company_id</code>
     * filtrati in base al <code>$learning_project_id</code>. Se
     * <code>$learning_project_id == 0</code> restituisce tutti gli acquisti.
     * 
     * @param type $company_id
     * @param type $learning_project_id
     * @return type
     */
    public function getPurchaseByCompany($company_id, $learning_project_id = 0) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $learning_project_id = filter_var($learning_project_id, FILTER_SANITIZE_NUMBER_INT);
        if ($learning_project_id == 0) {
            $query = "SELECT tutors_purchases.id as purchase_id, 
                        learning_project.title, 
                        learning_project_id, 
                        SUM(qta) as qta 
                      FROM tutors_purchases 
                        JOIN learning_project ON learning_project_id = learning_project.id 
                      WHERE customer_company_id = '$company_id' GROUP BY learning_project_id";
        } else {
            $query = "SELECT tutors_purchases.id as purchase_id,
                        learning_project.title, 
                        learning_project_id, 
                        SUM(qta) as qta 
                      FROM tutors_purchases 
                        JOIN learning_project ON learning_project_id = learning_project.id 
                      WHERE learning_project.id = '$learning_project_id' 
                        AND customer_company_id = '$company_id' 
                      GROUP BY learning_project_id";
        }
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getPurchaseNotInvoicedByCompany($company_id, $learning_project_id = 0) {
        $company_id = sanitize($company_id, INT);
        $learning_project_id = sanitize($learning_project_id, INT);
        if ($learning_project_id == 0) {
            $query = "SELECT purchase_id, title, learning_project_id, SUM(qta) as qta FROM (SELECT tutors_purchases.id as purchase_id,learning_project.title, learning_project_id, qta FROM tutors_purchases  JOIN learning_project ON learning_project_id = learning_project.id WHERE customer_company_id = " . $company_id . " AND invoiced = 0 ORDER BY purchase_id DESC) as T GROUP BY learning_project_id";
        } else {
            $query = "SELECT purchase_id, title, learning_project_id, SUM(qta) as qta FROM (SELECT tutors_purchases.id as purchase_id,learning_project.title, learning_project_id, qta FROM tutors_purchases  JOIN learning_project ON learning_project_id = learning_project.id WHERE learning_project.id = " . $learning_project_id . " AND customer_company_id = " . $company_id . " AND invoiced = 0) as T";
        }
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function addPurchase($purchase_id, $qta) {
        $purchase_id = sanitize($purchase_id, INT);
        $qta = sanitize($qta, INT);
        $query = "UPDATE tutors_purchases SET qta = qta + " . $qta . " WHERE id = " . $purchase_id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    /**
     * Restituisce la quantità di licenze per il corso $learning_project_id assegnate a utenti dell'azienda $customer_company_id
     * ATTENZIONE!! SE SPOSTO L'UTENTE IN UN ALTRA AZIENDA SI PORTA DIETRO LA LICENZA
     * @param type $customer_company_id
     * @param type $learning_project_id
     * @return type
     */
    public function countSeatBusyLearningProject($customer_company_id, $learning_project_id) {
        $learning_project_id = sanitize($learning_project_id, INT);
        $query = "SELECT count(*) as busy_seat "
                . "FROM learning_project_users "
                . "LEFT JOIN users ON users.id = learning_project_users.user_id "
                . "WHERE learning_project_id = $learning_project_id AND (id_company = $customer_company_id OR users.company_id = $customer_company_id)";
        $res = $this->db_conn->query($query);
        return $res[0]['busy_seat'];
    }

    public function getAssignmentPurchase($learn_id, $customer_company_id) {
        $learn_id = sanitize($learn_id, INT);
        $customer_company_id = sanitize($customer_company_id, INT);
        //learning_project_users.finish_within >= CURDATE() AND
        $query = "SELECT learning_project_users.*,name,surname,tax_code,username FROM learning_project_users JOIN users ON users.id = learning_project_users.user_id WHERE learning_project_id = " . $learn_id . " and users.company_id = " . $customer_company_id;
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getNotCompletedAssignementPurchase($customer_company_id = 0, $tutor_company_id = 0) {
        $customer_company_id = filter_var($customer_company_id, FILTER_SANITIZE_NUMBER_INT);
        $tutor_company_id = filter_var($tutor_company_id, FILTER_SANITIZE_NUMBER_INT);
        $and_filter_companies = $customer_company_id > 0 ? " AND learning_project_users.id_company = $customer_company_id" : 
            (
                $tutor_company_id > 0 ? " AND learning_project_users.id_company IN "
                    . "(SELECT companies.id FROM companies "
                    . "JOIN users ON companies.owner_user_id = users.id "
                    . "WHERE users.company_id = $tutor_company_id)" : ""
            );
        $query = "SELECT learning_project_users.*, 
                    customer_companies.business_name as customer_company_business_name, 
                    users.name, users.surname,
                    users.tax_code, users.username, 
                    users.email as user_email, 
                    learning_project.title 
                  FROM learning_project_users 
                    JOIN users ON users.id = learning_project_users.user_id 
                    LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id 
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    JOIN companies as customer_companies ON learning_project_users.id_company = customer_companies.id 
                  WHERE (learning_events.end_date_time IS NULL OR learning_events.end_date_time = '0000-00-00 00:00:00')
                    $and_filter_companies";
        $res = $this->db_conn->query($query);
        return !empty($res) ? $res : false;
    }

    public function getReportAssignmentPurchase($learn_id, $customer_company_id) {
        $learn_id = sanitize($learn_id, INT);
        $customer_company_id = sanitize($customer_company_id, INT);
        $query = "
              SELECT learning_project_users.*,
					name,surname,tax_code,username,short_desc_pu, short_desc_dep_type
              FROM learning_project_users 
	            JOIN users ON users.id = learning_project_users.user_id
                LEFT JOIN department_employees ON users.id = department_employees.user_id
	            LEFT JOIN departments ON dep_id = departments.id_dep
	            LEFT JOIN product_units ON pu_id = id_pu 
	            LEFT JOIN department_types ON dep_type_id = id_dep_type
              WHERE learning_project_id = " . $learn_id . " and users.company_id = " . $customer_company_id."
              ORDER BY learning_project_users.id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getTutorByLearningProject($company_id, $learning_project) {
        $company_id = sanitize($company_id, INT);
        $learning_project = sanitize($learning_project, INT);
        $query = "SELECT tutor_id FROM tutors_purchases WHERE customer_company_id = " . $company_id . " AND learning_project_id = " . $learning_project;
        $res = $this->db_conn->query($query);
        return $res[0]['tutor_id'];
    }

    /**
     * Restituisce l'elenco degli utenti dell'azienda <code>$company_id</code>
     * che non hanno seguito o in corso un learning project di quelli contenuti
     * in <code>$learning_project_array</code>
     * 
     * @param type $company_id
     * @param type $learning_project_array array di learning_project
     * @return type array multidimensionale di utenti
     */
    public function getUsersFree($company_id, $learning_project_array) {
        $company_id = sanitize($company_id, INT);
        $learning_project_where_clause = "learning_project_users.learning_project_id = " . sanitize($learning_project_array[0], INT);
        for ($i = 1; $i < count($learning_project_array); $i++) {
            $learning_project_where_clause .= " OR  learning_project_users.learning_project_id = " . sanitize($learning_project_array[$i], INT);
        }
        $query = "SELECT users.*, business_functions.name as business_function_name
                          FROM users
                            JOIN business_functions ON users.business_function_id = business_functions.id
                          WHERE deleted = 0 
                            AND users.company_id = $company_id
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

    /**
     * Restituisce l'elenco degli utenti dell'azienda <code>$company_id</code>
     * che hanno seguito o hanno in corso un learning project di quelli contenuti
     * in <code>$learning_project_array</code>
     * 
     * @param type $company_id
     * @param type $learning_project_array array di learning_project
     * @return type array multidimensionale di utenti
     */
    public function getUsersAlreadyFormed($company_id, $learning_project_array) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $learning_project_where_clause = "learning_project_users.learning_project_id = " . filter_var($learning_project_array[0], FILTER_SANITIZE_NUMBER_INT);
        for ($i = 1; $i < count($learning_project_array); $i++) {
            $learning_project_where_clause .= " OR learning_project_users.learning_project_id = " . filter_var($learning_project_array[$i], FILTER_SANITIZE_NUMBER_INT);
        }
        $query = "SELECT users.*, business_functions.name as business_function_name
                          FROM users
                            JOIN business_functions ON users.business_function_id = business_functions.id
                          WHERE deleted = 0 
                            AND users.company_id = $company_id
                            AND users.id IN 
                            (
				SELECT user_id 
				FROM learning_project_users
				WHERE $learning_project_where_clause
                            ) 
                          ORDER BY surname, name";
        $res = $this->db_conn->query($query);
        return !empty($res) ? $res : false;
    }

    public function getAllUsersCompanyByID($comp_id) {
        $comp_id = sanitize($comp_id, INT);
        $query = "SELECT users.* FROM users WHERE company_id = " . $comp_id . " ORDER BY surname, name";
        $res = $this->db_conn->query($query);
        return $res;
    }
    
    public function setCompanyOwner($company_id,$owner_user_id){
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $owner_user_id = filter_var($owner_user_id, FILTER_SANITIZE_NUMBER_INT);
        $res = false;
        if ($owner_user_id > 0) {
            $query = "UPDATE companies SET owner_user_id = $owner_user_id WHERE id = $company_id";
            $res = $this->db_conn->update($query) ? : false;
        }
        return $res;
    }

    public function setIsTutor($comp_id) {
        $comp_id = sanitize($comp_id, INT);
        $is_tutor = sanitize($is_tutor, INT);
        $query = "UPDATE companies SET is_tutor = 1 WHERE id = " . $comp_id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function setIsNotTutor($comp_id, $owner_user_id) {
        $comp_id = sanitize($comp_id, INT);
        $owner_user_id = sanitize($owner_user_id, INT);
        $query = "UPDATE companies SET is_tutor = 0, owner_user_id = " . $owner_user_id . " WHERE id = " . $comp_id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function addDidacticTutor($comp_id, $user_id) {
        $comp_id = sanitize($comp_id, INT);
        $user_id = sanitize($user_id, INT);
        $query = "INSERT INTO didactic_tutors (company_id, user_id) VALUES ($comp_id, $user_id)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function setDidacticTutor($comp_id, $user_id) {
        $comp_id = sanitize($comp_id, INT);
        $user_id = sanitize($user_id, INT);
        $query = "UPDATE didactic_tutors SET user_id = $user_id WHERE company_id = $comp_id";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function getDidacticTutor($comp_id) {
        $comp_id = sanitize($comp_id, INT);
        $query = "SELECT didactic_tutors.id as didactic_tutor_id,users.* FROM users JOIN didactic_tutors ON users.id = didactic_tutors.user_id WHERE didactic_tutors.company_id = $comp_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }
    
    public function getPlans(){
        return $this->db_conn->query("SELECT * FROM plans WHERE active = 1 ORDER BY for_tutor, plan_price");
    }
    
    /**
     * Restituisce per l'azienda <code>$company_id</code> la licenza corrente o 
     * la lista di tutte le licenze se <code>$current == FALSE</code>
     * 
     * @param Integer $company_id
     * @param Boolean $current
     * @return mixed array
     */
    public function getCompanyPlan($company_id, $current = TRUE, $suspended = FALSE){
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $current = filter_var($current, FILTER_VALIDATE_BOOLEAN);
        $suspended = filter_var($suspended, FILTER_VALIDATE_BOOLEAN) ? '1': '0';
        $and_current = $current ? "AND validity_start <= CURDATE() AND validity_end >= CURDATE()": "";
        $query = "SELECT company_plans.*, plans.short_desc_plan 
                  FROM company_plans
                    LEFT JOIN plans ON company_plans.plan_id = plans.id
                  WHERE company_plans.company_id = $company_id $and_current 
                      AND company_plans.suspended = $suspended 
                  ORDER BY company_plans.validity_end DESC";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? ($current ? $res[0] : $res) : false;
    }
    
    public function assignCompanyPlan($plan_id, $tutor_id, $company_id, 
            $validity_start, $validity_end, $discount, $ecommerce, 
            $customized_courses, $max_admin, $max_concurrent_users, $price) {
        
        $plan_id = filter_var($plan_id, FILTER_SANITIZE_NUMBER_INT);
        $tutor_id = filter_var($tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $validity_start = $this->db_conn->escapestr($validity_start);
        $validity_end = $this->db_conn->escapestr($validity_end);
        $discount = filter_var($discount, FILTER_SANITIZE_NUMBER_INT);
        $ecommerce = filter_var($ecommerce, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $customized_courses = filter_var($customized_courses, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $max_admin = filter_var($max_admin, FILTER_SANITIZE_NUMBER_INT);
        $max_concurrent_users = filter_var($max_concurrent_users, FILTER_SANITIZE_NUMBER_INT);
        $price = filter_var($price, FILTER_SANITIZE_NUMBER_INT);
        
        try {
            $validity_start = new DateTime($validity_start);
            $validity_end = new DateTime($validity_end);
            $query = "INSERT INTO company_plans 
                    (plan_id, tutor_id, company_id, validity_start, validity_end, discount, 
                    ecommerce, customized_courses, max_admin, max_concurrent_users, price)
                VALUES ('$plan_id', '$tutor_id', '$company_id', '{$validity_start->format('Y-m-d')}',
                    '{$validity_end->format('Y-m-d')}', '$discount', '$ecommerce', 
                    '$customized_courses', '$max_admin', '$max_concurrent_users', '$price')";
            return $this->db_conn->insert($query) ? : false;
        } catch (Exception $e){
            return false;
        }
    }
    
    public function editCompanyPlan($id, $plan_id, $tutor_id, $validity_start, $validity_end){
        $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
        $plan_id = filter_var($plan_id, FILTER_SANITIZE_NUMBER_INT);
        $tutor_id = filter_var($tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $validity_start = $this->db_conn->escapestr($validity_start);
        $validity_end = $this->db_conn->escapestr($validity_end);
        try {
            $validity_start = new DateTime($validity_start);
            $validity_end = new DateTime($validity_end);
            $query = "UPDATE company_plans 
                      SET plan_id = $plan_id, 
                          tutor_id = $tutor_id, 
                          validity_start = '{$validity_start->format('Y-m-d')}', 
                          validity_end = '{$validity_end->format('Y-m-d')}'
                      WHERE id = $id";
            return $this->db_conn->update($query) ? : false;
        } catch (Exception $e){
            return false;
        }
    }
    
    public function suspendCompanyPlan($id, $suspended = TRUE){
        $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
        $suspended = filter_var($suspended, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        return $this->db_conn->update("UPDATE company_plans SET suspended = '$suspended' WHERE id = '$id'") ? : false;
    }
    
    public function deleteCompanyPlan($company_id) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "DELETE FROM company_plans WHERE company_id = $company_id";
        $res = $this->db_conn->delete($query);
        return $res > 0 ? true : false;
    }
    
    public function deleteCompany($company_id) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "DELETE FROM companies WHERE id = $company_id";
        $res = $this->db_conn->delete($query);
        return $res > 0 ? true : false;
    }
    
    public function setSendCertificate($company_id, $send_certificate){
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $send_certificate = filter_var($send_certificate, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        return $this->db_conn->update("UPDATE companies SET send_certificate = '$send_certificate' WHERE id = '$company_id'") ? : false;
    }

    public function closeiWDCompany() {
        //PHP B id=30525
        //@mysql_close($this->conn);
    }
    
}