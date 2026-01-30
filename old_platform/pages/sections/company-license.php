<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 25-set-2015
 * File: pages/sections/company-license.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_company.php';
$comp_obj = new T81Company();

$company = $comp_obj->getBusinessDetail(filter_input(INPUT_GET, 'company_id', FILTER_SANITIZE_NUMBER_INT)) ? : $_SESSION['company'];
$plan_detail = $comp_obj->getCompanyPlan($company['id']);
$plans = $comp_obj->getPlans();
if ($_SESSION['user']['role'] == 1000) {
    $tutors = $comp_obj->getBusinessTutor();
}
if (!$plan_detail){ ?>

<div id="company-license" class="container-fluid">
    <form class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-3 control-label" for="plan_id">Tipo di licenza</label>
            <div class="col-sm-9 controls">
                <select name="plan_id" class="form-control">
                    <option value="0" selected>Seleziona una licenza</option>
                    
                <?php foreach ($plans as $plan) { 
                    if (($plan['for_tutor'] && $_SESSION['user']['role'] == 1000 && $company['is_tutor'])
                            || (!$plan['for_tutor'] && !$company['is_tutor'])){
                    ?>

                    <option value="<?= $plan['id'] ?>">
                        <?= $plan['short_desc_plan'] ?>
                    </option>

                    <?php } 
                } ?>

                </select>
            </div>
        </div>
        
        <?php if ($_SESSION['user']['role'] == 1000) { ?>
        
        <div class="form-group">
            <label class="col-sm-3 control-label" for="tutor_id">Ente formativo</label>
            <div class="col-sm-9 controls">
                <select name="tutor_id" class="form-control">

                <?php foreach ($tutors as $tutor) { ?>

                    <option value="<?= $tutor['id'] ?>"<?= $tutor['id'] == $_SESSION['tutor']['id'] ? ' selected' : '' ?>>
                        <?= $tutor['business_name'] ?>
                    </option>

                <?php } ?>

                </select>
            </div>
        </div>

        <?php } ?>

        <div class="form-group">
            <label class="col-sm-3 control-label" for="validity_start">Valida dal</label>
            <div class="col-sm-9 controls">
                <input type="text" name="validity_start" class="form-control datepicker">
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Salva</button>
        </div>
    </form>
</div>

<?php } else {
    $validity_start = new DateTime($plan_detail['validity_start']);
    $validity_end = new DateTime($plan_detail['validity_end']); 
    ?>

<div id="company-license" class="container-fluid">
    <form class="form-horizontal">
        <div class="form-group">
            <label class="col-sm-3 control-label" for="plan_id">Tipo di licenza</label>
            <div class="col-sm-9 controls">
                <select name="plan_id" class="form-control">
                    <option value="0" selected>Seleziona una licenza</option>
                    
                <?php foreach ($plans as $plan) { 
                    if (($plan['for_tutor'] && $_SESSION['user']['role'] == 1000 && $company['is_tutor'])
                            || (!$plan['for_tutor'] && !$company['is_tutor'])){
                    ?>

                    <option value="<?= $plan['id'] ?>"
                            <?= $plan['id'] == $plan_detail['plan_id'] ? ' selected' : '' ?>>
                        <?= $plan['short_desc_plan'] ?>
                    </option>

                    <?php } 
                } ?>

                </select>
            </div>
        </div>
        
        <?php if ($_SESSION['user']['role'] == 1000) { ?>
        
        <div class="form-group">
            <label class="col-sm-3 control-label" for="tutor_id">Ente formativo</label>
            <div class="col-sm-9 controls">
                <select name="tutor_id" class="form-control">

                <?php foreach ($tutors as $tutor) { ?>

                    <option value="<?= $tutor['id'] ?>"
                        <?= $tutor['id'] == $plan_detail['tutor_id'] ? ' selected' : '' ?>>
                        <?= $tutor['business_name'] ?>
                    </option>

                <?php } ?>

                </select>
            </div>
        </div>

        <?php } ?>

        <div class="form-group">
            <label class="col-sm-3 control-label" for="validity_start">Valida dal</label>
            <div class="col-sm-9 controls">
                <input type="text" name="validity_start" class="form-control datepicker" 
                       data-date-format="dd/mm/yyyy" value="<?= $validity_start->format('d/m/Y') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label" for="validity_end">Valida fino a</label>
            <div class="col-sm-9 controls">
                <input type="text" name="validity_end" class="form-control datepicker" 
                       data-date-format="dd/mm/yyyy" value="<?= $validity_end->format('d/m/Y') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Salva</button>
        </div>
    </form>
</div>

<?php } ?>
<script>

    /* Inizializzazione calendari */
    var now = new Date();
    $('#company-license .datepicker').datepicker({
            format: "dd/mm/yyyy",
            todayBtn: "linked",
            language: "it",
            autoclose: true,
            todayHighlight: true
        }).on('show', function (e) {
            $('#company-license .datepicker.dropdown-menu').css('z-index', '10000');
        }).on('hide', function (e) {
            $('#company-license .datepicker.dropdown-menu').css('z-index', '1000');
        });
    
    <?php if (!$plan_detail) {?>
        
    $('[name="validity_start"]').datepicker('update', $.datepicker.formatDate('dd-mm-yy', now));
    
    function assignLicense(){
        $.post('manage/company.php',{
            op_type: 'assign_company_plan',
            plan_id: $('[name="plan_id"]').val(),
            tutor_id: $('[name="tutor_id"]').val(),
            company_id: <?= $company['id'] ?>,
            validity_start: $.datepicker.formatDate('yy-mm-dd', $('[name="validity_start"]').datepicker('getDate'))
            }, function(data){
                if (data > 0) location.reload();
            }
        );
    }
    
    $('#company-license form').on('submit', function(e){
        e.preventDefault();
        if ($('[name="plan_id"]').val() == 0) alert('Seleziona una licenza');
        else assignLicense();
    });
    <?php } else { ?>
        
    function editLicense(){
        $.post('manage/company.php',{
            op_type: 'edit_company_plan',
            id: <?= $plan_detail['id'] ?>,
            plan_id: $('[name="plan_id"]').val(),
            tutor_id: $('[name="tutor_id"]').val(),
            validity_start: $.datepicker.formatDate('yy-mm-dd', $('[name="validity_start"]').datepicker('getDate')),
            validity_end: $.datepicker.formatDate('yy-mm-dd', $('[name="validity_end"]').datepicker('getDate'))
            }, function(data){
                if (data > 0) location.reload();
            }
        );
    }
    
    $('#company-license form').on('submit', function(e){
        e.preventDefault();
        if ($('[name="plan_id"]').val() == 0) alert('Seleziona una licenza');
        else editLicense();
    });
    
    <?php } ?>
        
    
        
</script>