<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 10-giu-2015
 * File: modals/avviacorso.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_user.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

$user_obj = new T81User();
$learn_obj = new T81LearningProject();

$learning_project_user_id = filter_input(INPUT_GET, 'learning_project_user_id', FILTER_SANITIZE_NUMBER_INT);

$license = $user_obj->getUserLicenseById($learning_project_user_id);

?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"
            aria-hidden="true">×</button>
    <h3 id="myModalLabel">Avvia corso</h3>
</div>
<div class="modal-body">

<?php
if ($license  && ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['id'] == $license['user_id'])){
    $course = $learn_obj->getCourseDetailFromLearningProject($license['learning_project_id']);
    $today = new DateTime('now', new DateTimeZone('Europe/Rome'));
    $programmato = isset($license['starting_from']) && $license['starting_from'] != '0000-00-00' ?
            DateTime::createFromFormat('Y-m-d', $license['starting_from'], new DateTimeZone('Europe/Rome')) :
            DateTime::createFromFormat('Y-m-d H:i:s', $license['creation_date'], new DateTimeZone('Europe/Rome'));


    if (isset($license['finish_within']) && $license['finish_within'] != '0000-00-00') {
        $termine = DateTime::createFromFormat('Y-m-d', $license['finish_within'], new DateTimeZone('Europe/Rome'));
    } else {
        $termine = clone $programmato;
        $termine->add(new DateInterval("P{$course['max_execution_time']}D"));
    }

    $avviato = isset($license['start_date_time']) && $license['start_date_time'] != '0000-00-00 00:00:00' ?
            DateTime::createFromFormat('Y-m-d H:i:s', $license['start_date_time'], new DateTimeZone('Europe/Rome')) : false;

    $attivo = (int)$programmato->diff($today)->format('%R%a') >= 0;
    $scaduto = (int)$today->diff($termine)->format('%R%a') < 0;
    
    $user = $user_obj->getDetail($license['user_id']);
    $learning_project = $learn_obj->getDetail($license['learning_project_id']);
?>
    
    <h3><small>Corso: </small> <?= strtoupper(substr($learning_project['title'], strpos($learning_project['title'], ' - ') + 3)) ?></h3>
    <br>

    <?php
    if (!$scaduto && $attivo){?>

    <form id="play-course" class="form-horizontal" action="<?= URL_PLAYER . 'lib/login.php' ?>" method="POST">
        <div class="form-group">
            <label class="col-xs-4 control-label" for="username">Nome Utente</label>
            <div class="col-xs-8 controls">
                <input type="text" name="username" class="form-control" value="<?= $user['username'] ?>" readonly>
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-4 control-label" for="password">Licenza</label>
            <div class="col-xs-8 controls">
                <input type="text" name="password" class="form-control" value="<?= $license['learning_project_pwd']?>" readonly>
            </div>
        </div>
        <input type="hidden" name="mode" value="learning">
        <input type="hidden" name="tos_authorized" value="on">
        <input type="hidden" name="in_working_time" value="1">
    </form>

    <?php } elseif(!$attivo){?>
    
    <h3>Corso non attivo</h3>
    <p>Il corso è programmato per iniziare in una data futura</p>

    <?php } else {?>
    
    <h3>Corso scaduto</h3>
    <p>Il termine programmato per completare il corso è stato superato.</p>

    <?php }
    
} else { ?>
    
    <h3>Non sei autorizzato alla visione di questo corso</h3>

<?php } ?>
    
</div>
<div class="modal-footer">
    <button class="btn btn-info" onclick="javascript: $('#play-course').submit()">Avvia corso</button>
    <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Chiudi</button>
</div>