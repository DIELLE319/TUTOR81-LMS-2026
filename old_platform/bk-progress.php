<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 17-set-2017
 * File: ecommerce/bk/progress.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
 // 2 minutes execution time
@set_time_limit(2 * 60);
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';

$company_obj = new T81Company();
$report_obj = new Report();

if ($_SESSION['user']['role'] == 0 || $_SESSION['user']['role'] == 8 || $_SESSION['user']['role'] == 16) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}
                    
require_once 'ecommerce/bk/header.php'; ?>

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

        <?php require "ecommerce/bk/modal-user-profile-settings.php" ?>

        <!-- Main Container -->
        <div id="main-container">

            <header class="navbar navbar-default" >
                <?php require "ecommerce/bk/search-form-header.php" ?>
            </header>
            <!-- Page content -->
            <div id="page-content" style="padding-top: 20px; min-height: 821px;" >

                <!-- Timeline Widget -->
                <div class="widget" style="margin:  0;">
                    <div class="widget-extra themed-background-dark">
                        <div class="row">
                            <div class="col-sm-12">
                                <h2 class="text-center">
                                    <strong>Lista Corsi Attivati</strong>
                                    <span class="progress" style="width: 75px; display: inline-table;">
                                        <span class="progress-bar progress-bar-striped " aria-valuemin="0" aria-valuemax="100" style="width: 100%;">
                                            in corso
                                        </span>
                                    </span>
                                    <span class="progress" style="width: 75px; display: inline-table;">
                                        <span class="progress-bar progress-bar-striped progress-bar-danger" aria-valuemin="0" aria-valuemax="100" style="width: 100%;">
                                            non avviati
                                        </span>
                                    </span>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->

                <!-- Datatables Content -->
                <div class="block full" >
                            <!-- Basic Wizard Title -->

                    <div class="form-group" style="margin-bottom: 5px;">
                        <input type="hidden" title="Cliente" id="clienteCompanyID" class="clienteCompanyID"
                               value="<?= $_SESSION['user']['company']['is_tutor'] ? $companies[0]['id'] : $_SESSION['user']['company']['id'] ?>">
                    </div>
<?php
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'class_learning_question.php';
require_once BASE_LIBRARY_PATH . 'class_departments.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';

$learn_obj = new T81LearningProject();
$dep_obj = new Departments();
$course_obj = new iWDCourse();

$customer_company_id = $_SESSION['user']['role'] == 2 ? $_SESSION['user']['company']['id'] : 0;
$tutor_company_id = $_SESSION['user']['role'] == 1000 ? 0 : $_SESSION['user']['company']['id'];

$user_assigned = $company_obj->getNotCompletedAssignementPurchase($customer_company_id, $tutor_company_id);
$product_units = false;//$dep_obj->getProductUnits($company_id);
?>             

