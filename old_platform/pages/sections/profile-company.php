<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 09-set-2015
 * File: pages/sections/profile-company.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_safety.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
$comp_obj = new T81Company();
$safety_obj = new Safety();
$report_obj = new Report();

$company = $_SESSION['company'];
$assignments = $safety_obj->getAssignments();
$license_detail = $comp_obj->getCompanyPlan($company['id']);
$tutor = $comp_obj->getDetail($company['owner_user_id']);
$company_managers = $comp_obj->getUsersCompanyByID($company['id'], 2);
$didactic_tutor = $comp_obj->getDidacticTutor($company['id']);
?>
<div class="row">
    <div id="company-info" class="col-sm-4 col-lg-6">
        <h4><?= strtoupper($_SESSION['company']['business_name']) ?></h4>
        <h4><?= $_SESSION['company']['address'] ?></h4>
        <h4><?= $_SESSION['company']['city'] ?></h4>
        <br>
        <div>
            <h4>
                <span class="glyphicons keys" style="vertical-align: top;"></span>
                Accesso piattaforma
                
                <?php if ($license_detail) { ?>
                
                <a href="javascript: void(0)" class="createAdminModal"><span class="glyphicon glyphicon-<?= $_SESSION['user']['role'] == 1000 ? 'plus' : 'question-sign' ?>"></span></a>
                
                <?php } ?>
                
            </h4>
            <?php if ($company_managers) {
                $i = count($company_managers);
                foreach ($company_managers as $manager) { ?>

                <a href="mailto:<?= $manager['email'] ?>"><?= ucwords("{$manager['surname']} {$manager['name']}") ?></a>
                <?= --$i > 0 ? ", " : "" ?>

                <?php } 
            } ?>
        </div>
    </div>
    <div class="col-sm-4 col-lg-3">
        <dl class="dl-horizontal">
            <dt>Dipendenti inseriti:</dt>
            <dd><?= $comp_obj->countUsersCompanyByBusinessFunction($company['id']) ?></dd>
            <dt>Lavoratori:</dt>
            <dd><?= $comp_obj->countUsersCompanyByBusinessFunction($company['id'], 1) ?></dd>
            <dt>Preposti:</dt>
            <dd><?= $comp_obj->countUsersCompanyByBusinessFunction($company['id'], 3) ?></dd>
            <dt>Dirigenti:</dt>
            <dd><?= $comp_obj->countUsersCompanyByBusinessFunction($company['id'], 7) ?></dd>
        </dl>
        
        <?php if ($license_detail) { ?>
        <h4>Tipo licenza</h4>
            <div class="company-license<?= $license_detail['suspended'] ? ' alert alert-warning' : '' ?>">
                <?= $license_detail['suspended'] ? '(sospesa)' : '' ?>
                <?= $license_detail['short_desc_plan'] ?>

                <?php if ($_SESSION['user']['role'] == 1000) { 
                    if ($license_detail['suspended']) {?>

                <a href="javascript: void(0)" class="active" title="Attiva licenza" data-toggle="tooltip">
                    <span class="glyphicon glyphicon-ok"></span>
                </a>                

                    <?php } else { ?>

                <a href="javascript: void(0)" class="edit" title="Modifica licenza" data-toggle="tooltip">
                    <span class="glyphicon glyphicon-pencil"></span>
                </a>
                <a href="javascript: void(0)" class="remove" title="Sospendi licenza" data-toggle="tooltip">
                    <span class="glyphicon glyphicon-remove"></span>
                </a>

                    <?php } 
                } ?>
            </div>

            <?php } else { ?>

            <div class="company-license alert alert-danger">
                Nessuna licenza attiva

                <?php if ($_SESSION['user']['role'] == 1000) { ?>

                <a href="javascript: void(0)" class="alert-link add"><span class="pull-right glyphicon glyphicon-plus"></span></a>

                <?php } ?>

            </div>

            <?php } ?>

    </div>
    <div class="col-sm-4 col-lg-3">
        <strong><em>SERVIZIO PP</em></strong>
        <br><br>
        <table style="border-collapse: separate; border-spacing: 10px 0; white-space: nowrap;">
            <tbody>
                
            <?php foreach ($assignments as $assign) { 
                $user_assignments = $safety_obj->getUserAssgnmentsFromCompanyAndAssignId($company['id'], $assign['id_assign'])
                ?>
            
                <tr style="vertical-align: top;">
                    <td class="text-right">
                        <strong><em><?= $assign['very_short_desc_assign']?>:</em></strong>
                    </td>
                    <td>
                        
                        <?php if ($user_assignments) {
                            foreach ($user_assignments as $user_assign){ ?>
                                
                            <?= strtoupper($user_assign['surname'] . " " . $user_assign['name']) ?><br>
                            
                            <?php }
                        } ?>
                        
                    </td>                    
                </tr>
            
            <?php } ?>
            
            </tbody>
        </table>
    </div>
</div>


<!-- ------------------------------------------------------------------------------------------------------------------------- -->
<!-- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -- MODAL CREA DIPENDENTE -->
<!-- ------------------------------------------------------------------------------------------------------------------------- -->
<div id="createAdminModal" class="modal fade" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        </div>
    </div>
</div>
<script>
    $('.company-license').on('click', '.add, .edit', function(){
        $('#simpleModal').modal()
                .find('.modal-content')
                .html('<img src="img/loading_gif.gif" />')
                .load('modals/company-license.php');
    });
    
<?php if ($license_detail) { ?>

    $('.company-license').on('click', '.remove', function(){
        bootbox.confirm("Vuoi sospendere la licenza attualmente attiva assegnata a questa azienda?", function(reponse) {
            if (reponse) {
                suspendedCompanyLicense(<?= $license_detail['id'] ?>, 'true');
                location.reload();
            }
        });
    });
    $('.company-license').on('click', '.active', function(){
        bootbox.confirm("Vuoi attivare la licenza attualmente sospesa assegnata a questa azienda?", function(reponse) {
            if (reponse) { 
                suspendedCompanyLicense(<?= $license_detail['id'] ?>, 'false');
                location.reload();
            }            
        });
    });
    
    
    
    /* ******** MODAL CREA NUOVO UTENTE ********** */
    $('.createAdminModal').click(function(){
    
    <?php if ($_SESSION['user']['role'] == 1000) { ?>
            
       $('#createAdminModal').modal().find('.modal-content').load('modals/new-employee.php?role=2');
       
    <?php } else { ?>
        
        alert('Per creare un responsabile aziendale chiedi al tuo referente.');
        
    <?php } ?>
        
    });
        
    /* ******** SALVA CREA NUOVO UTENTE ********** */
    $('#createAdminModal').on('click', '.save-modal', function(){
        $.isLoading({text: "Attendere ..."});
        var user = newUser(<?= $_SESSION['company']['id'] ?>, <?= $_SESSION['user']['id'] ?>);
        if (user) {
            $('#createAdminModal').removeClass('fade').modal('hide'); // rimuove la classe fade per eliminare l'animazione che si blocca
            alert("Dipendente creato correttamente");
            location.reload();
        }
        $.isLoading("hide");
    });

<?php } ?>

</script>