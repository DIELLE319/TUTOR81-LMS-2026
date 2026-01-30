<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 04-set-2015
 * File: pages/sections/courses-menu.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';

$course_obj = new iWDCourse();
$course_type_obj = new T81CourseType();
$report_obj = new Report();

$learning_project_id = filter_input(INPUT_GET, 'learning_project_id', FILTER_SANITIZE_NUMBER_INT);

$categories = $course_obj->getCategories();
$purchases = $report_obj->getPurchasesByCompany($_SESSION['company']['id']);
$learning_purchases = array();
if ($purchases) {
    foreach ($purchases as $purchase){
        $learning_purchases[$purchase['id']] = $purchase;
    }
}
$status = $report_obj->getLearningStatus($_SESSION['company']['id']);
$learning_status = array();
$max_ls = 0;
if ($status) {
    foreach ($status as $single_status){
        $max_ls = $single_status['total'] > $max_ls ? $single_status['total'] : $max_ls;
        $learning_status[$single_status['learning_project_id']] = $single_status;
    }
}
$panel_color = $course_for_menu === 'classroom' ? 'panel-success' : 'panel-default';
?>
<div class="panel-group" id="accordion-courses" role="tablist">
    <?php
    foreach ($categories as $category) {
        if ($category['id'] != 4 && $category['id'] != 5 && $category['id'] != 8)
            continue;
        $subcategories = $course_obj->getSubCategories($category['id']);
        ?>

        <div class="panel <?= $panel_color ?>">
            <div class="panel-heading" role="tab" <?= 'id="heading_' . $category['id'] . '"' ?>>
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#accordion-courses" href="#collapse_<?= $category['id'] ?>" aria-controls="collapse_<?= $category['id'] ?>">
                        <?= ucwords($category['name']) ?>
                    </a>
                </h4>
            </div>
            <div <?= 'id="collapse_' . $category['id'] . '"'?> class="panel-collapse collapse <?= $category['id'] == 5 ? ' in' : '' ?>" role="tabpanel">

                <ul class="nav nav-pills nav-stacked categories">

                    <?php foreach ($subcategories as $subcategory) { ?>
                        <li <?= 'id="filter-subcategory_' . strtoupper($category['name']) . '"' ?>>
                            <a href="javascript: void(0)" data-subcategory_id="<?= $subcategory['id'] ?>">

                                <?php if (!empty($subcategory['icon']) && file_exists(BASE_ROOT_PATH . 'img/menu/subcategories/' . $subcategory['icon'])) { ?>

                                    <img src="img/menu/subcategories/<?= $subcategory['icon'] ?>">

                                <?php } else {
                                    echo strtoupper($subcategory['name']);
                                } ?></a>
                            <ul class="nav nav-pills nav-stacked courses" style="display: none;">

                                <?php
                                if ($course_for_menu === 'classroom') {
                                    $course_types = $course_type_obj->getClassroomCourseTypesListBySubcategory($subcategory['id']);
                                    if ($course_types) {
                                        foreach ($course_types as $single) { ?>

                                            <li>
                                                <a href="javascript: void(0)" data-id_course_type="<?= $single['id_course_type'] ?>">
                                                    <?= $single['course_code'] ?>
                                                    <span class="glyphicon glyphicon-chevron-right pull-right"></span>
                                                </a>
                                            </li>

                                        <?php }
                                    }
                                } else {
                                    $course_types = $course_obj->getCourseDetailedListOfAvailableLearningProject ($_SESSION['company']['id'], $subcategory['id']);
                                    if ($course_types) {
                                        foreach ($course_types as $single) { ?>

                                            <li class="<?= key_exists($single['learning_project_id'], $learning_purchases) ? 'purchased' : '' ?>
                                            <?= key_exists($single['learning_project_id'], $learning_status) ? 'assigned' : '' ?>
                                            <?= key_exists($single['learning_project_id'], $learning_status) && $learning_status[$single['learning_project_id']]['finished'] > 0 ? 'completed' : '' ?>">

                                                <?php if (key_exists($single['learning_project_id'], $learning_status)) {
                                                    $completed = ($learning_status[$single['learning_project_id']]['finished']);
                                                    $completed_rate = $completed/$max_ls*300;
                                                    $active = ($learning_status[$single['learning_project_id']]['started'] - $learning_status[$single['learning_project_id']]['finished']);
                                                    $active_rate = $active/$max_ls*300;
                                                    $pending = ($learning_status[$single['learning_project_id']]['total'] - $learning_status[$single['learning_project_id']]['started']);
                                                    $pending_rate = $pending/$max_ls*300;

                                                    ?>

                                                    <!-- <svg viewBox="0 0 300 5">
                                            <rect x="<?= 300 - $active_rate ?>" y="0" width="<?= $active_rate ?>" height="5" class="course-active svg-fill-info"
                                                  data-toggle="tooltip" title="In corso <?= $active ?>"></rect>
                                            <rect x="<?= 300 - $active_rate - $completed_rate ?>" y="0" width="<?= $completed_rate ?>" height="5" class="course-completed svg-fill-success"
                                                  data-toggle="tooltip" title="Completati <?= $completed ?>">></rect>
                                            <rect x="<?= 300 - $active_rate - $completed_rate - $pending_rate ?>" y="0" width="<?= $pending_rate ?>" height="10" class="course-pending svg-fill-danger"
                                                  data-toggle="tooltip" title="Non avviati <?= $pending ?>"></rect>
                                        </svg> -->

                                                <?php } ?>

                                                <a href="javascript: void(0)" data-learning_project_id="<?= $single['learning_project_id'] ?>">
                                                    <?= strtoupper(substr($single['title'], strpos($single['title'], ' - ') + 3)) ?>
                                                </a>

                                                <?php if (key_exists($single['learning_project_id'], $learning_purchases)) {
                                                    if (key_exists($single['learning_project_id'], $learning_status)) {
                                                        if ($learning_status[$single['learning_project_id']]['total'] == $learning_purchases[$single['learning_project_id']]['somma']) {
                                                            $label_class = 'label-primary';
                                                            $label_title = 'corsi acquistati';
                                                        } elseif ($learning_status[$single['learning_project_id']]['total'] < $learning_purchases[$single['learning_project_id']]['somma']) {
                                                            $label_class = 'label-warning';
                                                            $label_title = ($learning_purchases[$single['learning_project_id']]['somma'] - $learning_status[$single['learning_project_id']]['total']) . ' corsi non assegnati';
                                                        } else {
                                                            $label_class = 'label-danger';
                                                            $label_title = 'Errore acquisto: ' . ($learning_status[$single['learning_project_id']]['total'] - $learning_purchases[$single['learning_project_id']]['somma']) . ' corsi assegnati non acquistati';
                                                        }
                                                    } else {
                                                        $label_class = 'label-warning';
                                                        $label_title = $learning_purchases[$single['learning_project_id']]['somma'] . ' corsi non assegnati';
                                                    }
                                                    ?>

                                                    <span class="label <?= $label_class ?> purchased" data-toggle="tooltip" title="<?= $label_title ?>"><?= $learning_purchases[$single['learning_project_id']]['somma'] ?></span>

                                                <?php } ?>

                                            </li>

                                        <?php }
                                    }
                                } ?>

                            </ul>
                        </li>
                    <?php } ?>

                </ul>

            </div>
        </div>

    <?php } ?>

</div>

<script>

    function selectMenuCourse(selection){
        $('#accordion-courses ul.courses > li.active').not(selection).removeClass('active');
        selection.parent().addClass('active').parents('ul').show();
    }

    function resetMenuCourse(){
        $('#accordion-courses ul.courses > li.active').removeClass('active');
        $('#accordion-courses ul.courses').hide();
    }

    function arrangeMenuHeight(){
        var categories = 3; //<?= count($categories) ?>;
        var max_height = $('body').height() - 70 - categories*40 - (categories - 1)*5 - 62;
        $('#accordion-courses .categories').css('max-height', max_height);
    }

    $('#accordion-courses ul.categories > li > a').click(function () {
        $(this).parent().addClass('active').siblings().removeClass('active').find('ul').hide();
        $(this).siblings('ul').show();
    });


    $('#accordion-courses ul.courses > li > a').click(function(){
        selectMenuCourse($(this));
    });

    <?php if (isset($learning_project_id)){ ?>

    selectMenuCourse($('#accordion-courses ul.courses > li > a[data-learning_project_id="<?= $learning_project_id ?>"]'));

    <?php } ?>

    $(function(){
        arrangeMenuHeight();

        $(window).resize(function(){
            arrangeMenuHeight();
        });

    });

</script>