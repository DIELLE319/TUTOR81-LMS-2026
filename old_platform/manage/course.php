<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 18-ago-2015
 * File: manage/course.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_module_object.php';
require_once BASE_LIBRARY_PATH . 'class_lesson_object.php';
require_once BASE_LIBRARY_PATH . 'sanitize.php';

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

$course_obj = new iWDCourse();
$module_obj = new ModuleObject();
$lesson_obj = new LessonObject();

if ($op_type == 'new_course') {
    $created_by = $_POST['created_by'];
    $title = $_POST['title'];
    $subcategory_id = $_POST['subcategory'];
    $type_id = sanitize($_POST['type'], INT);
    $custom = sanitize($_POST['custom'], INT);
    $version = $_POST['version'];
    $law = $_POST['law'];
    $validity = $_POST['validity'];
    $integration = $_POST['integration'];
    $total_duration = $_POST['total_duration'];
    $total_elearning = $_POST['total_elearning'];
    $max_duration = $_POST['max_duration'];
    $producer = $_POST['producer'];
    $professor = $_POST['professor'];
    $customer = $_POST['customer'];
    $didactics = $_POST['didactics'];
    $perc = $_POST['perc'];
    $video = $_POST['video'];
    $description = $_POST['description'];

    $res = $course_obj->create($created_by, $title, $subcategory_id, $type_id, $custom, $version, $law, $validity, $integration, $total_duration, $total_elearning, $max_duration, $producer, $professor, $customer, $didactics, $perc, $video, $description);

    $custom_categories = $_POST['custom_categories'];
    foreach ($custom_categories as $custom_category_id) {
        if ($custom_category_id > 0) {
            $course_obj->addCourseCustomCategory($res, $custom_category_id);
        }
    }
} elseif ($op_type == 'edit_course') {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $subcategory_id = $_POST['subcategory'];
    $type_id = $_POST['type'];
    $custom = sanitize($_POST['custom'], INT);
    $version = $_POST['version'];
    $law_reference = $_POST['law'];
    $validity = $_POST['validity'];
    $integration = $_POST['integration'];
    $total_duration = $_POST['total_duration'];
    $total_elearning = $_POST['total_elearning'];
    $max_duration = $_POST['max_duration'];
    $producer = $_POST['producer'];
    $professor = $_POST['professor'];
    $customer = $_POST['customer'];
    $didactics = $_POST['didactics'];
    $perc = $_POST['perc'];
    $video = $_POST['video'];
    $description = $_POST['description'];

    $res = $course_obj->edit($course_id, $title, $custom, $version, $law_reference, $validity, $integration, $total_duration, $total_elearning, $max_duration, $producer, $professor, $customer, $didactics, $perc, $video, $description, $subcategory_id, $type_id);

    if (isset($_POST['old_custom_categories'])) {
        $old_custom_categories = $_POST['old_custom_categories'];
    } else {
        $old_custom_categories = array();
    }
    if (isset($_POST['new_custom_categories'])) {
        $new_custom_categories = $_POST['new_custom_categories'];
    } else {
        $new_custom_categories = array();
    }
    $toDelete = array_diff($old_custom_categories, $new_custom_categories);
    $toAdd = array_diff($new_custom_categories, $old_custom_categories);

    foreach ($toDelete as $custom_category_id) {
        if ($custom_category_id > 0) {
            $course_obj->deleteCourseCustomCategory($course_id, $custom_category_id);
        }
    }

    foreach ($toAdd as $custom_category_id) {
        if ($custom_category_id > 0) {
            $course_obj->addCourseCustomCategory($course_id, $custom_category_id);
        }
    }
} elseif ($op_type == 'close_course') {
    $course_id = $_POST['course_id'];
    $res = $course_obj->closeCourse($course_id);
} elseif ($op_type == 'open_course') {
    $course_id = $_POST['course_id'];
    $res = $course_obj->openCourse($course_id);
} elseif ($op_type == 'remove_course') {
    $course_id = $_POST['course_id'];
    $res = $course_obj->removeCourse($course_id);
} elseif ($op_type == 'new_module') {
    $title_module = $_POST['title_module'];
    $max_duration_module = $_POST['max_duration_module'];
    $duration_module = $_POST['duration_module'];
    $description_module = $_POST['description_course'];
    $course_id = $_POST['course_id'];
    $new_module_id = $module_obj->createNewModule($title_module, $description_module, $duration_module, $max_duration_module);
    $res = $module_obj->associateModuleToCourse($course_id, $new_module_id);
} elseif ($op_type == 'edit_module') {
    $title_module = $_POST['title_module'];
    $max_duration_module = $_POST['max_duration_module'];
    $duration_module = $_POST['duration_module'];
    $description_module = $_POST['description_course'];
    $module_id = $_POST['module_id'];
    $res = $module_obj->editModule($module_id, $title_module, $description_module, $duration_module, $max_duration_module);
} elseif ($op_type == 'new_lesson') {
    $title_lesson = $_POST['title_lesson'];
    $duration_lesson = $_POST['duration_lesson'];
    $percentage_lesson = $_POST['percentage_lesson'];
    $description_lesson = $_POST['description_lesson'];
    $owner_user_id = $_POST['owner_user_id'];
    $module_id = $_POST['module_id'];
    $lesson_id = $lesson_obj->createNewLesson($title_lesson, $description_lesson, $duration_lesson, $percentage_lesson, $owner_user_id);
    $learn_obj_order = $_POST['learn_obj'];
    $learn_obj_order = explode(",", $learn_obj_order);
    $position = 1;
    foreach ($learn_obj_order as $obj_id) {
        $id = substr($obj_id, 4);
        $lesson_obj->addNewLearningObjectToLesson($id, $lesson_id, $position);
        $position++;
    }
    $module_obj->addNewLessonObjectToModule($lesson_id, $module_id);
    $res = $lesson_id;
} elseif ($op_type == 'edit_lesson') {
    $lesson_id = $_POST['lesson_id'];
    $title_lesson = $_POST['title_lesson'];
    $duration_lesson = $_POST['duration_lesson'];
    $percentage_lesson = $_POST['percentage_lesson'];
    $description_lesson = $_POST['description_lesson'];
    $res = $lesson_obj->editLesson($lesson_id, $title_lesson, $description_lesson, $duration_lesson, $percentage_lesson);


    if (key_exists('learn_obj', $_POST)) {
        $learn_obj_order = $_POST['learn_obj'];
        $learn_obj_order = explode(",", $learn_obj_order);
        require_once BASE_LIBRARY_PATH . 'class_om.php';
        $om_obj = new T81DOM();
        $lesson_objects = $om_obj->getLearningObjectByLessonID($lesson_id);
        foreach ($lesson_objects as $object) {
            if (!in_array("sec_" . $object['id'], $learn_obj_order)) {
                $lesson_obj->removeLearningObjectToLesson($lesson_id, $object['id']);
            }
        }
        $position = 1;
        foreach ($learn_obj_order as $obj_id) {
            $id = substr($obj_id, 4);
            if ($lesson_obj->isObjectInLesson($id, $lesson_id))
                $lesson_obj->updatePositionLearningObjectInLesson($id, $lesson_id, $position);
            else
                $lesson_obj->addNewLearningObjectToLesson($id, $lesson_id, $position);
            $position++;
        }
    }
}elseif ($op_type == 'remove_lesson') {
    $course_module_lesson_id = $_POST['course_module_lesson_id'];
    $res = $module_obj->removeLesson($course_module_lesson_id);
} elseif ($op_type == 'get_specific_custom_categories') {
    $owner_user_id = $_POST['owner_user_id'];
    $subcategory_id = $_POST['subcategory_id'];
    $subcategory_detail = $course_obj->getDetailSubcategory($subcategory_id);
    $res = $course_obj->getSpecificCustomCategoriesByOwner($owner_user_id, $subcategory_detail['category_id']);
    $res = json_encode($res);
} elseif ($op_type == 'add_single_price') {
    $res = $course_obj->addPrice($_POST['course_id'], $_POST['price']);
} elseif ($op_type == 'add_standard_price') {
    $res = $course_obj->addStandardPrice($_POST['course_id'], $_POST['range_id'], $_POST['price']);
} elseif ($op_type == 'add_custom_price') {
    $res = $course_obj->addPrice($_POST['course_id'], $_POST['price'], 1, $_POST['upper_limit'], $_POST['lower_limit']);
} elseif ($op_type == 'edit_price') {
    $res = $course_obj->editPrice($_POST['ref_id'], $_POST['price']);
} elseif ($op_type == 'remove_price') {
    $res = $course_obj->removePrice($_POST['ref_id'], $_POST['range_id']);
} elseif ($op_type == 'edit_price_range') {
    $res = $course_obj->editPrice($_POST['ref_id'], $_POST['price']);
    $res += $course_obj->editRange($_POST['range_id'], $_POST['upper_limit'], $_POST['lower_limit']);
} elseif ($op_type == 'get_count_questions') {
    $total_questions = 0;
    $questions_correct = 0;
    $questions_wrong = 0;

    require_once BASE_LIBRARY_PATH . 'class_learning_question.php';
    $quest_obj = new Tutor81QuestionObj();

    if (isset($_POST['learning_event_id']) && isset($_POST['learning_project_id'])) {
        $total_questions = $quest_obj->countQuestions($_POST['learning_project_id']);
        $questions_correct = $quest_obj->countQuestionsCorrect($_POST['learning_event_id']);
        $questions_wrong = $quest_obj->countQuestionsWrong($_POST['learning_event_id']);
    }
    $res = json_encode(array('total' => $total_questions, 'correct' => $questions_correct, 'wrong' => $questions_wrong));
} elseif($op_type == 'get_learning_project_detail'){
    
}


echo $res;
