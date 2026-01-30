<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 18-ago-2015
 * File: manage/license.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_purchase.php';
require_once BASE_LIBRARY_PATH . 'sanitize.php';

$purchase_obj = new iWDPurchase();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


/**
 * NUOVA ASSEGNAZIONE
 */
if ($op_type === 'assign_new') {

    // se la data di inizio del corso non è impostata definisco data di inizio, di fine e giorni di alert
    if (!isset($_POST['start'])) {
        require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
        $learning_project_obj = new T81LearningProject();
        $course = $learning_project_obj->getCourseDetailFromLearningProject($_POST['learn_prj']);
        $start_date = date("Y-m-d");
        $end_date = date("Y-m-d", strtotime($start_date . $course['max_execution_time'] . 'days'));
        $alert = 15;
    } else {
        $start_date = $_POST['start'];
        $end_date = $_POST['end'];
        $alert = $_POST['alert'];
    }

    $res = $purchase_obj->createNewLicense($_POST['user_id'], $_POST['learn_prj'], $_POST['tutor_id'], $start_date, $end_date, $alert, $_POST['id_company'], $_POST['accreditation_code']);

    // invia notifica assegnazione
    require_once BASE_LIBRARY_PATH . 'class_notification.php';
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
        require_once BASE_LIBRARY_PATH . 'class_notification.php';
        $not_obj = new Tutor81Notification();
        foreach ($res as $tutor_purchase_id) {
            $not_obj->notifyPurchase($tutor_purchase_id);
        }
        $res = 1;
    }


    /**
     * CALCOLA CORSI ELEARNING ACQUISTATI MA NON ASSEGNATI
     */
} elseif ($op_type === 'get_elearning_purchase_unassigned'){
    $res = $purchase_obj->countElearningProjectUnassigned($_POST['company_id'], $_POST['learning_project_id']);
     


    /**
     * RIMUOVI ACQUISTO
     */
} elseif ($op_type === 'remove_purchase') {

    $res = $purchase_obj->removePurchaseCourse($_POST['purchase_id'], $_POST['license_qta']);


    /**
     * RIMUOVI LICENZA CORSO SINGOLO E ACQUISTO
     */
} elseif ($op_type === 'remove_licence_purchase') {
    $licence_id = filter_input(INPUT_POST, "licence_id");
    $licence_detail = $purchase_obj->getLicenceDetail($licence_id);
    
    $purchase_detail = $purchase_obj->getPurchase($licence_detail['tutor_purchase_id']);
    $res = $purchase_obj->removeLicense($licence_id);
    // remove learnig events
    if (!empty($licence_detail['learning_event_id'])) {
        require_once BASE_LIBRARY_PATH . 'class_learning_event.php';
        $learning_event_obj = new Tutor81LearningEvt();
        $purge_res = $learning_event_obj->purgeHistoryLearningEvents($licence_detail['learning_event_id']);
    }
//    remove purchasez
    if ($res > 0) {
        if ($purchase_detail && $purchase_detail['invoiced'] == 0 && (empty($licence_detail['learning_event_id']) || $licence_detail['progress_rate'] == 0)) {
            $licence_qta = $purchase_detail['qta'] > 1 ? 1 : 'acquisto';
            $purchase_obj->removePurchaseCourse($purchase_detail['id'], $licence_qta);
        }
//        } else {
//            $res = "SUCCESS INVOICED";
//        }
        $res = "SUCCESS";
    }
    
    
    /**
     * INVIA NOTIFCA ASSEGNAZIONE CORSO
     */
} elseif ($op_type === 'notify_course_assignment') {

    require_once BASE_LIBRARY_PATH . 'class_notification.php';
    $not_obj = new Tutor81Notification();
    $sending = $not_obj->notifyCourseAssignment($_POST['license_id']);
    $res = $sending['result'] ? 1 : 0;


    /**
     * INVIA ALERT
     */
} elseif ($op_type === 'send_alert') {

    require_once BASE_LIBRARY_PATH . 'class_notification.php';
    $not_obj = new Tutor81Notification();
    if ($not_obj->notifyAlert($_POST['license_id'], $_POST['custom_message']) == true)
        $res = 1;
    else
        $res = 0;


    /**
     * INVIO AUTOMATICO ALERT
     */
} elseif ($op_type === 'auto_alert') {
    $licenses_expiring = $purchase_obj->getLicenseExpiring();

    if ($licenses_expiring) {
        require_once BASE_LIBRARY_PATH . 'class_notification.php';
        $not_obj = new Tutor81Notification();
        $res = 0;
        foreach ($licenses_expiring as $license) {
            $res += (int) $not_obj->notifyAlert($license['id']);
        }
    }

    /**
     * ISCRIZIONE MULTIPLA
     */
} elseif ($op_type === 'subscribe') {
    $total_purchased = 0;
    if ($_POST['to_buy'] !== "false") {
        if (filter_input(INPUT_POST,'packed', FILTER_SANITIZE_NUMBER_INT) > 0) {
            $to_buy = filter_input(INPUT_POST, 'to_buy', FILTER_SANITIZE_NUMBER_INT);
            require_once BASE_LIBRARY_PATH . 'class_pack.php';
            $pack_obj = new T81Pack();
            $pack_purchased = $pack_obj->getCurrentPackPurchased($_POST['tutor_company_id']);
            $learning_project_price_duration = $pack_obj->getElearningPriceDuration($_POST['learning_project_id']);
            foreach ($pack_purchased as $pack){
                if ($to_buy <= 0) break;
                if ($pack['content_type'] === 'COURSES') {
                    $available = $pack['content_available'];
                    $to_purchase = $available >= $to_buy ? $to_buy : $available;
                    $to_reduce = $to_purchase;
                } elseif ($pack['content_type'] === 'HOURS' && (integer) $learning_project_price_duration['duration'] > 0) {
                    $available = $pack['content_available']/((integer) $learning_project_price_duration['duration']);
                    $to_purchase = $available >= $to_buy ? $to_buy : $available;
                    $to_reduce = $to_purchase*((integer) $learning_project_price_duration['duration']);
                } elseif ($pack['content_type'] === 'MONEY' && $learning_project_price_duration['price'] > 0) {
                    $available = floor($pack['content_available']/$learning_project_price_duration['price']);
                    $to_purchase = $available >= $to_buy ? $to_buy : $available;
                    $to_reduce = $to_purchase*$learning_project_price_duration['price'];
                }
                $purchased = $purchase_obj->purchaseCourse($_POST['company_id'], $_POST['learning_project_id'], 
                                                    $to_purchase, $_POST['user_company_ref'], $_POST['tutor_id'], 
                                                        $_POST['ext_po_number'], $_POST['cost_centre'], $pack['id_pack_purchase']);
                if ($purchased) {
                    $to_buy -= $to_purchase;
                    // riduco il content available del pack
                    $pack_obj->setContentAvialablePackPurchased($pack['id_pack_purchase'], $pack['content_available'] - $to_purchase);
                    
                }
            }
        } else {
            $purchased = $purchase_obj->purchaseCourse($_POST['company_id'], $_POST['learning_project_id'], 
                                            $_POST['to_buy'], $_POST['user_company_ref'], $_POST['tutor_id'], 
                                                $_POST['ext_po_number'],$_POST['cost_centre']);
        }
    }

    $res = array();
    foreach ($_POST['users'] as $user_id) {
        $license_id = $purchase_obj->createNewLicense($user_id, $_POST['learning_project_id'], $_POST['tutor_id'], $_POST['start'], $_POST['end'], $_POST['alert'], $_POST['company_id']);
         array_push($res, $license_id);
    }
    
    $res = !empty($res) ? json_encode($res) : 0;


    /**
     * MODIFICA SCADENZA CORSO
     */
} elseif ($op_type === 'schedule_license') {
    $res = $purchase_obj->scheduleLicense($_POST['license_id'], $_POST['starting_from'], $_POST['finish_within'], $_POST['days_to_alert']);
    if ($res && $_POST['send_mail'] === "true") {
        require_once BASE_LIBRARY_PATH . 'class_notification.php';
        $not_obj = new Tutor81Notification();
        $not_obj->notifyLicenseExpirationDate($_POST['license_id']);
    }
}

echo $res;