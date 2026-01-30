<?php
require_once 'config.api.php';
require_once BASE_LIBRARY_PATH . 'class_db.php';
require_once BASE_LIBRARY_PATH . 'class_user.php';
require_once BASE_LIBRARY_PATH . 'class_purchase.php';
require_once BASE_LIBRARY_PATH . 'class_notification.php';

class Demo {
    
    const COMPANY_ID = 341; // azienda demo
    const ASSIGN_TO = 6;
    const DEMO_PROJECT_ID = 25;
    const TUTOR_ID = 6;
    
    private $db_conn;
    private $user_obj;
    private $purchase_obj;
    private $notification_obj;
    private $demo_data;
    
    public function __construct() {
        $this->db_conn = new MySQLConn();
        $this->user_obj = new T81User();
        $this->purchase_obj = new iWDPurchase();
        $this->notification_obj = new Tutor81Notification();
        $this->demo_data = false;
    }
    
    public function create($surname, $email){
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'INVALID_EMAIL_ADDRESS';
        $surname = filter_var(trim($surname), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        
        $username = "demo.$surname";
        $suffix = 1;
        while ($this->user_obj->usernameExist($username)){
            $username = "demo.$surname".($suffix++);
        }
        $tax_code = $username;
        $suffix = 1;
        while ($this->user_obj->taxCodeExist($tax_code)){
            $tax_code = $username.($suffix++);
        }
        $user_id = $this->user_obj->createUser(self::ASSIGN_TO, 0, '', $surname, $username, '', $email, self::COMPANY_ID, $tax_code, "1");
        if (!$user_id || $user_id == 'UTENTE') return 'ERROR_ADDING_NEW_USER';
        
        $starting_from = new DateTime('now', new DateTimeZone('Europe/Rome'));
        $finish_within = clone $starting_from;
        $finish_within->add(new DateInterval('P3M'));
        $license_id = $this->purchase_obj->createNewLicense($user_id, self::DEMO_PROJECT_ID,
                self::TUTOR_ID, $starting_from->format('Y-m-d'), $finish_within->format('Y-m-d'), 5, self::COMPANY_ID);
        if (!$license_id) return 'ERROR_ADDING_NEW_LICENSE';
        
        $notified = $this->notification_obj->notifyLicense($license_id);
        if (!$notified) return 'LICENSE_NON_NOTIFIED';
        
        $this->user_obj->editUser($user_id, 0, 'demo', $surname, '', $tax_code, $username, 1);
        return 'SUCCESS';
    }
}