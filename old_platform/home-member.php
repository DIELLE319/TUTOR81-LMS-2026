<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 03-lug-2015
 * File: home-member.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';
if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once '403.php';
    exit();
}
require_once 'lib/class_company.php';
require_once 'lib/class_report.php';
$comp_obj = new T81Company();
$report_obj = new Report();

$companies = $comp_obj->getCompanyByTutorCompany($_SESSION['tutor']['id']);
$users_have_sessions = $comp_obj->getUsersHaveSessionsByTutorCompany($_SESSION['tutor']['id']);
$tutors = $comp_obj->getUsersCompanyByID($_SESSION['tutor']['id'], 1);
$didactic_tutor = $comp_obj->getDidacticTutor($_SESSION['tutor']['id']);
if (isset($didactic_tutor['user_id']) && $didactic_tutor['user_id'] != 6) {
    $didactic_tutor_name = ucwords("{$didactic_tutor['name']} {$didactic_tutor['surname']}");
} else {
    $didactic_tutor_name = "Luca Pedretti";
}
$license_detail = $comp_obj->getCompanyPlan($_SESSION['tutor']['id']);
?>
            <div id="companies" class="panel panel-default main-menu">
                <div class="panel-heading main-menu-heading">
                    I tuoi clienti
                    <a href="javascript: void(0);" class="createCompanyModal pull-right">
                        <span class="glyphicon glyphicon-plus"></span> aggiungi
                    </a>
                </div>
                <div id="companies-list" class="main-menu-body">
                    <table class="table table-striped table-condensed">
                        <tbody>

                    <?php
                    if ($companies)
                        foreach ($companies as $company){$delete = false;
                        $users = $comp_obj->getAllUsersCompanyByID($company['id']);
                        if (!$users) {
                            $companies = $comp_obj->getCompanyByTutorCompany($company['id']);
                            if (!$companies) {
                                $purchases = $comp_obj->getPurchaseByCompany($company['id']);
                                if (!$purchases) $delete = true;
                            }
                        }?>

                            <tr data-company_id="<?= $company['id'] ?>">
                                <td><?= strtoupper($company['business_name']) ?></td>
                                <td class="action text-right" style="width: 40px;">
                                    <?= $delete ? '<a href="javascript: void(0)" class="delete"><span class="glyphicon glyphicon-remove red"></span></a>' : '' ?>

                                    <?php if ($_SESSION['user']['company']['is_tutor'] == "1") { ?>

                                    <a href="javascript: void(0)" class="edit"><span class="glyphicon glyphicon-pencil"></span></a>

                                    <?php } ?>

                                </td>
                            </tr>

                    <?php } ?>

                        </tbody>
                    </table>
                </div>
            </div>
<!--
<!-- ------------------------------------------------------------------------------------------------------------------------- -->
<!-- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -->
<!-- ------------------------------------------------------------------------------------------------------------------------- -->
<div id="createCompanyModal" class="modal fade" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        </div>
    </div>
    
</div>

<script> 
$(function(){
    
    fitMainMenu();
    
    $('#monitor .panel-body').load("pages/monitor.php", {tutor_id: <?= $_SESSION['tutor']['id'] ?>, area: '<?= $area ?>'});

    $('.graph-progress .graph').load('graphs/tutor-total-progress-bar.php');

    $('#feedback .panel-body').load('report/feedback.php?is_tutor=true&company_id=<?= $_SESSION['tutor']['id'] ?>');
    
    $('.graph-progress h3 a').click(function(){
        $('.graph-progress .graph')
            .html('<img class="preloader" src="img/preloader-snake-blue.gif">')
            .load('graphs/tutor-total-progress-bar.php');
    });
    
    $('#sessions select').change(function(){
        $('#sessions .user-sessions')
            .html('<img class="preloader" src="img/preloader-snake-blue.gif">')
            .load('report/sessions.php?user_id=' + $(this).val());
    });

    $('#companies td.action > a').click(function(e){
       e.stopPropagation(); 
    });
    
    /* ******** MODAL CREA NUOVA COMPANY ********** */
    $('.createCompanyModal').click(function(){
       $('#createCompanyModal').modal().find('.modal-content').load('modals/new-company.php');
    });
    
    /* link alla pagina dell'azienda */
    $('#companies tbody > tr').click(function(){
        location.href = 'company/home?company_id=' + $(this).data('company_id');
    });
    
    /* Apre la modal per la modifica dell'azienda */
    $('#companies tbody > tr .action .edit').click(function(){
        var company_id = $(this).parents('tr').data('company_id');
        $('#simpleModal').modal()
                .find('.modal-content')
                .html('<img src="img/loading_gif.gif" />')
                .load('modals/edit-company.php?company_id=' + company_id);
    }); 
    
    /* Elimina l'azienda */
    $('#companies tbody > tr .action .delete').click(function(){
        var company_id = $(this).parents('tr').data('company_id');
        var company_name = $(this).parents('tr').children().first().text();
        var deleted = false;
        if (confirm("vuoi eliminare l'azienda " + company_name + "?")) { 
            deleted = deleteCompany(company_id);
        }
        if (deleted) location.reload();
    });   
    
    $(window).resize(function(){fitMainMenu();});

});
</script>