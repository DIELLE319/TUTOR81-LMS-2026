<?php
require_once 'class_user.php';
require_once 'sanitize.php';
/** Error reporting  */
error_reporting(E_ALL);
ini_set('display_errors', FALSE);
ini_set('display_startup_errors', FALSE);

$user_obj = new iWDUser();
$op_type = $_POST['op_type'];


/**
 * CREAZIONE NUOVO UTENTE
 */
if ($op_type == 'nuovo_utente') {
    require_once 'function.php';
    $password = strtoupper(trim($_POST['tax_code'])); //generatePassword(); // password scelta dalla piattaforma
    $username = isset($_POST['username']) && $_POST['username'] != '' ? $_POST['username'] : trim($_POST['name']).'.'.trim($_POST['surname']);

    $res = $user_obj->createUser($_POST['assignto'], $_POST['role'], $_POST['name'], $_POST['surname'], 
            $username, $password, $_POST['email'], $_POST['company_id'], $_POST['tax_code'], $_POST['func_id']);

    if ($res > 0) {
        require_once 'class_departments.php';
        $dep_obj = new Departments();
        
        if (isset($_POST['dep_id']) && $_POST['dep_id'] > 0) {
            $dep_id = $_POST['dep_id'];
        } elseif (isset($_POST['department'])) {
            // check if department type, product_unit and department exist
            $product_unit = $dep_obj->getProductUnitByShortDescription($_POST['product_unit'], $_POST['company_id']);
            if ($product_unit) $pu_id = $product_unit['id_pu'];
            else $pu_id = $dep_obj->addProductUnit($_POST['product_unit'], "", $_POST['company_id']);
            $department_types = $dep_obj->getDepartmentsTypeByShortDescription($_POST['department'], $_POST['company_id']);
            if ($department_types) $dep_type_id = $department_types['id_dep_type'];
            else $dep_type_id = $dep_obj->addDepartmentType ($_POST['department'], "", $_POST['company_id']);
            $dep_id = $dep_obj->getIdDepartmentByDepartmentTypeAndProductUnit($dep_type_id, $pu_id) ? : $dep_obj->addDepartmentInProductUnit($dep_type_id, $pu_id);
        }        

        if (isset($dep_id) && $dep_id > 0) {
            $dep_obj->addEmployeeInDepartment($res, $dep_id, $_POST['hire_date']);
        }

        if (isset($_POST['assignments'])) {
            require_once 'class_safety.php';
            $safe_obj = new Safety();
            foreach ($_POST['assignments'] as $assignment) {
                if ($assignment['start_date'] != '') {
                    $id_user_assign = $safe_obj->addUserAssignment($res, $assignment['assign_id'], $assignment['start_date']);
                    if ($id_user_assign > 0 && $assignment['end_date'] != '') {
                        $safe_obj->editUserAssignment($id_user_assign, $assignment['start_date'], $assignment['end_date']);
                    }
                }
            }
        }

        if ($_POST['send_mail'] === "true") {
            require_once 'class_notification.php';
            $not_obj = new Tutor81Notification();
            $not_obj->notifyUserRegistration($res);
            //$not_obj->notifyUserPassword($res, $password);
        }
    }

 /**
 * CREAZIONE UTENTI MULTIPLI
 */
} else if ($op_type == 'add_multiple_users') {
    
    $users = array();
    $users_not_saved = array();
    foreach ($_POST['users_data'] as $user_data){
        $user = json_decode($user_data);
        $password = strtoupper(trim($user->tax_code));
        $username = trim($user->name).'.'.trim($user->surname);
        $user_id = $user_obj->createUser($user->assignto, $user->role, $user->name, $user->surname, 
            $username, $password, $user->email, $_POST['company_id'], $user->tax_code, $user->func_id);
        
        
        if ($user_id == "UTENTE" || !$user_id) array_push ($users_not_saved, trim($user->surname).' '.trim($user->name));
        else {
            array_push ($users, $user_id);
            require_once 'class_departments.php';
            $dep_obj = new Departments();
            
            if (isset($user->department) && $user->department != "") {
                // check if department type, product_unit and department exist
                $product_unit = $dep_obj->getProductUnitByShortDescription($user->product_unit, $_POST['company_id']);
                if ($product_unit) $pu_id = $product_unit['id_pu'];
                else $pu_id = $dep_obj->addProductUnit($user->product_unit, "", $_POST['company_id']);
                $department_types = $dep_obj->getDepartmentsTypeByShortDescription($user->department, $_POST['company_id']);
                if ($department_types) $dep_type_id = $department_types['id_dep_type'];
                else $dep_type_id = $dep_obj->addDepartmentType ($user->department, "", $_POST['company_id']);
                $dep_id = $dep_obj->getIdDepartmentByDepartmentTypeAndProductUnit($dep_type_id, $pu_id) ? : $dep_obj->addDepartmentInProductUnit($dep_type_id, $pu_id);
                if (isset($dep_id) && $dep_id > 0) $dep_obj->addEmployeeInDepartment($user_id, $dep_id, $user->hire_date);
            }
            
            if ($_POST['send_mail'] === "true") {
                require_once 'class_notification.php';
                $not_obj = new Tutor81Notification();
                $not_obj->notifyUserRegistration($user_id);
                //$not_obj->notifyUserPassword($user_id, $password);
            }
        }
    }
    if (count($users_not_saved) == count($_POST['users_data'])){
        $res = 0;
    } else {
        
        $res = json_encode(array('users' => $users, 'users_not_saved' => implode(', ',$users_not_saved)));
    }
    /*
    if (count($users_not_saved) == 0) {
        $res = 1;
    } else {
        $res = implode(', ',$users_not_saved);
    } 
    */

    /**
     * MOFIFICA UTENTE
     */
} elseif ($op_type == 'edit_utente') {
    $res = $user_obj->editUser($_POST['user_id'], $_POST['role'], $_POST['name'], 
            $_POST['surname'], $_POST['email'], $_POST['tax_code'], $_POST['username'], $_POST['func_id']);
} elseif ($op_type == 'change_role') {
    $id = $_POST['id'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    $res = $user_obj->setRole($id, $role, $password);
} elseif ($op_type == 'remove_utente') {
    $id = $_POST['id'];
    $res = $user_obj->disableUser($id);
} elseif ($op_type == 'enable_user') {
    $id = $_POST['id'];
    $res = $user_obj->enableUser($id);
} elseif ($op_type == 'send_license') {
    $license_id = sanitize($_POST['license_id'], INT);
    $license = $user_obj->getUserLicenseById($license_id);
    $password = $license['learning_project_pwd'];
    $user_id = $license['user_id'];
    $tutor_id = $license['company_id'];
    $learn_id = $license['learning_project_id'];
    require_once 'class_notification.php';
    $not = new Tutor81Notification();
    $res = $not->notifyUserAssignation($user_id, $tutor_id, $learn_id, $password);
} elseif ($op_type == 'remove_license') {
    $purchase_id = sanitize($_POST['purchase_id'], INT);
    $res = $user_obj->removeLicense($purchase_id);
} elseif ($op_type == 'check_tax_code') {
    $tax_code = $_POST['tax_code'];
    $user_id = $_POST['user_id'];
    $res = $user_obj->taxCodeExist($tax_code, $user_id);


    /**
     * RESET PASSWORD
     */
} elseif ($op_type == 'reset_user_password') {
    require_once 'function.php';
    $user = $user_obj->getDetail($_POST['user_id']);
    $password = strtoupper($user['tax_code']);//generatePassword(10, 15);
    $res = $user_obj->setUserPassword($_POST['user_id'], $password);
    if ($res > 0) {
        require_once 'class_notification.php';
        $not_obj = new Tutor81Notification();
        //$not_obj->notifyUserPassword($_POST['user_id'], $password); // vecchia password scelta dalla piattaforma
        $not_obj->notifyPasswordReset($_POST['user_id']);
    }



    /**
     * SAVE USER PASSWORD
     */
} elseif ($op_type == 'set_user_password') {
    $res = $user_obj->setUserPassword($_POST['user_id'], $_POST['password']);
    

    /**
     * SEND REGISTRATION USERNAME
     */
} elseif ($op_type == 'send_registration') {
    require_once 'class_notification.php';
    $not_obj = new Tutor81Notification();
    $res = $not_obj->notifyUserRegistration($_POST['user_id']);


    /**
     * RESEND USERNAME
     */
} elseif ($op_type == 'send_user_name') {
    require_once 'class_notification.php';
    $not_obj = new Tutor81Notification();
    $res = $not_obj->notifyUserName($_POST['user_id']);


    /**
     * CREATE PASSWORD RECOVER CODE
     */
} elseif ($op_type == 'recover_password') {
    $email = $_POST['email'];
    $tax_code = $_POST['tax_code'];
    if (isset($email) && isset($tax_code)) {
        $user = $user_obj->getUserByEmailAndTaxCode($email, $tax_code);
        if ($user) {
            $code = $user_obj->createRecoverPasswordCode($user['id']);
            require_once 'class_notification.php';
            $not_obj = new Tutor81Notification();
            $res = $not_obj->notifyPasswordRecoverCode($user['id'], $code) ? 1 : 0;
        } else {
            $res = 0;
        }
    } else {
        $res = 0;
    }
}


echo $res;