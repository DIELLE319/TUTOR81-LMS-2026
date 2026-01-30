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
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_notification.php';

$comp_obj = new T81Company();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

if ($op_type == 'nuova_company') {
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
    $res = $comp_obj->createCompany($business_name, $vat, $address, $postal_code, $city, $province_id, $is_tutor, $is_partner, $owner_user_id, $discount, $ateco_sector_id, $telephone, $email, $gmt, $contract_id, $test_in_the_presence, $risk_evaluation, $_POST['iban'], $_POST['regional_authorization'], $_POST['ateco']);    
    if ($res > 0){
        $plan_id = 6;
        $validity_start = new DateTime('now');
        $validity_end = clone $validity_start;
        $validity_end->add(new DateInterval('P50Y'));
        $license_id = $comp_obj->assignCompanyPlan($plan_id, $_POST['tutor_id'], $res, $validity_start->format('Y-m-d'), $validity_end->format('Y-m-d'), 0, 0, 0, 0, 0, 0) ? : 0;
        if (!$is_tutor) {
            $not = new Tutor81Notification();
            $not->notifyCompanyCreation($res);
        }
    }
    
} elseif ($op_type == 'get_company_by_vat_code'){
    $company = $comp_obj->getCompanyByVatCode($_POST['vat_code']);
    $res = $company == FALSE ? 0 : json_encode($company);
}

echo $res;