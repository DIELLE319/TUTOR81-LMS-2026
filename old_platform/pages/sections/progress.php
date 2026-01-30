<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 01-lug-2015
 * File: pages/progress.php
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

//$is_tutor = filter_input(INPUT_GET, 'is_tutor',FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
$company_id = $_SESSION['company']['id'];

$learning_project_id = filter_input(INPUT_GET, 'learning_project_id', FILTER_SANITIZE_NUMBER_INT);
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
//$num_lo = $learn_obj->get_num_learning_objects($learning_project_id);
?>
<!-- ---- AVANZAMENTO ------ AVANZAMENTO ------ AVANZAMENTO ---- -->
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
    
    <h3 class="text-center">Corso <?= $learn_detail['title']?></h3>
        
    <table id="progress-table" class="table table-sorter row-selectable">
        <thead>
            <tr>
                <th class="{sorter: false, filter: false}"><input type="checkbox" class="select-visible tristate"></th>
                <th>Cognome Nome</th>

                <?php if($product_units) { ?>

                <th>Unità Produttiva</th>
                <th>Reparto</th>

                <?php } ?>

                <th style="width: 100px;">Data inzio</th>
                <th style="width: 100px;">Ultimo accesso</th>
                <th style="width: 100px;">Termine programmato</th>
                <th data-placeholder="Seleziona">Progresso</th>

            <?php if ($course_need_test_in_the_presence == true && $_SESSION['company']['test_in_the_presence'] !== "NO") {?>

                <th class="{sorter: false, filter: false}">Test</th>

            <?php } ?>

                <th class="{sorter: false, filter: false}">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            foreach ($user_assigned as $single) {
                $visible = false;
                $learning_project_user_id = $single['id'];
                /*
                $num_exe_lo = $learn_obj->get_num_lo_executed($learning_project_user_id);
                if ($num_exe_lo != 0) {
                    $execution_percentage = round($num_exe_lo / $num_lo * 100);
                } else {
                    $execution_percentage = 0;
                }
                 */

                $learning_event = $report_obj->getLearningEvent($learning_project_user_id);

                if (isset($learning_event['end_date_time']) && $learning_event['end_date_time'] != "0000-00-00 00:00:00") {
                    $completed = true;
                    $visible = false;
                } else {
                    $completed = false;
                    $visible = true;
                }

                /*if (($single['id'] == 4330 || $single['id'] == 4341 || $single['id'] == 3470 || $single['id'] == 2558 || $single['id'] == 4414 || $single['id'] == 4428 || $single['id'] == 3680 || $single['id'] == 3684)) {
                    $completed = true;
                    $execution_percentage = 100;
                }*/


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





                if ($visible) {?>






                <tr class="<?= $completed ? "completed" : "" ?>" 
                    data-license_id="<?= $single['id'] ?>" 
                    data-learning_event_id="<?= $learning_event['id'] ?>" 
                    data-user_id="<?= $single['user_id'] ?>" 
                    data-learning_project_user_id="<?= $learning_project_user_id ?>">
                    <td><input type="checkbox"></td>
                    <td class="student-name"><a href="company/home/employees?user_id=<?= $single['user_id'] ?>"><?= ucwords(strtolower("{$single['surname']} {$single['name']}")) ?></a></td>

                <?php if($product_units) { ?>

                    <td><?= $user_dep_detail ? $user_dep_detail[0]['short_desc_pu'] : '' ?></td>
                    <td><?= $user_dep_detail ? $user_dep_detail[0]['short_desc_dep_type'] : '' ?></td>

                <?php } ?>

                    <td><?= $start_date_time ?></td>
                    <td><?= $end_date_time ?></td>
                    <td><?= $finish_within ?></td>
                    <td class="progress-status<?= $completed == true ? ' completed' : '' ?>">
                        <?php
                        if ($completed == true) {

                            if (file_exists(BASE_MEDIA_PATH . "attestati/attestato_licenza_" . $single['id'] . ".pdf")) {
                                ?>

                                <a target="_blank" href="manage/render_document.php?doc_type=attestato_elearning&license_id=<?= $single['id'] ?>">SCARICA ATTESTATO</a>

                            <?php } else { ?>

                                <a target="_blank" href="lib/genera.php?course_id=<?= $single['id'] ?>.pdf">SCARICA ATTESTATO</a>

                                <?php 
                            }
                        } else {
                            ?>
                                
                            <img src="img/loading_gif.gif">
                            
                            <?php
                        }
                        ?>
                    </td>


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
                            data-original-title="Upload test in presenza" data-container="body" data-placement="left"
                            data-content="Carica sul server il Test in Presenza da te effettuato.">
                            <span class="glyphicon glyphicon-exclamation-sign"></span></a>

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
                                        href="javascript: void(0)">Invia avvia corso</a></li>

                                <li <?= !$completed ? '' : ' class="disabled"' ?>>
                                    <a class="send-alert" tabindex="-1" href="javascript: void(0)">Invia
                                        sollecito</a></li>

                                <!-- <li><a class="notify-username" tabindex="-1" href="javascript: void(0)">Invia
                                        credenziali</a></li> -->

                                <li class="divider"></li>

                            <?php if (!$completed && $_SESSION['user']['role'] == 1000){?>

                                <li>
                                    <a class="play-course" href="javascript: void()">Avvia corso</a>
                                </li>

                            <?php } ?>

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







                <?php }
            }
            ?>
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
<script>
    
