<?php
/**
 * Created by PhpStorm.
 * User: endrit
 * Date: 3/23/2017
 * Time: 9:20 AM
 */

require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';

$company_obj = new T81Company();
$companies = $company_obj->getCompanyByTutorCompany($_SESSION['tutor']['id']);
?>
<div id="invia_Licenze_Modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="width: 95%;">
        <div class="modal-content">
            <div class="modal-header text-center" style="background-color: #394263;">
                <h3 style="margin:0;color: #1bbae1;" ><span style="font-size: smaller;">Invia i codici di accesso per il corso </span><span id="modalSellTitle" style="margin:0;color: #fff; font-weight: 800;"><b></b></span></h3>
            </div>

            <!-- Modal Body -->
            <div class="modal-body" style="padding: 10px 15px;">
                <form autocomplete="off">


                <!-- Basic Wizard Block -->
                <div class="block" style="padding-top: 10px;">
                    
                    <div class="tab-content" style="background-color: rgb(57, 66, 99); padding: 5px 15px 2px;">

                    <!-- Basic Wizard Title -->
                    <?php if ($_SESSION['user']['company']['is_tutor']) { ?>
                            <div class="form-group" id="clienteCompanyIDContainer" style="margin-top:12px;">
                                <select title="Scegli Cliente" id="clienteCompanyID"
                                        class="clienteCompanyID form-control input-sm"
                                        style="padding: 3px; text-align-last: center!important; font-size: 15px; font-weight: bold;"
                                        name="select_client">
                                    
                                    <?= count($companies) > 1 ? '<option value="0" selected>---- Scegli il cliente ----</option>' : '';?>
                                    <!--<option value="-->
                                    <?php //= $_SESSION['tutor']['id']?><!--">Vendita diretta per -->
                                    <?php //= $_SESSION['tutor']['business_name']?><!--</option>-->
                                    <?php

                                    if ($companies) {
                                        foreach ($companies as $company) {
                                            ?>
                                            <option
                                                value="<?= $company["id"] ?>"><?= $company["business_name"] ?>
                                            </option>
                                        <?php } ?>
                                    <?php } ?>
                                </select>
                                <!--
                                <a type="button" href="/ec-company-registration.php" target="_blank"
                                   class="btn btn-sm btn-alt btn-info"
                                   style="margin-left: 5px;line-height: 1.7;">
                                    <i class="fa fa-plus"></i>
                                </a>
                                -->
                            </div>
                            <?php } else { ?>
                                <div class="form-group" style="margin-bottom: 5px;">
                                    <input type="hidden" title="Cliente" id="clienteCompanyID" class="clienteCompanyID"
                                           value="<?= $_SESSION['user']['company']['id'] ?>">
                                </div>
                            <?php } ?>


                    </div>
                    
                    
                    <!-- Basic Wizard Title -->
                    <!--                    <div class="block-title">-->

                    <!--                    </div>-->
                    <!-- END Basic Wizard Title -->

                    <!-- Step Info -->
                    <div class="wizard-steps" data-toggle="tabs">
                        <div class="row">
                            <div class="col-sm-2">
                                <p style="margin-top: 40px;"><strong>Chi deve svolgere il corso</strong></p>
                            </div>
                            <div class="col-sm-4" style="display: inline-flex;">
                                <div style="position:relative;">
                                    <label class="tab-linker-label"></label>
                                </div>
                                <div>
                                    <div class="active" id="nuovoUtenteTab">
                                    <span class="text-left">
                                        <a href="#nuovoUtente"
                                           style=" color:#fff;margin-left: 10px;">
                                            <!-- <i class="gi gi-circle_plus" style="margin-right: 5px;"></i> -->
                                            <b> Nuovo utente</b>
                                        </a>
                                    </span>
                                    </div>
                                    <div class="" id="utenteEsistenteTab">
                                    <span class="text-left">
                                        <a href="#utenteEsistente" id="utenteEsistenteTabLink"
                                           style="color:black;margin-left: 10px;">
                                            <!-- <i class="gi gi-group" style="margin-right: 5px;"></i> -->
                                            <b>Utente esistente</b>
                                        </a>
                                    </span>
                                    </div>
                                </div>

                            </div>
                            <table class="col-sm-6" style="">
                                <tbody>
                                    <tr>
                                        <td class="text-center">
                                            <h5><b>ISTRUZIONI</b></h5>
                                            <p style="background-color: rgb(57, 66, 99); color: white; padding: 10px;">
                                                <b>Non sei obbligato ad intestare la licenza, il destinatario potrà farlo direttamente (GUARDA esempio)</b>
                                            </p>
                                        </td>
                                        <td>
                                            <img src="/img/bk/donna-che-indica.png" class="img-responsive girl-image" style="height:150px; min-width: 195px;">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!--                            <div class="col-xs-4" id="importExcelTab">-->
                    <!--                            <span style="font-size: 12px; position: relative; line-height: normal;">-->
                    <!--                                <a href="#importExcel"-->
                    <!--                                   style="position: absolute; top: 35px; left: 2px; color:black;">-->
                    <!--                                    <b>IMPORTATO DA LISTA EXCEL</b>-->
                    <!--                               </a>-->
                    <!--                            </span>-->
                    <!--                            </div>-->


                    <div class="tab-content" style="margin-top: 0;">
                        <!-- Nuovo utente  -->
                        <div class="tab-pane active" id="nuovoUtente">
                            <table id="ecom-licences" class="table table-responsive table-vcenter">
                                <tr>
                                    <td class="text-left" id="amountCellId">
                                        <span style="margin-right: 30px;">Quantità corsi </span>
                                        <button type="button" class="btn btn-alt btn-xs dec decQuantita"
                                                style="background-color: #d3d3d3; border-color:#d3d3d3; color: #808080;vertical-align: top;">
                                            <i class="fa fa-minus"></i>
                                        </button>
                                        <input
                                            class="amount productQuantita initialQuantita step1-amount-css whiteFontOnClick"
                                            title="Corsi Quantita" maxlength="3" min="1" max="999"
                                            type="text" value="1" readonly
                                            style="cursor: default;width: 35px;height:22px;text-align: center;border: none;color:#000;border-radius: 2px;">
                                        <button type="button" class="btn btn-alt btn-xs inc"
                                                style="background-color: #d3d3d3; border-color:#d3d3d3; color: #808080;vertical-align: top;">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="inviaLicenzeRow">
                                    <td colspan="7" class="td_id" id="td_ID">
                                        <section class="sendLicenceForm" id="sendLicenceForm" style="margin-top: 10px;">

                                            <div class="sendLiceneceRowsContainerInfo" style="position: relative;">
                                                <div class="licenseInfo">
                                                    <div class="form-group" style="width: 164px;">
                                                        <small class="license-info"> Email dove inviare licenza</small>
                                                    </div>
                                                    <div class="form-group" style="width: 80px;">
                                                        <small class="license-info"> Data inizio</small>
                                                    </div>
                                                    <div class="form-group" style="width: 80px;">
                                                        <small class="license-info"> Fine corso</small>
                                                    </div>
                                                    <div class="form-group" style="width: 100px;">
                                                        <small class="license-info"> Alert giorni prima</small>
                                                    </div>
                                                    <div class="form-group license-info-optional"
                                                         style="width: 648px; margin-left: 10px;">
                                                        <small class="license-info"> Intesta codice corso</small>
                                                    </div>
                                                </div>
