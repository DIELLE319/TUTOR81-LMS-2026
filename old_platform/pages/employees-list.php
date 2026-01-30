<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 10-lug-2015
 * File: pages/employees-list.php
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
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'class_departments.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';

$comp_obj = new T81Company();
$learn_obj = new T81LearningProject();
$dep_obj = new Departments();
$report_obj = new Report();

$user_id = filter_input(INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT);

$users_list = $comp_obj->getUsersCompanyByID($_SESSION['company']['id'], -1, $_SESSION['user']['role'] ==  1000 ? -1 : 0 );
$course_purchased = $comp_obj->getPurchaseByCompany($_SESSION['company']['id']);
$product_units = $dep_obj->getProductUnits($_SESSION['company']['id']);
$cost_centre = $comp_obj->getCostCentre($_SESSION['tutor']['id']);

if (!$users_list) {?>
        
    <h4>Nessun utente registrato</h4>
     
<?php } else {?>
    
    <div class="row">
        <div style="overflow: auto;">

            <table id="users-list-table" class="table table-sorter row-selectable">
                <thead>
                    <tr>
                        <th class="{sorter: false, filter: false}"><input type="checkbox" class="select-visible tristate"></th>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Codice fiscale</th>
                        <th>Funzione</th>
                        <th>Email</th>
                        
                        <?php if ($product_units) { ?>
                        
                        <th>Unità Produttiva</th>
                        <th>Reparto</th>
                        
                        <?php } ?>
                        
                        <?php $_SESSION['user']['role'] != 1000 ?>
                        
                        <th>&nbsp;</th>
                        
                        <?php ?>
                        
                    </tr>
                </thead>
                <tbody>

                    <?php
                    foreach ($users_list as $single) {
                        $user_dep_detail = $dep_obj->getEmployeeDetail($single['id']);
                        ?>

                        <tr data-user_id="<?= $single['id'] ?>" class="<?= $single['role'] > 0 ? 'resp' : '' ?>
                                <?= isset($_GET['user_id']) && $_GET['user_id'] == $single['id'] ? ' selected' : '' ?>
                                <?= $single['deleted'] ? ' danger' : '' ?>">
                            <td><input type="checkbox"></td>
                            <td><?= ucwords(strtolower("{$single['name']}")) ?></td>
                            <td><?= ucwords(strtolower("{$single['surname']}")) ?></td>
                            <td><?= $single['tax_code'] ?></td>
                            <td><?= $single['function'] ?></td>
                            <td class="email"><?= $single['email'] ?></td>
                        
                            <?php if ($product_units) { ?>
                        
                            <td><?= $user_dep_detail ? $user_dep_detail[0]['short_desc_pu'] : '' ?></td>
                            <td><?= $user_dep_detail ? $user_dep_detail[0]['short_desc_dep_type'] : '' ?></td>
                            
                            <?php } ?>
                        
                            <?php $_SESSION['user']['role'] != 1000 ?>

                            <td>
                                <a href="javascript: void(0);" class="employee-<?= $single['deleted'] ? 'enable': 'disable' ?>">
                                    <span class="glyphicon glyphicon-eye-<?= $single['deleted'] ? 'open': 'close' ?>"></span>
                                </a>
                            </td>

                            <?php ?>
                            
                        </tr>

                    <?php } ?>

                </tbody>
            </table>
        </div>
    </div>
        
<?php } ?>
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
                    <!-- <div class="control-send-email">
                        <fieldset>
                            <legend>Invio email di registrazione?</legend>
                            <label class="radio-inline"><input type="radio" name="send-mail" value="true" checked> Si, invia immediatamente &nbsp;&nbsp;&nbsp;&nbsp;</label>
                            <label class="radio-inline"><input type="radio" name="send-mail" value="false"> No, non inviare</label>
                        </fieldset>
                    </div> -->
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



<!-- ------------------------------------------ -->
<!-- MODAL SALVA ISCRIZIONE CON CENTRO DI COSTO -->
<!-- ------------------------------------------ -->
<div id="saveSubscription" class="modal fade" role="dialog" aria-labelledby="saveSubscriptionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close clear_modal" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="saveSubscriptionLabel">Salva iscrizione al corso</h3>
            </div>
            <div class="modal-body">
                
                <?php if ($cost_centre) { ?>
                    
                <form class="form-inline">
                    <div class="form-group">
                        <label>Seleziona il centro di costo per l'acquisto </label>
                        <select class="input-sm form-control">

                    <?php foreach ($cost_centre as $single_cost_centre) { ?>

                            <option value="<?= $single_cost_centre['id_cost_centre'] ?>"><?= $single_cost_centre['cost_centre'] ?></option>

                    <?php } ?>

                        </select>
                    </div>
                </form>     
                   
                <?php } ?>
                        
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary save-modal">Salva iscrizione</button>
                <button class="btn btn-default clear_modal" data-dismiss="modal" aria-hidden="true">Chiudi</button>
            </div>
        </div>
    </div>
