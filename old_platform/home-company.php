<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 07-set-2015
 * File: home-company.php 
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

require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';

$comp_obj = new T81Company ();
$course_obj = new iWDCourse();
$course_type_obj = new T81CourseType();

$learning_project_id = filter_input(INPUT_GET, 'learning_project_id', FILTER_SANITIZE_NUMBER_INT);

$categories = $course_obj->getCategories();
$course_for_menu = 'elearning';
$prov = $comp_obj->getProvinces();
?>
<div id="home-company" class="container-fluid">
    <div class="row">


        <div id="menuCourses" class="col-sm-3 col-fixed col-menu hidden">

            <?php require BASE_ROOT_PATH . 'pages/sections/courses-menu.php'; ?>

        </div>

        <div id="home-container" class="">

            <?php
            if ($page === 'home') {?>

                <div class="panel-group" id="home-accordion" role="tablist" aria-multiselectable="true">

                    <div id="profile" class="panel panel-default hidden">
                        <div class="panel-heading">
                            <span class="glyphicon glyphicon-list"></span>
                            &nbsp;DATI CLIENTE
                            &nbsp;&nbsp;&nbsp;
                            <a href="/bk-sold-courses.php?scelta=acquistati" target="_top">
                                &nbsp;ACQUISTI
                            </a>
                            &nbsp;&nbsp;&nbsp;
                            <a href="javascript: void(0)"
                               onclick="$('#simpleLargeModal').modal().find('.modal-content').load('modals/departments.php');">
                                &nbsp;CREA UNIT&Agrave; E REPARTI
                            </a>
<!--                            <a class="collapse-control" role="button" data-toggle="collapse"-->
<!--                               data-parent="#home-accordion" href="#collapse-profile"-->
<!--                               aria-expanded="--><?php//= !$section ? 'true' : 'false' ?><!--" aria-controls="collapse-profile">-->
<!--                            </a>-->
                        </div>
                        <div id="collapse-profile" class="panel-collapse collapse in" role="tabpanel">
                            <div class="panel-body">

                                <?php require 'pages/sections/profile-company.php';?>

                            </div>
                        </div>
                    </div>

                    <div id="employees" class="panel panel-default hidden">
                        <div class="planner-header panel-heading">
                            <span class="glyphicon glyphicon-folder-open"></span>
                            <span>&nbsp;GESTIONE UTENTI PIATTAFORMA</span>
                            &nbsp;&nbsp;
                            <div id="add-user" style="display: none; vertical-align: middle; font-size: 15px; line-height: 15px">
                                <div style="display: table-cell;">
                                    <span class="glyphicons user_add" style="padding: 0; min-width: 30px; positin: relative; top: -6px;"></span>
                                </div>
                                <div style="display: table-cell;">
                                    <a href="javascript: void(0)" class="createEmployeeModal"><strong>crea utente</strong></a>
                                    <br>
                                    <a href="javascript: void(0)" class="import_employees">importa da Excel</a>
                                </div>
                            </div>
                            &nbsp;&nbsp;
                            <a href="javascript: void(0)" class="save-subscription glyphicon glyphicon-share-alt"
                               style="display: none;
                                    font-size: 36px;
                                    position: absolute;
                                    top: inherit;
                                    color: #43ac6a !important;"></a>
<!--                            <a class="collapse-control" role="button" data-toggle="collapse"-->
<!--                               data-parent="#home-accordion" href="#collapse-employees"-->
<!--                               aria-expanded="--><?php//= $section === 'employees' ? 'true' : 'false' ?><!--" aria-controls="collapse-employees">-->
<!--                            </a>-->
                        </div>
                        <div id="collapse-employees" class="panel-collapse collapse in" role="tabpanel">
                            <div class='panel-body'>

                                <?php require 'pages/employees-list.php'; ?>

                            </div>
                        </div>
                    </div>

                    <div id="progress" class="panel panel-default hidden">
                        <div class="panel-heading">
                            <span class="glyphicon glyphicon-stats"></span>
                            <span>&nbsp;STATO DI AVANZAMENTO DEI CORSI</span>
<!--                            <a class="collapse-control" role="button" data-toggle="collapse"-->
<!--                               data-parent="#home-accordion" href="#collapse-progress"-->
<!--                               aria-expanded="--><?php//= !$section || $section === 'progress' ? 'true' : 'false' ?><!--" aria-controls="collapse-progress">-->
<!--                            </a>-->
                            <div class="pull-right" style="margin-right: 10px;">
                                Esporta
                                <button type="button" class="export-pdf btn btn-default btn-xs disabled">PDF</button>
                                <button type="button" class="export-xls btn btn-default btn-xs disabled">XLS</button>
                            </div>
                        </div>
                        <div id="collapse-progress" class="panel-collapse collapse in" role="tabpanel">
                            <div class="panel-body">

                                <h4 class="text-right handwrite1">clicca sui corsi per vedere lo stato di avanzamento</h4>

                                <?php require_once 'graphs/company-progress-hbar.php'; ?>

                                <div id="progress-list">

                                    <?php if ($learning_project_id) require_once 'pages/sections/progress.php'; ?>

                                </div>

                            </div>
                        </div>
                    </div>

                    <div id="monitor" class="panel panel-default hidden">
                        <div class="panel-heading">
                            <span class="glyphicons global" style="vertical-align: top;padding-left: 20px;"></span>
                            <span>&nbsp;UTENTI ONLINE</span>
<!--                            <a class="collapse-control" role="button" data-toggle="collapse"-->
<!--                               data-parent="#home-accordion" href="#collapse-monitor"-->
<!--                               aria-expanded="--><?php//= $section === 'monitor' ? 'true' : 'false' ?><!--" aria-controls="collapse-monitor">-->
<!--                            </a>-->
                        </div>
                        <div id="collapse-monitor" class="panel-collapse collapse in" role="tabpanel">
                            <div class="panel-body">

                                <?php require 'pages/monitor.php'; ?>

                            </div>
                        </div>
                    </div>

                    <div id="feedback" class="panel panel-default hidden">
                        <div class="panel-heading">
                            <span class="glyphicons pie_chart" style="vertical-align: top;padding-left: 20px;"></span>
                            <span>&nbsp;FEEDBACK</span>
<!--                            <a class="collapse-control" role="button" data-toggle="collapse"-->
<!--                               data-parent="#home-accordion" href="#collapse-feedback"-->
<!--                               aria-expanded="--><?php//= $section === 'feedback' ? 'true' : 'false' ?><!--" aria-controls="collapse-feedback">-->
<!--                            </a>-->
                        </div>
                        <div id="collapse-feedback" class="panel-collapse collapse in" role="tabpanel">
                            <div id="feedback-report" class="panel-body">

                                <?php require_once 'report/feedback.php'; ?>

                            </div>
                        </div>
                    </div>
                </div>

                <?php
            } elseif ($page === 'subscribe') {
                require 'pages/subscribe.php';
            }
            ?>

        </div><!-- /.col-sm-9 -->
    </div><!-- /.row -->
