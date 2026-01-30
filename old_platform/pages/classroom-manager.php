<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 14-lug-2015
 * File: pages/classroom-manager.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_classroom.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';

$classroom_obj = new T81Classroom();
$comp_obj = new T81Company ();
$course_obj = new iWDCourse();
$course_type_obj = new T81CourseType();

$id_course_type = filter_input(INPUT_GET, 'id_course_type', FILTER_SANITIZE_NUMBER_INT);

$course_for_menu = 'classroom';
$classroom_scheduled = $classroom_obj->getClassroomsScheduled(array('tutor_id' => $_SESSION['tutor']['id']));
$prov = $comp_obj->getProvinces();
?>
<div id="classroom-manager" class="container-fluid">
    <div class="row">
<!--        <div class="col-sm-3 col-fixed col-menu">-->

<!--            --><?php //require BASE_ROOT_PATH . 'pages/sections/courses-menu.php'; ?>
            
            <!-- <div class="form-group form-group-lg">
                <a href="" class="btn btn-primary btn-block btn-lg" style="white-space: pre-wrap;"><span class="glyphicon glyphicon-calendar"></span><br>CALENDARIO CORSI TUTORITALIA</a>
            </div> -->
<!--        </div>-->
        <div class="col-md-12">
            <h3 class="text-center">LA TUA FORMAZIONE IN AULA: <strong><?= strtoupper($_SESSION['tutor']['business_name']) ?></strong></h3>
            <!-- <div class="title-section grayT81">
                <h1 class="title">Benvenuto!</h1>
                <div class="subtitle">
                    <h4>Qui puoi creare il catalogo della formazione in aula e pubblicarlo in TutorItalia</h4>
                </div>
            </div> -->
            
            <?php require_once 'pages/sections/classroom-booking.php'; ?>
            
            <div id="classroom-planner" class="panel panel-success" <?= isset($id_course_type) ? '' : 'style="display: none;"' ?>>
                <div class="planner-header panel-heading">
                    Pianifica le edizioni del corso <span class="title"></span>
                </div>
                
                <div id="courses-container">
                    <dl class="dl-horizontal course-detail" data-course_id="">
                        <dt>Descrizione</dt>
                        <dd class="description"></dd>
                        <dt>Tipo</dt>
                        <dd class="type"></dd>
                        <dt>Durata</dt>
                        <dd class="duration"></dd>
                    </dl>
                </div>
            
                <div id="classroom-container" class="panel-body">

                    <table id="new-classroom" class="table table-condensed tablesorter-greyT81 table-nobordered">
                        <thead>
                            <tr>
                                <th style="width: 85px;">Data</th>
                                <th>Sede</th>
                                <th style="width: 180px;">Provincia</th>
                                <th style="width: 50px;">Posti</th>
                                <th>email</th>
                                <th style="width: 140px;">prezzo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="form-group">
                                    <input type="text" class="form-control new-classroom-start_date input-date" placeholder="Data">
                                </td>
                                <td class="form-group">
                                    <input type="text" class="form-control new-classroom-location" placeholder="Sede">
                                </td>
                                <td class="form-group">
                                    <select class="form-control new-classroom-province_id">
                                        <option value="">Seleziona una provincia</option>

                                        <?php foreach ($prov as $single) { ?>

                                            <option value="<?= $single['id'] ?>"><?= strtoupper($single['name']) ?></option>

                                        <?php } ?>

                                    </select>
                                </td>
                                <td class="form-group">
                                    <input type="number" class="form-control new-classroom-places" placeholder="Disponibilità" min="1" value="1">
                                </td>
                                <td class="form-group">
                                    <input type="email" class="form-control new-classroom-email" placeholder="email" value="<?= $_SESSION['user']['email']?>">
                                </td>
                                <td class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-addon">€</div>
                                        <input type="number" class="form-control new-classroom-price" placeholder="prezzo" min="0" value="0">
                                        <div class="input-group-addon">.00</div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="form-group" colspan="6">
                                    <textarea class="form-control new-classroom-note" placeholder="note" rows="10" maxlength="1024" style="height: 100px; text-align: left;"></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="text-right">
                        <button class="btn btn-primary publish">Pubblica su TutorItalia</button>
                    </div>
                </div>
            </div>

            <div id="classroom-scheduled" class="panel panel-success">
                <div class="panel-heading">
                    I tuoi corsi di formazione
                </div>

                <?php if (!$classroom_scheduled) { ?>

                    <h4>Non ci sono corsi di formazione programmati</h4>

                    <?php
                } else {

                    $prov = $comp_obj->getProvinces();
                    $mesi = array();
                    $mesi[1] = 'Gennaio';
                    $mesi[2] = 'Febbraio';
                    $mesi[3] = 'Marzo';
                    $mesi[4] = 'Aprile';
                    $mesi[5] = 'Maggio';
                    $mesi[6] = 'Gugno';
                    $mesi[7] = 'Luglio';
                    $mesi[8] = 'Agosto';
                    $mesi[9] = 'Settembre';
                    $mesi[10] = 'Ottobre';
                    $mesi[11] = 'Novembre';
                    $mesi[12] = 'Dicembre';
                    ?>

                    <table id="classroom-scheduled-table" class="table table-condensed tablesorter">
                        <thead>
                            <tr>
                                <th>Provincia</th>
                                <th>Titolo Corso</th>
                                <th>Mese</th>
                                <th data-sorter="shortDate" data-date-format="ddmmyyyy">Data</th>
                                <th>Posti</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            foreach ($classroom_scheduled as $classroom) {
                                if (!$classroom['places_available'])
                                    continue;
                                $start_time = new DateTime($classroom['start_time']);
                                $end_time = new DateTime($classroom['end_time']);
                                if ($classroom['start_date'] != '0000-00-00') {
                                    $start_date = new DateTime($classroom['start_date']);
                                    $start_date = $start_date->format('d/m/Y');
                                } else {
                                    $anno = new DateTime('now');
                                    $start_date = "01/{$classroom['month']}/" . $anno->format('Y');
                                    ;
                                }
                                ?>

                                <tr data-id_classroom_scheduled="<?= $classroom['id_classroom_scheduled'] ?>"
                                    data-start_time="<?= $start_time->format('H:i') ?>"
                                    data-end_time="<?= $end_time->format('H:i') ?>"
                                    data-location="<?= $classroom['location'] ?>">
                                    <td><?= $classroom['province'] ?></td>
                                    <td class="course_code" data-toggle="tooltip" data-html="true" title="<?= nl2br(html_entity_decode($classroom['note'])) ?>">
                                        <?= $classroom['course_code'] ?>
                                    </td>
                                    <td class=""><?= $mesi[$classroom['month']] ?></td>
                                    <td class="start_date" <?= $classroom['start_date'] == '0000-00-00' ? ' style="visibility:hidden;"' : '' ?>>
        <?= $start_date ?>
                                    </td>
                                    <td class="places"><?= $classroom['places_available'] ?>/<?= $classroom['places'] ?></td>
                                    <td>
                                        <div class="btn-group action">
                                            <button class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                                            <ul class="dropdown-menu pull-right">
                                                <!-- dropdown menu links -->

                                                <li>

        <?php if ($classroom['published']) { ?>

                                                        <a class="classroom-hide" tabindex="-1" href="javascript: void(0)">Ritira da piattaforma</a>

        <?php } else { ?>

                                                        <a class="classroom-publish" tabindex="-1" href="javascript: void(0)">Pubblica in piattaforma</a>

                                                    <?php } ?>

                                                </li>
                                                <li>

        <?php if ($classroom['published_in_ecommerce']) { ?>

                                                        <a class="classroom-hide_in_ecommerce" tabindex="-1" href="javascript: void(0)">Ritira da ecommerce</a>

        <?php } else { ?>

                                                        <a class="classroom-publish_in_ecommerce" tabindex="-1" href="javascript: void(0)">Pubblica in ecommerce</a>

        <?php } ?>

                                                </li>
                                                <li<?= $classroom['places_available'] != $classroom['places'] ? ' class="disabled"' : ''; ?>>
                                                    <a class="classroom-delete" tabindex="-1" href="javascript: void(0)">Elimina</a>
                                                </li>

                                            </ul>
                                        </div>
                                    </td>
                                </tr>            

                    <?php } ?>            

                        </tbody>
                    </table>

<?php } ?>

            </div>
            
            <div id="classrooom-calendar" class="panel panel-success">
                <div class="panel-heading">
                    <span class="glyphicon glyphicon-calendar"></span> 
                    TUTORITALIA - calendario formazione in aula
                </div>
                
                <?php require_once 'pages/sections/classroom-calendar.php'; ?>
                
            </div>
            
        </div>
    </div>