<div id="progress-container">
    <div class="action-selected multi-action" style="display: none;">
        <button class="btn btn-warning send-alert" type="button">
            Alert <span class="glyphicon glyphicon-envelope"></span>
        </button>
        <button class="btn btn-default notify-course-assignment" type="button">
            Invia avvia corso <span class="glyphicon glyphicon-envelope"></span>
        </button>
        <!-- <button class="btn btn-default notify-username" type="button">
            Credenziali <span class="glyphicon glyphicon-envelope"></span>
        </button> -->
    </div>
        
    <table id="progress-table" class="table table-bordered table-striped table-vcenter">
        <thead>
            <tr>
                <!-- <th  data-orderable="false"><input type="checkbox" class="select-visible tristate"></th> -->
                <th>Azienda</th>
                <th>Cognome Nome</th>
                <th>Corso</th>
                <th>Email</th>
                <th style="width: 100px;">Data inzio</th>
                <th style="width: 100px;">Ultimo accesso</th>
                <th style="width: 100px;">Termine programmato</th>
                <th >Progresso</th>
                <th data-orderable="false">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($user_assigned as $single) {
                $learning_project_user_id = $single['id'];
                $learning_project_id = $single['learning_project_id'];
                
                $learning_event = $report_obj->getLearningEvent($learning_project_user_id);
                $course_detail = $learn_obj->getCourseDetailFromLearningProject($learning_project_id);

                $is_abandoned_course = FALSE;
                
                if ($learning_event) {
                    $start_date_time = date("d/m/Y", strtotime($learning_event['start_date_time']));
                    $end_date_time = date("d/m/Y", strtotime($learning_event['status_stored_time']));
                    if ($learning_event["progress_rate"]<100){
                        $sdt = new DateTime($learning_event['start_date_time']);
                        $today = new DateTime("now");
                        $diff = $sdt->diff($today);
                        if ($diff->format('%a') > 365) {
                            $is_abandoned_course = TRUE;
                        }
                    }
                } else {
                    $start_date_time = "-";
                    $end_date_time = "-";
                }
                
                if (isset($single['starting_from']) && $single['starting_from'] !== '0000-00-00') {
                    $starting_from = DateTime::createFromFormat("Y-m-d", $single['starting_from'], new DateTimeZone('Europe/Rome'));
                    $max_end_date = DateTime::createFromFormat("Y-m-d", $single['starting_from'], new DateTimeZone('Europe/Rome'));
                } else {
                    $starting_from = DateTime::createFromFormat("Y-m-d H:i:s", $single['creation_date'], new DateTimeZone('Europe/Rome'));
                    $max_end_date = DateTime::createFromFormat("Y-m-d H:i:s", $single['creation_date'], new DateTimeZone('Europe/Rome'));
                }

                $max_end_date->add(new DateInterval('P' . $course_detail['max_execution_time'] . 'D'));

                if (isset($single['finish_within']) && $single['finish_within'] !== "0000-00-00") {
                    $finish_within = DateTime::createFromFormat("Y-m-d", $single['finish_within'], new DateTimeZone('Europe/Rome'));
                    //$finish_within = $finish_within->format('d/m/Y');
                } else {
                    $finish_within = $max_end_date;
                    //$finish_within = $max_end_date->format('d/m/Y');
                }
                $starting_from = $starting_from->format('d/m/Y');

                //$user_dep_detail = $dep_obj->getEmployeeDetail($single['user_id']);
                ?>

                <tr class="<?= $learning_event ? ('started' . ($learning_event['total_num_lo'] > 0 ? ' prog_rate' : '')) : 'not-started' ?>"
                    data-license_id="<?= $single['id'] ?>" 
                    data-learning_event_id="<?= $learning_event ? $learning_event['id'] : '' ?>" 
                    data-user_id="<?= $single['user_id'] ?>" 
                    data-learning_project_id="<?= $single['learning_project_id'] ?>" 
                    data-learning_project_user_id="<?= $learning_project_user_id ?>">
                    <!-- <td><input type="checkbox"></td> -->
                    <td class="company"><?= strtoupper($single['customer_company_business_name']) ?></td>
                    <td class="student-name"><?= ucwords(strtolower("{$single['surname']} {$single['name']}")) ?></td>
                    <td class="course-title"><?= T81LearningProject::formatTitle($single['title']) ?></td>
                    <td class="student-email"><?= strtolower($single['user_email']) ?></td>
                    <td data-order="<?= $learning_event ? $learning_event['start_date_time'] : '0000-00-00 00:00:00'?>"><?= $start_date_time ?></td>
                    <td data-order="<?= $learning_event ? $learning_event['status_stored_time'] : '0000-00-00 00:00:00'?>"><?= $end_date_time ?></td>
                    <td data-order="<?= $finish_within->format('Y-m-d H:i:s') ?>"><?= $finish_within->format('d/m/Y') ?></td>
                    <td class="progress-status">
                    
                <?php if ($learning_event && $learning_event['total_num_lo'] == 0) { ?>
                        
                        <img src="img/loading_gif.gif" alt="loading"></td>
                           
                <?php } else { ?>
                        
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped <?= !$learning_event || $learning_event['progress_rate'] == 0 ? 'progress-bar-danger' : '' ?>"  
                                aria-valuemin="0" aria-valuemax="100"
                                style="width: <?= $learning_event ? $learning_event['progress_rate'] : 0 ?>%; min-width: 2em;">
                                    <?= $learning_event ? $learning_event['progress_rate'] : 0?>
                            </div>
                        </div>
                    
                <?php } ?>
                    
                    <td>
                        <div class="btn-group action">
                            <button class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu pull-right">
                                <!-- dropdown menu links -->


                                <li>
                                    <a class="schedule-license" tabindex="-1" href="javascript: void(0)">Modifica scadenza</a></li>

                                <li class="divider"></li>

                                <li>
                                    <a class="notify-course-assignment" tabindex="-1"
                                        href="javascript: void(0)">Invia avvia corso</a></li>

                                <li>
                                    <a class="send-alert" tabindex="-1" href="javascript: void(0)">Invia
                                        sollecito</a></li>

                            <?php if ($_SESSION['user']['role'] == 1000){?>

                                <li class="divider"></li>

                                <li>
                                    <a class="play-course" href="javascript: void(0)">Avvia corso</a>
                                </li>

                            <?php } ?>
                                

                            <?php if ($_SESSION['user']['role'] == 1000 || 
                                    ($_SESSION['user']['role'] == 1 && ((!$learning_event || $learning_event['progress_rate'] == 0) || $is_abandoned_course))){?>

                                <li class="divider"></li>
                                <li>
                                    <a class="remove-course" style="background-color: #eb6759;" href="javascript: void(0)">Rimuovi licenza</a>
                                </li>

                            <?php } ?>

                            </ul>
                        </div>
                    </td>
                </tr>
                
            <?php } ?>    
                
        </tbody>
    </table>
    
    <div class="hidden">
        <div>
            <a href="lib/report-avanzamento-xls.php/?id=<?= $company_id ?>&course_id=<?= $learning_project_id ?>" class="export-xls hidden-print">
                Scarica report in Excel 2003
            </a>
        </div>
        <div>
            <a href="lib/report-avanzamento-pdf.php/?id=<?= $company_id ?>&course_id=<?= $learning_project_id ?>" class="export-pdf hidden-print">
                Scarica report in PDF
            </a>
        </div>
    </div>

    <div id="mySimpleModal" class="modal fade" role="dialog" aria-labelledby="mySimpleModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h3>Titolo</h3>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            </div>
        </div>
    </div>
