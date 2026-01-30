<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 24-lug-2015
 * File: modals/schdule-license.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32 && $_SESSION['user']['role'] != 2) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_user.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

$user_obj = new T81User();
$learn_obj = new T81LearningProject();

$learning_project_user_id = filter_input(INPUT_GET, 'learning_project_user_id', FILTER_SANITIZE_NUMBER_INT);

$license = $user_obj->getUserLicenseById($learning_project_user_id);
$user = $user_obj->getDetail($license['user_id']);
$learning_project = $learn_obj->getCourseDetailFromLearningProject($license['learning_project_id']);
$today = new DateTime('now', new DateTimeZone('Europe/Rome'));
if (isset($license['starting_from']) && $license['starting_from'] !== '0000-00-00'){
    $start_date = DateTime::createFromFormat("Y-m-d", $license['starting_from'], new DateTimeZone('Europe/Rome'));
    $max_end_date = DateTime::createFromFormat("Y-m-d", $license['starting_from'], new DateTimeZone('Europe/Rome'));

} else {
    $start_date = DateTime::createFromFormat("Y-m-d H:i:s", $license['creation_date'], new DateTimeZone('Europe/Rome'));
    $max_end_date = DateTime::createFromFormat("Y-m-d H:i:s", $license['creation_date'], new DateTimeZone('Europe/Rome'));
}
$max_execution_time = $learning_project['max_execution_time']*4; // aumentato a 4 volte il tempo di esecuzione massimo
$max_end_date->add(new DateInterval('P'.$max_execution_time.'D'));
if ($max_end_date < $today) {?>

<h3>Termine massimo superato<br>
    <small>il tempo massimo per la conclusione del corso (<?=$max_execution_time?> gg) 
        è stato superato. Non è più possibile posticipare la scadenza</small></h3>

<?php }else {
    $max_end_date = $max_end_date->format('d/m/Y');
    if (isset($license['finish_within']) && $license['finish_within'] !== '0000-00-00') {
        $current_end_date = DateTime::createFromFormat("Y-m-d", $license['finish_within'], new DateTimeZone('Europe/Rome'));
        $current_end_date = $current_end_date->format('d/m/Y');
    } else {
        $current_end_date = $max_end_date;
    }
?>

<script>
function scheduleLicense(){
    $.isLoading({text: "Attendere il completamento ..."});
    var end = $('#schedule-license input[name="end"]').datepicker('getDate');
    end = $.datepicker.formatDate('yy-mm-dd', end);
    $.ajax({
        type: "POST",
        url: 'manage/license.php',
        data: {
            op_type: 'schedule_license',
            license_id: <?=$learning_project_user_id?>,
            starting_from: '<?=$start_date->format("Y-m-d")?>',
            finish_within: end,
            days_to_alert: $('#schedule-license input[name="alert"]').val(),
            send_mail: $('#schedule-license input[name="send_mail"]')[0].checked
        }
    }).done(function(){
        alert('Data e alert modificati.');
        location.reload();
    });
}
</script>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"
            aria-hidden="true">×</button>
    <h3 id="myModalLabel">Modifica scadenza corso</h3>
</div>
<div class="modal-body">
    <div id="schedule-license">
        <h3><small>corsista: </small><?=ucwords(strtolower("{$user['surname']} {$user['name']}"))?></h3>
        <br>
        <div class="form-horizontal">
            <div class="form-group">
                <label class="col-xs-4 control-label" for="end">Imposta nuova scadenza </label>
                <div class="col-xs-8 controls">
                    <input type="text" name="end" class="form-control datepicker" value="<?=$current_end_date?>">
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-4 control-label" for="alert">Imposta nuovo alert </label>
                <div class="col-xs-8 controls">
                    <input type="number" name="alert" class="form-control" min="0" value="<?=$license['days_to_alert']?>">
                </div>
            </div>
            <div class="checkbox">
                <label class="checkbox">
                    <input type="checkbox" name="send_mail"> Invia email al corsista
                </label>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button class="btn btn-info" onclick="scheduleLicense()">Salva modifiche</button>
    <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Chiudi</button>
</div>

<script>
    $('#schedule-license input[name="end"]').datepicker({
	format: "dd/mm/yyyy",
  	startDate: '0d',
  	endDate: '<?=$max_end_date?>',
  	todayBtn: "linked",
  	language: "it",
  	autoclose: true,
  	todayHighlight: true
	}).on('show', function(e){
		$('.datepicker.dropdown-menu').css('z-index','10000');
	}).on('hide', function(e){
		$('.datepicker.dropdown-menu').css('z-index','1000');
	});
</script>
<?php }