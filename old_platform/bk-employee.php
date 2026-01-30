<?php
require_once 'lib/check_session.php';
require_once 'lib/class_company.php';

$company_obj = new T81Company();

$scelta = filter_input(INPUT_GET, 'scelta', FILTER_SANITIZE_STRING);

$companies = $scelta == "attestati" ? 
        $company_obj->getCompanyWithCompletedCoursesByTutorCompany($_SESSION['tutor']['id']) 
        : 
        $company_obj->getCompanyByTutorCompany($_SESSION['tutor']['id']);

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
                                    <strong>
                                
                                <?php if ($scelta == "lavoratori") {
                                    echo "Elenco utenti";
                                } elseif ($scelta == "attestati") {
                                    echo "Attestati";
                                }
                                ?>
                                        <a href="javascript: void(0);" class="import_employees" style="<?= $_SESSION['user']['company']['is_tutor'] && count($companies) > 1 ? 'display: none;' : ''; ?> position: absolute; left: 20px; font-size: smaller;">
                                            <span class="glyphicon glyphicon-plus"></span> importa utenti
                                        </a>
                                        <a href="javascript: void(0);" id="add-user" style="<?= $_SESSION['user']['company']['is_tutor'] && count($companies) > 1 ? 'display: none;' : ''; ?> position: absolute; right: 20px; font-size: smaller;">
                                            <span class="glyphicon glyphicon-plus"></span> aggiungi utente singolo
                                        </a>
                                
                                    </strong>
                                </h2>
                            
                   
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->

                <!-- Datatables Content -->
                <div class="block full" >

                    <?php if ($_SESSION['user']['company']['is_tutor'] && count($companies) > 1) { ?>
                    
                    <div class="block" style="padding: 5px 15px 1px">

                        <div class="row">
                            <div class="col-sm-8 ">
                                <div class="form-group" id="clienteCompanyIDContainer">
                                    <select title="Scegli Cliente" id="clienteCompanyID"
                                            class="clienteCompanyID form-control input-sm"
                                            style="margin-top: 10px; padding: 3px; width: 100%; max-width: 100%; text-align-last: center!important; font-size: 15px; font-weight: bold;"
                                            name="select_client">

                                        <option value="0">----- Scegli il cliente al quale applicare il filtro -----</option>

                                        <?php
                                        if ($companies) {
                                            foreach ($companies as $company) {
                                                ?>

                                                <option value="<?= $company["id"] ?>"><?= $company["business_name"] ?>
                                                </option>

                                            <?php } 
                                        } ?>

                                    </select>
                                </div>                                    
                            </div>
                        </div>
                        
                    </div> 

                    <?php } else { ?>

                    <div class="form-group" style="margin-bottom: 5px;">
                        <input type="hidden" title="Cliente" id="clienteCompanyID" class="clienteCompanyID"
                               value="<?= $_SESSION['user']['company']['is_tutor'] ? $companies[0]['id'] : $_SESSION['user']['company']['id'] ?>">
                    </div>

                    <?php } ?>    

                    <div class="row" hidden>
                        <div id="utenteEsistenteTab" class="active"></div>
                        <div class="col-md-6">
                            <h2 style="font-size: 15px;"><i class="fa fa-folder-open"></i> Filtra gli utenti: </h2>
                        </div>
                        <div class="col-md-6 text-center">
                            <div style="padding-top: 10px;">
                                <!-- UNITA' PRODUTTIVA -->
                                <div class="form-group product_unit"
                                     style="display: inline-block; width: 200px;">
                                    <!-- Append select tag with options using javascript when client is selected-->
                                    <div class="pu_controls">
                                        <select title="Unità Produtiva" name="product_unit"
                                                class="form-control pu_select" size="1">
                                        </select>
                                    </div>
                                </div>
                                <!-- REPARTO -->
                                <div class="form-group departments"
                                     style="display: inline-block; width: 200px;">
                                    <!-- Append select tag with options using javascript when product unit is selected-->
                                    <div class="dep_controls">

                                    </div>
                                </div>
                                <!-- END REPARTO -->
                            </div>
                        </div>
                        <!-- /.col-md-6 -->
                    </div>   

                    <!-- Datatables Content -->
                    <div class="table-responsive">
                        
                        <?php if ($scelta === 'attestati') { ?>
                        
                        <table id="attestati-table"
                               class="employees-table table table-bordered table-striped table-vcenter"
                               data-option="attestati">
                            <thead>
                            <tr>
                                <th class="text-center">Cognome e nome</th>
                                <th class="text-center">Corso</th>
                                <th class="text-center">Data termine</th>
                                <th class="text-center" data-orderable="false">Attestato</th>
                            </tr>
                            </thead>
                            <tbody class="attestati-table-body">

                            </tbody>
                        </table>
                        
                        <?php } elseif ($scelta === 'lavoratori') {?>
                        
                        <table id="employees-table"
                               class="employees-table table table-bordered table-striped table-vcenter"
                               data-option="employees">
                            <thead>
                            <tr>
                                <th class="text-center">Nome</th>
                                <th class="text-center">Cognome</th>
                                <th class="text-center">Codice Fiscale</th>
                                <th class="text-center">Funzione</th>
                                <th class="text-center">Email</th>
                                <th class="text-center" data-orderable="false">Sospendi</th>
                            </tr>
                            </thead>
                            <tbody class="employee-table-body">

                            </tbody>
                        </table>
                        
                        <?php } ?>
                        
                    </div>
                    <!-- END Datatables Content -->

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
                        <a href="javascript: void(0)" id="pickfiles_excel">Importa un file<br/>
                            <small>formato Excel (xls, xlsx), Open document (ods) o csv (campi separati con ; ) contenente la lista dei nuovi utenti che vuoi creare.</small>
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
                                        <a href="/media/download/nuovi_utenti.xlsx" download><img src="img/xlsx.png"> Excel 2007 (xlsx)</a><br>
                                        <a href="<?= BASE_MEDIA_PATH ?>download/nuovi_utenti.xls" download><img src="img/xls.png"> Excel 1997-2003 (xls)</a><br>
                                        <a href="<?= BASE_MEDIA_PATH ?>download/nuovi_utenti.ods" download><img src="img/ods.png"> Open document (ods)</a>
                                    </td>
                                </tr>
                                <!--
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
                                -->
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

