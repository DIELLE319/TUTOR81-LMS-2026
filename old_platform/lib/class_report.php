<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 27-lug-2015
 * File: lib/class_report.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'class_db.php';
require_once 'sanitize.php';

class Report {

    var $db_conn;

    public function __construct() {
        $this->db_conn = new MySQLConn();
    }

    public function getOnlineUsers($company_id = 0) {
        $company_id = sanitize($company_id, INT);
        $and_company = $company_id > 0 ? " AND companies.id = $company_id" : "";
        $date_now = date("Y-m-d H:i:s");
        $query = "SELECT name,surname,business_name, learning_objects.title as title, learning_project.title as title_project FROM learning_event_questions JOIN learning_events ON learning_event_id = learning_events.id  JOIN learning_objects ON last_learning_object_id = learning_objects.id JOIN learning_project_users ON learning_project_user_id = learning_project_users.id JOIN users ON users.id = user_id JOIN companies ON users.company_id = companies.id JOIN learning_project ON learning_project_id = learning_project.id WHERE generation_time > '" . $date_now . "' - INTERVAL 15 MINUTE $and_company GROUP BY user_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    public function getOnlineUsersByCompany($company_id = 0) {
        $company_id = sanitize($company_id, INT);
        $and_company = $company_id > 0 ? " AND users.company_id = $company_id" : "";
        $query = "SELECT users.surname, users.name, 
                        learning_project.title as title_project,
                        learning_objects.title as title_object, 
                        learning_event_id, MAX(end_object) as last_end_object 
                  FROM users_events_session 
                    JOIN learning_events ON users_events_session.learning_event_id = learning_events.id
                    JOIN learning_project_users ON  learning_events.learning_project_user_id = learning_project_users.id
                    JOIN users ON learning_project_users.user_id = users.id
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    JOIN learning_objects ON learning_events.last_learning_object_id = learning_objects.id
                  WHERE DATE_SUB(NOW(),INTERVAL 59 SECOND) <= users_events_session.end_object
                    $and_company
                  GROUP BY learning_event_id
                  ORDER BY last_end_object DESC";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    public function getOnlineUsersByTutorCompany($tutor_company_id = 0) {
        $tutor_company_id = sanitize($tutor_company_id, INT);
        $and_tutor_company = $tutor_company_id > 0 ? " AND tutors.company_id = $tutor_company_id" : "";
        $query = "SELECT corsisti.surname, corsisti.name, companies.business_name, 
                        learning_project.title as title_project, learning_objects.title as title_object, 
                        learning_event_id, MAX(end_object) as last_end_object 
                  FROM users_events_session 
                    JOIN learning_events ON users_events_session.learning_event_id = learning_events.id
                    JOIN learning_project_users ON  learning_events.learning_project_user_id = learning_project_users.id
                    JOIN users as tutors ON learning_project_users.company_id = tutors.id
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    JOIN learning_objects ON learning_events.last_learning_object_id = learning_objects.id
                    JOIN users as corsisti ON learning_project_users.user_id = corsisti.id
                    JOIN companies ON corsisti.company_id = companies.id
                  WHERE DATE_SUB(NOW(),INTERVAL 59 SECOND) <= users_events_session.end_object
                    $and_tutor_company
                  GROUP BY learning_event_id
                  ORDER BY last_end_object DESC";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    public function getOnlineUsersByTutorAdmin($tutor_admin_id = 0) {
        $tutor_admin_id = sanitize($tutor_admin_id, INT);
        $and_tutor_admin = $tutor_admin_id > 0 ? " AND learning_project_users.company_id = $tutor_admin_id" : "";
        $query = "SELECT corsisti.surname, corsisti.name, companies.business_name, 
                        learning_project.title as title_project, learning_objects.title as title_object, 
                        learning_event_id, MAX(end_object) as last_end_object 
                  FROM users_events_session 
                    JOIN learning_events ON users_events_session.learning_event_id = learning_events.id
                    JOIN learning_project_users ON  learning_events.learning_project_user_id = learning_project_users.id
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    JOIN learning_objects ON learning_events.last_learning_object_id = learning_objects.id
                    JOIN users as corsisti ON learning_project_users.user_id = corsisti.id
                    JOIN companies ON corsisti.company_id = companies.id
                  WHERE DATE_SUB(NOW(),INTERVAL 59 SECOND) <= users_events_session.end_object
                    $and_tutor_admin
                  GROUP BY learning_event_id
                  ORDER BY last_end_object DESC";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    public function getAllPurchase(){
        $query = "SELECT tutors_purchases.*, companies.business_name,
                        companies.is_tutor, companies.is_partner, 
                        users.name, users.surname,
                        learning_project.title, cost_centre.cost_centre
                  FROM tutors_purchases 
                    JOIN companies ON tutors_purchases.customer_company_id = companies.id 
                    JOIN users ON tutors_purchases.tutor_id = users.id 
                    JOIN learning_project ON tutors_purchases.learning_project_id = learning_project.id 
                    LEFT JOIN cost_centre ON tutors_purchases.cost_centre_id = cost_centre.id_cost_centre
                  WHERE companies.id <> 408 AND learning_project.id <> 25
                  ORDER BY tutors_purchases.creation_date DESC"; // 408 = adecco test -  25 = corso lavoratore demo
        $res = $this->db_conn->query($query);
        require_once 'class_user.php';
        $user_obj = new T81User();
        for ($i = 0; $i < count($res); $i++){
            $tutor_company = $user_obj->getUserCompany($res[$i]['user_company_ref']);
            $res[$i]['tutor_company'] = $tutor_company['business_name'];
        }
        return $res;
    }
    
