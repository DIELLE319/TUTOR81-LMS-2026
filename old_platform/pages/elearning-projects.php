<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 18-nov-2015
 * File: pages/elearning-projects.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32 && $_SESSION['user']['role'] != 2) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';

$course_obj = new iWDCourse();
$course_type_obj = new T81CourseType();

$learning_project_id = filter_input(INPUT_GET, 'learning_project_id', FILTER_SANITIZE_NUMBER_INT);

$categories = $course_obj->getCategories();
$course_for_menu = 'elearning';
?>
<div id="learning-project" class="container-fluid">
    <div class="row">
        <div class="col-sm-3 col-fixed col-menu">

            <?php require BASE_ROOT_PATH . 'pages/sections/courses-menu.php'; ?>

        </div>
        <div class="col-sm-9 col-sm-offset-3">
            <h3 class="text-center" style="margin-top: 0;">CATALOGO DEI CORSI</h3>
            <div id="learning-project-container">
                
            </div>
            
        </div><!-- /.col-sm-9 -->
    </div><!-- /.row -->
</div><!-- /#learning-project -->

<script>
    $('#accordion-courses ul.courses > li > a').click(function () {
        var learning_project_id = $(this).data("learning_project_id");
        $('#learning-project-container')
            .html('<img src="img/loading_gif.gif" />')
            .load('pages/sections/elearning-project.php?learning_project_id=' + learning_project_id);
    });
</script>