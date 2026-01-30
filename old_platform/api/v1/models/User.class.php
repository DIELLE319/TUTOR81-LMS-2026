<?php
require_once 'config.api.php';
require_once BASE_LIBRARY_PATH . 'class_db.php';
require_once BASE_LIBRARY_PATH . 'class_user.php';

class User
{
    private $db_conn;
    private $user_obj;
    private $user_data;
    
    public function __construct() {
        $this->db_conn = new MySQLConn();
        $this->user_obj = new T81User();
        $this->user_data = false;
    }
    
    public function get($method, $value){
        $success = false;
        switch ($method){
            case 'token':
                if (is_array($value) && $value['username'] != '' && $value['password'] != ''){
                    $this->user_data = $this->user_obj->loginWithUsernameAndPassword($value['username'], $value['password']);
                    $success = true;
                }
                break;
        }
        return $success;
    }
    
    public function __get($property) {
        if ($property === 'user_data') return $this->$property;
        else return false;
    }
}