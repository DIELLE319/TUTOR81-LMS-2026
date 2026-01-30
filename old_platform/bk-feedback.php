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

$company_id = filter_input(INPUT_GET, 'company_id', FILTER_SANITIZE_NUMBER_INT) ? : $_SESSION['company']['id'];
$is_tutor = $_SESSION['company']['is_tutor']; //filter_input(INPUT_GET, 'is_tutor',FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
$learning_project_id = filter_input(INPUT_GET, 'learning_project_id', FILTER_SANITIZE_NUMBER_INT) ? : 0;

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
<?php require_once 'ecommerce/bk/header.php'; ?>
<body style="background-color: white;">

<!-- Page Wrapper -->
<!-- In the PHP version you can set the following options from inc/config file -->
<!--
    Available classes:

    'page-loading'      enables page preloader
-->
<div id="page-wrapper">

    <div id="page-container" class="sidebar-partial sidebar-visible-lg sidebar-no-animations">

        <!-- Main Sidebar -->
        <?php require_once "ecommerce/bk/menu-left.php" ?>
        <!-- END Main Sidebar -->

        <!-- Main Container -->
        <div id="main-container">

            <!-- Page content -->
            <div id="page-content" style="padding-top: 0;" >

                <!-- All Orders Block -->
                <div class="block full">
                    
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

                    </div>
                <!-- END All Orders Block -->

            </div>
            <!-- END Page Content -->

        </div>
        <!-- END Main Container -->
    </div>
    <!-- END Page Container -->
</div>
<!-- END Page Wrapper -->

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-double-up"></i></a>

<!-- Load and execute javascript code used only in this page -->
<script type="application/javascript" src="js/pages/bk-index.js"></script>



<script>
    $(function () {

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

</body>
</html>