<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 20-lug-2015
 * File: report/feedback.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

$is_tutor = filter_input(INPUT_GET, 'is_tutor',FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
$company_id = filter_input(INPUT_GET, 'company_id', FILTER_SANITIZE_NUMBER_INT) ? : $_SESSION['company']['id'];

if ($_SESSION['user']['role'] != 1000) {
    if ((($_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32) && !$is_tutor)
            || ($_SESSION['user']['role'] == 2 && $_SESSION['user']['company']['id'] != $company_id)) {
        require_once BASE_ROOT_PATH . '403.php';
        return false;
    }
}

require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

$comp_obj = new T81Company();
$report_obj = new Report();
$learn_obj = new T81LearningProject();

$is_tutor = filter_input(INPUT_GET, 'is_tutor',FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
$company_id = filter_input(INPUT_GET, 'company_id', FILTER_SANITIZE_NUMBER_INT) ? : $_SESSION['company']['id'];
$learning_project_id = filter_input(INPUT_GET, 'learning_project_id', FILTER_SANITIZE_NUMBER_INT) ? : 0;

if ($learning_project_id != 0){
    $learn_detail = $learn_obj->getDetail($learning_project_id);
}

$questions_topic = array();
$feedback_for_Tutor81 = $report_obj->getFeedbackTopics();
if ($feedback_for_Tutor81) {
    foreach ($feedback_for_Tutor81 as $feedback) {
        $questions_topic[$feedback['long_description']] = $report_obj->getFeedbackQuestions($feedback['id']);
    }
}

$feedback_for_tutor = $report_obj->getFeedbackTopics($_SESSION['tutor']['id']);
if ($feedback_for_tutor) {
    foreach ($feedback_for_tutor as $feedback) {
        $questions_topic[$feedback['long_description']] = $report_obj->getFeedbackQuestions($feedback['id']);
    }
}
$feedback_for_tutor_and_learning_project = !empty($learning_project_id) ? $report_obj->getFeedbackTopics($_SESSION['tutor']['id'], $learning_project_id) : false;
if ($feedback_for_tutor_and_learning_project) {
    foreach ($feedback_for_tutor_and_learning_project as $feedback) {
        $questions_topic[$feedback['long_description']] = $report_obj->getFeedbackQuestions($feedback['id']);
    }
}
$feedback_for_company = $report_obj->getFeedbackTopics($company_id);
if ($feedback_for_company) {
    foreach ($feedback_for_company as $feedback) {
        $questions_topic[$feedback['long_description']] = $report_obj->getFeedbackQuestions($feedback['id']);
    }
}
$feedback_for_company_and_learning_project = !empty($learning_project_id) ? $report_obj->getFeedbackTopics($company_id, $learning_project_id) : false;
if ($feedback_for_company_and_learning_project) {
    foreach ($feedback_for_company_and_learning_project as $feedback) {
        $questions_topic[$feedback['long_description']] = $report_obj->getFeedbackQuestions($feedback['id']);
    }
}
?>

<!-- ---- SCHEDA FEEDBACK ---- -->

<div id="feedback-report" class="text-center">
    <h3>FEEDBACK UTENTI: <strong><?= strtoupper($_SESSION['company']['business_name']) ?></strong></h3>
    <div class="report-container">

        <?php
        foreach ($questions_topic as $topic => $questions) {
            if ($questions) {
                ?>

                <h4><?= $topic ?></h4>

        <?php foreach ($questions as $question) { ?>

                    <div <?= 'id="feedback_' . $question['question_sentence_id'] . '"'?> 
                        class="single-feedback well" data-question_sentence_id="<?= $question['question_sentence_id'] ?>">
                        <h5><?= $question['text'] ?></h5>
                        <div class="graf-container">
                        </div>
                    </div>

                <?php
                }
            }
        }
        ?>

    </div>
</div>


<script>
    $(function () {

        function courseSelection(selected) {
            var learn_id = $('#courses_in_progress option').filter(function ()
            {
                return this.value == selected;
            }
            ).data('learn_id');
            if (learn_id > 0) {
                window.location.href = "index.php/elearning?setting=reports&type=feedback&learn_id=" + learn_id;
            } else {
                window.location.href = "index.php/elearning?setting=reports&type=feedback";
            }
        }

        if (!Modernizr.input.list) {
            var options = $('#courses_in_progress').children();
            var controls = $('#courses').parent();
            $('#courses').remove();
            if (!(controls.children('select').length > 0)) {
                $('#courses_in_progress').remove();
                $('<select id="courses_in_progress">' +
                        '<option data-comp_id="0" value="">Seleziona un corso</option>' +
                        '</select>').prependTo(controls).append(options);
            }
            $('#feedback-report').on('change', '#courses_in_progress', function (e) {
                courseSelection($(this).val());
            });
        } else {
            $('#feedback-report').on('input', '#courses', function (e) {
                courseSelection($(this).val());
            });
        }


        $('.single-feedback').each(function () {
            var question_sentence_id = $(this).data('question_sentence_id');
            $(this)
                    .find('.graf-container')
                    .html('<img src="img/loading_gif.gif" />')
                    .load('graphs/feedback-pie-text.php',
                            {
                                is_tutor: '<?= $is_tutor ?>',
                                question_sentence_id: question_sentence_id,
                                company_id: <?= $company_id ?>,
                                learning_project_id: <?= $learning_project_id ?>
                            }
                    );
        });

    });
</script>