<?php if ($_SESSION['user']['role'] == 1000){?>

        /* ************** AVVIA IL CORSO ****************** */
        $('#progress-table .action .play-course').click(function () {
            var license_id = $(this).parents('tr').data('license_id');
            $('#simpleModal .modal-content')
                    .empty()
                    .load('modals/avviacorso.php?learning_project_user_id=' + license_id)
                    .parents('#simpleModal')
                    .modal();
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

    $(function () {

        $('#progress-table').tablesorter({
            theme: 'greyT81',
            sortList: [[1, 0]],
            // hidden filter input/selects will resize the columns, so try to minimize the change
            widthFixed: true,
            // initialize zebra striping and filter widgets
            widgets: ["filter"],
            // headers: { 5: { sorter: false, filter: false } },
            dateFormat: "ddmmyyyy",
            widgetOptions: {
                //filter_columnFilters: false,
                // extra css class applied to the table row containing the filters & the inputs within that row
                filter_cssFilter: '',
                // If there are child rows in the table (rows with class name from "cssChildRow" option)
                // and this option is true and a match is found anywhere in the child row, then it will make that row
                // visible; default is false
                filter_childRows: false,
                // if true, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
                // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus
                filter_hideFilters: false,
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
                filter_functions: {
                    
                    <?= $product_units ? 7 : 5; ?>: {
                        "in corso": function (e, n, f, i, $r) {
                            return n > 0 && n <= 100;
                        },
                        "non avviati": function (e, n, f, i, $r) {
                            return e === "0%";
                        },
                        "completati": function (e, n, f, i, $r) {
                            return e === "SCARICA ATTESTATO";
                        }
                    }
                }

            }

        });
        
        
        $.post('manage/project.php', {
                op_type: "calc_progress_rate",
                learning_project_id: <?= $learning_project_id ?>,
                learning_project_users: $('#progress-table tbody tr:not(".completed")').map(function(){ return $(this).data('learning_project_user_id');}).get()
            }, function(data){
                try {
                    var result = $.parseJSON(data);
                    $.each(result, function(index, execution_percentage){
                        $('#progress-table tbody tr[data-learning_project_user_id="'+index+'"] td.progress-status')
                                .html('<div class="progress">'
                                            + '<div class="progress-bar progress-bar-striped ' + (execution_percentage == 0 ? ' progress-bar-danger" ' : '" ')  
                                                + 'aria-valuemin="0" aria-valuemax="100" '
                                                + 'style="width: ' + execution_percentage + '%; min-width: 2em;">'
                                                + execution_percentage
                                            + '</div>'
                                        + '</div>');
                    });
                } catch (err){
                    //alert('error: ' + err.message);
                }
            });
            
    
        /* ******** SELEZIONE DI TUTTI GLI UTENTI VISIBILI E GESTIONE TRISTATE  ******* */
        $('#progress-table th input.select-visible')
            .tristate({
                change: function(state, value){
                        if (state === null || state === true) $('#progress-container .action-selected').show();
                        else  $('#progress-container .action-selected').hide();
                    }
                })
            .on('click', function (e) {
                if ($(this).tristate('state') === true)
                    $('#progress-table tbody > tr:visible input[type="checkbox"]:not(:checked)').click();
                else if ($(this).tristate('state') === false) {
                    if ($('#progress-table tbody > tr:visible input[type="checkbox"]:checked').length == $('#progress-table tbody > tr:visible input[type="checkbox"]').length)
                        $('#progress-table tbody > tr:visible input[type="checkbox"]:checked').click();
                    else
                        $('#progress-table tbody > tr:visible input[type="checkbox"]:not(:checked)').click();
                }
                else
                    $('#progress-table tbody > tr:visible input[type="checkbox"]:checked').click();
        });

        $('#progress-table tbody > tr input[type="checkbox"]').click(function (e) {
            var checked = $('#progress-table tbody > tr input[type="checkbox"]:checked');
            if ($('#progress-table tbody > tr input[type="checkbox"]').length > checked.length) {
                if (checked.length == 0) {
                    $('#progress-table th input.select-visible').tristate('state', false);
                    $('#progress-table tbody > tr .quick-subscription a').show();
                } else {
                    $('#progress-table th input.select-visible').tristate('state', null);
                }
            } else {
                $('#progress-table th input.select-visible').tristate('state', true);
            }
        });

        /* *********** VERIFICA SUPERAMENTO CORSO **********/
        /* Non più utilizzato perchè tutti i link ora sono neri
        $('#progress-table td.progress-status.completed').each(function (index, item) {
            $.post('manage/course.php',
                    {
                        op_type: "get_count_questions",
                        learning_event_id: $(item).parent().data("learning_event_id"),
                        learning_project_id: $learning_project_id
                    }, function (data) {
                var questions = JSON.parse(data);
                if (questions['correct'] / (parseInt(questions['correct']) + parseInt(questions['wrong'])) * 100 >= 70)
                    $(item).addClass('passed').children().addClass('text-success');
                else
                    $(item).addClass('failed').children().addClass('text-error');
            });
        });
        */
/*
        $('input[name="search"]').on('input propertychange', function () {
            var columns = [];
            columns [1] = $(this).val();
            $('#progress-table').trigger('search', [columns]);
        });
*/
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
                    var execution = $(this).find('td.progress-status div.progress-bar').text();
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
                    var execution = $(this).find('td.progress-status div.progress-bar').text();
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
            if (confirm("Vuoi inviare un sollecito al corsista selezionato?"))
                sendAlertCourse($(this).parents('tr').data('license_id'), '');
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
        
        
        /* ***** ABILITA BOTTONI ESPORTAZIONE ***** */
        $('#progress .panel-heading .export-pdf').removeClass('disabled').click(function(){
            location.href = $('#progress-container .export-pdf').attr('href');
        });
        $('#progress .panel-heading .export-xls').removeClass('disabled').click(function(){
            location.href = $('#progress-container .export-xls').attr('href');
        });
    });

</script>