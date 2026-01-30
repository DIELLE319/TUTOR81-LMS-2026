<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 25-lug-2015
 * File: bar-company.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';
require_once 'lib/class_company.php';
$company_obj = new T81Company();

$user_name = strtoupper("{$_SESSION['user']['name']} {$_SESSION['user']['surname']}");
?>
<nav class="navbar navbar-fixed-top navbar-area navbar-area-<?= $area ?> hidden">
    <div class="container-fluid">
        <div class="col-sm-9 navbar-area-left">
            <a href="<?= $area ?>/home" class="icon"><span class="glyphicon glyphicon-home"></span></a>
            &nbsp;
            Benvenuto <?= $user_name ?>
<?php if ($area === 'tutor' || $area === 'member') { ?>
        
            ti trovi nell'AREA <span class="text-nowrap">AMMINISTRAZIONE ENTE FORMATIVO</span>
        
<?php } elseif ($area === 'admin') { ?>
        
            ti trovi nell'AREA <span class="text-nowrap">AMMINISTRAZIONE SUPERUTENTE</span>
        
<?php } elseif ($area === 'company') { ?>
    
            ti trovi nell'AREA <span class="text-nowrap">DI GESTIONE AZIENDA:</span> <span class="text-nowrap"><?= strtoupper($_SESSION['company']['business_name']) ?></span>
        
<?php } elseif ($area === 'user') { ?>
    
            i trovi nell'AREA SVOLGIMENTO CORSI
        
<?php } ?>
        
        </div>
        
        <div class="col-sm-3 navbar-area-right">
            
            
                
            <?php if (($area === 'admin' || $area === 'tutor') && $_SESSION['user']['role'] == 1000){?>

            <a href="#tutorSelectionModal" data-toggle="modal">SELEZIONA ENTE</a>&nbsp;

            <?php } ?>
                
            <?php if ($area === 'company' && ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32)){?>

            <a href="#companySelectionModal" data-toggle="modal">SELEZIONA AZIENDA</a>&nbsp;

            <?php } ?>
            
            <a href="javascript:history.back()" class="icon"><span class="glyphicon glyphicon-chevron-left"></span></a>
            
            <a href="javascript:history.forward()" class="icon"><span class="glyphicon glyphicon-chevron-right"></span></a>

        </div>
    </div>
</nav>

<?php if (($area === 'admin' || $area === 'tutor') && $_SESSION['user']['role'] == 1000){ 
    $tutors = $company_obj->getBusinessTutor();
?>

<!-- Modal -->
<div class="modal fade" id="tutorSelectionModal" tabindex="-1" role="dialog" aria-labelledby="tutorSelectionModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="tutorSelectionModalLabel">Seleziona un Ente Formativo</h4>
            </div>
            <div class="modal-body">
                <select class="form-control">
                    
                <?php foreach ($tutors as $single_tutor) { ?>
                
                    <option value="<?= $single_tutor['id'] ?>" <?php if ($single_tutor['id'] == $_SESSION['tutor']['id']) echo 'selected'; ?>>
                    <?= strtoupper($single_tutor['business_name']) ?></option>
                    
                <?php } ?>
                    
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
                <button type="button" class="btn btn-primary" onclick="location.href = 'tutor/home?tutor=' + $('#tutorSelectionModal select').val();">Visualizza ente</button>
            </div>
        </div>
    </div>
</div>

<?php } elseif ($area === 'company' && ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32)){ 
    $companies = $company_obj->getCompanyByTutorCompany($_SESSION['tutor']['id']);
?>

<!-- Modal -->
<div class="modal fade" id="companySelectionModal" tabindex="-1" role="dialog" aria-labelledby="companySelectionModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="companySelectionModalLabel">Seleziona un'azienda</h4>
            </div>
            <div class="modal-body">
                <select class="form-control">
                    
                <?php foreach ($companies as $company) { ?>
                
                    <option value="<?= $company['id'] ?>" <?php if ($company['id'] == $_SESSION['tutor']['id']) echo 'selected'; ?>>
                    <?= strtoupper($company['business_name']) ?></option>
                    
                <?php } ?>
                    
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
                <button type="button" class="btn btn-primary" onclick="location.href = location.href.split('?')[0] + '?company_id=' + $('#companySelectionModal select').val();">Visualizza Azienda</button>
            </div>
        </div>
    </div>
</div>

 <?php }