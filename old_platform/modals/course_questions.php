<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 07-lug-2015
 * File: modals/course_questions.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_om.php';
require_once BASE_LIBRARY_PATH . 'class_learning_question.php';
$course_obj = new iWDCourse();
$om_obj = new T81DOM();
$learn_que = new Tutor81QuestionObj();

$course_id = 0;
if (key_exists('course_id', $_GET)){
    $course_id = filter_input(INPUT_GET, 'course_id', FILTER_SANITIZE_NUMBER_INT);
} elseif (key_exists('course_id', $_POST)){
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_SANITIZE_NUMBER_INT);
}

$course = $course_obj->getCourseObjectByID($course_id);
$total_question = $learn_que->countDistinctQuestions($course_id);
$modules = $course_obj->getModulesByCourseID($course_id);
?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"
            aria-hidden="true">×</button>
    <h3 id="myModalLabel">Magazzino domande</h3>
</div>
<div class="modal-body">
    <div id="questions_box">
        <h1 class="title_detail">Corso: <?= $course['title'] ?></h1>	


        <?php
        $num_q = 0;
        foreach ($modules as $single_mo) {
            $lessons = $course_obj->getLessonsByModule($single_mo['id']);
            foreach ($lessons as $single) {
                $lo = $course_obj->getLearningObjByLesson($single['id']);

                foreach ($lo as $single_lo) {

                    if ($single_lo['learning_object_type_id'] == 1) {

                        $obj_questions = $om_obj->getVideoQuestions($single_lo['id']);
                    } elseif ($single_lo['learning_object_type_id'] == 2) {

                        $obj_questions = $om_obj->getSlideQuestions($single_lo['id']);
                    } elseif ($single_lo['learning_object_type_id'] == 3) {

                        $obj_questions = $om_obj->getDocQuestions($single_lo['id']);
                    } elseif ($single_lo['learning_object_type_id'] == 4) {

                        $obj_questions = $om_obj->getWebQuestions($single_lo['id']);
                    } else {

                        $obj_questions = "";
                    }

                    if (!empty($obj_questions)) {
                        foreach ($obj_questions as $single_question) {
                            $question = $learn_que->getQuestionDetail($single_question['question_sentence_id']);
                            ?>

                            <div class="question_box">
                                <h5><?= ++$num_q . '. ' . $question['text'] ?></h5>
                                <ol class="answer_box">

                        <?php $answers = $learn_que->getAnswersByQuestionID($single_question['question_sentence_id']);

                        foreach ($answers as $single_answer) {
                            ?>

                                        <li><?= $single_answer["text"] ?></li>

                        <?php } ?>

                                </ol>
                            </div>

                    <?php
                    }
                }
            }
        }
    }
    ?>
    </div>
</div>
<div class="modal-footer">
    <button class="btn btn-info" onclick="$('#questions_box').printArea()">Stampa</button>
    <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Chiudi</button>
</div>