<?php
/*---------------------------------------------------------
 * Tutor81 - auto.php
 * ---------------------------------------------------------
 * funzioni per esecuzione automatica
 * 
 * ---------------------------------------------------------
 * date creation: 17-11-2014
 * last_update: 25-04-2024
 *  auto alert expiring course
 *  auto notifies the last two months of purchases
 *  auto suspend plans every day 30 of odd months
 * 
 * ---------------------------------------------------------
 * by Roberto Zaniol
 */

require_once 'class_course.php';
require_once 'class_purchase.php';
require_once 'class_company.php';
require_once 'class_notification.php';
require_once 'function.php';

$course_obj = new iWDCourse();
$purchase_obj = new iWDPurchase();
$company_obj = new T81Company();
$not_obj = new Tutor81Notification();

$current_date = new DateTime('now', new DateTimeZone('Europe/Rome'));

//$not_obj->testNotify("zaniol.roberto@gmail.com", "Esecuzione file auto.php " . $current_date->format('d/m/Y H:i:s'));//controllo esecuzione task 

/**
 * auto alert expiring course
 */
$licenses_expiring = $purchase_obj->getLicenseExpiring();
if ($licenses_expiring) {
    foreach ($licenses_expiring as $license) {
        $not_obj->notifyAlert($license['id']);
    }
}


if (is_first_day_month($current_date)) {
    /**
     * auto notify purchases every 1 months
     */
    $all_tutors = $company_obj->getBusinessTutor();
   
    if ($all_tutors) {
        
        $to_date = clone $current_date;
        $to_date->setTime(0,0,0,0);
        $from_date = clone $to_date;
        $from_date->sub(new DateInterval('P1M'));
        
        $message = <<< EOF
        <p>Seguiranno indicazioni per pagamento e fattura.</p>
        <p>TUTOR81</p>
EOF;
        $message = htmlentities($message);
        
        foreach ($all_tutors as $tutor){
            try {
                $not_obj->notifyTutorPurchasesDateInterval($tutor['id'], $from_date, $to_date, $message);
            } catch (Exception $exc) {
                $err_message = "<p><b>TUTOR OBJECT:<b></p>";
                $err_message .= "<p>" . var_dump($tutor) . "</p>";
                $err_message .= "<p>" . $exc->getTraceAsString() . "</p>";
                $not_obj->notifyError(htmlentities($err_message));
            }
        }
    }
}// elseif ($current_date instanceof DateTime && $current_date->format('j') == 6) {
//    /**
//     * auto suspend plan of tutor every day 30 of month
//   */
//    $all_tutors = $company_obj->getBusinessTutor();
//    if ($all_tutors) {
//        $current_date->sub(new DateInterval('P5D'));
//        $to_date = clone $current_date;
//        $to_date->setTime(0,0,0,0);
//        $from_date = clone $to_date;
//        $from_date->sub(new DateInterval('P1M'));
//        foreach ($all_tutors as $tutor){
//            if($tutor["id"]== 2) {
//                continue;
//            }
//            try {
//                $company_plan = $company_obj->getCompanyPlan($tutor['id']); //cerca un piano attivo per l'ente formativo
//                if ($company_plan){
//                    $purchases = $course_obj->getPurchasesTutor($tutor['id'], $from_date, $to_date);
//                    if ($purchases) {
//                        $company_obj->suspendCompanyPlan($company_plan['id']);
//                    }
//                }
//            } catch (Exception $exc) {
//                $err_message = "<p><b>TUTOR OBJECT:<b></p>";
//                $err_message .= "<p>" . var_dump($tutor) . "</p>";
//                $err_message = "<p><b>PURCHASES OBJECT:<b></p>";
//                $err_message .= "<p>" . var_dump($purchases) . "</p>";
//                $err_message .= "<p>" . $exc->getTraceAsString() . "</p>";
//                $not_obj->notifyError(htmlentities($err_message));
//            }
//        }
//    }
//}