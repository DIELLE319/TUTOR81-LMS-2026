<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 01-lug-2015
 * File: report/progress.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] == 0 || $_SESSION['user']['role'] == 8 || $_SESSION['user']['role'] == 16) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

$is_tutor = filter_input(INPUT_GET, 'is_tutor',FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
$company_id = filter_input(INPUT_GET, 'company_id', FILTER_SANITIZE_NUMBER_INT);

$learning_project_id = filter_input(INPUT_GET, 'learn_id', FILTER_SANITIZE_NUMBER_INT);
if (empty($learning_project_id)) {
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
require_once BASE_LIBRARY_PATH . 'class_learning_question.php';
require_once BASE_LIBRARY_PATH . 'class_departments.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';

$comp_obj = new T81Company();
$learn_obj = new T81LearningProject();
$report_obj = new Report();
$dep_obj = new Departments();
$course_obj = new iWDCourse();

$learn_detail = $learn_obj->getDetail($learning_project_id);
$course_detail = $learn_obj->getCourseDetailFromLearningProject($learning_project_id);
$user_assigned = $comp_obj->getAssignmentPurchase($learning_project_id, $company_id);
$product_units = $dep_obj->getProductUnits($company_id);
$course_need_test_in_the_presence = $course_obj->courseHasCustomCategory($course_detail['id'], 17);
?>
<!-- ---- AVANZAMENTO ------ AVANZAMENTO ------ AVANZAMENTO ---- -->
<div id="avanzamento">
    <div class="text-right multi-action clearfix hidden-print">
        <button class="btn btn-warning send-alert" type="button">
            Invia alert <i class="icon-envelope"></i>
        </button>
        <button class="btn btn-default notify-course-assignment" type="button">
            Notifica assegnazione <i class="icon-envelope"></i>
        </button>
        <button class="btn btn-default notify-username" type="button">
            Notifica credenziali <i class="icon-envelope"></i>
        </button>
    </div>
    <ul id="progress-filter" class="filter-button filter-category nav nav-pills pull-left">
        <li>
            <a class="active" href="javascript: void(0)" data-filter-column="6" data-filter-text="">Elenco</a>
            <ul class="filter-button filter-category nav nav-pills">
                <li class="filter-arrow">
                    <i class="icon-arrow-right"></i>
                </li>
                <li>
                    <a href="javascript: void(0)" data-filter-column="6" data-filter-text="1% - 99%">in corso</a>
                    <a href="javascript: void(0)" data-filter-column="6" data-filter-text="=0%">non avviati</a>
                    <a href="javascript: void(0)" data-filter-column="6" data-filter-text="SCARICA ATTESTATO">completati</a>
                </li>
            </ul>
        </li>
    </ul>

    <?php if ($product_units) { ?>

        <ul id="dep-filter" class="filter-button filter-category nav nav-pills pull-left">
            <li>
                <a class="active" href="javascript: void(0)" data-target-subcategories="filter-by-pu">Unità Produttive</a>
                <ul id="filter-by-pu" class="filter-button filter-category nav nav-pills">
                    <li class="filter-arrow">
                        <i class="icon-arrow-right"></i>
                    </li>
                    <li>

                        <?php foreach ($product_units as $pu) { ?>

                            <a href="javascript: void(0)" data-filter-column="7" data-filter-text="<?= $pu['id_pu'] ?>"><?= $pu['short_desc_pu'] ?></a>

                            <?php
                        }
                        foreach ($product_units as $pu) {
                            $departments = $dep_obj->getDepartmentsByProductUnit($pu['id_pu']);
                            if ($departments){
                            ?>

                            <ul<?= ' id="filter-by-dep-of-' . $pu['id_pu'] .'"'?> class="filter-by-dep filter-button filter-category nav nav-pills" style="display:none;">
                                <li class="filter-arrow">
                                    <i class="icon-arrow-right"></i>
                                </li>
                                <li>

                                    <?php foreach ($departments as $dep) { ?>

                                        <a href="javascript: void(0)" data-filter-column="8" data-filter-text="<?= $dep['id_dep'] ?>"><?= $dep['short_desc_dep_type'] ?></a>

                                    <?php } ?>

                                </li>
                            </ul>

                            <?php }
                        } ?>

                    </li>
                </ul>
            </li>
        </ul>
    
    <?php } ?>

    <form class="form-inline pull-right">
        <div class="form-group search hidden-print">
            <div class="input-group">
                <input type="text" class="form-control search-query" name="search" placeholder="Cerca...">
                <div class="input-group-addon"><span class="glyphicon glyphicon-search"></span></div>
            </div>
        </div>
    </form>
    


    <table id="progress-table" class="table table-sorter row-selectable">
        <thead>
            <tr>
                <th class="{sorter: false}">&nbsp;</th>
                <th>Cognome Nome</th>
                <th>Data inzio</th>
                <th>Ultimo accesso</th>
                <th>Termine programmato</th>
                <th class="{sorter: false} hidden">Licenza</th>
                <th>Progresso</th>
                
                <th style="display:none">Unità Produttiva</th>
                <th style="display:none">Reparto</th>
                    
            <?php if ($course_need_test_in_the_presence == true && $_SESSION['company']['test_in_the_presence'] !== "NO") {?>
                    
                <th class="{sorter: false}">Test</th>
                
            <?php } ?>
                
                <th class="{sorter: false}">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            foreach ($user_assigned as $single) {
                $learning_project_user_id = $single['id'];
                $num_lo = $learn_obj->get_num_learning_objects($learning_project_id);
                $num_exe_lo = $learn_obj->get_num_lo_executed($learning_project_user_id);
                if ($num_exe_lo != 0) {
                    $execution_percentage = round($num_exe_lo / $num_lo * 100);
                } else {
                    $execution_percentage = 0;
                }

                $learning_event = $report_obj->getLearningEvent($learning_project_user_id);

                if (isset($learning_event['end_date_time']) && $learning_event['end_date_time'] != "0000-00-00 00:00:00") {
                    $completed = true;
                } else {
                    $completed = false;
                }

                if (($single['id'] == 4330 || $single['id'] == 4341 || $single['id'] == 3470 || $single['id'] == 2558 || $single['id'] == 4414 || $single['id'] == 4428 || $single['id'] == 3680 || $single['id'] == 3684)) {
                    $completed = true;
                    $execution_percentage = 100;
                }


                if ($learning_event) {
                    if (strtotime($learning_event['start_date_time']) > strtotime('2013-06-24 14:30:00')) {
                        $start_date_time = date("d/m/Y", strtotime($learning_event['start_date_time']));
                    } else {
                        $start_date_time = date("d/m/Y", strtotime($learning_event['start_date_time']) + 28900);
                    }
                    if (!isset($learning_event['end_date_time']) || $learning_event['end_date_time'] == "0000-00-00 00:00:00") {
                        if (strtotime($learning_event['status_stored_time']) > strtotime('2013-06-24 14:30:00')) {
                            $end_date_time = date("d/m/Y", strtotime($learning_event['status_stored_time']));
                        } else {
                            $end_date_time = date("d/m/Y", strtotime($learning_event['status_stored_time']) + 28900);
                        }
                    } elseif (strtotime($learning_event['end_date_time']) > strtotime('2013-06-24 14:30:00')) {
                        $end_date_time = date("d/m/Y", strtotime($learning_event['end_date_time']));
                    } else {
                        $end_date_time = date("d/m/Y", strtotime($learning_event['end_date_time']) + 28900);
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
                    $finish_within = $finish_within->format('d/m/Y');
                } else {
                    $finish_within = $max_end_date->format('d/m/Y');
                }
                $starting_from = $starting_from->format('d/m/Y');

                $user_dep_detail = $dep_obj->getEmployeeDetail($single['user_id']);
                
                $need_test_in_the_presence = $completed == true 
                        && $course_need_test_in_the_presence == true
                        && $_SESSION['company']['test_in_the_presence'] !== "NO" 
                        && !file_exists(BASE_MEDIA_PATH . "test_in_presenza/test_licenza_{$single['id']}.pdf"); // necessario upload test in presenza
                ?>
                <tr data-license_id="<?= $single['id'] ?>" data-learning_event_id="<?= $learning_event['id'] ?>" data-user_id="<?= $single['user_id'] ?>">
                    <td class="{sorter: false}"><input type="checkbox"></td>
                    <td class="student-name"><a href="company/employee?user_id=<?= $single['user_id'] ?>"><?= ucwords(strtolower("{$single['surname']} {$single['name']}")) ?></a></td>
                    <td><?= $start_date_time ?></td>
                    <td><?= $end_date_time ?></td>
                    <td><?= $finish_within ?></td>
                    <td class="hidden"><?= $single['learning_project_pwd'] ?></td>
                    <td class="progress<?= $completed == true ? ' completed' : '' ?>">
                        <?php
                        if ($completed == true) {

                            if (file_exists(BASE_MEDIA_PATH . "attestati/attestato_licenza_" . $single['id'] . ".pdf")) {
                                ?>

                                <a target="_blank" href="manage/render_document.php?doc_type=attestato_elearning&license_id=<?= $single['id'] ?>">SCARICA ATTESTATO</a>

                            <?php  } else { ?>

                                <a target="_blank" href="lib/genera.php?course_id=<?= $single['id'] ?>.pdf">SCARICA ATTESTATO</a>

                                <?php 
                            }
                        } else {
                            ?>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped<?= $execution_percentage == 0 ? ' progress-bar-warning' : '' ?>"  
                                     aria-valuemin="0" aria-valuemax="100"
                                     style="width: <?= $execution_percentage ?>%; min-width: 2em;">
                                    
                                    <?= $execution_percentage ?>%
                                    
                                </div>
                            </div>
                            <?php 
                        }
                        ?>
                    </td>
                    
                    <td style="display:none;"><?= $user_dep_detail ? $user_dep_detail[0]['id_pu'] : 0 ?></td>
                    <td style="display:none;"><?= $user_dep_detail ? $user_dep_detail[0]['id_dep'] : 0 ?></td>
                    
                    <?php if ($course_need_test_in_the_presence == true && $_SESSION['company']['test_in_the_presence'] !== "NO") {?>
                    
                    <td class="test">
                        
                        <?php if (!$completed){?>
                            
                        &nbsp;
                        
                        <?php } else {
                        
                            if (file_exists(BASE_MEDIA_PATH . "test_in_presenza/test_licenza_{$single['id']}.pdf")){ ?>
                        
                        <a href="manage/render_document.php?doc_type=test_in_presenza&license_id=<?=$single['id']?>" target="_blank">
                            <img src="img/course_archive.png">
                        </a>
                        
                            <?php } else { ?>
                            
                            <a href="javascript: void(0);" tabindex="0" class="pop_info test upload" data-toggle="popover" 
                            data-original-title="Uploda test in presenza" data-container="body" data-placement="left"
                            data-content="Carica sul server il Test in Presenza da te effettuato.">
                            <i class="icon-exclamation-sign"></i></a>
                            
                            <?php } 
                        }?>
                        
                    </td>
                    
                    <?php } ?>
                    

                    <td>
                        <div class="btn-group action">
                            <button class="btn btn-default btn-xs dropdown-toggle<?=$need_test_in_the_presence ? ' btn-danger' : ''?>"
                                    data-toggle="dropdown">
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu pull-right">
                                <!-- dropdown menu links -->


                                <li <?= !$completed ? '' : ' class="disabled"' ?>>
                                    <a class="schedule-license" tabindex="-1" href="javascript: void(0)">Modifica scadenza</a></li>

                                <li class="divider"></li>
                                
                                <li <?= !$completed ? '' : ' class="disabled"' ?>>
                                    <a class="notify-course-assignment" tabindex="-1"
                                        href="javascript: void(0)">Notifica assegnazione</a></li>

                                <li <?= !$completed ? '' : ' class="disabled"' ?>>
                                    <a class="send-alert" tabindex="-1" href="javascript: void(0)">Invia
                                        sollecito</a></li>

                                <li><a class="notify-username" tabindex="-1" href="javascript: void(0)">Invia
                                        credenziali</a></li>

                                <li class="divider"></li>
                                
                            <?php if (!$completed && $_SESSION['user']['role'] == 1000){?>
                                
                                <li>
                                    <a class="play-course" href="javascript: void()">Avvia corso</a>
                                </li>
                                
                            <?php } ?>

                                <li<?= $execution_percentage == 0 ? ' class="disabled"' : '' ?>>
                                    <a class="stato" tabindex="-1" href="javascript: void(0)">Report attività</a>
                                </li>

                                <li <?= $completed ? '' : 'class="disabled"' ?>>
                                    <a class="attestato<?= !file_exists(BASE_MEDIA_PATH . "attestati/attestato_licenza_{$single['id']}.pdf") ? ' genera' : '' ?>"
                                        tabindex="-1" href="manage/render_document.php?doc_type=attestato_elearning&license_id=<?= $single['id'] ?>" target="_blank">Attestato</a></li>
                                
                            <?php if ($completed && $course_need_test_in_the_presence == true && $_SESSION['company']['test_in_the_presence'] !== "NO") {?>
                                        
                                <li>
                                    <a class="test upload"
                                       tabindex="-1" href="javascript: void(0)" target="_blank">Upload Test in presenza</a>
                                </li>
                                    
                            <?php } ?>
                                        
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php 
            }
            ?>
        </tbody>
    </table>

    <div>
        <a href="lib/report-avanzamento-xls.php/?id=<?= $company_id ?>&course_id=<?= $learning_project_id ?>" class="hidden-print">
            Scarica report in Excel 2003
        </a>
    </div>
    <div>
        <a href="lib/report-avanzamento-pdf.php/?id=<?= $company_id ?>&course_id=<?= $learning_project_id ?>" class="hidden-print">
            Scarica report in PDF
        </a>
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
<script>

    function setFilterProgress() {
        var columns = [];
        $('#avanzamento .filter-category a[data-filter-column].active').each(function () {
            if ($(this).data("filter-text") != "")
                columns[$(this).data("filter-column")] = String($(this).data("filter-text"));
        }).promise().done(function () {
            if (columns.length > 0)
                $('#progress-table').trigger('filterReset').trigger('search', [columns]);
            else
                $('#progress-table').trigger('filterReset');
        });
    }

    $(function () {

        $('#progress-table').tablesorter({
            theme: 'greyT81',
            sortList: [[1, 0]],
            // hidden filter input/selects will resize the columns, so try to minimize the change
            widthFixed: true,
            // initialize zebra striping and filter widgets
            widgets: ["filter"],
            // headers: { 5: { sorter: false, filter: false } },
            widgetOptions: {
                filter_columnFilters: false,
                // extra css class applied to the table row containing the filters & the inputs within that row
                filter_cssFilter: '',
                // If there are child rows in the table (rows with class name from "cssChildRow" option)
                // and this option is true and a match is found anywhere in the child row, then it will make that row
                // visible; default is false
                filter_childRows: false,
                // if true, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
                // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus
                filter_hideFilters: true,
                // Set this option to false to make the searches case sensitive
                filter_ignoreCase: true,
                // class added to filtered rows (rows that are not showing); needed by pager plugin
                filter_filteredRow: 'filtered',
                // jQuery selector string of an element used to reset the filters
                filter_reset: '.reset',
                // Delay in milliseconds before the filter widget starts searching; This option prevents searching for
                // every character while typing and should make searching large tables faster.
                filter_searchDelay: 300,
                // Set this option to true to use the filter to find text from the start of the column
                // So typing in "a" will find "albert" but not "frank", both have a's; default is false
                filter_startsWith: false,
                // if false, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
                // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus
                filter_hideFilters : true

            }

        });

        /* *********** FILTRI **********/
        $('#avanzamento').on('click', '#progress-filter ul a, .filter-by-dep > li > a', function () {
            var filter = $(this);
            var columns = [];
            filter.addClass('active').siblings('a.active').removeClass('active');
            setFilterProgress();
        });

        $('#avanzamento').on('click', '#progress-filter > li > a, #dep-filter > li > a', function () {
            var filter = $(this);
            filter.addClass('active').siblings('ul').find('a.active').removeClass('active');
            setFilterProgress();
        });

        $('#filter-by-pu > li > a').click(function () {
            var filter = $(this);
            filter.addClass('active').siblings('a.active').removeClass('active');
            $('.filter-by-dep a.active').removeClass('active');
            $('#filter-by-dep-of-' + filter.data('filter-text')).show().siblings('.filter-by-dep').hide();
            setFilterProgress();
        });

        $('#avanzamento .filter-category a.active').click();

        /* *********** VERIFICA SUPERAMENTO CORSO **********/
        $('#progress-table td.progress.completed').each(function (index, item) {
            $.post('manage/course.php',
                    {
                        op_type: "get_count_questions",
                        learning_event_id: $(item).parent().data("learning_event_id"),
                        learning_project_id: <?= $learning_project_id ?>
                    }, function (data) {
                var questions = JSON.parse(data);
                if (questions['correct'] / (parseInt(questions['correct']) + parseInt(questions['wrong'])) * 100 >= 70)
                    $(item).addClass('passed').children().addClass('text-success');
                else
                    $(item).addClass('failed').children().addClass('text-error');
            });
        });

        $('input[name="search"]').on('input propertychange', function () {
            var columns = [];
            columns [1] = $(this).val();
            $('#progress-table').trigger('search', [columns]);
        });

        /* ************** INVIA NOTIFICA ASSEGNAZIONE CORSO SINGOLA ****************** */
        $('#progress-table .action li:not(.disabled) .notify-course-assignment').click(function (e) {
            notifyCourseAssignment($(this).parents('tr').data('license_id'));
        });

        /* ************** INVIA NOTIFICA ASSEGNAZIONE CORSO MULTIPLI **************** */
        $('div.multi-action > button.notify-course-assignment').click(function (e) {
            if ($('#progress-table tr.selected ').length == 0)
                alert('Nessun utente selezionato');
            else if (confirm("Vuoi notificare l'assegnazione del corso agli utenti selezionati?")) {
                $.isLoading({text: "Attendere il completamento ..."});
                $('#progress-table tr.selected ').each(function () {
                    var execution = $(this).find('td.progress div.bar').text();
                    execution = execution != "" ? execution.match(/[0-9]+/).join('') : false;
                    if (execution !== false && execution < 100) {
                        $.post("manage/license.php", {op_type: "notify_course_assignment", 
                                                          license_id: $(this).data('license_id')});
                    }
                    ;
                }).promise().done(function(){
                    $.isLoading("hide");
                    alert("Notifiche inviate con successo.");
                });
            }
        });

        /* ************** INVIA ALERT MULTIPLI **************** */
        $('div.multi-action > button.send-alert').click(function (e) {
            if ($('#progress-table tr.selected ').length == 0)
                alert('Nessun utente selezionato');
            else if (confirm("Vuoi inviare gli alert agli utenti selezionati?")) {
                $.isLoading({text: "Attendere il completamento ..."});
                $('#progress-table tr.selected ').each(function () {
                    var execution = $(this).find('td.progress div.bar').text();
                    execution = execution != "" ? execution.match(/[0-9]+/).join('') : false;
                    if (execution !== false && execution < 100) {
                        $.post("manage/license.php", {op_type: "send_alert", 
                                                          license_id: $(this).data('license_id'), 
                                                          custom_message: ''});
                    }
                }).promise().done(function(){
                    $.isLoading("hide");
                    alert("Alert inviati con successo.");
                });
            }
        });

        /* ************** INVIA ALERT SINGOLO ****************** */
        $('#progress-table .action li:not(.disabled) .send-alert').click(function (e) {
            sendAlertCourse($(this).parents('tr').data('license_id'), '')
        });

        /* ************** INVIA CREDENZIALI MULTIPLI **************** */
        $('div.multi-action > button.notify-username').click(function (e) {
            if ($('#progress-table tr.selected ').length == 0)
                alert('Nessun utente selezionato');
            else if (confirm("Vuoi inviare le credenziali agli utenti selezionati?")) {
                $.isLoading({text: "Attendere il completamento ..."});
                $('#progress-table tr.selected ').each(function () {
                    $.post("manage/user.php", {op_type: "send_user_name", user_id: $(this).data('user_id')});
                }).promise().done(function(){
                    $.isLoading("hide");
                    alert("Credenziali inviate con successo.");
                });
            }
        });


        /* ************** INVIA CREDENZIALI SINGOLO ****************** */
        $('#progress-table .action li:not(.disabled) .notify-username').click(function (e) {
            sendUserName($(this).parents('tr').data('user_id'));
        });

        /* ************** MOSTRA ATTESTATO ****************** */
        $('#progress-table .action li:not(.disabled) .attestato').click(function (e) {
            if ($(this).hasClass('genera')) {
                e.preventDefault();
                var license_id = $(this).parents('tr').data('license_id');
                window.open('lib/genera.php?course_id=' + license_id, '_blank');
            }
        });

        /* ************** MOSTRA OVERLAY STATO ****************** */
        $('#progress-table .action li:not(.disabled) .stato').click(function (e) {
            var license_id = $(this).parents('tr').data('license_id');
            $('#mySimpleModal')
            .modal('show')
            .find('div.modal-header h3')
            .text('Stato corso')
            .parent()
            .next()
            .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
            .load('report/course-detail.php?upload_type=test_in_yhe_presence&learning_project_user_id=' + license_id);
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
        $('#progress-table .action li:not(.disabled) .schedule-license').click(function (e) {
            var license_id = $(this).parents('tr').data('license_id');
            $('#myModal').modal().find('.modal-content').load('modals/schedule-license.php?learning_project_user_id=' + license_id);
        });

    });
    
<?php if ($_SESSION['user']['role'] == 1000){?>

        /* ************** AVVIA IL CORSO ****************** */
        $('#progress-table .action .play-course').click(function () {
            var license_id = $(this).parents('tr').data('license_id');
            $('#myModal').modal().find('.modal-content').load('modals/avviacorso.php?learning_project_user_id=' + license_id);
        });

<?php } ?>
    
    
<?php if ($course_need_test_in_the_presence == true && $_SESSION['company']['test_in_the_presence'] !== "NO") {?>

    
    /* *********************** UPLOAD *************************/
    
    $('#progress-table').on('click','.test.upload',function(e){
        e.preventDefault();
        var license_id = $(this).parents('tr').data('license_id');
            $('#mySimpleModal').modal()
                    .find('div.modal-header h3')
                    .html('Carica file test fine corso')
                    .parent()
                    .next()
                    .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                    .load('modals/uploader.php?upload_type=test_in_the_presence&learning_project_user_id=' + license_id);
    });
        
    
<?php } ?>

</script>