</div>
    
<!-- ---------------------------------- -->
<!-- MODAL CONFERMA ISCRIZIONE AVVENUTA -->
<!-- ---------------------------------- -->
<div id="subscriptionOk" class="modal fade" role="dialog" aria-labelledby="subscriptionOkLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content success-bg">
            <div class="modal-header modal-no-header">
            </div>
            <div class="modal-body text-center">
                <span class="glyphicon glyphicon-thumbs-up" style="font-size: 28px;"></span>
                <br>
                <br>
                <span class="message">Hai iscritto al corso gli utenti selezionati, che riceveranno per email le credenziali del corso</span>
            </div>
            <div class="modal-footer text-center modal-no-border">
                <a href="company/home/employees" class="btn btn-default">OK</a>
                <a href="javascript: void(0)" class="white small" style="position: absolute; right: 10px; bottom: 23px;">maggiori dettagli</a>
            </div>
        </div>
    </div>
</div>
    
<!-- ----------------------- -->
<!-- MODAL ERRORE ISCRIZIONE -->
<!-- ----------------------- -->
<div id="subscriptionError" class="modal fade" role="dialog" aria-labelledby="subscriptionOkLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content danger-bg">
            <div class="modal-header modal-no-header">
            </div>
            <div class="modal-body text-center">
                <span class="glyphicon glyphicon-warning-sign" style="font-size: 28px;"></span>
                <br>
                <br>
                <span class="message">Errore. La procedura non è stata completata. Verifica nuovamente i dati.</span>
            </div>
            <div class="modal-footer text-center modal-no-border">
                <button class="btn btn-default" data-dismiss="modal">OK</button>
                <!-- <a href="javascript: void(0)" class="white small" style="position: absolute; right: 10px; bottom: 23px;">maggiori dettagli</a> -->
            </div>
        </div>
    </div>
</div>

<!-- ------------------------ -->
<!-- MODAL ASSEGNAZIONE CORSO -->
<!-- ------------------------ -->
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog" aria-labelledby="assignModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content info-bg" style="color: #fff;">
            <div class="modal-body">
                <div>
                    <span class="glyphicon glyphicon-calendar" 
                          style="display: table-cell; vertical-align: middle; 
                            color: #000; font-size: 28px; padding-right: 10px;">
                    </span>
                    <div style="display: table-cell; vertical-align: middle; ">Gli utenti selezionati saranno iscritti a:<br>
                        <span class="course-title" style="text-transform: uppercase; color: #000;">CORSO nome corso</span>
                    </div>
                </div>
                <br>
                <br>
                <form class="form-horizontal">
                    <div class="form-group">
                        <label for="start" class="col-sm-2 control-label">data inizio</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control course-date" name="start">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="end" class="col-sm-2 control-label">data fine</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control course-date" name="end">
                        </div>
                    </div>
                </form>
                <form class="form-inline">
                    <div class="form-group">
                        <label for="alert">Quanti giorni prima devono ricevere un avviso che il corso sta scadendo?</label> 
                        <input type="number" class="form-control" name="alert" value="15" style="max-width: 70px;">
                    </div>
                </form>
                
                <?php if ($cost_centre) { ?>
                
                <br>
                <br>
                <form class="form-inline">
                    <div class="form-group">
                        <label>Seleziona il centro di costo per l'acquisto </label>
                        <select class="input-sm form-control" name="cost_centre">

                    <?php foreach ($cost_centre as $single_cost_centre) { ?>

                            <option value="<?= $single_cost_centre['id_cost_centre'] ?>"><?= $single_cost_centre['cost_centre'] ?></option>

                    <?php } ?>

                        </select>
                    </div>
                </form>
                
                <?php } ?>
                
            </div>
            <div class="modal-footer">
                <a href="javascript: void(0)" class="save-modal glyphicon glyphicon-circle-arrow-right" style="font-size: 28px;"></a>
            </div>
        </div>
    </div>
