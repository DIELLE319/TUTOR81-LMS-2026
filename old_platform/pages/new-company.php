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

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32 ) {
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

$tutor_admins = $comp_obj->getUsersCompanyByID($_SESSION['tutor']['id'], array(1,32));

$contract_types = $comp_obj->getContracts();

//$tutor = $user_obj->getUserCompany($created_by);
$dt = $comp_obj->getDidacticTutor($_SESSION['tutor']['id']);
$didactic_tutor = $dt ? $dt ['id'] : 6;
?>
<div id="workspace">

    <form class="form-horizontal">
        <div class="container-fluid">
            <!-- <div class="row">
                <div class="col-lg-6"> -->
                    <input type="hidden" name="created_by" value="<?= $_SESSION ['user']['id']; ?>">
                    <input type="hidden" name="is_partner" value="0">
                    <input type="hidden" name="is_tutor" value="0">
                    <input type="hidden" name="tutor_id" value="<?= $_SESSION['tutor']['id'] ?>" />
                    
                    <?php 
                    if ($_SESSION['user']['role'] == 1000) {
                        ?>
                    
                        <div class="form-group form-group-lg" id="as_normal">
                            <label class="col-sm-3 control-label" for="owner_user_id">Scegli amministratore*</label>
                            <div class="col-sm-9">
                                <select name="owner_user_id" class="form-control">
                                    <?php foreach ($tutor_admins as $single) {?>

                                        <option value ="<?= $single ['id'] ?>"<?= $single['id'] == $_SESSION['user']['id'] ? ' selected' : '' ?>>
                                            <?= ucwords(strtolower("{$single ['name']} {$single ['surname']}")) ?>
                                        </option>

                                    <?php } ?>

                                </select>
                            </div>
                        </div>
                        
                    <?php } else { ?>
                        
                        <input type="hidden" name="owner_user_id" value="<?= $_SESSION['user']['id'] ?>">
                        
                    <?php }?>
                        
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
                        
                    <input type="hidden" id="showRegionalField">

                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="trainer">Soggetto formatore autorizzato</label>
                        <div class="col-sm-9 controls">
                            <textarea id="trainer" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <input type="hidden" id="iban" value="">

                    <div class="form-group form-group-lg">
                        <label class="col-sm-3 control-label" for="ateco">Codice Ateco</label>
                        <div class="col-sm-9 controls">
                            <input type="text" id="ateco" class="form-control">
                        </div>
                    </div>
                        
                    <hr>
                    <div class="tutor81-contract table-responsive hidden">
                        <table class="table table-bordered table-vcenter text-center">
                            <tr>
                                <td rowspan="3"><h3 class="text-center"><b>LICENZA DI<br/> ACCESSO</b></h3></td>
                                <td class="text-center"><h4>Contratto tipo</h4></td>
                                <td class="text-center">
                                    <select id="contract" title="Contratto" name="contract_name" class="form-control">
                                        <option value="0">Tipo di licenza</option>        

                                    <?php foreach ($contract_types as $contract) {
                                        if (!$contract['for_tutor']) { ?>

                                        <option value="<?= $contract['id'] ?>">
                                            <?= $contract['name'] ?>
                                        </option>

                                        <?php }
                                        } ?>

                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><h4>Canone annuo</h4></td>
                                <td><h5 style="padding-top: 12px;">*</h5></td>
                            </tr>
                            <tr>
                                <td><h4>Sconto corsi (%)</h4></td>
                                <td>
                                    <input id="new_discount" type="text" title="Percentuale" class="form-control input" value="0">
                                </td>
                            </tr>
                        </table>
                    </div>
                <!-- </div>
                <div class="col-lg-6"> -->
                    <!-- TUTOR DIDATTICO -->
                    <div id="didactic-tutor" hidden>
                        <h3>Tutor Didattico</h3>

                        <div id="didactic-selection" class="form-group form-group-lg">
                            <fieldset class="contract">
                                <!-- <legend>Tipologia Contratto</legend> -->
                                <div class="radio">
                                    <label>
                                        <input type="radio" id="tutor-didactic" name="didactic" value="1"
                                           data-tutor-didactic="<?= $didactic_tutor ?>" checked> 
                                        Assegna referente di Tutor81</label>
                                </div>
                                <!--<div class="radio">
                                    <label>
                                    <input type="radio" id="existent" name="didactic" value="2">Usa dati Referente Aziendale</label>
                                </div>
                                <div class="radio">
                                    <label>
                                    <input type="radio" id="new-didactic" name="didactic" value="3"> 
                                        Crea nuovo utente</label>
                                </div>-->
                            </fieldset>
                        </div>

                        <!--<div id="new-didactic-tutor" style="display: none;">
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
                                           required="required">
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
                            
                        </div>-->
                    </div>
                    <!--<hr>-->
                    <!-- AMMINISTRATORE ENTE - REFERENTE AZIENDALE -->
                    <!--<button type="button" class="btn btn-primary" id="show-new-admin">
                        Crea Utente Referente Aziendale
                    </button>                    
                    <div id="new-admin" style="display: none;">                        
                        <h3>Crea Utente Referente Aziendale
                            <button type="button" class="btn btn-warning" id="hide-new-admin">Non creare</button>
                        </h3>
                        <div>
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
                    </div>-->

                    
                <!-- </div><!-- /.span6 -->
            <!-- </div><!-- /.row -->
            <!-- <div class="row"> -->
            
                <!--<h3>Funzionalità opzionali</h3>-->
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
                    </div>
                    
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
                    </div>
                </div>
            <!-- </div>
            
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

        $("#gmt").val(23);

        $('input[name="didactic"]').click(function (e) {
            $(this).val() == 3 ? $('#new-didactic-tutor').fadeIn() : $('#new-didactic-tutor').fadeOut();
        });

        $('#new-company').click(function (e) {
            $.isLoading({text: "Attendere ..."});
            var company_id = createCompany();
            if (company_id > 0)
                window.location =  "bk-clienti.php?scelta=clienti";//"company/home?company_id=" + company_id;
            else
                $.isLoading("hide");
        });   
        
        $('#show-new-admin').click(function(){
            $(this).hide();
            $('#new-admin').show();
            $('#hide-new-admin').show();
        });
        
        $('#hide-new-admin').click(function(){
            $(this).hide();
            $('#show-new-admin').show();
            $('#new-admin').hide();
        });

        
        $('#existent').click(function(){
            $('#show-new-admin').hide();
            $('#hide-new-admin').hide();
            $('#new-admin').show();
        });
                
        $('#didactic-selection').on('click', '#tutor-didactic,#new-didactic', function(){
            $('#show-new-admin').show();
            $('#new-admin').hide();
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

    });
</script>