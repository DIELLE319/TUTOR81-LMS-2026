<?php
require_once 'check_session.php';
require_once 'class_user.php';
require_once 'class_company.php';
$user_obj = new T81User();
$company_obj = new T81Company();

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if(!$isAjax) {
  $user_error = 'Access denied - not an AJAX request...';
  trigger_error($user_error, E_USER_ERROR);
}

// get what user typed in autocomplete input
$term = filter_input(INPUT_GET, 'term');
$company_id = filter_input(INPUT_GET, 'companyid', FILTER_SANITIZE_NUMBER_INT);
$learningprojectid = filter_input(INPUT_GET, 'learningprojectid', FILTER_SANITIZE_NUMBER_INT);
$isFieldSurname = filter_input(INPUT_GET, 'option', FILTER_SANITIZE_STRING) === "surname" ? true : false;
$FieldsTable = filter_input(INPUT_GET, 'option',FILTER_SANITIZE_STRING);
$isTutor = filter_input(INPUT_GET, 'istutor', FILTER_SANITIZE_NUMBER_INT) == 1 ? true : false;
$unita_id = filter_input(INPUT_GET, 'unita', FILTER_SANITIZE_NUMBER_INT);
$reparto_id = filter_input(INPUT_GET, 'reparto', FILTER_SANITIZE_NUMBER_INT);
$isSuperadmin = $_SESSION['user']['role'] == 1000;

//$company_id = intval(trim($_GET['company']));

$a_json = array();
$a_json_row = array();

$a_json_invalid = array(array("id" => "#", "value" => $term, "label" => "Only letters and digits are permitted..."));
$json_invalid = json_encode($a_json_invalid);

// replace multiple spaces with one
$term = preg_replace('/\s+/', ' ', $term);

// SECURITY HOLE ***************************************************************
// allow space, any unicode letter and digit, underscore and dash
if(preg_match("/[^\040\pL\pN_-]/u", $term)) {
  print $json_invalid;
  exit;
}
// *****************************************************************************

if($company_id > 0) {

    if ($FieldsTable === "employees"){
        $a_json = $user_obj->searchEmployeesInCompany("", $company_id, $unita_id, $reparto_id, $learningprojectid);
    } elseif ($FieldsTable === "employeesfree") {
        $a_json = $user_obj->searchEmployeesFreeInCompany($company_id, $unita_id, $reparto_id, $learningprojectid);
    } elseif ($FieldsTable === "attestati") {
        $a_json = $user_obj->searchAttestatiInCompany($company_id, $unita_id, $reparto_id, $learningprojectid);
    } else {
        if(!$isFieldSurname){
            if(!$isTutor){
                $a_json = $user_obj->searchUserInCompany($term, $company_id);
            }
            else{
                // Search from every user of tutor company users
                $a_json = $user_obj->searchUserInAllTutorCompanies($term, $company_id, $isSuperadmin);
            }
        }
        else{
            // Search by field surname con company specified
            $a_json = $user_obj->searchUserInCompanyBySurname($term, $company_id, $learningprojectid);
        }
    }
}

$json = json_encode($a_json);
print $json;