</div><!-- /#classroom-manager -->
<script>
    $(function () {
        
        function toNewClassroom(id_course_type){
            $.post('manage/course_type.php', 
                {
                    op_type: 'get_course_detail',
                    id_course_type: id_course_type
                }, 
                function(data){
                    if (data != 0){
                        var course = JSON.parse(data);
                        $('#classroom-planner .panel-heading .title').text(course.course_code);
                        $('#classroom-planner .course-detail ').data('course_id', course.id_course_type);
                        $('#classroom-planner .course-detail .description').text(course.course_description);
                        $('#classroom-planner .course-detail .type').text(course.type);
                        $('#classroom-planner .course-detail .duration').text(course.duration + ' ore');
                    }
                    $('#classroom-planner').show();
                }
            );
            history.pushState({}, '', 'tutor/classroom/new?id_course_type=' + id_course_type);
        }
        
        $('#accordion-courses ul.courses > li > a').click(function () {
            //toNewClassroom($(this).data("id_course_type"));
            var id_course_type = $(this).data("id_course_type");
            $('#simpleModal')
                    .modal()
                    .find('.modal-content')
                    .load('modals/new-classroom.php?id_course_type=' + id_course_type);
        });
    
            
    <?php if (isset($id_course_type)){ ?>

        selectMenuCourse($('#accordion-courses ul.courses > li > a[data-id_course_type="<?= $id_course_type ?>"]'));
        //toNewClassroom(<?= $id_course_type ?>);
        $('#simpleModal')
                    .modal()
                    .find('.modal-content')
                    .load('modals/new-classroom.php');

    <?php } ?>
        
        /* ***** INIZIALIZZAZIONE CAMPO DATA ***** */
        $('#new-classroom .input-date').datepicker({
            format: "dd/mm/yyyy",
            startDate: '0d',
            todayBtn: "linked",
            language: "it",
            autoclose: true,
            todayHighlight: true
        });

        /* ***** PUBBLICA AULE ***** */
        $('.publish').click(function () {
            $.isLoading({text: "Attendere il completamento ..."});
            var course_type_id = $('#classroom-planner .course-detail ').data('course_id');
            var is_valid = true;
            var planned_date = $('.new-classroom-start_date').datepicker('getDate');
            var new_classroom = {
                'month': planned_date.getMonth(),
                'location': $('.new-classroom-location').val(),
                'province_id': $('.new-classroom-province_id').val(),
                'places': $('.new-classroom-places').val(),
                'email': $('.new-classroom-email').val(),
                'price': $('.new-classroom-price').val()
            };
            for (var key in new_classroom) {
                if (!new_classroom[key]) {
                    is_valid = false;
                    break;
                }
            }
            if (!is_valid) {
                $.isLoading("hide");
                alert('Compilare tutti i campi.');
                return false;
            }
            new_classroom['month'] += 1;
            new_classroom['start_date'] = planned_date.getFullYear() + '-' + new_classroom['month'] + '-' + planned_date.getDate();
            new_classroom['course_type_id'] = course_type_id;
            new_classroom['created_by'] = <?= $_SESSION['user']['id'] ?>;
            new_classroom['tutor_id'] = <?= $_SESSION['tutor']['id'] ?>;
            new_classroom['published'] = 1;
            new_classroom['published_in_ecommerce'] = 1;
            new_classroom['note'] = $('.new-classroom-note').val();
            $.post('manage/classroom.php',
                {
                    op_type: 'new_classroom',
                    new_classroom: new_classroom
                },
                function (data) {
                    $.isLoading("hide");
                    if (data > 0) {
                        alert('Programmazione corso completata.');
                        location.href = "tutor/classroom/manager";
                    } else {
                        alert('Errore: la programmazione non è stata salvata. Verifica i dati e riprova.')
                    }
                }
            );
        });

        $('#classroom-scheduled-table').tablesorter({
            theme: 'greyT81',
            sortList: [[2, 1]],
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

        /* ****** NASCONDE L'AULA IN PIATTAFORMA ****** */
        $('#classroom-scheduled .action .classroom-hide').click(function () {
            var id_classroom_scheduled = $(this).parents('tr').data('id_classroom_scheduled');
            $.post('manage/classroom.php',
                    {
                        op_type: 'set_published_state_classroom_scheduled',
                        id_classroom_scheduled: id_classroom_scheduled,
                        published: 0
                    }, function (data) {
                if (data > 0)
                    location.reload();
                else
                    alert("Errore. Non è possibile nascondere l'aula.");
            }
            );
        });

        /* ****** PUBBLICA L'AULA IN PIATTAFORMA ****** */
        $('#classroom-scheduled .action .classroom-publish').click(function () {
            var id_classroom_scheduled = $(this).parents('tr').data('id_classroom_scheduled');
            $.post('manage/classroom.php',
                    {
                        op_type: 'set_published_state_classroom_scheduled',
                        id_classroom_scheduled: id_classroom_scheduled,
                        published: 1
                    }, function (data) {
                if (data > 0)
                    location.reload();
                else
                    alert("Errore. Non è possibile pubblicare l'aula.");
            }
            );
        });

        /* ****** NASCONDE L'AULA IN ECOMMERCE ****** */
        $('#classroom-scheduled .action .classroom-hide_in_ecommerce').click(function () {
            var id_classroom_scheduled = $(this).parents('tr').data('id_classroom_scheduled');
            $.post('manage/classroom.php',
                    {
                        op_type: 'set_published_in_ecommerce_state_classroom_scheduled',
                        id_classroom_scheduled: id_classroom_scheduled,
                        published_in_ecommerce: 0
                    }, function (data) {
                if (data > 0)
                    location.reload();
                else
                    alert("Errore. Non è possibile nascondere l'aula.");
            }
            );
        });

        /* ****** PUBBLICA L'AULA IN ECOMMERCE ****** */
        $('#classroom-scheduled .action .classroom-publish_in_ecommerce').click(function () {
            var id_classroom_scheduled = $(this).parents('tr').data('id_classroom_scheduled');
            $.post('manage/classroom.php',
                    {
                        op_type: 'set_published_in_ecommerce_state_classroom_scheduled',
                        id_classroom_scheduled: id_classroom_scheduled,
                        published_in_ecommerce: 1
                    }, function (data) {
                if (data > 0)
                    location.reload();
                else
                    alert("Errore. Non è possibile pubblicare l'aula.");
            }
            );
        });

        /* ****** ELIMINA L'AULA ****** */
        $('#classroom-scheduled .action li:not(.disabled) .classroom-delete').click(function () {
            if (confirm("Vuoi eliminare l'aula?")) {
                var id_classroom_scheduled = $(this).parents('tr').data('id_classroom_scheduled');
                $.post('manage/classroom.php',
                        {
                            op_type: 'delete_classroom_scheduled',
                            id_classroom_scheduled: id_classroom_scheduled
                        }, function (data) {
                    if (data > 0)
                        location.reload();
                    else
                        alert("Errore. Non è possibile eliminare l'aula.");
                }
                );
            }
        });
    });

</script>