</div><!-- /#home-company -->

<script>

    <?php if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 2 || $_SESSION['user']['role'] == 32) { ?>

        $('#accordion-courses ul.courses > li > a').click(function () {
            $.isLoading({text: "Attendere ..."});
            var learning_project_id = $(this).data("learning_project_id");
            $('table#users-list-table').addClass('subscribe');
            $('table#users-list-table tbody > tr')
                .removeClass('disabled')
                .find('td:first-child')
                .html('<input type="checkbox">');
            $('[aria-controls="collapse-employees"][aria-expanded="false"]').click();
            $('#add-user').css('display', 'inline-block');
            $.post('manage/company.php', {
                    op_type: 'get_users_already_formed',
                    company_id: <?= $_SESSION['company']['id'] ?>,
                    learning_project_id: [learning_project_id]
                }, function (data){
                    if (data != 0){
                        var users_formed = $.parseJSON(data);
                        var count = users_formed.length;
                        $.each(users_formed, function(index, value){
                                $('table#users-list-table tbody > tr[data-user_id="' +  value.id + '"]')
                                    .addClass('disabled')
                                    .find('td')
                                    .first()
                                    .empty();
                                if (!--count) $.isLoading("hide");
                            }
                        );
                    } else {
                        $.isLoading("hide");
                    }
                }
            );
            //location.href = "company/subscribe?learning_project_id=" + learning_project_id;
        });

        <?php if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1) { ?>
            $("#feedback").removeClass("hidden");
            $("#profile").removeClass("hidden");
            $("#monitor").removeClass("hidden");
            $("#progress").removeClass("hidden");
            $("#employees").removeClass("hidden");
        <?php } ?>
    <?php } else { ?>

    $('#accordion-courses ul.courses > li > a').click(function () {
        var learn_id = $(this).data("learning_project_id");
        $('#simpleLargeModal')
            .modal()
            .find('.modal-content')
            .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
            .load("modals/show-course.php", {learn_id: learn_id, setting: 'description'});
    });

    <?php } ?>

    $('#collapse-progress').on('show.bs.collapse', function(){
        $('table#users-list-table').removeClass('subscribe');
        $('table#users-list-table tbody > tr')
            .removeClass('disabled')
            .find('td:first-child')
            .empty();
        $('#add-user').hide();
        $('.save-subscription').hide();
        resetMenuCourse();
    });

    //console.log("inside the frame: ......");
    //alert($("#pageIndexSelector", parent.document.body).val());

    var accordionSelectorTitle = $(".active", parent.document.body).attr("title");
    console.log(accordionSelectorTitle);
    if (accordionSelectorTitle == 'faicorso') {
        console.log("Con menu visibile");
        $("#home-container").addClass("col-sm-9");
        $("#home-container").addClass("col-sm-offset-3");
        $("#menuCourses").removeClass("hidden");
        $("#employees").removeClass("hidden");
    }
    else {
        $("#home-container").addClass("col-sm-12");
    }

    $("#" + $(".active", parent.document.body).attr("title")).removeClass("hidden");


</script>