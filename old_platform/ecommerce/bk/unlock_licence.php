<?php

require_once '../../config.php';
require_once BASE_LIBRARY_PATH . 'class_notification.php';
require_once BASE_LIBRARY_PATH . 'class_learning_event.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

$licence_id = isset($_POST['lpu_id']) ? $_POST['lpu_id'] : "0";

if ($licence_id != "0") {
    $ler_obj = new Tutor81LearningEvt();
    $not_obj = new Tutor81Notification();
    $learning_project_obj = new T81LearningProject();

    $purchase_detail = $ler_obj->getPurchaseDetailById($licence_id);
    $learningproject_id = $purchase_detail["learning_project_id"];
    $learning_project = $learning_project_obj->getCourseDetailFromLearningProject($learningproject_id);
    $start_date = date("Y-m-d");
    $end_date = date("Y-m-d", strtotime($start_date . $learning_project['max_execution_time'] . 'days'));
    $destination_email = $purchase_detail["email"];
    $not_obj->notifyUnlockLicenceEcommerce($purchase_detail, $destination_email, $learning_project, $purchase_detail["user_id"] != "0");

    // Put assigned to 1 notify that we alredy send the licence but not assigned to a user
    $is_notified = $ler_obj->setLPUUnlocked($licence_id, $start_date, $end_date);

}