<!-- ---- MODALS ---- -->

<!-- ------------------------------------------------------------------------------------------------------------------------- -->
<!-- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -->
<!-- ------------------------------------------------------------------------------------------------------------------------- -->
<div id="createEmployeeModal" class="modal fade" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        </div>
    </div>
</div>

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-double-up"></i></a>
<script src="js/vendor/datatables.min.js"></script>
<script src="js/vendor/plugins.js"></script>
<!-- Load and execute javascript code used only in this page -->
<script type="application/javascript" src="js/pages/bk-attestati.js"></script>
<script src="js/pages/tablesDatatables.js"></script>
<script>
    $(function(){
        TablesDatatables.init();

        $("select[name=department]").addClass("hidden");
        var customer_company_id = $("#clienteCompanyID").val();

        $(".product_unit > div.dep_controls").empty();
        
        if (customer_company_id > 0) {
            RefreshTable(0, 0);
            $.post("manage/department.php",
                {
                    op_type: "get_pu",
                    pu_id: customer_company_id
                }, function (product_unit) {
                    var pu_controls = '<option value="0">Seleziona unità</option>';
                    product_unit = $.parseJSON(product_unit);
                    if (product_unit == 0) {
                        $('.product_unit > div.pu_controls select').empty().append(pu_controls);
                    } else {
                        $.each(product_unit, function (i, item) {
                            pu_controls += '<option value="' + item.id_pu + '">' + item.short_desc_pu + '</option>';
                            if (i == Object.keys(product_unit).length - 1)
                                $('.product_unit > div.pu_controls select').empty().append(pu_controls);
                        });
                    }

                    var optionLength = $(".pu_select option").length;
                    if (optionLength > 1) {
                        $(".pu_select").removeClass("hidden");
                        //$('.employees-table').DataTable().clear().draw();
                    } else {
                        $('.pu_select').addClass("hidden");
                    }
                }
            );
        }

        $('#employees-table').on('click', '.employee-close', function(e){
            e.preventDefault();
            e.stopPropagation();
            var user_id = $(this).parents('tr').data('user_id');
            if (confirm("Hai chiesto di sospendere questo utente. Procedo?")) {
                var disabled = disableUser(user_id);
                if (disabled) {
                    alert("Utente sospeso.");
                    $(this).removeClass('employee-close').addClass('employee-open').empty().html('<span class="glyphicon glyphicon-eye-open"></span>');
                }
            }
        });

        $('#employees-table').on('click', '.employee-open', function(e){
            e.preventDefault();
            e.stopPropagation();
            var user_id = $(this).parents('tr').data('user_id');
            if (confirm("Hai chiesto di riattivare questo utente. Procedo?")) {
                var enabled = enableUser(user_id);
                if (enabled) {
                    alert("Utente attivato.");
                    $(this).removeClass('employee-open').addClass('employee-close').empty().html('<span class="glyphicon glyphicon-eye-close"></span>');
                }
            }
        });

        /**
        * Invio dell'attestato del corso completato
         */
        $('#attestati-table').on('click', '.send-attestato', function(e){
            e.preventDefault();
            e.stopPropagation();
            var this_link = $(this);
            var license_id = $(this).data('license_id');
            $.post(
                'manage/notification.php',
                {
                    op_type: 'send_attestato',
                    license_id: license_id,
                    destination_email: false
                }, 
                function(data){
                    if (data > 0) {
                        this_link.css('color', 'gray');
                        alert('Attestato inviato');
                    }
                }
            );
        });
        
        $('#employees-table').on('click', 'tbody > tr', function(){
            var user_id = $(this).data('user_id');
            $("#single-user").html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>').load("pages/sections/employee-detail.php", {
                    user_id: user_id
                });
            $("#cerca-utenti-modal").modal();
        });
        
        $('#clienteCompanyID').on('change', function(){
            $('#add-user').show();
            $('.import_employees').show();
        });
           
           
        /* ******** MODAL CREA NUOVO UTENTE ********** */
        $('#add-user').click(function(){
            var company_id = $("#clienteCompanyID").val() ;
            if (company_id > 0) {
                $('#createEmployeeModal').modal().find('.modal-content').load('/ecommerce/bk/modal-new-employee.php?company_id=' + company_id);
            } else {
                alert('Seleziona un cliente');
            }
        });
        
        /* ******** MODAL IMPORTA UTENTI ********** */
        $('.import_employees').click(function(){
            var company_id = $("#clienteCompanyID").val();
            if (company_id > 0) {
                $('[aria-controls="collapse-employees"][aria-expanded="false"]').click();
                $('#import_employees').modal();
            } else {
                alert('Seleziona un cliente');
            }
        });
        
        /* ******** SALVA NUOVI UTENTI MULTIPLI ********** */
        $('#create_from_xls').click(function () {
            $.isLoading({text: "Attendere ..."});
            var company_id = $("#clienteCompanyID").val();
            //var users = $('#enrollment-table tbody > tr.selected').map(function(){return $(this).data('user_id');}).get();
            var users_added = createUsers(company_id, <?= $_SESSION['user']['id'] ?>);
            if (users_added && typeof users_added.users != 'undefined'){
                $('#import_employees').removeClass('fade').modal('hide');
                if (users_added.users_not_saved.length > 0){
                    alert('I seguenti utenti non sono stati creati:\n' + 
                            + users_added.users_not_saved + 
                            + ".\nVerifica i dati inseriti. Non importare nuovamenti gli utenti già creati.");
                } else {
                    alert("Utenti creati correttamente.");
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
                $('#content_message').fadeIn().html('Caricati ' + conta + ' utenti. Scegli una funzione e procedi con la creazione.');
                $('#control').slideDown();
            } else {
                $('#upload-header').slideUp();
                $('#content_message').fadeIn().html('Nessun utente caricato. Verifica il contenuto del file selezionato.');
            }
        });
    });
</script>

</body>
</html>









