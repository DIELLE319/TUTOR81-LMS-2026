<?php
require_once 'class_purchase.php';
require_once 'sanitize.php';

$purchase_obj = new iWDPurchase();

$op_type = $_POST['op_type'];


/**
 * NUOVA ASSEGNAZIONE
 */
if ($op_type === 'assign_new') {

    // se la data di inizio del corso non è impostata definisco data di inizio, di fine e giorni di alert
    if (!isset($_POST['start'])){
        require_once 'class_learning_project.php';
        $learning_project_obj = new iWDLearningProject();
        $course = $learning_project_obj->getCourseDetailFromLearningProject($_POST['learn_prj']);
        $start_date = date("Y-m-d");
        $end_date = date("Y-m-d", strtotime($start_date.$course['max_execution_time'].'days'));
        $alert = 15;
    } else {
        $start_date = $_POST['start'];
        $end_date = $_POST['end'];
        $alert = $_POST['alert'];
    }
    
    $res = $purchase_obj->createNewLicense($_POST['user_id'], $_POST['learn_prj'], 
            $_POST['tutor_id'], $start_date, $end_date, $alert,$_POST['id_company'],$_POST['accreditation_code']);
    
    // invia notifica assegnazione
    require_once 'class_notification.php';
    $not_obj = new Tutor81Notification();
    $not_obj->notifyCourseAssignment($res);
    
/**
* NUOVO ACQUISTO
*/
} elseif ($op_type === 'new_purchase') {

    $arr_ext_po_number = isset($_POST['arr_ext_po_number']) ? $_POST['arr_ext_po_number'] : array("");
    $arr_cost_centre_id = isset($_POST['arr_cost_centre_id']) ? $_POST['arr_cost_centre_id'] : array(0);
    $res = $purchase_obj->purchaseCourse($_POST['comp_id'], $_POST['arr_id'], $_POST['arr_qta'], $_POST['arr_ref'], $_POST['arr_tutor'], $arr_ext_po_number, $arr_cost_centre_id);

    
    if ($res) {
        require_once 'class_notification.php';
        $not_obj = new Tutor81Notification();
        foreach ($res as $tutor_purchase_id) {
            $not_obj->notifyPurchase($tutor_purchase_id);
        }
    }
    

    /**
 * AGGIUNGI ACQUISTO
 */
/* 
 * da rimuovere
 * 
} elseif ($op_type === 'add_license'){
	
	$res = $purchase_obj->updatePurchaseCourse($_POST['purchase_id'],$_POST['license_qta']);
*/
	
	
/**
 * RIMUOVI ACQUISTO
 */
} elseif ($op_type === 'remove_license'){
	
	$res = $purchase_obj->removePurchaseCourse($_POST['purchase_id'],$_POST['license_qta']);
	
	
	/**
	* INVIA NOTIFCA ASSEGNAZIONE CORSO
	*/
} elseif ($op_type === 'notify_course_assignment'){
	
		require_once 'class_notification.php';
		$not_obj = new Tutor81Notification();
		if ($not_obj->notifyCourseAssignment($_POST['license_id']))	$res = 1;
		else $res = 0;
	
	
/**
 * INVIA ALERT
 */
} elseif ($op_type === 'send_alert'){
	
	require_once 'class_notification.php';
	$not_obj = new Tutor81Notification();
	if ($not_obj->notifyAlert($_POST['license_id'],$_POST['custom_message']) == true)	$res = 1;
	else $res = 0;
	
	
/**
 * INVIO AUTOMATICO ALERT
 */
} elseif ($op_type === 'auto_alert'){
	$licenses_expiring = $purchase_obj->getLicenseExpiring();
	
	if ($licenses_expiring) {
		require_once 'class_notification.php';
		$not_obj = new Tutor81Notification();
		$res = 0;
		foreach ($licenses_expiring as $license) {
			$res += (int) $not_obj->notifyAlert($license['id']);
		}
	}

/**
 * ISCRIZIONE MULTIPLA
 */
} elseif ($op_type === 'subscribe'){
    
    if ($_POST['send_assignation'] === "true" || $_POST['send_license'] === "true" || $_POST['to_buy'] !== "false"){
        require_once 'class_notification.php';
        $not_obj = new Tutor81Notification();
    }
    if ($_POST['to_buy'] !== "false"){
        $arr_id = array();
        $arr_qta = array();
        $arr_ref = array();
        $arr_tutor = array();
        $arr_ext_po_number = array();
        $arr_cost_centre_id = array();
        foreach ($_POST['to_buy'] as $course){
            $courses_to_buy = json_decode($course);
            array_push($arr_id, $courses_to_buy->learning_project_id);
            array_push($arr_qta, $courses_to_buy->qta);
            array_push($arr_ref, $_POST['tutor_id']);
            array_push($arr_tutor, $_POST['user_id']);
            array_push($arr_ext_po_number, "");
            array_push($arr_cost_centre_id, $courses_to_buy->cost_centre);
        }
        $purchased = $purchase_obj->purchaseCourse($_POST['company_id'], $arr_id, 
                $arr_qta, $arr_ref, $arr_tutor, $arr_ext_po_number, $arr_cost_centre_id);
        if (!empty($purchased)){
            foreach ($purchased as $tutor_purchase_id){
                $not_obj->notifyPurchase($tutor_purchase_id);
            }
        }
    }
    
    $res = 0;
    foreach ($_POST['users'] as $user){
        $user_to_subscribe = json_decode($user);
        foreach ($_POST['courses'] as $course){
            $course_to_subscribe = json_decode($course);
            $license_id = $purchase_obj->createNewLicense($user_to_subscribe->user_id,
                    $course_to_subscribe->learning_project_id,$_POST['tutor_id'],
                    $user_to_subscribe->start, $user_to_subscribe->end, $user_to_subscribe->alert,
                    $_POST['company_id'],$course_to_subscribe->accreditation_code);
            if ($_POST['send_assignation'] === "true") $not_obj->notifyCourseAssignment($license_id);
            if ($_POST['send_license'] === "true") $not_obj->notifyLicense($license_id);
            $res += $license_id;
        }
    }
    
    
/**
 * MODIFICA SCADENZA CORSO
 */   
} elseif ($op_type === 'schedule_license') {
    $res = $purchase_obj->scheduleLicense($_POST['license_id'], $_POST['starting_from'], $_POST['finish_within'], $_POST['days_to_alert']);
    if ($res && $_POST['send_mail'] === "true"){
        require_once 'class_notification.php';
        $not_obj = new Tutor81Notification();
        $not_obj->notifyLicenseExpirationDate($_POST['license_id']);
    }

}

echo $res;