<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 06-lug-2015
 * File: pages/new-company.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

$created_by = $_SESSION ['user']['id'];

if ($_SESSION['user']['role'] != 1000) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_user.php';

$comp_obj = new T81Company ();
$user_obj = new T81User ();

$gmt = $comp_obj->getTimezone();
$prov = $comp_obj->getProvinces();
$ateco = $comp_obj->getAteco();

// gli amministratori dell'ente formativo
$tutor_admins = $comp_obj->getUsersCompanyByID($_SESSION['tutor']['id'], array(1,32,1000));

// l'ente formativo dell'utente loggato (che crea l'azienda)
//$tutor = $user_obj->getUserCompany($created_by);

// gli enti formativi
$tutor_companies = $comp_obj->getBusinessTutor();

//$contract_types = $comp_obj->getContracts();
$plans = $comp_obj->getPlans();
$plans_id = array_column($plans, 'id');
$plans_type = array_combine($plans_id, $plans);

$dt = $comp_obj->getDidacticTutor($_SESSION['tutor']['id']);
$didactic_tutor = $dt ? $dt ['id'] : 6;
?>
<div id="workspace">
    <form id="create_new_tutor" class="form-horizontal">
        <div class="container-fluid">
            <!-- <div class="row">
                <div class="col-lg-6"> -->
                    <input type="hidden" name="created_by" value="<?= $_SESSION ['user']['id']; ?>">
                    <input type="hidden" name="is_partner" value="0">
                    <input type="hidden" name="is_tutor" value="1">
                    <input type="hidden" name="tutor_id" value="<?= $_SESSION['tutor']['id'] ?>" />
                    
                    <div class="form-group form-group-lg hidden" id="as_normal">
                        <label class="col-sm-3 control-label" for="owner_user_id">Scegli amministratore*</label>
                        <div class="col-sm-9">
                            <select name="owner_user_id" class="form-control">
                                
                                <option value="0">Assegna a dipendente</option>
                                
            
                                <?php foreach ($tutor_admins as $single) {?>

                                    <option value ="<?= $single ['id'] ?>"<?= $single['id'] == $_SESSION['user']['id'] ? ' selected' : '' ?>>
                                        <?= ucwords(strtolower("{$single ['name']} {$single ['surname']}")) ?>
                                    </option>

                                <?php } ?>

                            </select>
                        </div>
                    </div>
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="tipo_ente">Tipo ente</label>
                        <div class="col-sm-9">
                            <label>
                                <input id="type_ente" type="radio" name="tipo_ente" checked="checked"> ENTE FORMATIVO
                            </label>
                        </div>
                        <div class="col-sm-9">
                            <label>
                                <input id="type_company" type="radio" name="tipo_ente" name="tipo_ente"> COMPANY
                            </label>
                        </div>
                    </div>
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="business_name">Ragione sociale*</label>
                        <div class="col-sm-9">
                            <input type="text" id="business_name" class="form-control" placeholder="Ragione sociale"
                                   required="required">
                        </div>
                    </div>
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="vat">P.IVA*</label>
                        <div class="col-sm-9">
                            <input type="text" id="vat" class="form-control" placeholder="P.IVA"
                                   required="required"> <span id="error_codfisc"></span>
                        </div>
                    </div>
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="address">Indirizzo*</label>
                        <div class="col-sm-9">
                            <input type="text" id="address" class="form-control" placeholder="Indirizzo">
                        </div>
                    </div>
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="postal_code">CAP*</label>
                        <div class="col-sm-9">
                            <input type="text" id="postal_code" class="form-control" placeholder="CAP" required="required"> <span
                                id="error_iva"></span>
                        </div>
                    </div>
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="city">Citt&agrave;*</label>
                        <div class="col-sm-9">
                            <input type="text" id="city" class="form-control" placeholder="Città" required="required">
                        </div>
                    </div>
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="province_id">Provincia*</label>
                        <div class="col-sm-9">
                            <select id="province_id" class="form-control" required="required">
                                
                                <option value="0">Seleziona una provincia</option>

                                <?php foreach ($prov as $single) { ?>

                                    <option value="<?= $single['id'] ?>"><?= strtoupper($single['name']) ?></option>

                                <?php } ?>

                            </select>
                        </div>
                    </div>
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="telephone">Telefono</label>
                        <div class="col-sm-9">
                            <input type="text" id="telephone" class="form-control" placeholder="Telefono">
                        </div>
                    </div>
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="email">E-mail*</label>
                        <div class="col-sm-9">
                            <input type="text" id="email" class="form-control" placeholder="Email">
                        </div>
                    </div>
                    <div class="form-group form-group-lg hidden">
                        <label class="col-sm-3 control-label" for="gmt">Fuso orario</label>
                        <div class="col-sm-9">
                            <select id="gmt" class="form-control">

                                <?php foreach ($gmt as $single) { ?>

                                    <option value="<?= $single['id'] ?>"><?= $single['description'] ?></option>

                                <?php } ?>

                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="regional_authorization">Autorizzazione regionale</label>
                        <div class="col-sm-9 controls button-group">
                            <div class="col-sm-3">
                                <input type="radio" title="No" id="hideRegionalField"> No <br/>
                                <input type="radio" title="Si" id="showRegionalField" checked> Si
                            </div>
                            <div class="col-sm-9">
                                <input type="text" id="regional_authorization" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="trainer">Soggetto formatore autorizzato</label>
                        <div class="col-sm-9 controls">
                            <textarea id="trainer" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="ateco">Codice Ateco</label>
                        <div class="col-sm-9 controls">
                            <input type="text" id="ateco" class="form-control">
                        </div>
                    </div>

                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="iban">Iban</label>
                        <div class="col-sm-9 controls">
                            <input type="text" id="iban" class="form-control">
                        </div>
                    </div>
                    <hr>
                    <!-- AMMINISTRATORE ENTE -->
                    <!-- <button type="button" class="btn btn-primary" id="show-new-admin">
                        Crea Amministratore Ente
                    </button> -->                   
                    <div id="admin">                        
                        <h3>Amministratore Ente formativo</h3>

                        <div id="admin-selection">
                            <fieldset class="contract">
                                <div class="radio">
                                    <label>
                                        <input type="radio" id="new-admin" name="admin" value="1" checked> 
                                        Crea nuovo utente</label>
                                </div>
                                <div class="radio">
                                    <label>
                                    <input type="radio" id="existent-user" name="admin" value="2"> 
                                        Usa utente esistente</label>
                                </div>
                            </fieldset>
                        </div>
                        
                        <div id="search-user" style="display: none;" data-user_id="0">

                            <!-- Search Form -->
                            <form action="#" method="post" class="navbar-form-custom" style="margin-bottom: 0;">
                                <div class="search hidden-print form-group-lg">
                                    <div class="search-query">
                                        <input type="text" class="form-control search-query ui-autocomplete-input" id="top-search" name="top-search" placeholder="Cerca Utente.." data-toggle="popover"
                                               data-placement="bottom" data-trigger="hover" data-delay="{&quot;show&quot;:300,&quot;hide&quot;:300}" title=""
                                               data-original-title="Inserire le prime lettere del nome, cognome o codice fiscale" autocomplete="off" aria-describedby="popover965202">
                                    </div>
                                </div>
                            </form>
                            <p>Seleziona un utente già esistente in un altra azienda. 
                            L'utente verrà spostato in questo ente e gli verrà assegnato 
                            il ruolo di amministratore.</p>
                            <!-- END Search Form -->
                        </div>
                        
                        <div id="create-new-admin">
                            
                            <div class="form-group form-group-lg">
                                <label class="col-sm-3 control-label" for="admin_name">Nome*</label>
                                <div class="col-sm-9">
                                    <input type="text" id="admin_name" class="form-control" placeholder="Nome" required="required">
                                </div>
                            </div>
                            <div class="form-group form-group-lg">
                                <label class="col-sm-3 control-label" for="admin_surname">Cognome*</label>
                                <div class="col-sm-9">
                                    <input type="text" id="admin_surname" class="form-control" placeholder="Cognome" required="required">
                                </div>
                            </div>
                            <div class="form-group form-group-lg">
                                <label class="col-sm-3 control-label" for="admin_tax_code">Codice Fiscale*</label>
                                <div class="col-sm-9">
                                    <input type="text" id="admin_tax_code" class="form-control" placeholder="Codifce fiscale" required="required">
                                </div>
                            </div>
                            <div class="form-group form-group-lg">
                                <label class="col-sm-3 control-label" for="admin_email">Email*</label>
                                <div class="col-sm-9">
                                    <input type="email" id="admin_email" class="form-control" placeholder="Email" required="required"
                                           pattern="^([0-9a-zA-Z]+[-._+&amp;])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$">
                                </div>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" value="true" name="admin_send_mail" checked> 
                                        Invia mail di registrazione all'utente
                                </label>
                            </div>
                        </div>
                    </div>

                    
                <!-- </div>
                <div class="col-lg-6"> -->
                    <!-- TUTOR DIDATTICO -->
                    <div id="didactic-tutor">
                        <h3>Tutor Didattico</h3>

                        <div id="didactic-selection">
                            <fieldset class="contract">
                                <div class="radio">
                                    <label>
                                        <input type="radio" id="tutor-didactic" name="didactic" value="1"
                                           data-tutor-didactic="<?= $didactic_tutor ?>" checked> 
                                        Assegna referente di Tutor81</label>
                                </div>
                                <div class="radio">
                                    <label>
                                    <input type="radio" id="existent" name="didactic" value="2"> 
                                        Usa dati Amministratore Ente Formativo</label>
                                </div>
                                <div class="radio">
                                    <label>
                                    <input type="radio" id="new-didactic" name="didactic" value="3"> 
                                        Crea nuovo utente</label>
                                </div>
                            </fieldset>
                        </div>

                        <div id="new-didactic-tutor" style="display: none;">
                            <div class="form-group form-group-lg">
                                <label class="col-sm-3 control-label" for="td_name">Nome*</label>
                                <div class="col-sm-9">
                                    <input type="text" id="td_name" class="form-control" placeholder="Nome"
                                           required="required">
                                </div>
                            </div>
                            <div class="form-group form-group-lg">
                                <label class="col-sm-3 control-label" for="td_surname">Cognome*</label>
                                <div class="col-sm-9">
                                    <input type="text" id="td_surname" class="form-control" placeholder="Cognome"
                                           required="required">
                                </div>
                            </div>
                            <div class="form-group form-group-lg">
                                <label class="col-sm-3 control-label" for="td_tax_code">Codice Fiscale*</label>
                                <div class="col-sm-9">
                                    <input type="text" id="td_tax_code" class="form-control" placeholder="Codifce fiscale"
                                           pattern="[a-zA-Z0-9]{16}" required="required">
                                </div>
                            </div>
                            <div class="form-group form-group-lg">
                                <label class="col-sm-3 control-label" for="td_email">Email*</label>
                                <div class="col-sm-9">
                                    <input type="email" id="td_email" class="form-control" placeholder="Email"
                                           required="required"
                                           pattern="^([0-9a-zA-Z]+[-._+&amp;])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$">
                                </div>
                            </div>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" value="true" name="td_send_mail" checked> 
                                        Invia mail di registrazione all'utente 
                                </label>
                            </div>
                            
                        </div>
                    </div>
                <hr>
                <div class="tutor81-contract table-responsive">
                    <table class="table table-bordered table-vcenter text-center">
                        <tr>
                            <td rowspan="9"><h3 class="text-center"><b>PIANO DI<br/> ABBONAMENTO</b></h3></td>
                            <td class="text-center"><h4>Piano tipo</h4></td>
                            <td class="text-center">
                                <select id="plan_id" title="Piano" name="plan_id" class="form-control">
                                    <option value="0">Seleziona un piano</option>        

                                <?php foreach ($plans as $plan) {
                                    if ($plan['for_tutor']) { ?>
                                    
                                    <option value="<?= $plan['id'] ?>">
                                        <?= $plan['short_desc_plan'] ?>
                                    </option>

                                    <?php }
                                    } ?>

                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><h4>Inizio validità</h4></td>
                            <td><input id="validity_start" type="text" class="form-control input datepicker"></td>
                        </tr>
                        <tr>
                            <td><h4>Fine validità</h4></td>
                            <td><input id="validity_end" type="text" class="form-control input datepicker"></td>
                        </tr>
                        <tr>
                            <td><h4>Canone annuo</h4></td>
                            <td><input id="price" type="number" class="form-control input" min="0" value="0"></td>
                        </tr>
                        <tr>
                            <td><h4>Sconto corsi (%)</h4></td>
                            <td>
                                <input id="discount" type="number" title="Percentuale di sconto sul listino" 
                                       min="0" max="100" class="form-control input" value="0">
                            </td>
                        </tr>
                        <tr>
                            <td><h4>E-commerce</h4></td>
                            <td>
                                <div class="radio">
                                  <label>
                                    <input type="radio" name="ecommerce" id="ecommerce0" value="0" checked>
                                    NO
                                  </label>
                                </div>
                                <div class="radio">
                                  <label>
                                    <input type="radio" name="ecommerce" id="ecommerce1" value="1">
                                    SI
                                  </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><h4>Corsi personalizzati</h4></td>
                            <td>
                                <div class="radio">
                                  <label>
                                    <input type="radio" name="customized_courses" id="customized_courses0" value="0" checked>
                                    NO
                                  </label>
                                </div>
                                <div class="radio">
                                  <label>
                                    <input type="radio" name="customized_courses" id="customized_courses1" value="1">
                                    SI
                                  </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><h4>Max. numero<br> amministratori</h4></td>
                            <td><input id="max_admin" type="number" class="form-control input" min="1" value="1"></td>
                        </tr>
                        <tr>
                            <td><h4>Max. numero <br>utenti contemp.</h4></td>
                            <td><input id="max_concurrent_user" type="number" 
                                class="form-control input" min="0" value="0"
                                data-toggle="tooltip" title="inserire 0 per utenti illimitati"></td>
                        </tr>
                    </table>
                </div>
                    
                    
                <!-- </div><!-- /.span6 -->
            <!-- </div><!-- /.row -->
            <!-- <div class="row"> -->
            
                <!-- <h3>Funzionalità opzionali</h3> -->
                <div id="optional" class="hidden">
                    <div class="row optional">
                        <div class="col-xs-3 checkbox">
                            <label class="checkbox">
                                <input type="checkbox" id="test_in_the_presence" disabled>
                                Test in presenza
                            </label>
                        </div>
                        <div class="col-xs-9 radio optional-detail" style="display: none">
                            <label class="radio">
                                <input type="radio" name="test_in_the_presence" value="UPLOADABLE" checked>
                                Download moduli da compilare e upload moduli compilati
                            </label>
                        </div>
                    </div><!-- /.row -->
                    
                    <hr>
                    
                    <div class="row optional">
                        <div class="col-xs-3 checkbox">
                            <label class="checkbox">
                                <input type="checkbox" id="risk_evaluation" value="RISK" disabled> 
                                Valutazione Rischi
                            </label>
                        </div>
                        <div class="col-xs-9 optional-detail" style="display:none;">
                            <div class="form-group">
                                <label for="new_ateco">Scegli il settore Ateco</label>
                                <div>
                                    <select id="new_ateco" class="form-control" size="10" required>

                                        <?php foreach ($ateco as $single) { ?>

                                            <option value="<?= $single['id'] ?>"
                                                    <?php if ($single['id'] == 1) { ?> selected="selected" <?php } ?>><?= strtoupper($single['name']) ?></option>

                                        <?php } ?>

                                    </select>
                                </div>
                            </div>
                            <div class="form-group form-group-lg">
                                <label class="col-xs-6 control-label" for="fire_risk">Seleziona il livello di Rischio Incendio</label>
                                <div class="col-xs-6">
                                    <select class="form-control" name="fire_risk">
                                        <option value="4" selected="selected">Basso</option>
                                        <option value="5">Medio</option>
                                        <option value="6">Alto</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group form-group-lg">
                                <label class="col-xs-6 control-label" for="first_aid_risk">Seleziona il livello di Rischio Primo Soccorso</label>
                                <div class="col-xs-6">
                                    <select class="form-control" name="first_aid_risk">
                                        <option value="10" selected="selected">a</option>
                                        <option value="11">b</option>
                                        <option value="12">c</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group form-group-lg">
                                <label class="col-xs-6 control-label" for="first_aid_risk">Il numero di dipendenti è</label>
                                <div class="col-xs-6">
                                    <select class="form-control" name="first_aid_risk">
                                        <option value="14" selected="selected">&ge; 50</option>
                                        <option value="15">< 50</option>
                                    </select>
                                </div>
                            </div>
                                
                        </div>
                    </div><!-- /.row -->
                </div><!-- /#optional -->
            <!-- </div>
            
            <div class="row">
                <div id="contract-detail">
                    <h3>Dettagli contratto</h3>
                    <div id="contract" class="form-group" style="display: none">
                        <fieldset class="contract">
                            <legend>Tipologia Contratto</legend>
                            <div>
                                <input type="radio" id="entry" 
                                       name="contract" value="4" checked> <label for="entry">Entry</label>

                                <input type="radio" id="control" 
                                       name="contract" value="5"> <label for="control">Control</label>

                                <input type="radio" id="inclusive"
                                       name="contract" value="6"> <label for="inclusive">Inclusive</label>
                            </div>
                        </fieldset>
                    </div>
                    <div class="form-group" id="discount" style="display: none">
                        <label class="control-label" for="new_discount">Sconto percentuale</label>
                        <div class="controls">
                            <input type="number" min="0" max="100" id="new_discount" value="30"
                                   style="width: 40px">
                        </div>
                    </div>
                </div><!-- /#contract-detail -->
            <!-- </div><!-- /.row -->
        <!--</div><!-- /.container-fluid !-->

        <!-- <div class="clearfix"></div>
        <div style="clear: both; border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px;"> 
            <div class="form-group">
                <div class="controls">-->
        <hr>
                    <button type="button" id="new-company" class="btn btn-success">Salva nuova azienda</button>
                    <!-- <button type="reset" value="reset" class="btn btn-warning">Cancella i dati immessi</button> -->
                <!--</div>
            </div> -->
        </div>
    </form>

    <span id="error_generic"></span>

