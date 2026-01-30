<?php

require_once 'check_session.php';
require_once 'class_company.php';
require_once 'class_notification.php';
require_once 'sanitize.php';

$comp_obj = new iWDCompany();
$op_type = $_POST['op_type'];

if ($op_type == 'set_tutor_id_session'){
	$_SESSION['tutor_id'] = sanitize($_POST['tutor_id'], INT);
	$res = 1;
} elseif ($op_type == 'set_company_session'){
	$res = $comp_obj->getCompanyByID(sanitize($_POST['comp_id'], INT));
	if ($res) {
		$_SESSION['company'] = $res;
		$res = 1;
	} else {
		$res = 0;
	}
} elseif($op_type == 'nuovo_company'){
	$assignto = $_POST['assignto'];
	$role = $_POST['role'];
	$role_ref = $_POST['role_ref'];
	$business_name = $_POST['business_name'];
	$vat = $_POST['vat'];
        $address = $_POST['address'];
	$postal_code = $_POST['postal_code'];
	$city = $_POST['city'];
	$province_id = $_POST['province_id'];
	$telephone = $_POST['telephone'];
	$email = $_POST['email'];
	$discount = $_POST['discount'];
	$timezone = $_POST['timezone'];
	$ateco_sector_id = $_POST['ateco_sector_id'];
	$contract_id = $_POST['contract_id'];
        $test_in_the_presence = $_POST['test_in_the_presence'];
        $risk_evaluation = $_POST['risk_evaluation'];
	$res = $comp_obj->createCompany($role,$role_ref,$business_name, $vat, $address, $postal_code, $city, $province_id, $telephone,$email, $discount, $timezone, $assignto, $ateco_sector_id, $contract_id,$test_in_the_presence,$risk_evaluation);
}elseif($op_type == 'edit_company'){
	$comp_id = sanitize($_POST['comp_id'], INT);
	$role_ref = $_POST['role_ref'];
	$sociale = $_POST['sociale'];
	$iva = $_POST['iva'];
	$indirizzo = $_POST['indirizzo'];
	$provincia = $_POST['provincia'];
	$telefono = $_POST['telefono'];
	$email = $_POST['email'];
	$contract_id = $_POST['contract'];
	$discount = $_POST['discount'];
	$timezone = $_POST['timezone'];
	$ateco = $_POST['ateco'];
	$tutor_didactic = $_POST['tutor_didactic'];
        $test_in_the_presence = $_POST['test_in_the_presence'];
	$res = $comp_obj->editCompany($comp_id, $role_ref,$sociale, $iva, $indirizzo, $provincia, $telefono, $email, $discount, $timezone, $ateco, $contract_id,$test_in_the_presence);
	if ($res === 'PIVA') {
		echo $res;
		exit();
	}
	$td = $comp_obj->getDidacticTutor($comp_id);
	if ($td){
		$res += $td['id'] != $tutor_didactic ? $comp_obj->setDidacticTutor($comp_id, $tutor_didactic):0;
	} else {
		$res += $comp_obj->addDidacticTutor($comp_id, $tutor_didactic);
	}
}elseif($op_type == 'get_employee'){
	$comp_id = sanitize($_POST['comp_id'],INT);
	$owner = sanitize($_POST['owner'], INT);
	$role = isset($_POST['role']) ? $_POST['role'] : false;
	if ($role === false) $res1 = $comp_obj->getUsersCompanyByID($comp_id);
	else  $res1 = $comp_obj->getUsersCompanyByID($comp_id, $role);
	$res = "";
	foreach($res1 as $single){
		$res .= "<option value ='".$single['id']."'".($single['id']==$owner?' selected': '').">".ucfirst(strtolower($single['name']))." ".ucfirst(strtolower($single['surname']))."</option>";
	}
}elseif($op_type == 'get_didactic'){
	$comp_id = sanitize($_POST['comp_id'],INT);
	$res = $comp_obj->getDidacticTutor($comp_id);
	$res = $res ? $res['id'] : 0;
}elseif($op_type == 'remove_utente'){
	$id = $_POST['id'];
	$user = new iWDUser();
	$res = $user->disableUser($id);
}elseif($op_type == 'send_license'){
	$license_id = sanitize($_POST['license_id'], INT);
	$usr = new iWDUser();
	$license = $usr->getUserLicenseById($license_id);
	$password = $license['learning_project_pwd'];
	$user_id = $license['user_id'];
	$tutor_id = $license['company_id'];
	$learn_id = $license['learning_project_id'];
	$not = new Tutor81Notification();
	$res = $not->notifyUserAssignation($user_id,$tutor_id,$learn_id,$password);
}elseif($op_type == 'is_tutor'){
	$comp_id = $_POST['comp_id'];
	$res = $comp_obj->setIsTutor($comp_id);
}elseif($op_type == 'is_not_tutor'){
	$comp_id = $_POST['comp_id'];
	$owner_user_id = $_POST['owner_user_id'];
	$res = $comp_obj->setIsNotTutor($comp_id, $owner_user_id);
}elseif($op_type == 'add_didactic_tutor'){
	$comp_id = $_POST['comp_id'];
	$tutor_didactic_id = $_POST['tutor_didactic_id'];
	$res = $comp_obj->addDidacticTutor($comp_id, $tutor_didactic_id);

	
// GET COST CENTRE
} elseif($op_type == 'get_cost_centre'){
	$cost_centre = $comp_obj->getCostCentre($_POST['company_id']);
	$res = $cost_centre ? json_encode($cost_centre) : 0;
}

echo $res;
?>