</div>
<!-- ---- END MODALS ---- -->
<script type="text/javascript">

    $(function () {

        $('#users-list-table').tablesorter({
            theme: 'greyT81',
            sortList: [[2, 0]],
            // hidden filter input/selects will resize the columns, so try to minimize the change
            widthFixed: true,
            // initialize zebra striping and filter widgets
            widgets: ["filter"],
            // headers: { 5: { sorter: false, filter: false } },
            dateFormat: "ddmmyyyy",
            widgetOptions: {
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
                // if false, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
                // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus

            }
        });
        
        /* ********* INIZIALIZZAZIONE E GESTIONE CALENDARI  ******** */
        var now = new Date();
        var end_date = new Date();
        end_date.setDate(end_date.getDate() + 180);

        $('#assignModal .course-date').datepicker({
            format: "dd/mm/yyyy",
            startDate: '0d',
            endDate: '+180d',
            todayBtn: "linked",
            language: "it",
            autoclose: true,
            todayHighlight: true
        }).on('show', function (e) {
            $('#assignModal .datepicker.dropdown-menu').css('z-index', '10000');
        }).on('hide', function (e) {
            $('#assignModal .datepicker.dropdown-menu').css('z-index', '1000');
        });

        $('#assignModal input[name="start"]').datepicker('update', $.datepicker.formatDate('dd-mm-yy', now)).on('changeDate', function (e) {
            $(this).datepicker('hide');
            var startDate = $(this).datepicker('getDate');
            var new_end_date = new Date();
            new_end_date.setTime(startDate.getTime() + 180*86400000);
            if ($('#assignModal input[name="end"]').datepicker('getDate').getTime() > new_end_date.getTime()) {
                $('#assignModal input[name="end"]').datepicker('update', $.datepicker.formatDate('dd-mm-yy', new_end_date));
            }
            $('#assignModal input[name="end"]').datepicker('setStartDate', startDate);
            $('#assignModal input[name="end"]').datepicker('setEndDate', new_end_date);
        });

        $('#assignModal input[name="end"]').datepicker('update', $.datepicker.formatDate('dd-mm-yy', end_date)).on('changeDate', function (e) {
            $(this).datepicker('hide');
        });
        
        
        /* ******* APRI MODAL DETTAGLIO DIPENDENTE ******* */
        $('#users-list-table tbody').on('click', 'tr > td:not(:first-child)', function () {
            var user_id = $(this).parents('tr').data('user_id');
            $('#simpleLargeModal .modal-content').empty().load('modals/employee-detail.php', {user_id: user_id}).parents('#simpleLargeModal').modal();
        });
        /*
        $('#users-list-table tbody > tr > td:first-child').click(function (e) {
            e.stopPropagation();
        });
        */
        /* ******** SELEZIONE DI TUTTI GLI UTENTI VISIBILI E GESTIONE TRISTATE  ******* */
        $('#users-list-table th input.select-visible').tristate({
            change: function(state, value){
                
                    if (state === null || state === true) $('.save-subscription').css('display', 'inline-block');
                    else  $('.save-subscription').hide();
                }
            }).on('click', function (e) {
                if ($(this).tristate('state') === true) {
                    $('#users-list-table tbody > tr > td:first-child > input:not(:checked)').click();
                } else if ($(this).tristate('state') === false) {
                    if ($('#users-list-table tbody > tr > td:first-child > input:checked').length == $('#users-list-table tbody > tr:visible input[type="checkbox"]').length) {
                        $('#users-list-table tbody > tr > td:first-child > input:checked').click();
                    } else {
                        $('#users-list-table tbody > tr > td:first-child > input:not(:checked)').click();
                    }
                } else {
                    $('#users-list-table tbody > tr > td:first-child > input:checked').click();
                }
        });

        $('body').on('click', '#users-list-table tbody tr > td:first-child > input', function (e) {
            var checked = $('#users-list-table tbody > tr > td:first-child > input:checked');
            if ($('#users-list-table tbody > tr > td:first-child > input').length > checked.length) {
                if (checked.length == 0) {
                    $('#users-list-table th input.select-visible').tristate('state', false);
                } else {
                    $('#users-list-table th input.select-visible').tristate('state', null);
                }
            } else {
                $('#users-list-table th input.select-visible').tristate('state', true);
            }
        });
           
           
        /* ******** MODAL CREA NUOVO UTENTE ********** */
        $('.createEmployeeModal').click(function(){
            $('[aria-controls="collapse-employees"][aria-expanded="false"]').click();
            $('#createEmployeeModal').modal().find('.modal-content').load('modals/new-employee.php');
        });
           
           
        /* ******** MODAL IMPORTA UTENTI ********** */
        $('.import_employees').click(function(){
            $('[aria-controls="collapse-employees"][aria-expanded="false"]').click();
            $('#import_employees').modal();
        });
        
        /* ******** SALVA CREA NUOVO UTENTE ********** */
        $('#createEmployeeModal').on('click', '.save-modal', function(){
            $.isLoading({text: "Attendere ..."});
            var user = newUser(<?= $_SESSION['company']['id'] ?>, <?= $_SESSION['user']['id'] ?>);
            if (user) {
                $('#createEmployeeModal').removeClass('fade').modal('hide'); // rimuove la classe fade per eliminare l'animazione che si blocca
                alert("Dipendente creato correttamente");
                //var users = $('#enrollment-table tbody > tr.selected').map(function(){return $(this).data('user_id');}).get();
                //users.push(user);
                $('#users-list-table tbody')
                    .append('<tr data-user_id="' + user.id + '">'
                            + '<td><input type="checkbox"></td>'
                            + '<td>' + user.name + '</td>'
                            + '<td>' + user.surname + '</td>'
                            + '<td>' + user.tax_code + '</td>'
                            + '<td>' + user.business_function + '</td>'
                            + '<td>' + user.email + '</td>'
                            + <?php if ($product_units) { ?>
                            + '<td>' + user.product_unit + '</td>'
                            + '<td>' + user.department + '</td>'
                            + <?php } ?>
                            + '</tr>')
                    .parents('table')
                    .trigger("update")
                    .filter('.subscribe')
                    .find('tr[data-user_id="' + user.id + '"] > td:first-child > input')
                    .click();
                $('.save-subscription').click();
            }
            $.isLoading("hide");
        });

        /* ******** SALVA NUOVI UTENTI MULTIPLI ********** */
        $('#create_from_xls').click(function () {
            $.isLoading({text: "Attendere ..."});
            //var users = $('#enrollment-table tbody > tr.selected').map(function(){return $(this).data('user_id');}).get();
            var users_added = createUsers(<?= $_SESSION['company']['id'] ?>, <?= $_SESSION['user']['id'] ?>);
            if (users_added && typeof users_added.users != 'undefined'){
                $('#import_employees').removeClass('fade').modal('hide');
                if (users_added.users_not_saved.length > 0){
                    alert('I seguenti utenti non sono stati creati:\n' + 
                            + users_added.users_not_saved + 
                            + ".\nVerifica i dati inseriti. Non importare nuovamenti gli utenti già creati.");
                } else {
                    alert("Utenti creati correttamente. Seleziona dall'elenco a quali vuoi assegnare i corsi");
                }
                //users = users.concat(users_added.users);
                //console.log(users_added);
                //console.log(typeof users_added.users);
                var count = users_added.users.length;
                //console.log(count);
                if (count > 0) {
                    $.each(users_added.users, function(index, user){
                        var business_function = 
                        $('#users-list-table tbody')
                        .append('<tr data-user_id="' + user.id + '">'
                            + '<td><input type="checkbox"></td>'
                            + '<td>' + user.name + '</td>'
                            + '<td>' + user.surname + '</td>'
                            + '<td>' + user.tax_code + '</td>'
                            + '<td>' + $('#new-employee select[name="business_function"] > option[value="' + user.func_id + '"]').text() + '</td>'
                            + '<td>' + user.email + '</td>'
                            + <?php if ($product_units) { ?>
                            + '<td>' + user.product_unit + '</td>'
                            + '<td>' + user.department + '</td>'
                            + <?php } ?>
                            + '</tr>')
                        .parents('table')
                        .filter('.subscribe')
                        .find('tr[data-user_id="' + user.id + '"] > td:first-child > input')
                        .click();
                        if (!--count) {
                            $('#users-list-table').trigger("update");
                            $('.save-subscription').click();
                        }
                    });
                }
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
            max_file_size: '5mb',
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
           
<?php if ($user_id) { ?>

        $('#users-list-table tbody > tr[data-user_id="<?= $user_id ?>"] > td').click();

<?php } ?>

        $('.save-subscription').click(function(){
            $('#assignModal .course-title').text('CORSO ' + $('#accordion-courses ul.courses > li.active > a').text()).parents('.modal').modal();
        });

    
        $('#assignModal .save-modal').click(function(){
            if ($(this).hasClass('disabled')) return false;
            $(this).addClass('disabled');
            var start = $('#assignModal input[name="start"]').datepicker('getDate');
            var end = $('#assignModal input[name="end"]').datepicker('getDate');
            var alert = $('#assignModal input[name="alert"]').val();
            var cost_centre = $('#assignModal select[name="cost_centre"]').val();
            $('#assignModal').modal('hide');
            $(this).removeClass('disabled');
            $.isLoading({text: "Attendere il completamento ..."});
            var learning_project_id = $('#accordion-courses ul.courses > li.active > a').data('learning_project_id');
            var license_detail = getCompanyLicenseDetail(<?= $_SESSION['tutor']['id'] ?>);
            if (!license_detail) {
                $.isLoading("hide");
                bootbox.alert("La tua licenza non è valida. Contatta il tuo referente.");
                return false;
            }
            var users = $('#users-list-table tbody > tr.selected').map(function(){return $(this).data('user_id');}).get();
            if (users.length == 0){
                $.isLoading("hide");
                bootbox.alert("Selezionare un utente.");
                return false;
            }
            var available = calcElearningPurchaseUnassigned(<?= $_SESSION['company']['id'] ?>, learning_project_id);
            var prices = getLearningPrice(learning_project_id);
            var to_buy = users.length - available;
            var packed = 0;
            if (prices && to_buy > 0 && license_detail['purchase_courses_at_packs'] == '1') { 
                // devo acquistare corsi a pacchetto quindi verifico se non ho
                // già dei pacchetti disponibili sufficienti
                packed = calcElearningPacked(<?= $_SESSION['tutor']['id'] ?>, learning_project_id);
                if (to_buy - packed > 0) {
                    // devo acquistare dei pacchetti di corsi
                    // questo causerà il ricaricamento della pagina
                    $.isLoading("hide");
                    bootbox.alert("Devi acquistare dei pacchetti per " + (to_buy - packed) + " corsi.<br>" + 
                                "Una volta effettuato l'acquisto controlla i corsisti selezionati e " +
                                "clicca su salva per completare l'iscrizione.", function(){
                            $('#simpleModal')
                                .modal()
                                .find('.modal-content')
                                .html('<img src="img/loading_gif.gif" />')
                                .load('modals/purchase-pack.php');
                    });
                    return false;
                }
            }
            
            
            to_buy = to_buy > 0 ? to_buy : false;
            cost_centre = cost_centre > 0 ? cost_centre : false;
            start = $.datepicker.formatDate('yy-mm-dd', start);
            end = $.datepicker.formatDate('yy-mm-dd', end);
            $.ajax({
                type: "POST",
                url: "manage/license.php",
                data: {
                    op_type: "subscribe",
                    tutor_id: <?=$_SESSION['user']['id']?>,
                    company_id: <?=$_SESSION['company']['id'] ?>,
                    user_company_ref: <?=$_SESSION['company']['owner_user_id']?>,
                    tutor_company_id: <?= $_SESSION['tutor']['id'] ?>,
                    to_buy: to_buy,
                    packed: packed,
                    learning_project_id: learning_project_id,
                    ext_po_number: '',
                    cost_centre: cost_centre,
                    users: users,
                    start: start,
                    end: end,
                    alert: alert
                }
            }).done(function(res){
                $.isLoading("hide");
                $('.save-subscription').removeClass('disabled');
                $('.quick-subscription').removeClass('disabled');
                if (res != '0') {
                    var msg = "UTENTI CREATI CORRETTAMENTE, le email di iscrizione al corso sono state inviate.";
                    if (users.length == 1) {
                        msg = "UTENTE CREATO CORRETTAMENTE, l’email di iscrizione al corso è stata inviata a "
                                + $('#enrollment-table tbody > tr.selected > td.email').text();
                    }
                    $('#subscriptionOk .modal-body .message').html(msg).parents('#subscriptionOk').modal();
                    $.post('manage/notification.php',{
                        op_type: "notify_subscription",
                        licenses: res,
                        buyer_id: <?= $_SESSION['user']['id'] ?>,
                        company_id: <?= $_SESSION['company']['id'] ?>,
                        learning_project_id: learning_project_id
                    });
                } else {
                    //toSubscription();
                    $('#subscriptionError').modal();
                }
            });
            
            
        });
    
        
        $('.employee-disable').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            var user_id = $(this).parents('tr').data('user_id');
            if (confirm("Hai chiesto di sospendere questo utente. Procedo?")) {
                var disabled = disableUser(user_id);
                if (disabled) {
                    alert("Utente sospeso.");
                    location.href = "company/home/employees?company_id=<?= $_SESSION['company']['id'] ?>";
                };
            }
        });
        
        $('.employee-enable').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            var user_id = $(this).parents('tr').data('user_id');
            if (confirm("Hai chiesto di riattivare questo utente. Procedo?")) {
                var enabled = enableUser(user_id);
                if (enabled) {
                    alert("Utente attivato.");
                    location.href = "company/home/employees?company_id=<?= $_SESSION['company']['id'] ?>";
                };
            }
        });

    });

</script>