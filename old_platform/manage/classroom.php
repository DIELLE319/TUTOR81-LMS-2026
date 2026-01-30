<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 15-lug-2015
 * File: manage/classroom.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_classroom.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';

$classroom_obj = new T81Classroom();
$course_type_obj = new T81CourseType();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

/* CREA AULE */
if ($op_type === "new_classroom"){
    $res = $classroom_obj->addClassroomScheduled($_POST['new_classroom']) ? : 0;
    if ($res) {
        $user_id = filter_var($_POST['new_classroom']['created_by'], FILTER_SANITIZE_NUMBER_INT);
        $subfix = filter_input(INPUT_POST, 'subfix', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $from_path = BASE_MEDIA_PATH . "user_store/$user_id/classroom/temp/brochure_" . $subfix;
        if (file_exists($from_path)){
            $scheduled_path = BASE_MEDIA_PATH . 'public/classroom/scheduled/';
            if ( ! is_dir($scheduled_path)) {
                mkdir($scheduled_path, 0777, TRUE);
            }
            $to_path = $scheduled_path . "brochure_$res.pdf";
            rename($from_path, $to_path);
            chmod($to_path, 0644);
        }
    }

    
    
/* IMPOSTA STATO DI PUBBLICAZIONE AULA IN PIATTAFORMA */
} elseif ($op_type === "set_published_state_classroom_scheduled") {
    $id_classroom_scheduled = filter_input(INPUT_POST, 'id_classroom_scheduled', FILTER_SANITIZE_NUMBER_INT);
    $published = filter_input(INPUT_POST, 'published', FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    $res = $classroom_obj->setClassroomScheduledPublishedState($id_classroom_scheduled, $published) ? 1 : 0;

    
    
/* IMPOSTA STATO DI PUBBLICAZIONE AULA IN ECOMMERCE */
} elseif ($op_type === "set_published_in_ecommerce_state_classroom_scheduled") {
    $id_classroom_scheduled = filter_input(INPUT_POST, 'id_classroom_scheduled', FILTER_SANITIZE_NUMBER_INT);
    $published_in_ecommerce = filter_input(INPUT_POST, 'published_in_ecommerce', FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    $res = $classroom_obj->setClassroomScheduledPublishedInEcommerceState($id_classroom_scheduled, $published_in_ecommerce) ? 1 : 0;

    
    
/* ELIMINA AULA */
} elseif ($op_type === "delete_classroom_scheduled") {
    $id_classroom_scheduled = filter_input(INPUT_POST, 'id_classroom_scheduled', FILTER_SANITIZE_NUMBER_INT);
    $res = $classroom_obj->deleteClassroomScheduled($id_classroom_scheduled) ? 1 : 0;
    
    
    
/* PRENOTAZIONE AULA */
} elseif ($op_type === "booking_classroom") {
    /*$from_ecommerce = filter_var(INPUT_POST, 'from_ecommerce', FILTER_VALIDATE_BOOLEAN);
    if ($from_ecommerce)
        $id_classroom_booking = $classroom_obj->bookingClassroom ($_POST['classroom_scheduled_id'], 
                                $_POST['reserved_by'], $_POST['tutor_id'], $_POST['booked_places'],
                                1, $_POST['customer_name'], $_POST['customer_email'], $_POST['customer_phone']);
    else */
        $id_classroom_booking = $classroom_obj->bookingClassroom($_POST['classroom_scheduled_id'], 
                                $_POST['reserved_by_user_id'], $_POST['reserved_by_tutor_id'], $_POST['booked_places']);
    if ($id_classroom_booking > 0){
        // notifica prenotazione
        require_once BASE_LIBRARY_PATH . 'class_notification.php';
        $not_obj = new Tutor81Notification();
        //$res = $not_obj->notifyClassroomBooking($id_classroom_booking) ? "SUCCESS" : "NOT_NOTIFIED";
    } else {
        $res = "ERROR";
    }

    
    
/* CONFERMA PRENOTAZIONE AULA */
} elseif ($op_type === "confirm_classroom_booking") {
    $res = $classroom_obj->confirmClassroomBooking($_POST['id_classroom_booking'], $_POST['booked_places']) ? 1 : 0;

    
    
/* CANCELLA PRENOTAZIONE AULA */
} elseif ($op_type === "delete_classroom_booking") {
    $res = $classroom_obj->deleteClassroomBooking($_POST['id_classroom_booking']) ? 1 : 0;
    
    
    
}

echo $res;