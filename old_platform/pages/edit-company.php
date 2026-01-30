<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 27-lug-2015
 * File: pages/edit-company.php
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
require_once BASE_LIBRARY_PATH . 'class_user.php';

$comp_obj = new T81Company ();
$user_obj = new T81User ();


$company = $comp_obj->getBusinessDetail(filter_input(INPUT_GET, 'company_id', FILTER_SANITIZE_NUMBER_INT) ?: $_SESSION['company']['id']);

/*
if ($company['is_tutor'] && $_SESSION['user']['role'] != 1000) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}
*/
$tutor = $user_obj->getUserCompany($company['owner_user_id']);

$gmt = $comp_obj->getTimezone();
$prov = $comp_obj->getProvinces();
$ateco = $comp_obj->getAteco();

$dc = $comp_obj->getDidacticTutor($company['id']);
$didactic_comp = $dc ? $dc['id'] : 0;

$dt = $comp_obj->getDidacticTutor($tutor['id']);
$didactic_tutor = $dt ? $dt['id'] : 6;

$didactic_default = true;
if ($didactic_comp) {
    $company_didactic = $user_obj->getUserCompany($didactic_comp);
    $didactic_default = $company_didactic['id'] != $company['id'];
}
$plan_detail = $comp_obj->getCompanyPlan($company['id'], FALSE);
$plans = $comp_obj->getPlans();
$tutors = $comp_obj->getBusinessTutor();
$contract_types = $comp_obj->getContracts();
?>

<script type="text/javascript">
    function editCompany() {
        console.log("Start saving company....");
        var business_name = $("#business_name").val();
        var vat = $("#vat").val();
        var city = $("#city").val();
        var address = $("#address").val();
        var email = $('#email').val();
        var province_id = $("#province_id").val();
        console.log("Start saving empty check ...");
        if (business_name != "" != "" && vat != "" && city != "" && address != "" && email != "" && province_id != 0) {
            var role_ref = $("#role_ref").val();
            var telephone = $("#telephone").val();
            var site_url = $('#site_url').val();
            var gmt = $("#gmt").val();
            var regional_authorization = $('#regional_authorization').val();
            var ateco = $('#ateco').val();
            var ateco_sector_id = $("#ateco_sector_id").val();
            var contract = $("#contract").val();
            var discount = $("#new_discount").val();
            var iban_code = $("#iban").val();
            var ecommerce = $("#urlDiEcommerce").val();
            var trainer = $('#trainer').val();

            console.log("Fields check...");

            $.post("manage/company.php", {
                op_type: 'edit_company',
                comp_id: <?= $company['id'] ?>,
                role_ref: role_ref,
                business_name: business_name,
                vat: vat,
                city: city,
                address: address,
                province_id: province_id,
                telephone: telephone,
                email: email,
                contract_id: contract,
                discount: discount,
                gmt: gmt,
                regional_authorization: regional_authorization,
                ateco_sector_id: ateco_sector_id,
                iban: iban_code,
                url_ecommerce: ecommerce,
                ateco: ateco,
                site_url: site_url,
                trainer: trainer
            }, function (data) {
                console.log("After call to company.php ...");
                console.log(data);
                if (data == "PIVA") {
                    alert('Partita iva già esistente');
                } else if (data > 0) {
                    window.location.reload();
                }
            });
        } else {
            alert('Compilare i campi obbligatori');
        }
    }

    function selectReference() {
        $.post("manage/company.php", {
                op_type: "get_employee",
                owner: <?= $company['owner_user_id'] ?>,
                comp_id: $("#role").val(),
                role: 1
            },
            function (data) {
                $("#role_ref").html(data);
            });
    }