</div>

                </div>
                <!-- END Datatables Content -->

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
<script src="js/vendor/datatables.min.js"></script>
<script src="js/vendor/plugins.js"></script>
<script src="js/pages/bk-search-form-header.js"></script>
<!-- Load and execute javascript code used only in this page -->
<script>
<?php if ($_SESSION['user']['company']['is_tutor']) {?>

    $('#companySelect').on('change', function(){
        $.isLoading({text: "Attendere ..."});
        window.location = "/bk-progress.php?company_id=" + $(this).val();
    });

<?php } ?>
    
    $('#courseSelect').on('change', function(){
        $.isLoading({text: "Attendere ..."});
        window.location = "/bk-progress.php?company_id=<?= $company_id ?>&learning_project_id=" + $(this).val();
    });
    $("select[name=department]").addClass("hidden");
    
</script>


<script src="js/pages/tablesDatatables.js"></script>
<script>
    var customer_company_id = $("#companySelect").val();
    
    $(function(){
    
    <?php if ($_SESSION['user']['role'] == 1000){?>

    /* ************** AVVIA IL CORSO ****************** */
    $('#progress-table').on('click', '.action .play-course', function () {
        var license_id = $(this).parents('tr').data('license_id');
        $('#mySimpleModal .modal-content')
                .empty()
                .load('/modals/avviacorso.php?learning_project_user_id=' + license_id)
                .parents('#mySimpleModal')
                .modal();
    });
    
    <?php }
    
    if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1){?>
       
        /* ************** ELIMINA LICENZA ****************** */
        $('#progress-table .remove-course').click(function () {
            var licence_id = $(this).parents('tr').data('license_id');
            var row = progressTable.row($(this).parents('tr'));
            var res = removeLicenceAndPurchase(licence_id);
            if ( res ) {
                row.remove().draw();
            }   
        });

    <?php } ?>    
        
        TablesDatatables.init();
        
        <?php if($product_units) { ?>

//            $('select[name="product_unit"]').on("change", function() {
//                var pu_id = $('select[name="product_unit"]').val();
//                if (pu_id == 0) {
//                    $(".departments > div.dep_controls").empty();
//                    progressTable.columns(3).search('').draw();
//                    progressTable.columns(2).search('').draw();
//                } else {
//                    progressTable.columns(3).search('').draw();
//                    progressTable.columns(2).search(pu_id).draw();
//                    $.post("manage/department.php", {
//                        op_type: "get_pu_departments",
//                        pu_id: pu_id
//                    }, function(data) {
//                        var input_select = '<select title="Reparto" class="form-control" size="1" name="department" ><option value="0">Seleziona reparto</option>';
//                        data = $.parseJSON(data);
//                        if (data == 0) {
//                            $(".departments > div.dep_controls").empty().append(input_select + "</select>")
//                        } else {
//                            $.each(data, function(index, value) {
//                                input_select += '<option value="' + value.id_dep + '">' + value.short_desc_dep_type + "</option>";
//                                if (index == Object.keys(data).length - 1) {
//                                    $(".departments > div.dep_controls").empty().append(input_select + "</select>")
//                                }
//                            });
//                            $("select[name=department]").on("change", function() {
//                                var id_dep = $(this).val();
//                                if (id_dep == 0) {
//                                    progressTable.columns(3).search('').draw();
//                                } else {
//                                    progressTable.columns(3).search(id_dep).draw();
//                                }
//                            })
//                        }
//                    })
//                }
//            });
        

        <?php } ?>
    
        /* ******** SELEZIONE DI TUTTI GLI UTENTI VISIBILI E GESTIONE TRISTATE  ******* */
//        $('#progress-table th input.select-visible')
//            .tristate({
//                change: function(state, value){
//                        if (state === null || state === true) $('#progress-container .action-selected').show();
//                        else  $('#progress-container .action-selected').hide();
//                    }
//                })
//            .on('click', function (e) {
//                if ($(this).tristate('state') === true)
//                    $('#progress-table tbody > tr:visible input[type="checkbox"]:not(:checked)').click();
//                else if ($(this).tristate('state') === false) {
//                    if ($('#progress-table tbody > tr:visible input[type="checkbox"]:checked').length == $('#progress-table tbody > tr:visible input[type="checkbox"]').length)
//                        $('#progress-table tbody > tr:visible input[type="checkbox"]:checked').click();
//                    else
//                        $('#progress-table tbody > tr:visible input[type="checkbox"]:not(:checked)').click();
//                }
//                else
//                    $('#progress-table tbody > tr:visible input[type="checkbox"]:checked').click();
//        });
//
//        $('#progress-table tbody > tr input[type="checkbox"]').click(function (e) {
//            var checked = $('#progress-table tbody > tr input[type="checkbox"]:checked');
//            if ($('#progress-table tbody > tr input[type="checkbox"]').length > checked.length) {
//                if (checked.length == 0) {
//                    $('#progress-table th input.select-visible').tristate('state', false);
//                    $('#progress-table tbody > tr .quick-subscription a').show();
//                } else {
//                    $('#progress-table th input.select-visible').tristate('state', null);
//                }
//            } else {
//                $('#progress-table th input.select-visible').tristate('state', true);
//            }
//        });
        
        /* ************** INVIA NOTIFICA ASSEGNAZIONE CORSO SINGOLA ****************** */
        $('#progress-table').on('click', '.action li:not(.disabled) .notify-course-assignment', function (e) {
            notifyCourseAssignment($(this).parents('tr').data('license_id'));
        });

//        /* ************** INVIA NOTIFICA ASSEGNAZIONE CORSO MULTIPLI **************** */
//        $('div.multi-action > button.notify-course-assignment').click(function (e) {
//            if ($('#progress-table tr.selected ').length == 0)
//                alert('Nessun utente selezionato');
//            else if (confirm("Vuoi notificare l'assegnazione del corso agli utenti selezionati?")) {
//                $.isLoading({text: "Attendere il completamento ..."});
//                $('#progress-table tr.selected ').each(function () {
//                    var execution = $(this).find('td.progress-status div.progress-bar').text();
//                    execution = execution != "" ? execution.match(/[0-9]+/).join('') : false;
//                    if (execution !== false && execution < 100) {
//                        $.post("manage/license.php", {op_type: "notify_course_assignment", 
//                                                          license_id: $(this).data('license_id')});
//                    }
//                    ;
//                }).promise().done(function(){
//                    $.isLoading("hide");
//                    alert("Notifiche inviate con successo.");
//                });
//            }
//        });
//
//        /* ************** INVIA ALERT MULTIPLI **************** */
//        $('div.multi-action > button.send-alert').click(function (e) {
//            if ($('#progress-table tr.selected ').length == 0)
//                alert('Nessun utente selezionato');
//            else if (confirm("Vuoi inviare gli alert agli utenti selezionati?")) {
//                $.isLoading({text: "Attendere il completamento ..."});
//                $('#progress-table tr.selected ').each(function () {
//                    var execution = $(this).find('td.progress-status div.progress-bar').text();
//                    execution = execution != "" ? execution.match(/[0-9]+/).join('') : false;
//                    if (execution !== false && execution < 100) {
//                        $.post("manage/license.php", {op_type: "send_alert", 
//                                                          license_id: $(this).data('license_id'), 
//                                                          custom_message: ''});
//                    }
//                }).promise().done(function(){
//                    $.isLoading("hide");
//                    alert("Alert inviati con successo.");
//                });
//            }
//        });

        /* ************** INVIA ALERT SINGOLO ****************** */
        $('#progress-table').on('click', '.action li:not(.disabled) .send-alert', function (e) {
            if (confirm("Vuoi inviare un sollecito al corsista selezionato?"))
                sendAlertCourse($(this).parents('tr').data('license_id'), '');
        });

//        /* ************** INVIA CREDENZIALI MULTIPLI **************** */
//        $('div.multi-action > button.notify-username').click(function (e) {
//            if ($('#progress-table tr.selected ').length == 0)
//                alert('Nessun utente selezionato');
//            else if (confirm("Vuoi inviare le credenziali agli utenti selezionati?")) {
//                $.isLoading({text: "Attendere il completamento ..."});
//                $('#progress-table tr.selected ').each(function () {
//                    $.post("manage/user.php", {op_type: "send_user_name", user_id: $(this).data('user_id')});
//                }).promise().done(function(){
//                    $.isLoading("hide");
//                    alert("Credenziali inviate con successo.");
//                });
//            }
//        });


        /* ************** INVIA CREDENZIALI SINGOLO ****************** */
        $('#progress-table').on('click', '.action li:not(.disabled) .notify-username', function (e) {
            sendUserName($(this).parents('tr').data('user_id'));
        });
        
        /* ************** SVUOTA MODAL ****************** */
        $('#mySimpleModal').on('hidden', function (e) {
            if($(this).find('#filelist').hasClass('loaded')){
                var license_id = $(this).find('#filelist').data("license_id");
                $('#progress-table tr[data-license_id="' + license_id + '"] .btn-danger').removeClass('btn-danger');
                $('#progress-table tr[data-license_id="' + license_id + '"] td.test').empty()
                        .append('<a href="media/test_in_presenza/test_licenza_' + license_id + '.pdf" target="_blank">'+
                            '<img src="img/course_archive.png">'+
                        '</a>');
            }
            $(this).find(".modal-header h3").html("Titolo");
            $(this).find(".modal-body").empty();
            $(this).find(".modal-footer button.save-modal").remove();
        });

        /* ************** MODIFICA SCADENZA CORSO ****************** */
        $('#progress-table').on('click', '.action li:not(.disabled) .schedule-license', function (e) {
            var license_id = $(this).parents('tr').data('license_id');
            $('#myModal').modal().find('.modal-content').load('modals/schedule-license.php?learning_project_user_id=' + license_id);
        });
        
        
//        /* ***** ABILITA BOTTONI ESPORTAZIONE ***** */
//        $('#progress .panel-heading .export-pdf').removeClass('disabled').click(function(){
//            location.href = $('#progress-container .export-pdf').attr('href');
//        });
//        $('#progress .panel-heading .export-xls').removeClass('disabled').click(function(){
//            location.href = $('#progress-container .export-xls').attr('href');
//        });
        
        
        
        
        $('#progress-table').on('click', 'tbody > tr > td:first-child', function(){
            var user_id = $(this).parents('tr').data('user_id');
            $("#single-user").html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>').load("pages/sections/employee-detail.php", {
                    user_id: user_id
                });
            $("#cerca-utenti-modal").modal();
        });
    });

</script>

</body>
</html>
<!-- ---- AVANZAMENTO ------ AVANZAMENTO ------ AVANZAMENTO ---- -->