<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 18-ago-2015
 * File: manage/company.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_notification.php';

$comp_obj = new T81Company();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

if ($op_type == 'set_tutor_id_session') {
    $_SESSION['tutor']['id'] = filter_input(INPUT_POST, 'tutor_id', FILTER_SANITIZE_NUMBER_INT);
    $res = 1;
} elseif ($op_type == 'set_company_session') {
    $res = $comp_obj->getCompanyByID(filter_input(INPUT_POST, 'comp_id', FILTER_SANITIZE_NUMBER_INT));
    if ($res) {
        $_SESSION['company'] = $res;
        $res = 1;
    } else {
        $res = 0;
    }
} elseif ($op_type == 'nuova_company') {
    $business_name = filter_input(INPUT_POST, 'business_name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $vat = filter_input(INPUT_POST, 'vat', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $postal_code = filter_input(INPUT_POST, 'postal_code', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $province_id = filter_input(INPUT_POST, 'province_id', FILTER_SANITIZE_NUMBER_INT);
    $is_tutor = filter_input(INPUT_POST, 'is_tutor', FILTER_VALIDATE_BOOLEAN);
    $is_partner = filter_input(INPUT_POST, 'is_partner', FILTER_VALIDATE_BOOLEAN);
    $owner_user_id = filter_input(INPUT_POST, 'owner_user_id', FILTER_SANITIZE_NUMBER_INT);
    $discount = filter_input(INPUT_POST, 'discount', FILTER_SANITIZE_NUMBER_INT);
    $ateco_sector_id = filter_input(INPUT_POST, 'ateco_sector_id', FILTER_SANITIZE_NUMBER_INT);
    $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $gmt = filter_input(INPUT_POST, 'gmt', FILTER_SANITIZE_NUMBER_INT);
    $contract_id = filter_input(INPUT_POST, 'contract_id', FILTER_SANITIZE_NUMBER_INT);
    $test_in_the_presence = filter_input(INPUT_POST, 'test_in_the_presence', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $risk_evaluation = filter_input(INPUT_POST, 'risk_evaluation', FILTER_SANITIZE_NUMBER_INT);
    $tutor_id = filter_input(INPUT_POST, 'tutor_id', FILTER_SANITIZE_NUMBER_INT);
    $plan = json_decode($_POST['plan'], true);
    
    $res = $comp_obj->createCompany($business_name, $vat, $address, $postal_code, $city, $province_id, $is_tutor, $is_partner, $owner_user_id, $discount, $ateco_sector_id, $telephone, $email, $gmt, $contract_id, $test_in_the_presence, $risk_evaluation, $_POST['iban'], $_POST['regional_authorization'], $_POST['ateco'], $_POST['trainer']);    
    if ($res > 0){
        if (!$plan['validity_start']) {
            $plan['validity_start'] = new DateTime('now');
            $plan['validity_end'] = clone $plan['validity_start'];
            $plan['validity_end']->add(new DateInterval('P50Y'));
            $plan['validity_start'] = $plan['validity_start']->format('Y-m-d');
            $plan['validity_end'] = $plan['validity_end']->format('Y-m-d');
        }
        $license_id = $comp_obj->assignCompanyPlan($plan['plan_id'], $tutor_id, 
                $res, $plan['validity_start'], $plan['validity_end'],
                $plan['discount'], $plan['ecommerce'], $plan['customized_courses'], 
                $plan['max_admin'], $plan['max_concurrent_users'], $plan['price']) ? : 0;
        if (!$is_tutor) {
            $not = new Tutor81Notification();
            $not->notifyCompanyCreation($res);
        }
    }
    
} elseif ($op_type == 'edit_company') {
    $comp_id = filter_input(INPUT_POST, 'comp_id', FILTER_SANITIZE_NUMBER_INT);
    $role_ref = filter_input(INPUT_POST, 'role_ref', FILTER_SANITIZE_NUMBER_INT);
    $business_name = filter_input(INPUT_POST, 'business_name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $vat = filter_input(INPUT_POST, 'vat', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $iban = filter_input(INPUT_POST, 'iban', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $url_ecommerce = filter_input(INPUT_POST, 'url_ecommerce', FILTER_SANITIZE_URL);
    $province_id = filter_input(INPUT_POST, 'province_id', FILTER_SANITIZE_NUMBER_INT);
    $telephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $contract_id = filter_input(INPUT_POST, 'contract_id', FILTER_SANITIZE_NUMBER_INT);
    $discount = filter_input(INPUT_POST, 'discount', FILTER_SANITIZE_NUMBER_INT);
    $gmt = filter_input(INPUT_POST, 'gmt', FILTER_SANITIZE_NUMBER_INT);
    $ateco_sector_id = filter_input(INPUT_POST, 'ateco_sector_id', FILTER_SANITIZE_NUMBER_INT);
    $tutor_didactic = filter_input(INPUT_POST, 'tutor_didactic', FILTER_SANITIZE_NUMBER_INT);
    $test_in_the_presence = filter_input(INPUT_POST, 'contract_id', FILTER_SANITIZE_NUMBER_INT);
    $risk_evaluation = filter_input(INPUT_POST, 'risk_evaluation', FILTER_SANITIZE_NUMBER_INT);
    $ateco = filter_input(INPUT_POST, 'ateco', FILTER_SANITIZE_STRING);
    $site_url = filter_input(INPUT_POST, 'site_url', FILTER_SANITIZE_URL);
    $res = $comp_obj->editCompany($comp_id, $role_ref, $business_name, $vat, 
            $city, $address, $province_id, $telephone, $email, 
            $_POST['regional_authorization'], $discount, $gmt, $ateco_sector_id, 
            $contract_id, $test_in_the_presence, $iban, $url_ecommerce, $ateco, $site_url, $_POST['trainer']);
    if ($res === 'PIVA') {
        echo $res;
        exit();
    }
    $td = $comp_obj->getDidacticTutor($comp_id);
    if ($td) {
        $res += $td['id'] != $tutor_didactic ? $comp_obj->setDidacticTutor($comp_id, $tutor_didactic) : 0;
    } else {
        $res += $comp_obj->addDidacticTutor($comp_id, $tutor_didactic);
    }
} elseif ($op_type === "delete_company") {
    $comp_obj->deleteCompanyPlan($_POST['company_id']);
    $res = $comp_obj->deleteCompany($_POST['company_id']) ? 1 : 0;
    
    
} elseif ($op_type == 'get_employee') {
    $comp_id = filter_input(INPUT_POST, 'comp_id', FILTER_SANITIZE_NUMBER_INT);
    $owner = filter_input(INPUT_POST, 'owner', FILTER_SANITIZE_NUMBER_INT);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_NUMBER_INT) ?  : false;
    if ($role === false)
        $res1 = $comp_obj->getUsersCompanyByID($comp_id);
    else
        $res1 = $comp_obj->getUsersCompanyByID($comp_id, $role);
    $res = "";
    foreach ($res1 as $single) {
        $res .= "<option value ='" . $single['id'] . "'" . ($single['id'] == $owner ? ' selected' : '') . ">" . ucfirst(strtolower($single['name'])) . " " . ucfirst(strtolower($single['surname'])) . "</option>";
    }
} elseif ($op_type == 'get_didactic') {
    $comp_id = filter_input(INPUT_POST, 'comp_id', FILTER_SANITIZE_NUMBER_INT);
    $res = $comp_obj->getDidacticTutor($comp_id);
    $res = $res ? $res['id'] : 0;
} elseif ($op_type == 'remove_utente') {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $user = new T81User();
    $res = $user->disableUser($id);
} elseif ($op_type == 'send_license') {
    $license_id = filter_input(INPUT_POST, 'license_id', FILTER_SANITIZE_NUMBER_INT);
    $usr = new T81User();
    $license = $usr->getUserLicenseById($license_id);
    $password = $license['learning_project_pwd'];
    $user_id = $license['user_id'];
    $tutor_id = $license['company_id'];
    $learn_id = $license['learning_project_id'];
    $not = new Tutor81Notification();
    $res = $not->notifyUserAssignation($user_id, $tutor_id, $learn_id, $password);
} elseif ($op_type == 'set_owner') {
    $company_id = filter_input(INPUT_POST, 'company_id', FILTER_SANITIZE_NUMBER_INT);
    $owner_user_id = filter_input(INPUT_POST, 'owner_user_id', FILTER_SANITIZE_NUMBER_INT);
    $res = $comp_obj->setCompanyOwner($company_id, $owner_user_id);
} elseif ($op_type == 'is_tutor') {
    $comp_id = filter_input(INPUT_POST, 'comp_id', FILTER_SANITIZE_NUMBER_INT);
    $res = $comp_obj->setIsTutor($comp_id);
} elseif ($op_type == 'is_not_tutor') {
    $comp_id = filter_input(INPUT_POST, 'comp_id', FILTER_SANITIZE_NUMBER_INT);
    $owner_user_id = filter_input(INPUT_POST, 'owner_user_id', FILTER_SANITIZE_NUMBER_INT);
    $res = $comp_obj->setIsNotTutor($comp_id, $owner_user_id);
} elseif ($op_type == 'add_didactic_tutor') {
    $comp_id = filter_input(INPUT_POST, 'comp_id', FILTER_SANITIZE_NUMBER_INT);
    $tutor_didactic_id = filter_input(INPUT_POST, 'tutor_didactic_id', FILTER_SANITIZE_NUMBER_INT);
    $res = $comp_obj->addDidacticTutor($comp_id, $tutor_didactic_id);


// GET COST CENTRE
} elseif ($op_type == 'get_cost_centre') {
    $cost_centre = $comp_obj->getCostCentre(filter_input(INPUT_POST, 'company_id', FILTER_SANITIZE_NUMBER_INT));
    $res = $cost_centre ? json_encode($cost_centre) : 0;

    
// GET CURRENT PLAN
}elseif ($op_type == 'get_current_plan') {
    $plan_detail = $comp_obj->getCompanyPlan(filter_input(INPUT_POST, 'company_id', FILTER_SANITIZE_NUMBER_INT));
    $res = $plan_detail ? json_encode($plan_detail) : 0;

    
// ASSIGN COMPANY PLAN
}elseif ($op_type == 'assign_company_plan') {
    $validity_start = filter_input(INPUT_POST, 'validity_start', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $validity_start = new DateTime($validity_start);
    $validity_end = clone $validity_start;
    $validity_end->add(new DateInterval('P1Y'));
    $res = $comp_obj->assignCompanyPlan($_POST['plan_id'], $_POST['tutor_id'], $_POST['company_id'], $validity_start->format('Y-m-d'), $validity_end->format('Y-m-d')) ? : 0;


    
// EDIT COMPANY PLAN
}elseif ($op_type == 'edit_company_plan') {
    $res = $comp_obj->editCompanyPlan($_POST['id'], $_POST['plan_id'], $_POST['tutor_id'], $_POST['validity_start'], $_POST['validity_end']) ? : 0;


    
// SUSPEND COMPANY PLAN
}elseif ($op_type == 'suspended_company_plan') {
    $res = $comp_obj->suspendCompanyPlan($_POST['id'], $_POST['suspended']) ? : 0;

    
// GET USERS FREE FOR COURSES
} elseif ($op_type == 'get_users_free') {
    $users = $comp_obj->getUsersFree($_POST['company_id'], $_POST['learning_project_id']);
    $res = $users ? json_encode($users) : 0;

    
// GET USERS ALERADY FORMED
} elseif ($op_type == 'get_users_already_formed') {
    $users = $comp_obj->getUsersAlreadyFormed($_POST['company_id'], $_POST['learning_project_id']);
    $res = $users ? json_encode($users) : 0;
    
    
// GET COMPANY BY VAT CODE
} elseif ($op_type == 'get_company_by_vat_code'){
    $company = $comp_obj->getCompanyByVatCode($_POST['vat_code']);
    $res = $company == FALSE ? 0 : json_encode($company);
    
} elseif ($op_type == 'set_send_certificate'){
    $comp_id = filter_input(INPUT_POST, 'comp_id', FILTER_SANITIZE_NUMBER_INT);
    $send_certificate = filter_input(INPUT_POST, 'send_certificate', FILTER_VALIDATE_BOOLEAN) ? 1: 0;
    $res = $comp_obj->setSendCertificate($comp_id, $send_certificate) ? 1 : 0;
}

echo $res;