<!--                                                <div class="licenseInfoVertical">-->
<!--                                                    <div class="form-group" style="width: 500px;">-->
<!--                                                        <small class="license-info"> 1. Indica mail dove inviare il-->
<!--                                                            codice corso-->
<!--                                                        </small>-->
<!--                                                    </div>-->
<!--                                                </div>-->
                                                <div class="licenseInfoVerticalAbs">
                                                    <div class="form-group" style="width: 425px;">
                                                        <h5><i> Se vuoi puoi inserire i dati del corsista</i></h5>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="sendLiceneceRowsContainer">
                                                <?php require 'send-licences-rows.php'; ?>
                                            </div>
                                            <div class="text-center" style="margin: 45px;">
                                                <input type="hidden" title="learningProjectID" class="learningProjectID" value="">
                                            </div>
                                        </section>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <!--  End nuovo utente tab -->

                        <!--  Utente esistente tab -->
                        <div class="tab-pane" id="utenteEsistente">
                            <div class="block" style="border: none;">

                                <!-- Table Styles Title -->
                                <div class="block-title">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <h2><i class="fa fa-folder-open"></i> GESTIONE UTENTI PIATTAFORMA</h2>
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <div style="padding-top: 10px;">
                                                <!-- UNITA' PRODUTTIVA -->
                                                <div class="form-group product_unit"
                                                     style="display: inline-block; width: 164px;">
                                                    <!-- Append select tag with options using javascript when client is selected-->
                                                    <div class="pu_controls">
                                                        <select title="Unità Produtiva" name="product_unit"
                                                                class="form-control pu_select" size="1">

                                                        </select>
                                                    </div>
                                                </div>
                                                <!-- REPARTO -->
                                                <div class="form-group departments"
                                                     style="display: inline-block; width: 164px;">
                                                    <!-- Append select tag with options using javascript when product unit is selected-->
                                                    <div class="dep_controls">

                                                    </div>
                                                </div>
                                                <!-- END REPARTO -->
                                            </div>
                                        </div>
                                        <!-- /.col-md-6 -->
                                    </div>
                                    <!-- /.row -->
                                </div>
                                <!-- END Table Styles Title -->

                                <!-- Datatables Content -->
                                <div class="table-responsive">
                                    <table id="employees-sell-table"
                                           class="table table-vcenter table-hover table-bordered">
                                        <thead>
                                        <tr>
                                            <th class="text-center">Cognome</th>
                                            <th class="text-center">Nome</th>
                                            <th class="text-center">Codice Fiscale</th>
                                            <th class="text-center">Funzione</th>
                                            <th class="text-center">Email</th>
                                            <th class="text-center">User ID</th>
                                            <th class="text-center">Codice corso</th>
                                        </tr>
                                        </thead>
                                        <tbody class="employee-table-body">

                                        </tbody>
                                    </table>
                                </div>
                                <!-- END Datatables Content -->

                            </div>
                            <!-- End block-->
                        </div>
                        <!-- End Utente esistene tab -->

                        <!-- Import excel tab -->
                        <div class="tab-pane" id="importExcel" style="display: none;">
                            <div class="">
                                <h3>Importa lista utenti su file</h3>

                                <div id="upload-header">

                                    <div class="row">
                                        <div class="col-md-6 ">
                                            <div class="block full" style="margin-top: 15px;">
                                                <!-- Upload Title -->
                                                <div class="block-title" style="margin-bottom: 45px;">
                                                    <h1><i class="fa fa-cloud-upload"></i>
                                                        Importa un<strong>
                                                            file</strong>
                                                    </h1>
                                                </div>
                                                <!-- END Upload Title -->

                                                <!-- Upload Content -->
                                                <div class="dz-default dz-message text-center"
                                                     style="padding-bottom: 55px;">
                                                    <a href="#" id="pickfiles_excel">
                                                        <i class="fa fa-cloud-upload"
                                                           style="font-size: 50px; line-height: 50px;"></i>
                                                        <br/><br/>Importa un file
                                                        <p>di Excel (xls, xlsx), Open document (ods) o csv
                                                            (campi separati
                                                            con ; ) contenente la lista dei nuovi utenti che
                                                            vuoi creare.
                                                        </p>
                                                    </a>
                                                </div>
                                                <!-- END Upload Content -->
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div id="xls_model">
                                                <div class="block full" style="margin-top: 15px;">
                                                    <div class="block-title">
                                                        <h1>Scarica un modello</h1>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-vcenter"
                                                               style="margin-bottom: 0;">
                                                            <tbody>
                                                            <tr>
                                                                <td class="text-left" style="vertical-align: middle;">
                                                                    <h4>Importazione semplice<br>
                                                                        <small><em>nome,cognome,codice
                                                                                fiscale,email</em>
                                                                        </small>
                                                                    </h4>
                                                                </td>
                                                                <td class="text-left">
                                                                    <a href="download/nuovi_utenti.xlsx"
                                                                       target="_blank"><img
                                                                            src="img/xlsx.png"> Excel 2007
                                                                        (xlsx)</a><br>
                                                                    <a href="download/nuovi_utenti.xls" target="_blank"><img
                                                                            src="img/xls.png"> Excel 1997-2003 (xls)</a><br>
                                                                    <a href="download/nuovi_utenti.ods" target="_blank"><img
                                                                            src="img/ods.png"> Open document (ods)</a>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="text-left" style="vertical-align: middle;">
                                                                    <h4>Importazione completa<br>
                                                                        <small><em>nome,cognome,codice
                                                                                fiscale,email,<br>
                                                                                unità produttiva,reparto,data
                                                                                assunzione</em>
                                                                        </small>
                                                                    </h4>
                                                                </td>
                                                                <td class="text-left">
                                                                    <a href="download/nuovi_utenti_reparti.xlsx"
                                                                       target="_blank"><img src="img/xlsx.png"> Excel
                                                                        2007
                                                                        (xlsx)</a><br>
                                                                    <a href="download/nuovi_utenti_reparti.xls"
                                                                       target="_blank"><img
                                                                            src="img/xls.png"> Excel 1997-2003 (xls)</a><br>
                                                                    <a href="download/nuovi_utenti_reparti.ods"
                                                                       target="_blank"><img
                                                                            src="img/ods.png"> Open document (ods)</a>
                                                                </td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>
                        <!-- End Import excel tab -->

                    </div>
                </div>
                </form>
            </div>
            <!-- END Modal Body -->
            <form class="form-horizontal form-bordered"
                  style="margin: 0 20px 5px 20px; height: 50px;">
                <div class="text-center" style="background-color: #F9FAFC; height: 50px;">
                    <button type="button" class="btn btn-sm btn-default" data-dismiss="modal"
                            style="margin-top: 10px; background-color:#888888;color:#fff;">
                        Chiudi
                    </button>
                    <button type="submit" class="btn btn-sm sell btn-primary" style="margin-top: 10px;">
                        <i class="fa fa-share"></i> Invia Codici
                    </button>
                </div>
            </form>
            <!--            <div class="modal-footer" style="text-align: left;">-->
            <!--                <div class="block">-->
            <!--                        <h6>Help Online</h6>-->
            <!--                    <p>1. Puoi inviare per email il codice corso all'utente interessato oppure se questo non ha un email,-->
            <!--                        invialo ad un referente che può stampare il codice e le istruzioni</p>-->
            <!--                </div>-->
            <!--            </div>-->

        </div>

    </div>

</div>

