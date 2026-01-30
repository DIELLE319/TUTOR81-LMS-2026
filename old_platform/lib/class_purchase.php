<?php

require_once 'class_db.php';
require_once 'sanitize.php';

class iWDPurchase {

    var $db_conn;

    public function __construct() {
        $this->db_conn = new MySQLConn();
    }

    private function generatePassword() {
        $length = 5;
        $password = "";
        $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ";
        $maxlength = strlen($possible);
        if ($length > $maxlength) {
            $length = $maxlength;
        }
        $i = 0;
        while ($i < $length) {
            $char = substr($possible, mt_rand(0, $maxlength - 1), 1);
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }
        return $password;
    }

    public function pwdLicenseExist($pwd) {
        $pwd = $this->db_conn->escapestr($pwd);
        $query = "SELECT COUNT(*) as conta FROM learning_project_users WHERE learning_project_pwd = '" . $pwd . "'";
        $password_count = $this->db_conn->query($query);
        return ($password_count[0]['conta'] != 0);
    }

    public function getPurchase($tutor_purchase_id) {
        $tutor_purchase_id = sanitize($tutor_purchase_id, INT);
        $query = "SELECT * FROM tutors_purchases WHERE id = $tutor_purchase_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }
    
    
    public function getLicenceDetail($licence_id){
        $licence_id = filter_var($licence_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT learning_project_users.*, learning_events.id as learning_event_id, learning_events.progress_rate 
                FROM learning_project_users 
                LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id 
                WHERE learning_project_users.id = $licence_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function createNewLicense($user_id, $learn_prj, $tutor_id, $starting_from, $finish_within, $days_to_alert,$id_company,$accreditation_code = "") {
        $user_id = sanitize($user_id, INT);
        $learn_prj = sanitize($learn_prj, INT);
        $tutor_id = sanitize($tutor_id, INT);
        $starting_from = $this->db_conn->escapestr($starting_from);
        $finish_within = $this->db_conn->escapestr($finish_within);
        $days_to_alert = sanitize($days_to_alert, INT);
        $id_company = sanitize($id_company, INT);
        $accreditation_code = $this->db_conn->escapestr(trim($accreditation_code));
        $password = "";
        do {
            $password = $this->generatePassword();
        } while ($this->pwdLicenseExist($password));
        $query = "INSERT INTO learning_project_users(
                    user_id,learning_project_id,learning_project_pwd,company_id,starting_from,
                    finish_within,days_to_alert,id_company,accreditation_code)
                  VALUES($user_id,$learn_prj,'$password',$tutor_id,'$starting_from',
                    '$finish_within',$days_to_alert,$id_company,'$accreditation_code')";
        $res = $this->db_conn->insert($query);
        return $res;
    }
    
    /**
     * 
     * @param integer $user_id      -> l'utente a cui è assegnato il corso (0 se da ecommerce)
     * @param integer $learn_prj    -> ID del Learning Project
     * @param integer $tutor_id     -> l'amministratore che assegna la licenza
     * @param string YYYY-mm-dd $starting_from
     * @param type YYY-mm-dd $finish_within
     * @param type $days_to_alert
     * @param type $id_company
     * @param type $accreditation_code
     * @param type $tutor_purchase_id
     * @param type $destination_email
     * @param type $isAssigned
     * @param string $code
     * @return type il numero di inserimenti effettuato
     */
    public function createNewEcommerceLicense($user_id, $learn_prj, $tutor_id, 
            $starting_from, $finish_within, $days_to_alert,$id_company,$accreditation_code, 
            $tutor_purchase_id, $destination_email, $isAssigned=false) {
        $user_id = sanitize($user_id, INT);
        $learn_prj = sanitize($learn_prj, INT);
        $tutor_id = sanitize($tutor_id, INT);
        $starting_from = date('Y-m-d', strtotime($this->db_conn->escapestr($starting_from)));
        $finish_within = date('Y-m-d', strtotime($this->db_conn->escapestr($finish_within)));
        //error_log("lib/class_purchase.php --> data inizio: " + var_dump($starting_from));
        //error_log("lib/class_purchase.php --> data fine: " + var_dump($finish_within));
        $days_to_alert = sanitize($days_to_alert, INT);
        $id_company = sanitize($id_company, INT);
        $accreditation_code = $this->db_conn->escapestr(trim($accreditation_code));
        $password = "";
        do {
            $password = $this->generatePassword();
        } while ($this->pwdLicenseExist($password));
        $assigned = $isAssigned ? 1 : 0;

        $query = "INSERT INTO learning_project_users(
                    user_id,learning_project_id,learning_project_pwd,company_id,starting_from,
                    finish_within,days_to_alert,id_company,accreditation_code, tutor_purchase_id,assigned,email)
                  VALUES($user_id,$learn_prj,'$password',$tutor_id,'$starting_from',
                    '$finish_within',$days_to_alert,$id_company,'$accreditation_code', 
                    $tutor_purchase_id,$assigned,'$destination_email')";
        $res = $this->db_conn->insert($query);
        return $res;
    }


    /**
     * Registra l'acquisto dei corsi
     * 
     * @param integer $company_id l'azienda per cui si accquista e iscrive il corso
     * @param integer $learning_project_id
     * @param integer $qta
     * @param integer $user_company_ref l'amministratore tutor di riferimento dell'azienda
     * @param integer $tutor_id l'amministratore che effettua l'acquisto (0 se ecommerce)
     * @param string $ext_po_number
     * @param integer $cost_centre_id
     * @return integer il numero di inserimenti effettuato
     */
    public function purchaseCourse($company_id, $learning_project_id, $qta, $user_company_ref, 
            $tutor_id, $ext_po_number, $cost_centre_id, $pack_purchase_id = 0, $price=0, $code = '') {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $learning_project_id = filter_var($learning_project_id, FILTER_SANITIZE_NUMBER_INT);
        $qta = filter_var($qta, FILTER_SANITIZE_NUMBER_INT);
        $user_company_ref = filter_var($user_company_ref, FILTER_SANITIZE_NUMBER_INT);
        $tutor_id = filter_var($tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $ext_po_number = filter_var($ext_po_number, FILTER_SANITIZE_NUMBER_INT);
        $cost_centre_id = filter_var($cost_centre_id, FILTER_SANITIZE_NUMBER_INT);
        $pack_purchase_id = filter_var($pack_purchase_id, FILTER_SANITIZE_NUMBER_INT);
        $price = filter_var($price, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $code = filter_var($code, FILTER_SANITIZE_STRING);
        $query = "INSERT INTO tutors_purchases(tutor_id,customer_company_id,user_company_ref,learning_project_id,qta,ext_po_number,cost_centre_id, pack_purchase_id, price, code)
                VALUES('$tutor_id','$company_id','$user_company_ref','$learning_project_id','$qta','$ext_po_number','$cost_centre_id', '$pack_purchase_id','$price','$code')";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function updatePurchaseCourse($purchase_id, $license_qta) {
        $purchase_id = sanitize($purchase_id, INT);
        $license_qta = sanitize($license_qta, INT);
        $query = "UPDATE tutors_purchases SET qta = qta + " . $license_qta . " WHERE id = " . $purchase_id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function removePurchaseCourse($purchase_id, $licence_qta) {
        $purchase_id = filter_var($purchase_id, FILTER_SANITIZE_NUMBER_INT);
        if ($licence_qta === 'acquisto') {
            $query = "DELETE FROM tutors_purchases WHERE id = " . $purchase_id;
        } else {
            $licence_qta = filter_var($licence_qta, FILTER_SANITIZE_NUMBER_INT);
            $query = "UPDATE tutors_purchases SET qta = qta - " . $licence_qta . " WHERE id = " . $purchase_id;
        }
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function removeLicense($license_id) {
        $license_id = filter_var($license_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "DELETE FROM learning_project_users WHERE id = " . $license_id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function getLicenseExpiring() {
        $query = "SELECT learning_project_users.*
                  FROM learning_project_users
                    LEFT JOIN learning_events ON learning_project_users.id = learning_events.learning_project_user_id
                  WHERE (learning_events.end_date_time = '0000-00-00 00:00:00' 
                    OR learning_events.end_date_time IS NULL) 
                    AND CURDATE() = DATE_SUB(finish_within, INTERVAL days_to_alert DAY)";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function closeiWDPurchase() {
        //PHP B id=30525
        //@mysql_close($this->conn);
    }
    
    public function scheduleLicense($license_id,$starting_from,$finish_within,$days_to_alert){
        $license_id = sanitize($license_id, INT);
        $starting_from = $this->db_conn->escapestr($starting_from);
        $finish_within = $this->db_conn->escapestr($finish_within);
        $days_to_alert = sanitize($days_to_alert, INT);
        $query = "UPDATE learning_project_users "
                . "SET starting_from = '$starting_from', "
                . "finish_within = '$finish_within', "
                . "days_to_alert = $days_to_alert "
                . "WHERE id = $license_id";
        $res = $this->db_conn->update($query);
        return $res ? : false;
    }
    
    /**
     * Restituisce le licenze delle aziende (colonna <code>id_company</code> della 
     * tabella <code>learning_project_users</code>) dell'ente formativo <code>$tutor_id</code>
     * 
     * @param type $tutor_id
     * @return false if not exist, 
     *        array of learning_project_users, companies.business_name,
     *          users.name, users.surname, users.username, learning_project.title
     */
    public function getLicensesByTutor($tutor_id){
        $tutor_id = filter_var($tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT learning_project_users.*, "
                . "companies.business_name, "
                . "users.name, users.surname, users.username, "
                . "learning_project.title "
                . "FROM learning_project_users "
                . "JOIN companies ON learning_project_users.id_company = companies.id "
                . "JOIN users ON companies.owner_user_id = users.id "
                . "JOIN learning_project ON learning_project_users.learning_project_id = learning_project.id "
                . "WHERE users.company_id = $tutor_id "
                . "GROUP BY learning_project.id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    /**
     * Restituisce il numero di elearning acquistati ma non ancora non assegnati
     * in base a <code>$comapny_id</code> e <code>$learning_project_id</code>.
     * Calcola il numero come differenza fra acquisti effettuati e corsi assegnati. 
     * 
     * @param integer $company_id
     * @param integer $learning_project_id
     * @return integer
     */
    public function countElearningProjectUnassigned($company_id, $learning_project_id){
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $learning_project_id = filter_var($learning_project_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT 
                    (SELECT COALESCE(SUM(qta),0)
                        FROM tutors_purchases 
                        JOIN learning_project ON learning_project_id = learning_project.id 
                        WHERE learning_project.id = '$learning_project_id' 
                        AND customer_company_id = '$company_id')
                  -
                    (SELECT count(*) 
                        FROM learning_project_users 
                        LEFT JOIN users ON users.id = learning_project_users.user_id 
                        WHERE learning_project_id = '$learning_project_id' 
                        AND (id_company = '$company_id' 
                        OR users.company_id = '$company_id'))
                  as unassigned";
        $res = $this->db_conn->query($query);
        return $res[0]['unassigned'];
    }
    
    public function setPurchaseInvoiced($tutor_purchase_id, $invoiced = true){
        $tutor_purchase_id = filter_var($tutor_purchase_id, FILTER_SANITIZE_NUMBER_INT);
        $invoiced = filter_var($invoiced, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $query = "UPDATE tutors_purchases SET invoiced = $invoiced WHERE id = $tutor_purchase_id";
        return $this->db_conn->update($query) ? : false;
    }
    
    public function setInvoiceDate($tutor_purchase_id, $invoice_date = 'now'){
        $tutor_purchase_id = filter_var($tutor_purchase_id, FILTER_SANITIZE_NUMBER_INT);
        try {
            $invoice_date = new DateTime($invoice_date);
        } catch (Exception $e) {
            return 'Invalid date: ' . $e->getMessage();
        }
        $query = "UPDATE tutors_purchases 
                  SET invoiced = 1, 
                    invoice_date = '" . $invoice_date->format('Y-m-d') . "'
                  WHERE id = $tutor_purchase_id";
        return $this->db_conn->update($query) ? : false;
    }
    
    /**
     * Imposta l'acquisto come non fatturato cancellando anche la data eventualmente inserita precedentemente
     * 
     * @param integer $tutor_purchase_id
     * @return type
     */
    public function setPurchaseUninvoiced($tutor_purchase_id){
        $tutor_purchase_id = filter_var($tutor_purchase_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "UPDATE tutors_purchases 
                  SET invoiced = 0, 
                    invoice_date = ''
                  WHERE id = $tutor_purchase_id";
        return $this->db_conn->update($query) ? : false;
    }

    public function updateNota($id, $nota) {
        $id = sanitize($id, INT);
        $nota = $this->db_conn->escapestr($nota);
        $query = "UPDATE tutors_purchases SET nota = '" . $nota . "' WHERE id = " . $id;
        return $this->db_conn->update($query);
    }
    
    public function setAccreditationCode($license_id, $accreditation_code){
        $license_id = filter_var($license_id, FILTER_SANITIZE_NUMBER_INT);
        $accreditation_code = filter_var($accreditation_code, FILTER_SANITIZE_STRING);
        $query = "UPDATE learning_project_users SET accreditation_code = $accreditation_code WHERE id = $license_id";
        return $this->db_conn->update($query) ? : false;
    }
    
    public function getLicenseDetailFromPurchaseId($purchase_id){
        $purchase_id = filter_var($purchase_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT learning_project_users.*,
                    users.name as user_name,
                    users.surname as user_surname
                  FROM learning_project_users 
                  LEFT JOIN users ON learning_project_users.user_id = users.id 
                  WHERE learning_project_users.tutor_purchase_id = $purchase_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }


}