</div><!-- /#workspace -->

<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();

        $("#gmt").val(23);
        
        var plans = <?php echo json_encode($plans_type) ?>

        $("#showRegionalField").click(function () {
            $("#regional_authorization").removeClass("hidden");
            $('#showRegionalField').prop('checked', true);
            $('#hideRegionalField').prop('checked', false);
        });
        $("#hideRegionalField").click(function () {
            $("#regional_authorization").addClass("hidden");
            $('#showRegionalField').prop('checked', false);
            $('#hideRegionalField').prop('checked', true);
        });
    
        $('input[name="didactic"]').click(function (e) {
            $(this).val() == 3 ? $('#new-didactic-tutor').fadeIn() : $('#new-didactic-tutor').fadeOut();
        });

        $('#new-company').click(function (e) {
            $.isLoading({text: "Attendere ..."});
            var company_id = createCompany();
            if (company_id > 0)
                window.location = "bk-clienti.php?scelta=clienti";//"tutor/home?tutor_id=" + company_id;
            else
                $.isLoading("hide");
        });

        $('#existent-user').click(function(){
            $('#search-user').show();
            $('#create-new-admin').hide();
        });

        $('#new-admin').click(function(){
            $('#search-user').hide();
            $('#create-new-admin').show();
        });
        
        /**
         * Mostra o nasconde i dettagli delle funzionalità opzionali
         *//*
        $('#optional .col-xs-3 input').click(function(){
            if ($(this)[0].checked)
                $(this).closest('.optional').find('.optional-detail').show();
            else
                $(this).closest('.optional').find('.optional-detail').hide();
        });*/
        
        $("#search-user .search-query").autocomplete({
            source: "lib/employee_search.php?option=&istutor=" + $("#isTutor").val() + "&companyid=" + $("#companyID").val(),
            minLength: 1,
            select: function(e, j) {
                var h = j.item.id;
                if (h > 0) {
                    $('#search-user').data("user_id", h);
                }
                $(".ui-autocomplete").hide();
            },
            html: true,
            open: function(h, i) {
                $(".ui-autocomplete").css("z-index", 10000);
            }
        });
        
        /**
         * Inizializzazione piani di abbonamento
         */
        $('#create_new_tutor .datepicker').datepicker({
            format: "dd/mm/yyyy",
            language: "it",
            autoclose: true,
            todayHighlight: true
        });
        
        // init validity date
        var init_date = new Date();
        $('#validity_start').datepicker('setDate', init_date);
        init_date.setFullYear(init_date.getFullYear()+1);
        $('#validity_end').datepicker('setDate', init_date);
        
        
        $('#discount').on('input', function() {validateMinMax(this);});
        
        $('#plan_id').on('change', function(){
            if (confirm("Hai cambiato il piano di abbonamento. Vuoi aggiornare automaticamente i campi in base al piano scelto?")) {
                var plan_id = $(this).val();
                $('#discount').val(plans[plan_id].discount);
                $("#ecommerce" + plans[plan_id].ecommerce).click();
                $("#customized_courses" +  plans[plan_id].customized_courses).click();
                $('#max_admin').val(plans[plan_id].max_admin);
                $('#max_concurrent_users').val(plans[plan_id].max_concurrent_users);
                $('#price').val(plans[plan_id].plan_price);
            }
        });

    });
</script>