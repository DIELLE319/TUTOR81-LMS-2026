<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

$tutor_id = filter_input(INPUT_POST, 'tutor_id', FILTER_SANITIZE_NUMBER_INT) ? : $_SESSION['tutor']['id'];
$company_id = filter_input(INPUT_POST, 'company_id', FILTER_SANITIZE_NUMBER_INT) ? : $_SESSION['company']['id'];
if ($_SESSION['user']['role'] != 1000) {
    if ((($_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32) && $_SESSION['user']['company']['id'] != $tutor_id)
            || ($_SESSION['user']['role'] == 2 && $_SESSION['user']['company']['id'] != $company_id)) {
        require_once BASE_ROOT_PATH . '403.php';
        return false;
    }
}

require_once BASE_LIBRARY_PATH . 'class_report.php';

$report_obj = new Report();

$area_monitor = filter_input(INPUT_POST, 'area', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH) ? : $area;

if ($area_monitor === "admin") $online_users = $report_obj->getOnlineUsersByTutorCompany();
elseif ($area_monitor === 'member' || $area_monitor === 'tutor') $online_users = $report_obj->getOnlineUsersByTutorCompany($tutor_id);
elseif ($area_monitor === 'company') $online_users = $report_obj->getOnlineUsersByCompany($_SESSION['company']['id']);
if (!$online_users){ ?>

<h4>Nessun utente collegato.</h4>

<?php } else { ?>

<table class="table table-sorter">
    <thead>
	<tr>
            <th>Cognome Nome</th>
            <?= $area_monitor !== 'company' ? "<th>Azienda</td>" : "";?>
            <th>Progetto formativo</th>
            <th>Oggetto formativo</th>
        </tr>
    </thead>
    <tbody>

    <?php if ($online_users) foreach($online_users as $user){?>
		
	<tr>
            <td><?=ucwords("{$user['surname']} {$user['name']}")?></td>
            <?= $area_monitor !== 'company' ? "<td>{$user['business_name']}</td>" : "";?>
            <td><?=strtoupper($user['title_project'])?></td>
            <td><?=strtoupper($user['title_object'])?></td>
        </tr>
			
    <?php } ?>
        
    </tbody>
</table>

<?php }