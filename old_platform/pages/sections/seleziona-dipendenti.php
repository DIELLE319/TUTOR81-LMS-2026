<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 8-lug-2015
 * File: pages/sections/seleziona-dipendenti.php
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

require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

$comp_obj = new T81Company();
$learn_obj = new T81LearningProject();

$selected_learning_project = filter_input(INPUT_POST, 'courses', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
$selected_users = filter_input(INPUT_POST, 'users', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY) ? : array();

$company_id = $_SESSION['company']['id'];
$courses = array();

$max_execution_time = 180; 
foreach ($selected_learning_project as $learning_project_id) {
    $courses[$learning_project_id] = $learn_obj->getCourseDetailFromLearningProject($learning_project_id);
    $max_execution_time = $max_execution_time <= $courses[$learning_project_id]['max_execution_time'] ? $max_execution_time : $courses[$learning_project_id]['max_execution_time'];
}

$company = $comp_obj->getCompanyByID($company_id);

$tutor_id = $company['owner_user_id'];

$users_free = $comp_obj->getUsersFree($company_id, $selected_learning_project);
?>
<div id="multiple-assignments">
    
    <div class="button-line">
        <button class="btn btn-info" data-toggle="modal" 
           data-target="#import_employees">Importa nuovi dipendenti da un file</button>
        <button class="btn btn-info createEmployeeModal" >Crea un nuovo dipendente</button>
    </div>
    
    <div class="nav-assignments-category nav-filter">
        <div class="pull-right form-inline">
            <div class="form-group input-daterange" style="margin: 0 17px 10px 0;">
                modifica tutte le date
                <div class="input-group">
                    <input type="text" class="form-control input-sm input-mini" name="start-all">
                    <div class="input-group-addon input-sm input-mini"><span class="glyphicon glyphicon-arrow-right"></span></div>
                    <input type="text" class="form-control input-sm input-mini" name="end-all">
                </div>
            </div>

            <div>
                modifica tutti gli alert <input type="number" min="1" max="99" class="form-control input-sm input-mini" name="alert-all" /> gg	
            </div>
        </div>
        <div class="pull-left">

            <ul class="filter-button filter-category nav nav-pills pull-left">
                <li>
                    <a class="active" href="javascript: void(0)" data-filter-column="3" data-filter-text="">Elenco</a>
                    <ul class="filter-button filter-category nav nav-pills">
                        <li class="filter-arrow">
                            <i class="icon-arrow-right"></i>
                        </li>
                        <li>
                            <a href="javascript: void(0)" data-filter-column="3" data-filter-text="lavoratore">lavoratori</a>
                            <a href="javascript: void(0)" data-filter-column="3" data-filter-text="preposto">preposti</a>
                            <a href="javascript: void(0)" data-filter-column="3" data-filter-text="dirigente">dirigenti</a>
                        </li>
                    </ul>
                </li>
            </ul>

            <form class="form-inline pull-right">
            <div class="form-group search hidden-print">
                <div class="input-group">
                    <input type="text" class="form-control search-query" name="search" placeholder="Cerca...">
                    <div class="input-group-addon"><span class="glyphicon glyphicon-search"></span></div>
                </div>
            </div>
            </form>
        </div>
    </div>

    <table id="enrollment-table" class="table table-condensed table-sorter row-selectable form-inline" data-tutor_id="<?= $tutor_id ?>">
        <thead>
            <tr>
                <th class="{sorter: false}"><input type="checkbox" class="select-visible tristate"></th>
                <th>Cognome Nome</th>
                <th>Email</th>
                <th>Funzione</th>
                <th class="{sorter: false}">
                    Data Inizio - Data Termine
                </th>
                <th class="{sorter: false}">
                    Alert scadenza
                </th>
            </tr>
        </thead>
        <tbody>

            <?php foreach ($users_free as $single) { ?>

                <tr data-user_id="<?= $single['id'] ?>"<?= in_array($single['id'], $selected_users) ? ' class="selected"' : ""?>>
                    <td class="{sorter: false}"><input type="checkbox" <?= in_array($single['id'], $selected_users) ? ' checked' : ""?>></td>
                    <td><?= ucwords(strtolower("{$single['surname']} {$single['name']}")) ?></td>
                    <td><?= strtolower($single['email'])?></td>
                    <td><?= strtolower($single['business_function_name']) ?></td>
                    <td>
                        <div class="form-group input-daterange">
                             <div class="input-group">
                                <input type="text" class="form-control input-sm input-mini" name="start" />
                                <div class="input-group-addon input-sm input-mini"><span class="glyphicon glyphicon-arrow-right"></span></div>
                                <input type="text" class="form-control input-sm input-mini" name="end" />
                             </div>
                        </div>
                    </td>
                    <td><input type="number" min="1" max="99" class="form-control input-sm input-mini" name="alert"> gg</td>
                </tr>

            <?php } ?>

        </tbody>
    </table>	

</div>
<!-- ---- MODALS ---- -->


<!-- ---------------------------------------------------------------------------------------------------------- -->
<!-- MODAL IMPORTA FILE -- MODAL IMPORTA FILE -- MODAL IMPORTA FILE -- MODAL IMPORTA FILE -- MODAL IMPORTA FILE -->
<!-- ---------------------------------------------------------------------------------------------------------- -->
<div id="import_employees" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="importEmployeesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close clear_modal" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="importEmployeesLabel">Importa lista utenti su file</h3>
            </div>
            <div class="modal-body" style="overflow: hidden;">

                <div id="upload-header">
                    <h3>
                        <a href="javascript: void(0)" id="pickfiles_excel">Importa un file
                            <small>di Excel (xls, xlsx), Open document (ods) o csv (campi separati con ; ) contenente la lista dei nuovi utenti che vuoi creare.</small>
                        </a>
                    </h3>

                    <span class="btn" id="confirm_upload" style="float: left; font-weight: normal;  display: none; color:#000000; font-size:12px; width:370px; margin-right: 5px"></span>

                    <div id="xls_model" style="float: right;"><h3>Scarica un modello</h3>

                        <table class="table">
                            <tbody>
                                <tr>
                                    <td style="vertical-align: middle;">
                                        <h4>Importazione semplice<br><small><em>nome,cognome,codice fiscale,email</em></small></h4>
                                    </td>
                                    <td>
                                        <a href="download/nuovi_utenti.xlsx" target="_blank"><img src="img/xlsx.png"> Excel 2007 (xlsx)</a><br>
                                        <a href="download/nuovi_utenti.xls" target="_blank"><img src="img/xls.png"> Excel 1997-2003 (xls)</a><br>
                                        <a href="download/nuovi_utenti.ods" target="_blank"><img src="img/ods.png"> Open document (ods)</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="vertical-align: middle;">
                                        <h4>Importazione completa<br><small><em>nome,cognome,codice fiscale,email,<br>
                                            unità produttiva,reparto,data assunzione</em></small></h4>
                                    </td>
                                    <td>
                                        <a href="download/nuovi_utenti_reparti.xlsx" target="_blank"><img src="img/xlsx.png"> Excel 2007 (xlsx)</a><br>
                                        <a href="download/nuovi_utenti_reparti.xls" target="_blank"><img src="img/xls.png"> Excel 1997-2003 (xls)</a><br>
                                        <a href="download/nuovi_utenti_reparti.ods" target="_blank"><img src="img/ods.png"> Open document (ods)</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="upload_container_excel">
                    <div id="filelist_excel">

                    </div>
                </div>


                <!-- ---- MESSAGE ------ MESSAGE ------ MESSAGE ---- -->
                <div id="content_message" style="display:none">

                </div>
                <!-- -- END MESSAGE -- END MESSAGE -- END MESSAGE -- -->


                <div id="control" style="display:none">

                    <!-- ---- FUNZIONI ------ FUNZIONI ------ FUNZIONI ---- -->
                    <div class="control-function">
                        <fieldset>
                            <legend>Seleziona una funzione</legend>
                            <label class="radio-inline"><input type="radio" name="funzione" value="1" checked/> Lavoratore &nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="radio-inline"><input type="radio" name="funzione" value="3" /> Preposto &nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="radio-inline"><input type="radio" name="funzione" value="7" /> Dirigente</label>
                        </fieldset>
                    </div>
                    <!-- -- END FUNZIONI -- END FUNZIONI -- END FUNZIONI -- -->

                    <!-- ---- OPZIONI ------ OPZIONI ------ OPZIONI ---- -->
                    <div class="control-send-email">
                        <fieldset>
                            <legend>Invio email di registrazione?</legend>
                            <label class="radio-inline"><input type="radio" name="send-mail" value="true" checked> Si, invia immediatamente &nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="radio-inline"><input type="radio" name="send-mail" value="false"> No, non inviare</label>
                        </fieldset>
                    </div>
                    <!-- -- END OPZIONI -- END OPZIONI -- END OPZIONI -- -->

                    <div class="clearfix"></div>
                </div>

                <!-- ---- USERS ------ USERS ------ USERS ---- -->
                <div id="control-users" style="display:none; overflow:auto; max-height: 255px;">
                    <table class="table table-stripped">
                        <thead>
                            <tr>
                                <th>Nome*</th>
                                <th>Cognome*</th>
                                <th>Codice Fiscale*</th>
                                <th>Email*</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <!-- ---- USERS ------ USERS ------ USERS ---- -->


            </div>
            <div class="modal-footer">
                <button id="create_from_xls" class="btn btn-primary" style="display:none">Crea dipendenti</button>
                <button id="close_from_xls" class="btn btn-default clear_modal" data-dismiss="modal" aria-hidden="true">Chiudi</button>
            </div>
        </div>
    </div>
</div>




<!-- ------------------------------------------------------------------------------------------------------------------------- -->
<!-- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -->
<!-- ------------------------------------------------------------------------------------------------------------------------- -->
<div id="createEmployeeModal" class="modal fade" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        </div>
    </div>
    
</div>

<!-- ---- END MODALS ---- -->
<script>
    $(function () {

        $('#enrollment-table').tablesorter({
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

        $('#multiple-assignments .filter-category a[data-filter-column]').click(function () {
            var filter = $(this);
            $('#multiple-assignments .filter-category a[data-filter-column]').siblings('.active').removeClass('active').promise().done(function () {
                filter.addClass('active');
                var columns = [];
                if (filter.data("filter-text") != "") {
                    columns[filter.data("filter-column")] = filter.data("filter-text");
                    $('#enrollment-table').trigger('search', [columns]);
                } else {
                    $('#enrollment-table').trigger('filterReset');
                }
            });
            return false;
        });

        /* ******** SELEZIONE DI TUTTI GLI UTENTI VISIBILI E GESTIONE TRISTATE  ******* */
        $('#enrollment-table th input.select-visible').tristate({
            change: function(state, value){
                    if (state === null || state === true) $('#step-button > li.current').next().removeClass('disabled');
                    else  $('#step-button > li.current').next().addClass('disabled');
                }
            }).on('click', function (e) {
            if ($(this).tristate('state') === true)
                $('#enrollment-table tbody > tr:visible input[type="checkbox"]:not(:checked)').click();
            else if ($(this).tristate('state') === false) {
                if ($('#enrollment-table tbody > tr:visible input[type="checkbox"]:checked').length == $('#enrollment-table tbody > tr:visible input[type="checkbox"]').length)
                    $('#enrollment-table tbody > tr:visible input[type="checkbox"]:checked').click();
                else
                    $('#enrollment-table tbody > tr:visible input[type="checkbox"]:not(:checked)').click();
            }
            else
                $('#enrollment-table tbody > tr:visible input[type="checkbox"]:checked').click();
        });

        $('#enrollment-table tbody > tr input[type="checkbox"]').click(function (e) {
            if ($('#enrollment-table tbody > tr input[type="checkbox"]').length > $('#enrollment-table tbody > tr input[type="checkbox"]:checked').length) {
                if ($('#enrollment-table tbody > tr input[type="checkbox"]:checked').length == 0)
                    $('#enrollment-table th input.select-visible').tristate('state', false);
                else
                    $('#enrollment-table th input.select-visible').tristate('state', null);
            } else {
                $('#enrollment-table th input.select-visible').tristate('state', true);
            }
        });

        var selected = $('#enrollment-table tbody > tr.selected').length;
        if (selected > 0) {
            $('#step-button a.step-button[data-step="3"]').removeClass('step-disabled');
            if (selected < <?= count($users_free) ?>) $('#enrollment-table th input.select-visible').tristate('state', null);
            else $('#enrollment-table th input.select-visible').tristate('state', true); 
        }

        /* ********* INIZIALIZZAZIONE E GESTIONE CALENDARI  ******** */
        var now = new Date();
        var end_date = new Date();
        end_date.setDate(end_date.getDate() + <?= $max_execution_time ?>);

        $('#multiple-assignments .input-daterange input').datepicker({
            format: "dd/mm/yyyy",
            startDate: '0d',
            endDate: '+<?= $max_execution_time ?>d',
            todayBtn: "linked",
            language: "it",
            autoclose: true,
            todayHighlight: true
        }).on('show', function (e) {
            $('#multiple-assignments .datepicker.dropdown-menu').css('z-index', '10000');
        }).on('hide', function (e) {
            $('#multiple-assignments .datepicker.dropdown-menu').css('z-index', '1000');
        });

        $('#multiple-assignments .input-daterange input[name="start"]').datepicker('update', $.datepicker.formatDate('dd-mm-yy', now)).on('changeDate', function (e) {
            $(this).datepicker('hide');
            var startDate = $(this).datepicker('getDate');
            var new_end_date = new Date();
            new_end_date.setDate(startDate.getDate() + <?= $max_execution_time ?>);
            $(this).next().next().datepicker('setStartDate', startDate);
            $(this).next().next().datepicker('setEndDate', new_end_date);
        });

        $('#multiple-assignments .input-daterange input[name="end"]').datepicker('update', $.datepicker.formatDate('dd-mm-yy', end_date)).on('changeDate', function (e) {
            $(this).datepicker('hide');
            var endDate = $(this).datepicker('getDate');
            $(this).prev().prev().datepicker('setEndDate', endDate);
        });

        $('#multiple-assignments .input-daterange input[name="start-all"]').datepicker('update', $.datepicker.formatDate('dd-mm-yy', now)).on('changeDate', function (e) {
            $(this).datepicker('hide');
            var startDate = $(this).datepicker('getDate');
            var new_end_date = new Date();
            new_end_date.setDate(startDate.getDate() + <?= $max_execution_time ?>);
            $('#multiple-assignments .input-daterange input[name="end-all"]').datepicker('setEndDate', new_end_date);
            $('#multiple-assignments .input-daterange input[name="end"]').datepicker('setEndDate', new_end_date);
            $(this).next().next().datepicker('setStartDate', startDate);
            $('#multiple-assignments .input-daterange input[name="start"]').datepicker('setStartDate', startDate);
            $('#multiple-assignments .input-daterange input[name="start"]').datepicker('update', $.datepicker.formatDate('dd-mm-yy', startDate));
            $('#multiple-assignments .input-daterange input[name="end"]').datepicker('setStartDate', startDate);
        });

        $('#multiple-assignments .input-daterange input[name="end-all"]').datepicker('update', $.datepicker.formatDate('dd-mm-yy', end_date)).on('changeDate', function (e) {
            $(this).datepicker('hide');
            var endDate = $(this).datepicker('getDate');
            $('#multiple-assignments .input-daterange input[name="end"]').datepicker('update', $.datepicker.formatDate('dd-mm-yy', endDate));
            $(this).prev().prev().datepicker('setEndDate', endDate);
            $('#multiple-assignments .input-daterange input[name="start"]').datepicker('setEndDate', endDate);
        });

        $('#multiple-assignments input[name|="alert"]').val('15');

        $('#multiple-assignments input[name="alert-all"]').on('change', function (e) {
            var alert = $(this).val();
            $('input[name="alert"]').val(alert);
        });

        $('#multiple-assignments input[name="search"]').on('input propertychange', function () {
            var columns = [];
            columns [1] = $(this).val();
            $('#enrollment-table').trigger('search', [columns]);
        });
        
        /* ******** MODAL CREA NUOVO UTENTE ********** */
        $('.createEmployeeModal').click(function(){
           $('#createEmployeeModal').modal().find('.modal-content').load('modals/new-employee.php');
        });
        
        /* ******** SALVA CREA NUOVO UTENTE ********** */
        $('#createEmployeeModal').on('click', '.save-modal', function(){
            $.isLoading({text: "Attendere ..."});
            var user = newUser(<?= $company_id ?>, <?= $_SESSION['user']['id'] ?>);
            if (user) {
                $('#createEmployeeModal').removeClass('fade').modal('hide'); // rimuove la classe fade per eliminare l'animazione che si blocca
                alert("Dipendente creato correttamente");
                var users = $('#enrollment-table tbody > tr.selected').map(function(){return $(this).data('user_id');}).get();
                users.push(user);
                subscribeStep2(users);
            }
            $.isLoading("hide");
        });

        /* ******** SALVA NUOVI UTENTI MULTIPLI ********** */
        $('#create_from_xls').click(function () {
            $.isLoading({text: "Attendere ..."});
            var users = $('#enrollment-table tbody > tr.selected').map(function(){return $(this).data('user_id');}).get();
            var users_added = createUsers(<?= $_SESSION['company']['id'] ?>, <?= $_SESSION['user']['id'] ?>);
            if (users_added.err){
                alert (users_added.err);
            } 
            if (users_added.users.length > 0){
                $('#import_employees').removeClass('fade').modal('hide');
                if (users_added.users_not_saved.length > 0){
                    alert('I seguenti utenti non sono stati creati:\n' + users_added.users_not_saved + ".\nVerifica i dati inseriti.");
                } else {
                    alert("Utenti creati correttamente. Seleziona dall'elenco a quali vuoi assegnare i corsi");
                }
                users = users.concat(users_added.users);
                subscribeStep2(users);
            }            
            $.isLoading("hide");
        });
        
        /* ******** SVUOTA MODAL IMPORTAZIONE UTENTI ********** */
        $('#import_employees').on('hidden.bs.modal', function (e) {
            $('#upload-header').show();
            $('#content_message').empty().hide();
            $('.control-function').find('input:checked').prop("checked", false);
            $('.control-function').find('input').first().prop("checked", true);
            $('.control-email').find('input:checked').prop("checked", false);
            $('.control-email').find('input').first().prop("checked", true);            
            $('#control').hide();
            $('#control-users thead tr').html('<th>Nome*</th><th>Cognome*</th><th>Codice Fiscale*</th><th>Email*</th>');
            $('#control-users tbody').empty();
            $('#control-users').hide();
            $('#create_from_xls').text('Crea utenti').hide();
        });

        
        /* *********************** UPLOAD *************************/
        

        uploader_excel = new plupload.Uploader({
            runtimes: 'html5,flash,silverlight,html4',
            browse_button: 'pickfiles_excel',
            container: 'import_employees',
            max_file_size: '100mb',
            multiple_queues: false,
            url: '<?= BASE_WEBSITE_PATH . "lib/parse_xls.php" ?>',
            multi_selection: false,
            filters: {mime_types: [{title: "application/vnd.ms-excel", extensions: "xls"}, 
                                   {title: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", extensions: "xlsx"}, 
                                   {title: "text/csv", extensions: "csv"}, 
                                   {title: "application/vnd.oasis.opendocument.spreadsheet", extensions: "ods"}]}
        });

        uploader_excel.init();

        uploader_excel.bind('FilesAdded', function (up, files) {
            if (files.length > 1) {
                alert("E' possibile caricare un solo file.");
            } else {
                uploader_excel.refresh(); // Reposition Flash/Silverlight
                uploader_excel.start();
            }
        });

        uploader_excel.bind('Error', function (up, err) {
            $("#content_" + err.file.id).remove();
            alert("Errore durante il caricamento del file " + err.file.name + "\n" + err.message);
        });

        uploader_excel.bind('FileUploaded', function (up, file, response) {
            $('#' + file.id + " b").html("100%");
            var name = file.name;
            if (name.length > 38) {
                name = name.substring(0, 38) + "..";
            }
            $("#content_" + file.id).html("<div id='_" + file.id + "' style='font-size:11px'>" + name + " (" + plupload.formatSize(file.size) + ") <b></b><br><span style='font-size:9px'><b>Completed</b></span></div>");
            $("#content_" + file.id).css("background-color", "#C6E5C5");
            $("#content_" + file.id).css("border", "1px solid #08CA00");
            var conta = 0;
            var simple_import = true;
            var output = response.response.split("|");
            for (var i = 0; i < output.length; i++) {
                var output_2 = output[i].split(";");
                if (output_2[0] != "") {
                    var row_id = i + 1;
                    $('#control-users tbody').append('<tr id="new_user_' + row_id + '"></tr>');
                    $('#new_user_' + row_id).append('<td><input class="form-control" id="new_name_' + row_id + '" type="text" value="' + output_2[0] + '" required="required" /></td>');
                    $('#new_user_' + row_id).append('<td><input class="form-control" id="new_surname_' + row_id + '" type="text" value="' + output_2[1] + '" required="required" /></td>');
                    $('#new_user_' + row_id).append('<td><input class="form-control" id="new_cf_' + row_id + '" type="text" value="' + output_2[2] + '" required="required" /></td>');
                    $('#new_user_' + row_id).append('<td><input class="form-control" id="new_email_' + row_id + '" type="email" required="required" pattern="^([0-9a-zA-Z]+[-._+&amp;])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$" value="' + output_2[3] + '" /></td>');
                    if (output_2.length > 4) {
                        simple_import = false;
                        $('#new_user_' + row_id).append('<td><input class="form-control" id="new_product_unit_' + row_id + '" type="text" value="' + output_2[4] + '" /></td>');
                        $('#new_user_' + row_id).append('<td><input class="form-control" id="new_department_' + row_id + '" type="text" value="' + output_2[5] + '" /></td>');
                        $('#new_user_' + row_id).append('<td><input class="form-control" id="new_hire_date_' + row_id + '" type="date" value="' + output_2[6] + '" /></td>');
                    }
                    conta++;
                }
            }
            
            if (!simple_import) {
                $('#control-users thead tr').append('<th>Unità produttiva</th><th>Reparto</th><th>Data assunzione</th>');
            }
            if (conta > 0) {
                $('#create_from_xls').show();                
                $('#upload-header').slideUp();
                $('#control-users').slideDown();
                $('#content_message').fadeIn().html('Caricati ' + conta + ' utenti. Scegli una funzione e se inviare la mail di registrazione agli utenti.');
                $('#control').slideDown();
            } else {
                $('#upload-header').slideUp();
                $('#content_message').fadeIn().html('Nessun utente caricato. Verifica il contenuto del file selezionato.');
            }
        });

    });
</script>