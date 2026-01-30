<?php
require_once 'class_report.php';

$report_obj = new iWDReport();

$op_type = $_REQUEST['op_type'];


/**
 * LOAD SESSION USER
 */
if ($op_type == "load_sessions_user"){
	if (isset($_POST['learning_event_id'])) $sessions_user = $report_obj->getSessionsUser($_REQUEST['user_id'], $_POST['learning_event_id']);
	else $sessions_user = $report_obj->getSessionsUser($_REQUEST['user_id']);
	if ($sessions_user){	
		$learning_event_id = $sessions_user[0]['learning_event_id'];
		$learning_project['title'] = "NON DEFINITO";
		if ($learning_event_id){
			$learning_project = $report_obj->getLearnDetailByLearningEvent($learning_event_id);
		}
		$res = <<< EOT
<ul class="sessions_report">
	<li class="course_title">CORSO {$learning_project['title']}</li>
	<ul class="course_detail">	
EOT;

		$session_id = "";
		$end_session = "";
		$end_this_session = "";
		foreach ($sessions_user as $session){
			$start_session = new DateTime($session['start_session'], new DateTimeZone('Europe/Rome'));
			$start_object = new DateTime($session['start_object'], new DateTimeZone('Europe/Rome'));
			$end_object = new DateTime($session['end_object'], new DateTimeZone('Europe/Rome'));
			$end_session = new DateTime($session['end_session'], new DateTimeZone('Europe/Rome'));
			$duration_object = $start_object->diff($end_object);
			if ($session['learning_event_id']){
				$learning_project = $report_obj->getLearnDetailByLearningEvent($session['learning_event_id']);
			}
			$learning_object = $report_obj->getLearningObject($session['object_id']);
			
			if ($session['learning_event_id'] != $learning_event_id ) {	
				$res .= <<< EOT
		</ul> <!-- end session detail -->
		<li class="session_end"><span class="datetime session_datetime session_duration">{$duration_session}</span><span class="datetime session_datetime session_end">{$end_this_session}</span></li>
	</ul> <!-- end course detail -->
		<li class="course_title">CORSO {$learning_project['title']}</li>
		<ul class="course_detail">
			<div>
				<li class="session_start">Sessione id: <em>{$session['session_id']}</em> <span class="datetime session_datetime session_start">{$start_session->format('d/m/Y H:i:s')}</span></li>
				<ul class="session_detail">
EOT;
				$learning_event_id = $session['learning_event_id'];
				$end_this_session = $session['end_session'] == "0000-00-00 00:00:00" ? ($session['end_object'] == "0000-00-00 00:00:00" ? "---" : $end_object->format('d/m/Y H:i:s')) : $end_session->format('d/m/Y H:i:s');
				$duration_session = $session['end_session'] == "0000-00-00 00:00:00" ? $start_session->diff($end_object) : $start_session->diff($end_session);
				if ($session['end_session'] != "0000-00-00 00:00:00" || $session['end_object'] != "0000-00-00 00:00:00") {
					if ($duration_session->format('%h') != 0){
						$duration_session = $duration_session->format('%h ore %i minuti %s secondi');
					} elseif ($duration_session->format('%i') != 0) {
						$duration_session = $duration_session->format('%i minuti %s secondi');
					} elseif ($duration_session->format('%s') != 0) {
						$duration_session = $duration_session->format('%s secondi');
					} else {
						$duration_session = $duration_session->format('%s');
					}
				} else {
					$duration_session = "0";
				}
				$session_id = $session['session_id'];
			} elseif ($session['session_id'] != $session_id){
				if ($session_id){
					
					$res .= <<< EOT
				</ul> <!-- end session detail -->
				<li class="session_end"><span class="datetime session_datetime session_duration">{$duration_session}</span><span class="datetime session_datetime session_end">{$end_this_session}</span></li>
			</div>
EOT;
				}
			
				$res .= <<< EOT
			<div>
				<li class="session_start">Sessione id: <em>{$session['session_id']}</em><span class="datetime session_datetime session_start">{$start_session->format('d/m/Y H:i:s')}</span><span></li>
				<ul class="session_detail">
EOT;
				$session_id = $session['session_id'];
			}
			
			$duration = $session['end_object'] == '0000-00-00 00:00:00' ? "---" : $duration_object->format('%h ore %i minuti %s secondi');
			$end_this_object = $session['end_object'] == '0000-00-00 00:00:00' ? "---" : $end_object->format('d/m/Y H:i:s');
			$res .= <<< EOT
				<li class="object_detail clearfix">
					<span class="object_title">{$learning_object['title']}</span>
					<span class="datetime object_datetime object_end">{$end_this_object}</span>
					<span class="datetime object_datetime object_start">{$start_object->format('d/m/Y H:i:s')}</span>
					<span class="datetime object_datetime object_duration">{$duration}</span>
				</li>
EOT;

			$end_this_session = $session['end_session'] == "0000-00-00 00:00:00" ? ($session['end_object'] == "0000-00-00 00:00:00" ? "---" : $end_object->format('d/m/Y H:i:s')) : $end_session->format('d/m/Y H:i:s');
			$duration_session = $session['end_session'] == "0000-00-00 00:00:00" ? $start_session->diff($end_object) : $start_session->diff($end_session);
			if ($session['end_session'] != "0000-00-00 00:00:00" || $session['end_object'] != "0000-00-00 00:00:00") {
				if ($duration_session->format('%h') != 0){
					$duration_session = $duration_session->format('%h ore %i minuti %s secondi');
				} elseif ($duration_session->format('%i') != 0) {
					$duration_session = $duration_session->format('%i minuti %s secondi');
				} elseif ($duration_session->format('%s') != 0) {
					$duration_session = $duration_session->format('%s secondi');
				} else {
					$duration_session = $duration_session->format('%s');
				}
			} else {
				$duration_session = "0";
			}
		}
		
		$res .= <<< EOT
				</ul> <!-- end session detail -->
				<li class="session_end"><span class="datetime session_datetime session_duration">{$duration_session}</span><span class="datetime session_datetime session_end">{$end_this_session}</span></li>
			</div>
		</ul> <!-- end session detail -->
	</ul> <!-- end course detail -->
</ul> <!-- end sessions report -->
EOT;
		
	} else {
		$res = '<p>Nessuna sessione registrata</p>';
	}
}

echo $res;