    public function countAllPurchase(){
        $query = "SELECT SUM(tutors_purchases.qta) as qta
                  FROM tutors_purchases 
                  WHERE 1";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0]['qta'] : 0;
    }
    
    /**
     * Restituisce tutti gli ordini degli enti formativi a cui il socio ha
     * venduto una licenza delle piattaforma
     * @param type $member_id
     * @return type
     */
    public function getAllPurchasesByMemberTutors($member_id){
        $member_id = filter_var($member_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT company_plans.*, companies.business_name
                    FROM company_plans
                    JOIN companies ON company_plans.tutor_id = companies.id
                    WHERE tutor_id = $member_id";
        $tutors = $this->db_conn->query($query);
        if (isset($tutors[0])){
            $res = array();
            foreach ($tutors as $tutor){
                $tutor_purchases = $this->getAllPurchasesByTutor($tutor['company_id']);
                if ($tutor_purchases) {
                    $res[$tutor['business_name']] = $tutor_purchases;
                }
            }
            return $res;
        } else {
            return false;
        }
    }

    /**
     * Restituisce tutti gli acquisti effettuati dall'ente formativo 
     * e dalle aziende a lui associate ordinati per data
     * @param type $tutor_id
     * @return type
     */
    public function getAllPurchasesByTutor($tutor_id) {
        $tutor_id = sanitize($tutor_id, INT);
        $query = "SELECT tutors_purchases.*, 
                        users.name, users.surname, companies.business_name,
                        companies.is_tutor, companies.is_partner,
                        learning_project.title, cost_centre.cost_centre
                    FROM tutors_purchases
                        JOIN companies ON tutors_purchases.customer_company_id = companies.id
                        JOIN users ON companies.owner_user_id = users.id
                        JOIN learning_project ON tutors_purchases.learning_project_id = learning_project.id
                        LEFT JOIN cost_centre ON tutors_purchases.cost_centre_id = cost_centre.id_cost_centre
                    WHERE tutors_purchases.customer_company_id = $tutor_id 
                        OR tutors_purchases.customer_company_id IN (SELECT companies.id 
                                                                    FROM companies
                                                                    JOIN users ON companies.owner_user_id = users.id
                                                                    WHERE users.company_id = $tutor_id)
                    ORDER BY tutors_purchases.creation_date DESC";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    /**
     * Conta gli acquisti effettuati dall'ente formativo 
     * e dalle aziende a lui associate ordinati per data
     * @param integer $tutor_id
     * @return type
     */
    public function countPurchasesByTutor($tutor_id) {
        $tutor_id = sanitize($tutor_id, INT);
        $query = "SELECT SUM(tutors_purchases.qta) as qta 
                    FROM tutors_purchases
                        JOIN companies ON tutors_purchases.customer_company_id = companies.id
                        JOIN users ON companies.owner_user_id = users.id
                        JOIN learning_project ON tutors_purchases.learning_project_id = learning_project.id
                        LEFT JOIN cost_centre ON tutors_purchases.cost_centre_id = cost_centre.id_cost_centre
                    WHERE tutors_purchases.customer_company_id IN (SELECT companies.id 
                                                                    FROM companies
                                                                    JOIN users ON companies.owner_user_id = users.id
                                                                    WHERE users.company_id = $tutor_id)";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0]['qta'] : 0;
    }

    /**
     * Restituisce gli acquisti effettuati per l'azienda ordinati per data decrescente
     * @param type $company_id
     * @return type
     */
    public function getAllPurchasesByCompany($company_id) {
        $company_id = sanitize($company_id, INT);
        $query = "SELECT tutors_purchases.*, users.name, users.surname, companies.business_name, learning_project.title, cost_centre.cost_centre
                  FROM tutors_purchases
                    JOIN companies ON customer_company_id = companies.id
                    JOIN users ON tutors_purchases.tutor_id = users.id
                    JOIN learning_project ON tutors_purchases.learning_project_id = learning_project.id
                    LEFT JOIN cost_centre ON tutors_purchases.cost_centre_id = cost_centre.id_cost_centre
                  WHERE tutors_purchases.customer_company_id = " . $company_id . "
                  ORDER BY tutors_purchases.creation_date DESC";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    /**
     * Restituisce il conteggio degli acquisti effettuati per l'azienda divisi per corso
     * @param type $company_id
     * @return type
     */
    public function getPurchasesByCompany($company_id) {
        $company_id = sanitize($company_id, INT);
        $query = "SELECT learning_project.id, learning_project.title, SUM(tutors_purchases.qta) as somma
					  FROM tutors_purchases
					  JOIN learning_project ON learning_project_id = learning_project.id
					  WHERE customer_company_id = " . $company_id . "
					  GROUP BY learning_project.id
					  ORDER BY learning_project.title";

        $res = $this->db_conn->query($query);
        return $res;
    }

    /**
     * Restituisce il conteggio degli acquisti effettuati per l'azienda 
     * 
     * @param Integer $company_id
     * @return type
     */
    public function countPurchasesByCompany($company_id) {
        $company_id = sanitize($company_id, INT);
        $query = "SELECT SUM(tutors_purchases.qta) as qta
					  FROM tutors_purchases
					  WHERE customer_company_id = $company_id";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0]['qta'] : 0;
    }

    public function getPurchasesByCompanyByLearningProject($company_id, $learning_project_id) {
        $company_id = sanitize($company_id, INT);
        $learning_project_id = sanitize($learning_project_id, INT);
        $query = "SELECT tutors_purchases.*, users.name, users.surname, cost_centre.cost_centre
					  FROM tutors_purchases
					  JOIN users ON tutor_id = users.id
                                          LEFT JOIN cost_centre ON tutors_purchases.cost_centre_id = cost_centre.id_cost_centre
					  WHERE customer_company_id = " . $company_id . " AND learning_project_id = " . $learning_project_id . "
					  ORDER BY creation_date";

        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getLearningEvent($learning_project_user_id) {
        $learning_project_user_id = sanitize($learning_project_user_id, INT);
        $query = "SELECT learning_events.* FROM learning_events WHERE learning_project_user_id = " . $learning_project_user_id;
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getLastLearningEvent($learning_project_user_id) {
        $learning_project_user_id = sanitize($learning_project_user_id, INT);
        $query = "SELECT COUNT(id) as 'count' FROM learning_events WHERE learning_events.end_date_time is NULL AND learning_project_user_id = " . $learning_project_user_id;
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getLearningEventWithoutFinished($learning_project_user_id) {
        $learning_project_user_id = sanitize($learning_project_user_id, INT);
        $query = "SELECT learning_events.* FROM learning_events WHERE learning_events.end_date_time = NULL AND learning_project_user_id = " . $learning_project_user_id;
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function formatDate($date, $format = 'd-m-Y H:i:s') {
        if (!isset($date) || $date == "0000-00-00 00:00:00") {
            return "-";
        } else {
            $date = date_create($date);
            return date_format($date, $format);
        }
    }

    public function changeInvoiced($id) {
        $id = sanitize($id, INT);
        $query = "SELECT invoiced FROM tutors_purchases WHERE id = " . $id;
        $res = $this->db_conn->query($query);
        $invoiced = $res[0]['invoiced'];
        if ($invoiced) {
            $invoiced = 0;
        } else {
            $invoiced = 1;
        }
        $query = "UPDATE tutors_purchases SET invoiced = " . $invoiced . " WHERE id = " . $id;
        return $this->db_conn->update($query);
    }
    
    public function getLastAccess($user_id){
        $user_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT end_object
                  FROM users_login_session 
                    JOIN users_events_session ON users_login_session.session_id = users_events_session.session_id
                  WHERE user_id = $user_id
                  ORDER BY end_object DESC
                  LIMIT 1";
        $res = $this->db_conn->query($query);
        return !empty($res) ? $res[0]['end_object'] : false;
    }

    public function getSessionsFromUser($user_id) {
        $user_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT user_id, 
                    learning_event_id, 
                    object_id, 
                    users_login_session.session_id, 
                    ip_address, user_agent, 
                    start_session, 
                    start_object, 
                    end_object, 
                    end_session
                  FROM users_login_session 
                    JOIN users_events_session ON users_login_session.session_id = users_events_session.session_id
                  WHERE user_id = $user_id 
                  GROUP BY learning_event_id, object_id, users_login_session.session_id 
                  ORDER BY start_session ASC";
        $res = $this->db_conn->query($query);
        return $res ? : false;
    }

    public function getSessionsFromLearningEvent($learning_event_id) {
        $learning_event_id = filter_var($learning_event_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT user_id, 
                    learning_event_id, 
                    object_id, 
                    users_login_session.session_id, 
                    ip_address, user_agent, 
                    start_session, 
                    start_object, 
                    end_object, 
                    end_session
                  FROM users_login_session 
                    JOIN users_events_session ON users_login_session.session_id = users_events_session.session_id
                  WHERE learning_event_id = $learning_event_id 
                  GROUP BY learning_event_id, object_id, users_login_session.session_id 
                  ORDER BY start_session ASC";
        $res = $this->db_conn->query($query);
        return $res ? : false;
    }

    public function getLearnDetailByLearningEvent($learning_event_id) {
        $learning_event_id = sanitize($learning_event_id, INT);
        $query = "SELECT learning_project.* FROM learning_events
								JOIN learning_project_users ON learning_project_user_id = learning_project_users.id
								JOIN learning_project ON learning_project_id = learning_project.id
								WHERE learning_events.id = $learning_event_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getLearningObject($learning_object_id) {
        $learning_object_id = sanitize($learning_object_id, INT);
        $query = "SELECT * FROM learning_objects WHERE id = " . $learning_object_id;
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    /**
     * Restituisce lo stato di avanzamento dei corsi assegnati agli utenti dell'azienda
     *
     * @param unknown $company_id
     * @return array o boolean
     */
    public function getLearningStatus($company_id) {
        $company_id = sanitize($company_id, INT);
        $query = "SELECT
                    learning_project_users.learning_project_id,
                    learning_project.title,
                    COUNT(learning_project_users.id) as total,
                    COUNT(learning_events.start_date_time) as started,
                    COUNT(learning_events.end_date_time) as finished
                  FROM learning_project_users JOIN users ON user_id = users.id
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
                  WHERE users.company_id = $company_id
                  GROUP BY learning_project_users.learning_project_id
                  ORDER BY learning_project.title";
    $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    /**
     *
     * @param integer $tutor_admin_id
     * @return array o boolean
     *
     * Restituisce lo stato d'avanzamento dei corsi assegnati agli utenti delle aziende
     * del assegnate all'ente formativo $tutor_company_id
     * (Nota bene: comprende anche quelli assegnati o acquistati da altri)
     *
     *
     * In scadenza ovvero nei days_of_alert
     * SELECT
        count(*)
        FROM learning_project_users
        JOIN users ON learning_project_users.company_id = users.id
        JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
        LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
        WHERE users.company_id = 2 AND learning_project_users.finish_within >= now() AND datediff(learning_project_users.finish_within, now()) <= learning_project_users.days_to_alert
     */
    public function getLearningStatusByTutorCompany($tutor_company_id, $total = false) {
        $tutor_company_id = filter_var($tutor_company_id, FILTER_SANITIZE_NUMBER_INT);
        $total = filter_var($total, FILTER_VALIDATE_BOOLEAN);
        $select = $total ? '' : 'learning_project_users.learning_project_id,learning_project.title,';
        $group = $total ? '' : 'GROUP BY learning_project_users.learning_project_id ORDER BY learning_project.title';
        $query = "SELECT
                    $select
                    COUNT(learning_project_users.id) as total,
                    COUNT(learning_events.start_date_time) as started,
                    COUNT(learning_events.end_date_time) as finished
                FROM learning_project_users
                    JOIN users ON learning_project_users.user_id = users.id
                    JOIN companies ON users.company_id = companies.id
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
                WHERE companies.owner_user_id IN (SELECT users.id FROM users WHERE users.company_id = $tutor_company_id)
                $group";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? ($total ? $res[0] : $res) : false;
    }

    public function getLearningStatusByTutorCompanyOnlyAlertEndDate($tutor_company_id, $total = false) {
        $tutor_company_id = filter_var($tutor_company_id, FILTER_SANITIZE_NUMBER_INT);
        $total = filter_var($total, FILTER_VALIDATE_BOOLEAN);
        $select = $total ? '' : 'learning_project_users.learning_project_id,learning_project.title,';
        $group = $total ? '' : 'GROUP BY learning_project_users.learning_project_id ORDER BY learning_project.title';
        $query = "SELECT
                    $select
                    COUNT(*) as alert
                FROM learning_project_users
                    JOIN users ON learning_project_users.company_id = users.id
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
                WHERE learning_project_users.finish_within >= now() AND datediff(learning_project_users.finish_within, now()) <= learning_project_users.days_to_alert AND users.company_id = $tutor_company_id
                $group";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? ($total ? $res[0] : $res) : false;
    }

    /**
     *
     * @param integer $tutor_admin_id
     * @return array o boolean
     *
     * Restituisce lo stato d'avanzamento dei corsi assegnati agli utenti delle aziende
     * di cui $tutor_admin_id è amministratore di sistema
     * (Nota bene: comprende anche quelli assegnati o acquistati da altri)
     *
     */
    public function getLearningStatusByTutorAdmin($tutor_admin_id) {
        $tutor_admin_id = sanitize($tutor_admin_id, INT);
        $query = "SELECT
                    learning_project_users.learning_project_id,
                    learning_project.title,
                    COUNT(learning_project_users.id) as total,
                    COUNT(learning_events.start_date_time) as started,
                    COUNT(learning_events.end_date_time) as finished
                FROM learning_project_users
                    JOIN users ON learning_project_users.user_id = users.id
                    JOIN companies ON users.company_id = companies.id
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
                WHERE companies.owner_user_id = $tutor_admin_id
                GROUP BY learning_project_users.learning_project_id
                ORDER BY learning_project.title";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    /**
     * 
     * @param integer $company_id
     * @param boolean $total
     * @return array
     * 
     * Restituisce lo stato dei corsi acquistati dall'azienda (numero di corsi totali, iniziati, finiti)
     */
    public function getLearningStatusByCompany($company_id, $total = false) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $total = filter_var($total, FILTER_VALIDATE_BOOLEAN);
        $select = $total ? '' : 'learning_project_users.learning_project_id,learning_project.title,';
        $group = $total ? '' : 'GROUP BY learning_project_users.learning_project_id ORDER BY learning_project.title';
        $query = "SELECT
                    $select
                    COUNT(learning_project_users.id) as total,
                    COUNT(learning_events.start_date_time) as started,
                    COUNT(learning_events.end_date_time) as finished
                FROM learning_project_users
                    JOIN users ON learning_project_users.user_id = users.id
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
                WHERE users.company_id = $company_id
                $group";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? ($total ? $res[0] : $res) : false;
    }

    /**
     * 
     * @param integer $company_id
     * @param boolean $total
     * @return array
     * 
     * Restituisce lo stato dei corsi dei corsi in scadenza acquistati dall'azienda
     */
    public function getLearningStatusByCompanyOnlyAlertEndDate($company_id, $total = false) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $total = filter_var($total, FILTER_VALIDATE_BOOLEAN);
        $select = $total ? '' : 'learning_project_users.learning_project_id,learning_project.title,';
        $group = $total ? '' : 'GROUP BY learning_project_users.learning_project_id ORDER BY learning_project.title';
        $query = "SELECT
                    $select
                    COUNT(*) as alert
                FROM learning_project_users
                    JOIN users ON learning_project_users.user_id = users.id
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                    LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
                WHERE learning_project_users.finish_within >= now() 
                AND datediff(learning_project_users.finish_within, now()) <= learning_project_users.days_to_alert 
                AND users.company_id = $company_id
                $group";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? ($total ? $res[0] : $res) : false;
    }

    public function getLearningProjectPurchasedByTutorAdmin($tutor_admin_id) {
        $tutor_admin_id = sanitize($tutor_admin_id, INT);
        $query = "SELECT
                    learning_project.id,
                    learning_project.title
                FROM tutors_purchases
                    JOIN companies ON customer_company_id = companies.id
                    JOIN users ON companies.owner_user_id = users.id
                    JOIN learning_project ON tutors_purchases.learning_project_id = learning_project.id
                WHERE users.id = $tutor_admin_id
                GROUP BY learning_project.id
                ORDER BY learning_project.title";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getLearningProjectPurchasedByTutorCompany($tutor_company_id) {
        $tutor_company_id = sanitize($tutor_company_id, INT);
        $query = "SELECT
                    learning_project.id,
                    learning_project.title
                FROM tutors_purchases
                    JOIN companies ON customer_company_id = companies.id
                    JOIN users ON companies.owner_user_id = users.id
                    JOIN learning_project ON tutors_purchases.learning_project_id = learning_project.id
                WHERE users.company_id = $tutor_company_id
                GROUP BY learning_project.id
                ORDER BY learning_project.title";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getNumLearningProjectPurchasedByCompany($learning_project_id, $company_id) {
        $learning_project_id = sanitize($learning_project_id, INT);
        $company_id = sanitize($company_id, INT);
        $query = "SELECT SUM(qta) as qta FROM tutors_purchases WHERE learning_project_id = $learning_project_id AND customer_company_id = $company_id";
        $res = $this->db_conn->query($query);
        return $res[0]['qta'] ? : 0;
    }

    public function getFeedbackTopics($company_id = 0, $learning_project_id = 0) {
        $company_id = sanitize($company_id, INT);
        $learning_project_id = sanitize($learning_project_id, INT);
        $query = "SELECT * FROM feedback_topic WHERE company_id = $company_id AND learning_project_id = $learning_project_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getFeedbackQuestions($feedback_topic_id = 0, $deleted = 0) {
        $feedback_topic_id = sanitize($feedback_topic_id, INT);
        $deleted = sanitize($deleted, INT);
        if ($feedback_topic_id > 0) {
            $where = "	WHERE feedback_topic_id = $feedback_topic_id";
            $and = $deleted >= 0 ? "AND deleted = $deleted" : "";
        } else {
            $where = $deleted >= 0 ? "WHERE deleted = $deleted" : "";
            $and = "";
        }
        $query = "SELECT
                    feedback_questions.*,
                    question_sentences.text,
                    feedback_topic.short_description,
                    feedback_topic.long_description
                FROM feedback_questions
                    JOIN question_sentences ON question_sentence_id = question_sentences.id
                    JOIN feedback_topic ON feedback_topic_id = feedback_topic.id
                $where $and";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    /* funzione basata sul learning_events - da eliminare per regole sulla tracciabilità */

    public function getFeedbackByQuestionAndCompany($question_sentence_id, $company_id) {
        $question_sentence_id = sanitize($question_sentence_id, INT);
        $company_id = sanitize($company_id, INT);
        $query = "SELECT answers.text, COUNT(answer_id) as qta
                FROM feedback
                    JOIN answers ON answer_id = answers.id
                WHERE feedback.question_sentence_id = $question_sentence_id
                AND learning_event_id IN
                (
                    SELECT learning_events.id
                    FROM learning_events
                        JOIN learning_project_users ON learning_project_user_id = learning_project_users.id
                        JOIN users ON learning_project_users.user_id = users.id
                    WHERE users.company_id = $company_id
                )
                GROUP BY answer_id ";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getFeedbackAnswerByCompanyAndLearningProject($question_sentence_id, $company_id, $learning_project_id = 0) {
        $question_sentence_id = sanitize($question_sentence_id, INT);
        $company_id = sanitize($company_id, INT);
        $learning_project_id = sanitize($learning_project_id, INT);
        $and_learning_project = $learning_project_id > 0 ? " AND feedback.learning_project_id = $learning_project_id" : "";
        $query = "SELECT answers.text, COUNT(answer_id) as qta
                FROM feedback
                    JOIN answers ON answer_id = answers.id
                WHERE feedback.question_sentence_id = $question_sentence_id
                AND feedback.company_id = $company_id $and_learning_project
                GROUP BY answer_id ";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getFeedbackCommentByCompanyAndLearningProject($question_sentence_id, $company_id, $learning_project_id = 0) {
        $question_sentence_id = sanitize($question_sentence_id, INT);
        $company_id = sanitize($company_id, INT);
        $learning_project_id = sanitize($learning_project_id, INT);
        $and_learning_project = $learning_project_id > 0 ? " AND feedback.learning_project_id = $learning_project_id" : "";
        $query = "SELECT *
                FROM feedback
                WHERE feedback.question_sentence_id = $question_sentence_id
                AND feedback.company_id = $company_id $and_learning_project
                ORDER BY id DESC";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getFeedbackAnswerByTutorAndLearningProject($question_sentence_id, $tutor_id, $learning_project_id = 0) {
        $question_sentence_id = sanitize($question_sentence_id, INT);
        $tutor_id = sanitize($tutor_id, INT);
        $learning_project_id = sanitize($learning_project_id, INT);
        $and_learning_project = $learning_project_id > 0 ? " AND feedback.learning_project_id = $learning_project_id" : "";
        $query = "SELECT answers.text, COUNT(answer_id) as qta
                FROM feedback
                    JOIN answers ON answer_id = answers.id
                WHERE feedback.question_sentence_id = $question_sentence_id
                AND feedback.company_id IN (
                    SELECT companies.id 
                    FROM companies 
                        JOIN users ON companies.owner_user_id = users.id
                    WHERE users.company_id = $tutor_id
                    ) $and_learning_project
                GROUP BY answer_id ";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getFeedbackCommentByTutorAndLearningProject($question_sentence_id, $tutor_id, $learning_project_id = 0) {
        $question_sentence_id = sanitize($question_sentence_id, INT);
        $tutor_id = sanitize($tutor_id, INT);
        $learning_project_id = sanitize($learning_project_id, INT);
        $and_learning_project = $learning_project_id > 0 ? " AND feedback.learning_project_id = $learning_project_id" : "";
        $query = "SELECT *
                FROM feedback
                WHERE feedback.question_sentence_id = $question_sentence_id
                AND feedback.company_id IN (
                    SELECT companies.id 
                    FROM companies 
                        JOIN users ON companies.owner_user_id = users.id
                    WHERE users.company_id = $tutor_id
                    )  $and_learning_project
                ORDER BY id DESC";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    /**
     * Restituisce l'elenco dei corsi assegnati agli utenti dell'azienda 
     * <code>$company_id</code> per cui è stato registrato un feedback;
     * 
     * @param integer $company_id  
     * @return array (id e titolo dei corsi)
     */
    public function getFeedbackLearningProjectByCompany($company_id){
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT learning_project.id, learning_project.title "
                . "FROM learning_project_users "
                . "JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id "
                . "JOIN feedback ON learning_project.id = feedback.learning_project_id "
                . "WHERE learning_project_users.id_company = $company_id "
                . "GROUP BY learning_project.id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    /**
     * Restituisce l'elenco dei corsi assegnati nella tabella delle licenze
     * <code>learning_project_users</code> il cui amministratore tutor (colonna 
     * <code>company_id</code>) è dipendente dell'azienda <code>$tutor_id</code>
     * e per cui è stato registrato un feedback;
     * 
     * @param integer $tutor_id  
     * @return array (id e titolo dei corsi)
     */
    public function getFeedbackLearningProjectByTutor($tutor_id){
        $tutor_id = filter_var($tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT learning_project.id, learning_project.title "
                . "FROM learning_project_users "
                . "JOIN companies ON learning_project_users.id_company = companies.id "
                . "JOIN users ON companies.owner_user_id = users.id "
                . "JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id "
                . "JOIN feedback ON learning_project.id = feedback.learning_project_id "
                . "WHERE users.company_id = $tutor_id "
                . "GROUP BY learning_project.id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    /**
     * Restituisce l'elenco dei corsi per cui è stata assegnata una licenza
     * all'azienda <code>$company_id</code>
     * 
     * @param integer $company_id
     * @return type
     */
    public function getProgressLearningProjectByCompany($company_id){
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT learning_project.id, learning_project.title "
                . "FROM learning_project_users "
                . "JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id "
                . "WHERE learning_project_users.id_company = $company_id "
                . "GROUP BY learning_project.id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    /**
     * Restituisce l'elenco dei corsi per cui è stata assegnata una licenza
     * all'aziende che hanno come referente un dipendente dell'ente formativo
     * <code>$tutor_id</code>
     * 
     * @param integer $company_id
     * @return type
     */
    public function getProgressLearningProjectByTutor($tutor_id){
        $tutor_id = filter_var($tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT learning_project.id, learning_project.title "
                . "FROM learning_project_users "
                . "JOIN companies ON learning_project_users.id_company = companies.id " // le aziende per le quali è stata assegnata la licenza
                . "JOIN users ON companies.owner_user_id = users.id " // i referenti dell'ente formativo delle aziende per cui è stata assegnata la licenza
                . "JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id "
                . "WHERE learning_project_users.id_company = $tutor_id " // l'ente formativo dei referenti delle aziende ...
                . "GROUP BY learning_project.id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    /**
     * Restituisce l'elenco delle licenze dei corsi completati per l'azienda <code>$company_id</code
     * 
     * @param type $company_id
     * @return type
     */
    public function getLeaningEventsClosedByCompany($company_id){
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT learning_events.id as learning_event_id,
                    learning_project_users.id as license_id,
                    learning_project_users.user_id,
                    learning_project_users.learning_project_id,
                    users.name,
                    users.surname,
                    business_functions.name as function,
                    learning_project.title
                  FROM learning_events
                    JOIN learning_project_users ON learning_events.learning_project_user_id = learning_project_users.id
                    JOIN users ON learning_project_users.user_id = users.id
                    JOIN business_functions ON users.business_function_id = business_functions.id
                    JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id
                  WHERE learning_events.end_date_time IS NOT NULL
                    AND learning_events.end_date_time!= '0000-00-00 00:00:00' 
                    AND users.company_id = $company_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
}