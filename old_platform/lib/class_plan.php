<?php
require_once dirname(__FILE__).'/../config.php';
require_once 'class_db.php';

class Plan {
    
    private $db_conn;
    public $features = false;

    public function __construct() {
        $this->db_conn = new MySQLConn();
    }
    
    /** Restituisce le caratteristiche del piano in base all'id
     * 
     * @param Integer $id_plan
     * @return array/false
     */
    public function getPlanFromId($id_plan){
	$id_plan = filter_var($id_plan, FILTER_SANITIZE_NUMBER_INT);
	$query = "SELECT * FROM plans WHERE id = $id_plan";
	$res = $this->db_conn->query($query);
        if (isset($res[0])) {
            $this->features = $res[0];
            return true;
        }
        return false;
    }
    
    /**Restituisce la lista dei piani attivi o non attivi (in accordo con la variabile passata)
     * 
     * @param boolean $active
     * @return multidimensional array/false
     */
    public function getActivePlans($active = true){
        $active = filter_var($active, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $query = "SELECT * FROM plans WHERE active = $active";
	$res = $this->db_conn->query($query);
        return (isset($res[0])) ? $res : false;
    }
    
    /**Aggiunge un nuovo tipo di piano
     * 
     * @param string (max 64 caratteri) $short_desc_plan
     * @param string (max 512 caratteri) $long_desc_plan
     * @param boolean $no_expiration
     * @param boolean $for_tutor
     * @param integer $plan_price
     * @param integer (max 100) $discount
     * @param integer (max 256) $included_courses
     * @param boolean $active
     * @return integer/false
     */
    public function addPlan($short_desc_plan,
                                $long_desc_plan,
                                $no_expiration,
                                $for_tutor,
                                $plan_price,
                                $discount,
                                $included_courses,
                                $active){
        // sanificazioni e validazioni
        $short_desc_plan = filter_var($short_desc_plan, FILTER_SANITIZE_STRING); //$this->db_conn->escapestr(htmlentities($short_desc_plan, ENT_QUOTES));
        $long_desc_plan = filter_var($long_desc_plan, FILTER_SANITIZE_STRING); //$this->db_conn->escapestr(htmlentities($long_desc_plan, ENT_QUOTES));
        $no_expiration = filter_var($no_expiration, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $for_tutor = filter_var($for_tutor, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $plan_price = filter_var($plan_price, FILTER_SANITIZE_NUMBER_INT);
        $discount = filter_var($discount, FILTER_SANITIZE_NUMBER_INT);
        $discount = $discount < 0 ? 0 : ($discount > 100 ? 100 : $discount);
        $included_courses = filter_var($included_courses, FILTER_SANITIZE_NUMBER_INT);
        $included_courses = $included_courses < 0 ? 0 : ($included_courses > 255 ? 255 : $included_courses);
        $active = filter_var($active, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        // formazione query
        $query = "INSERT INTO plans 
                    (
                        short_desc_pla_type,
                        long_desc_plan
                        no_esxpiration,
                        for_tutor,
                        plan_price,
                        discount,
                        included_courses,
                        active
                    )
                  VALUE 
                    (
                        '$short_desc_plan',
                        '$long_desc_plan',
                        $no_expiration,
                        $for_tutor,
                        $plan_price,
                        $discount,
                        $included_courses,
                        $active
                    )
                ";
        $id_plan = $this->db_conn->insert($query);
        return is_numeric($id_plan) AND $id_plan > 0 ? $id_plan : false;
    }
    
    /** Imposta il tipo di piano attivo o meno in accordo con i valori passati
     * 
     * @param integer $id_plan
     * @param boolean $active
     * @return integer/false
     */
    public function setActivePlan($id_plan, $active = true){
        $id_plan = filter_var($id_plan, FILTER_SANITIZE_NUMBER_INT);
        $active = filter_var($active, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $query = "UPDATE plans SET active = $active WHERE id = $id";
        $res = $this->db_conn->update($query);
        return $res > 0 ? $res : false;
    }
}