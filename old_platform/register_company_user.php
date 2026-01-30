<?php
/**
 * Created by PhpStorm.
 * User: Davide
 * Date: 26/12/2016
 * Time: 12.12
 * Call directly by a POST subscription
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_user.php';
require_once BASE_LIBRARY_PATH . 'class_notification.php';

try {
    $company_obj = new T81Company();
    $usr_obj = new T81User("");
}
catch (Exception $e) {
    return $e->getMessage();
}

// Point to right tutor in base of domain tutor81 alias add in any ecommerce page
$tutor = $company_obj->getCompanyByServerAlias($_SERVER['SERVER_NAME']);
if (!isset($tutor)) {
    $tutor = $company_obj->getCompanyByServerAlias("pre-amm.tutor81.com"); //hub.tutor81.local
}
$tutor["logo"] = file_exists("media/img/company/".$tutor["id"].".png") ?
    "media/img/company/".$tutor["id"].".png" :
    strtoupper($tutor['business_name']);

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$business_name = filter_input(INPUT_POST, 'business_name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$vat = filter_input(INPUT_POST, 'vat', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$telephone = filter_input(INPUT_POST, 'telephone_company', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$iban = filter_input(INPUT_POST, 'iban', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$licence = filter_input(INPUT_POST, 'licence', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$discount = 0;
switch ($licence) {
    case 7:
        $discount = 30;
        break;
    case 8:
        $discount = 50;
        break;
    case 9:
        $discount = 70;
        break;
}

$admin_name = filter_input(INPUT_POST, 'admin_name', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$admin_surname = filter_input(INPUT_POST, 'admin_surname', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$admin_tax_code = filter_input(INPUT_POST, 'admin_tax_code', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$admin_email = filter_input(INPUT_POST, 'admin_email', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$admin_telephone = filter_input(INPUT_POST, 'admin_telephone', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$is_tutor = filter_input(INPUT_POST, 'is_tutor', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$role = "";
$tutor_admin_id = 0;


if ($is_tutor == "0") {
    $tutor_admin_id = $tutor["owner_user_id"];
    $role =  "2";
}
else if ($is_tutor == "1") {
    //TODO: parameter for create personalized company without a tutor
    $tutor_admin_id = SUPERUSER_TUTOR81_USERID;
    $role =  "1";
    $is_tutor = true;
}
else {
    $role =  "";
}



if ($op_type == 'nuova_company') {

    /**
     * CREAZIONE NUOVO AMMINISTRATORE DI COMPANY PRIMA DI ASSEGNARLO ALLA NUOVA COMPANY
     */
    require_once BASE_LIBRARY_PATH . 'function.php';
    $password = strtoupper($admin_tax_code);
    $username = trim($admin_name).'.'.trim($admin_surname);

    try {

        $user_id = $usr_obj->createUser($tutor_admin_id, $role, $admin_name, $admin_surname,
            $username, $password, $admin_email, $tutor_admin_id, $admin_tax_code, "1");
        if(!is_numeric($user_id)) {
            echo $user_id;
        }
    }
    catch (Exception $e) {
        return $e->getMessage();
    }

    if ($user_id > 0) {
        $address = "";
        $postal_code = "";
        $city = "";
        $province_id = 0;
        $is_partner = false;
        $owner_user_id = $tutor_admin_id;
        $ateco_sector_id = 1;


        $gmt = 23;
        $contract_id = 0;
        $test_in_the_presence = "NO";
        $risk_evaluation = 0;
        $company_id = $company_obj->createCompany($business_name, $vat, $address, $postal_code, $city, $province_id, $is_tutor, $is_partner, $owner_user_id, $discount, $ateco_sector_id, $telephone, $email, $gmt, $contract_id, $test_in_the_presence, $risk_evaluation, $iban);
        if ($company_id > 0) {
            $res_company = $usr_obj->setUserCompany($user_id, $company_id);
        }


        if ($company_id > 0){
            $plan_id = 6;
            $validity_start = new DateTime('now');
            $validity_end = clone $validity_start;
            $validity_end->add(new DateInterval('P1Y'));
            $license_id = $company_obj->assignCompanyPlan($plan_id, 0, $company_id, $validity_start->format('Y-m-d'), $validity_end->format('Y-m-d'), 0, 0, 0, 0, 0, 0) ? : 0;
            if (!$is_tutor) {
                $not = new Tutor81Notification();
                $not->notifyCompanyCreation($company_id, $user_id);
            }
        }
        echo $company_id;

    }
}


