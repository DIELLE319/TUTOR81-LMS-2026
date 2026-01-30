<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 28-set-2015
 * File: pages/sections/profile-tutor.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
require_once BASE_LIBRARY_PATH . 'class_pack.php';

$comp_obj = new T81Company();
$report_obj = new Report();
$pack_obj = new T81Pack();

$tutors = $comp_obj->getUsersCompanyByID($_SESSION['tutor']['id'], 1);
$didactic_tutor = $comp_obj->getDidacticTutor($_SESSION['tutor']['id']);
if (isset($didactic_tutor['user_id']) && $didactic_tutor['user_id'] != 6) {
    $didactic_tutor_name = ucwords("{$didactic_tutor['name']} {$didactic_tutor['surname']}");
} else {
    $didactic_tutor_name = "Luca Pedretti";
}
$license_detail = $comp_obj->getCompanyPlan($_SESSION['tutor']['id']);
$pack_purchased = $pack_obj->getCurrentPackPurchased($_SESSION['tutor']['id']);
if ($license_detail) {
    $license_expiration_date = new DateTime($license_detail['validity_end']);
    $now = new DateTime('now');
    $license_expired = ($now > $license_expiration_date);
}
?>
<div class="row">
    <div class="col-sm-8">
        <dl class="dl-horizontal">
            <dt>Ragione sociale</dt>
            <dd><?= strtoupper($_SESSION['tutor']['business_name']) ?></dd>
            <dt>Indirizzo</dt>
            <dd><?= $_SESSION['tutor']['address'] ?></dd>
            <dt>Sede</dt>
            <dd><?= $_SESSION['tutor']['city'] ?></dd>
            <dt>Legale rappresentante</dt>
            <dd></dd>
            <dt>Autorizzazione regionale</dt>
            <dd><?= $_SESSION['tutor']['regional_authorization'] ?></dd>
            <dt>Licenza</dt>

            <?php if ($license_detail) { ?>

            <dd class="company-license<?= $license_detail['suspended'] || $license_expired ? ' alert alert-warning' : '' ?>">
                <?= $license_detail['suspended'] ? '(sospesa) ' : '' ?>
                <?= $license_detail['short_desc_plan'] ?>
                <?= $license_expired ? ' (scaduta)' : '' ?>

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
            </dd>

            <?php } else { ?>

            <dd class="company-license alert alert-danger">
                Nessuna licenza attiva

                <?php if ($_SESSION['user']['role'] == 1000) { ?>

                <a href="javascript: void(0)" class="alert-link add">
                    <span class="pull-right glyphicon glyphicon-plus"></span>
                </a>

                <?php } ?>

            </dd>

            <?php } ?>
        </dl>
    </div>
    <div class="col-sm-4">
        <h5>Amministratori piattaforma 
            <a href="javascript: void(0)" class="createEmployeeModal" title="Aggiungi un amministratore">
                <span class="glyphicon glyphicon-plus"></span>
            </a>
        </h5>
        <div style="max-height: 120px; overflow-y: scroll;">
            <?php 
            if (!empty($tutors)){ ?>
            
            <ul>

                <?php foreach ($tutors as $tutor) { ?>

                    <li>
                        <a href="company/home/employees?user_id=<?= $tutor['id'] ?>" data-admin_id="<?= $tutor['id'] ?>">
                            <?= ucwords(strtolower("{$tutor['surname']} {$tutor['name']}")) ?>
                        </a>
                    </li>

                    <?php } ?>

            </ul>
            
            <?php } else { ?>
                    
            <div class="alert alert-danger" role="alert"><strong>Attenzione!</strong> Aggiungi un amministratore</div>
                    
            <?php } ?>
        </div>

    </div>
</div>
<div class="row">
    <div class="col-sm-6">
        <h4><span class="glyphicon glyphicon-education"></span> TUTOR DIDATTICO ONLINE <span class="glyphicon glyphicon-info-sign"></span></h4>
        <p><?= $didactic_tutor_name ?></p>
    </div>
    <div class="col-sm-6">
        <h4>Pacchetti acquistati 
            
            <?php if ($license_detail && $license_detail['purchase_courses_at_packs'] && !$license_detail['suspended'] && !$license_expired) {?>
            
            <a class="add-purchase-pack" href="javascript: void(0)" title="Acquista un nuovo pacchetto" data-toggle="tooltip">
                <span class="glyphicon glyphicon-plus"></span>
            </a>
                    
            <?php } ?>
            
        </h4>
        <?php if ($pack_purchased) { ?>
        
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Contenuto</th>
                    <th>Disponibilità</th>
                    <th>Scadenza</th>
                </tr>
            </thead>
            <tbody>
                
            <?php foreach ($pack_purchased as $pack) { ?>
        
                <tr>
                    <td><strong><?= $pack['short_desc_pack_type'] ?></strong></td>
                    <td><?= $pack['content_type'] ?></td>
                    <td><?= $pack['content_available'] ?></td>
                    <td><?= $pack['expiration_date'] ?></td>
                </tr>
                    
            <?php } ?>
                
            </tbody>
        </table>
        
        <?php } else { ?>
        
        <div class="alert alert-warning" style="margin-bottom: 0px;">Nessun pacchetto attivo</div>
        
        <?php } ?>
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

<script>
    /*
     * Modal per impostare/modificare la licenza della piattaforma
     */
    $('.company-license').on('click', '.add, .edit', function(){
        $('#simpleModal').modal()
                .find('.modal-content')
                .html('<img src="img/loading_gif.gif" />')
                .load('modals/company-license.php');
    });
    
<?php if ($license_detail) { ?>
    /*
     * Sospesione della licenza della piattaforma
     */
    $('.company-license').on('click', '.remove', function(){
        bootbox.confirm("Vuoi sospendere la licenza attualmente attiva assegnata a questa azienda?", function(reponse) {
            if (reponse) {
                suspendedCompanyLicense(<?= $license_detail['id'] ?>, 'true');
                location.reload();
            }
        });
    });
    /*
     * Attivazione della licenza della piattaforma
     */
    $('.company-license').on('click', '.active', function(){
        bootbox.confirm("Vuoi attivare la licenza attualmente sospesa assegnata a questa azienda?", function(reponse) {
            if (reponse) { 
                suspendedCompanyLicense(<?= $license_detail['id'] ?>, 'false');
                location.reload();
            }            
        });
    });

<?php } ?>
    /*
     * Acquisto di un pacchetto corsi
     */
    $('.add-purchase-pack').click(function(){
        $('#simpleModal').modal()
            .on('hidden.bs.modal', function(){location.reload();})
            .find('.modal-content')
            .html('<img src="img/loading_gif.gif" />')
            .load('modals/purchase-pack.php');
    });
    
    /* ******** MODAL CREA NUOVO UTENTE ********** */
    $('.createEmployeeModal').click(function(){
       $('#createEmployeeModal').modal().find('.modal-content').load('modals/new-employee.php?role=1');
    });
        
    /* ******** SALVA CREA NUOVO UTENTE ********** */
    $('#createEmployeeModal').on('click', '.save-modal', function(){
        $.isLoading({text: "Attendere ..."});
        var user = newUser(<?= $_SESSION['tutor']['id'] ?>, <?= $_SESSION['user']['id'] ?>);
        if (user) {
            $('#createEmployeeModal').removeClass('fade').modal('hide'); // rimuove la classe fade per eliminare l'animazione che si blocca
            alert("Dipendente creato correttamente");
            location.reload();
        }
        $.isLoading("hide");
    });

</script>