</script>
<div id="edit-company" class="container-fluid">
    <form class="form-horizontal">
        <div class="company-detail">
            <!-- <h3 class="text-center">Dati azienda</h3> -->
            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="business_name">Ragione sociale*</label>
                <div class="col-sm-9 controls">
                    <input type="text" id="business_name" class="form-control"
                           value="<?= $company['business_name'] ?>" required>
                </div>
            </div>

            <?php if ($_SESSION['user']['role'] == 1000) { ?>

                <div class="form-group form-group-lg">
                    <label class="col-sm-3 control-label" for="role">Assegna a ente formativo*</label>
                    <div class="col-sm-9 controls">
                        <select id="role" onchange="selectReference()" class="form-control">

                            <?php
                            $comp_tutor = $comp_obj->getBusinessTutor();
                            foreach ($comp_tutor as $single) { ?>

                                <option value="<?= $single['id'] ?>">
                                    <?= strtoupper($single['business_name']) ?>
                                </option>

                            <?php } ?>

                        </select>
                    </div>
                </div>

            <?php } else { ?>

                <div class="form-group form-group-lg" style="margin: 0">
                    <div class="col-sm-9 controls">
                        <input type="hidden" id="role" value="<?= $tutor['id'] ?>" class="form-control">
                    </div>
                </div>

            <?php } ?>

            <div class="form-group form-group-lg" id="as_normal">
                <label class="col-sm-3 control-label" for="role_ref">Amministratore principale*</label>
                <div class="col-sm-9 controls">
                    <select id="role_ref" class="form-control" required>
                    </select>
                </div>
            </div>

            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="vat">P.IVA*</label>
                <div class="col-sm-9 controls">
                    <input type="text" id="vat" value="<?= $company['vat'] ?>"
                           class="form-control" required>
                </div>
            </div>

            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="city">LOCALIT&Agrave;*</label>
                <div class="col-sm-9 controls">
                    <input type="text" id="city" value="<?= $company['city'] ?>"
                           class="form-control" required>
                </div>
            </div>

            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="address">Indirizzo*</label>
                <div class="col-sm-9 controls">
                    <input type="text" id="address" class="form-control"
                           value="<?= $company['address'] ?>">
                </div>
            </div>

            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="province_id">Provincia*</label>
                <div class="col-sm-9 controls">
                    <select id="province_id" class="form-control">

                        <?php foreach ($prov as $single) { ?>

                            <option value="<?= $single['id'] ?>">
                                <?= strtoupper($single['name']) ?>
                            </option>

                        <?php } ?>

                    </select>
                </div>
            </div>

            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="telephone">Telefono</label>
                <div class="col-sm-9 controls">
                    <input type="text" id="telephone" class="form-control"
                           value="<?= $company['telephone'] ?>">
                </div>
            </div>
            
            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="email">E-mail*</label>
                <div class="col-sm-9 controls">
                    <input type="text" id="email" class="form-control" value="<?= $company['email'] ?>">
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
            <div class="form-group form-group-lg <?= $company['is_tutor'] ? '' : ' hidden'; ?>">
                <label class="col-sm-3 control-label" for="regional_authorization">Autorizzazione regionale</label>
                <div class="col-sm-9 controls button-group">
                    <div class="col-sm-3">
                        <input type="radio" title="No" id="hideRegionalField" <?= empty($company['regional_authorization']) ?  'checked' : ''?>> No <br/>
                        <input type="radio" title="Si" id="showRegionalField" <?= !empty($company['regional_authorization']) ?  'checked' : ''?>> Si
                    </div>
                    <div class="col-sm-9">
                        <input type="text" id="regional_authorization" class="form-control <?= empty($company['regional_authorization']) ?  'hidden' : ''?>"
                               value="<?= $company['regional_authorization'] ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="trainer">Soggetto formatore autorizzato</label>
                <div class="col-sm-9 controls">
                    <textarea type="text" id="trainer" class="form-control" rows="3"><?= $company['trainer'] ?></textarea>
                </div>
            </div>

            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="ateco">Codice Ateco</label>
                <div class="col-sm-9 controls">
                    <input type="text" id="ateco" class="form-control"
                           value="<?= $company['ateco'] ?>">
                </div>
            </div>

            <div class="form-group form-group-lg hidden">
                <label class="col-sm-3 control-label" for="site_url">Sito Ufficale</label>
                <div class="col-sm-9 controls" style="padding-top: 5px;">
                    <input type="text" id="site_url" class="form-control" value="<?= $company['site_url'] ?>">
                    <!-- <a id="offical_site" href="<?= $company['site_url'] ?>"> <?= $company['site_url'] ?></a> -->
                </div>
            </div>
        </div><!-- /.company-detail -->
        
        <hr>
        <div class="tutor81-contract table-responsive <?= $company['is_tutor'] && $_SESSION['user']['role'] == 1000  ? '' : ' hidden'; ?>">
            <table class="table table-bordered table-vcenter text-center">
                <tbody>
                    <tr>
                        <td rowspan="7"><h3 class="text-center"><b>PIANO DI<br/> ABBONAMENTO</b></h3>
                            <!--                        <br/>-->
                            <!--                        <h4><a href="#">pdf</a></h4>-->
                        </td>
                        <td class="text-center"><h4>Piano</h4></td>
                        <td><h4><?= $plan_detail[0]['short_desc_plan'] ?></h4></td>
                    </tr>
                    <tr>
                        <td class="text-center"><h4>Sconto su listino (%)</h4></td>
                        <td class="text-center"><h4><?= $plan_detail[0]['discount'] ?></h4></td>
                    </tr>
                    <tr>
                        <td class="text-center"><h4>Corsi personalizzati</h4></td>
                        <td class="text-center"><h4><?= $plan_detail[0]['customized_course'] == 0 ? "NO" : "SI" ?></h4></td>
                    </tr>
                    <tr>
                        <td class="text-center"><h4>Sito Ecommerce</h4></td>
                        <td class="text-center"><h4><?= $plan_detail[0]['ecommerce'] == 0 ? "NO" : "SI" ?></h4></td>
                    </tr>
                    <tr>
                        <td class="text-center"><h4>Max numero amministratori</h4></td>
                        <td class="text-center"><h4><?= $plan_detail[0]['max_admin'] ?></h4></td>
                    </tr>
                    <tr>
                        <td class="text-center"><h4>Max numero utenti contemporanei</h4></td>
                        <td class="text-center"><h4><?= $plan_detail[0]['max_concurrent_users'] ?></h4></td>
                    </tr>
                    <tr>
                        <td class="text-center"><h4>Prezzo</h4></td>
                        <td class="text-center"><h4>Euro <?= $plan_detail[0]['price'] ?></h4></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <hr>
        <div id="pagamentoEcommerce" class="<?= $company['is_tutor'] ? '' : ' hidden'; ?>">
            <h3>Pagamento ecommerce</h3>
            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="iban">Iban</label>
                <div class="col-sm-9 controls">
                    <input type="text" id="iban" class="form-control" value="<?= $company['iban'] ?>">
                </div>
            </div>
            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="paypal">Paypal</label>
                <div class="col-sm-9 controls">
                    <input type="text" id="paypal" class="form-control" value="" disabled>
                </div>
            </div>
            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="carteDiCredito">Carte di credito</label>
                <div class="col-sm-9 controls">
                    <input type="text" id="carteDiCredito" class="form-control" value="" disabled>
                </div>
            </div>
            <div class="form-group form-group-lg">
                <label class="col-sm-3 control-label" for="urlDiEcommerce">Url di ecommerce</label>
                <div class="col-sm-9 controls">
                    <input type="text" id="urlDiEcommerce" class="form-control" value="<?= $company['ecommerce'] ?>" <?= $_SESSION['user']['role'] != 1000 ? 'disabled' : ''?>>
                </div>
            </div>
        </div><!-- /#pagamentoEcommerce -->

        <div class="text-right">
            <button type="button" onclick="editCompany()" class="edit-company btn btn-primary">Salva modifiche
            </button>
            <button type="reset" value="reset" class="btn btn-warning">Cancella i dati immessi</button>
        </div>
    </form>
</div>

<script>

    <?php if ($_SESSION['user']['role'] == 1000) { ?>

    $("#role").val(<?= $tutor['id'] ?>);

    <?php } ?>

    selectReference();

    $("#province_id").val(<?= $company['province_id'] ?>);
    $("#gmt").val(<?= $company['gmt'] ?>);
    $("#ateco_sector_id").val(<?= $company['ateco_sector_id'] ?>);


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

</script>
