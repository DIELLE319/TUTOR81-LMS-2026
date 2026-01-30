<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 13-lug-2015
 * File: pages/sections/employee-detail.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32 && $_SESSION['user']['role'] != 2) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

if (isset($_POST['user_id'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
} elseif (isset($_GET['user_id'])) {
    $user_id = filter_input(INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT);
} else {
    require_once BASE_ROOT_PATH . '404.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_departments.php';
require_once BASE_LIBRARY_PATH . 'class_safety.php';
require_once BASE_LIBRARY_PATH . 'class_permissions.php';
require_once BASE_LIBRARY_PATH . 'class_user.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';

$dep_obj = new Departments();
$safe_obj = new Safety();
$perm_obj = new Permissions();
$user_obj = new T81User();
$learn_obj = new T81LearningProject();
$comp_obj = new T81Company();

$employee_history = $dep_obj->getEmployeeDetail($user_id);

if (!$employee_history) {
    require_once BASE_ROOT_PATH . '404.php';
    return false;
}
$employee = $employee_history[0];
if ($_SESSION['user']['id'] != $user_id && ((($employee['role'] == 1000 || $employee['role'] == 1 || $employee['role'] == 32) && ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32)) || ($employee['role'] == 2 && ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32)) || empty($_SESSION['user']['role']))) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

if ($_SESSION['user']['role'] == 1) {
    $company = $comp_obj->getBusinessDetail($employee['user_company_id']);
    $tutor_detail = $user_obj->getDetail($company['owner_user_id']);
    $companies = $comp_obj->getCompanyByTutorCompany($tutor_detail['company_id']);
} elseif ($_SESSION['user']['role'] == 1000) {
    $companies = $comp_obj->getAllCompanies();
}

$department_types = $dep_obj->getDepartmentTypes($_SESSION['company']['id']);
$business_functions = $safe_obj->getBusinessFunctions();
$roles = $perm_obj->getRoles();
$roles_name = array();
foreach ($roles as $single_role) {
    $roles_name[$single_role['id_role']] = $single_role['short_desc_role'];
}

$product_units = $dep_obj->getProductUnits($_SESSION['company']['id']);
$departments = isset($employee['id_dep']) ? $dep_obj->getDepartmentsByProductUnit($employee['id_pu']) : false;

$rspp_assign = $safe_obj->getUserAssignments($user_id, 1);
$aspp_assign = $safe_obj->getUserAssignments($user_id, 5);
$rspp_dl_assign = $safe_obj->getUserAssignments($user_id, 6);
$rls_assign = $safe_obj->getUserAssignments($user_id, 2);
$antincendio_assign = $safe_obj->getUserAssignments($user_id, 3);
$soccorso_assign = $safe_obj->getUserAssignments($user_id, 4);

$now = new DateTime("now", new DateTimeZone('Europe/Rome'));
if (isset($employee_history[count($employee_history) - 1]['hire_date']))
    $employee_hire_date = new DateTime($employee_history[count($employee_history) - 1]['hire_date'], new DateTimeZone('Europe/Rome'));
else
    $employee_hire_date = false;

$start_date_rspp = DateTime::createFromFormat('Y-m-d', $rspp_assign[0]['assign_start_date'], new DateTimeZone('Europe/Rome'));
$end_date_rspp = DateTime::createFromFormat('Y-m-d', $rspp_assign[0]['assign_end_date'], new DateTimeZone('Europe/Rome'));
$is_RSPP = $rspp_assign && (!$rspp_assign[0]['assign_end_date'] || $end_date_rspp >= $now);

$start_date_aspp = DateTime::createFromFormat('Y-m-d', $aspp_assign[0]['assign_start_date'], new DateTimeZone('Europe/Rome'));
$end_date_aspp = DateTime::createFromFormat('Y-m-d', $aspp_assign[0]['assign_end_date'], new DateTimeZone('Europe/Rome'));
$is_ASPP = $aspp_assign && (!$aspp_assign[0]['assign_end_date'] || $end_date_aspp >= $now);

$start_date_rspp_dl = DateTime::createFromFormat('Y-m-d', $rspp_dl_assign[0]['assign_start_date'], new DateTimeZone('Europe/Rome'));
$end_date_rspp_dl = DateTime::createFromFormat('Y-m-d', $rspp_dl_assign[0]['assign_end_date'], new DateTimeZone('Europe/Rome'));
$is_RSPP_DL = $rspp_dl_assign && (!$rspp_dl_assign[0]['assign_end_date'] || $end_date_rspp_dl >= $now);

$start_date_rls = DateTime::createFromFormat('Y-m-d', $rls_assign[0]['assign_start_date'], new DateTimeZone('Europe/Rome'));
$end_date_rls = DateTime::createFromFormat('Y-m-d', $rls_assign[0]['assign_end_date'], new DateTimeZone('Europe/Rome'));
$is_RLS = $rls_assign && (!$rls_assign[0]['assign_end_date'] || $end_date_rls >= $now);

$start_date_antincendio = DateTime::createFromFormat('Y-m-d', $antincendio_assign[0]['assign_start_date'], new DateTimeZone('Europe/Rome'));
$end_date_antincendio = DateTime::createFromFormat('Y-m-d', $antincendio_assign[0]['assign_end_date'], new DateTimeZone('Europe/Rome'));
$is_ANTINCENDIO = $antincendio_assign && (!$antincendio_assign[0]['assign_end_date'] || $end_date_antincendio >= $now);

$start_date_soccorso = DateTime::createFromFormat('Y-m-d', $soccorso_assign[0]['assign_start_date'], new DateTimeZone('Europe/Rome'));
$end_date_soccorso = DateTime::createFromFormat('Y-m-d', $soccorso_assign[0]['assign_end_date'], new DateTimeZone('Europe/Rome'));
$is_SOCCORSO = $soccorso_assign && (!$soccorso_assign[0]['assign_end_date'] || $end_date_soccorso >= $now);



// CLASSIFICAZIONE DEL DIPENDENTE
// eredita da unità produttiva
$risk = $dep_obj->getProductUnitSpecificCustomCategories($employee['pu_id'], 2);
$employee['fire_risk'] = $risk ? $risk['ccat_id'] : false;

$risk = $dep_obj->getProductUnitSpecificCustomCategories($employee['pu_id'], 3);
$employee['first_aid_risk'] = $risk ? $risk['ccat_id'] : false;

$risk = $dep_obj->getProductUnitSpecificCustomCategories($employee['pu_id'], 4);
$employee['50dip_risk'] = $risk ? $risk['ccat_id'] : false;

$risk = $dep_obj->getProductUnitAteco($employee['pu_id']);
$employee['ateco_risk'] = $risk ? $risk['ateco_risk_id'] : false;


// incarichi
$employee['assignments'] = array();
if ($is_RSPP)
    array_push($employee['assignments'], 1);
if ($is_ASPP)
    array_push($employee['assignments'], 5);
if ($is_RSPP_DL)
    array_push($employee['assignments'], 6);
if ($is_RLS)
    array_push($employee['assignments'], 2);
if ($is_ANTINCENDIO)
    array_push($employee['assignments'], 3);
if ($is_SOCCORSO)
    array_push($employee['assignments'], 4);



// CLASSIFICAZIONE DEI CORSI 
$learning_needs = $safe_obj->getSafetyLearningNeeds();
foreach ($learning_needs as $key => $learning_need) {
    $risk = $safe_obj->getLearningNeedCustomCategory($learning_need['id_learning_need'], 2);
    $learning_needs[$key]['fire_risk'] = $risk ? $risk['ccat_id'] : false;

    $risk = $safe_obj->getLearningNeedCustomCategory($learning_need['id_learning_need'], 3);
    $learning_needs[$key]['first_aid_risk'] = $risk ? $risk['ccat_id'] : false;

    $risk = $safe_obj->getLearningNeedCustomCategory($learning_need['id_learning_need'], 4);
    $learning_needs[$key]['50dip_risk'] = $risk ? $risk['ccat_id'] : false;

    $risk = $safe_obj->getLearningNeedAtecoRisk($learning_need['id_learning_need']);
    $learning_needs[$key]['ateco_risk'] = $risk ? $risk['ateco_risk_id'] : false;

    $risk = $safe_obj->getLearningNeedAssign($learning_need['id_learning_need']);
    $learning_needs[$key]['assignments'] = $risk ? $risk['assign_id'] : false;

    $risk = $safe_obj->getLearningNeedBizFunc($learning_need['id_learning_need']);
    $learning_needs[$key]['business_function_id'] = $risk ? $risk['biz_func_id'] : false;

    $risk = $safe_obj->getLearningNeedCustomCategory($learning_need['id_learning_need'], 1);
    $learning_needs[$key]['is_new'] = $risk ? $risk['ccat_id'] == 1 : false;

    $user_learning_need = $safe_obj->getUserLearningNeeds($user_id, $learning_need['id_learning_need']);
    $learning_needs[$key]['execution_date'] = $user_learning_need ? $user_learning_need[0]['execution_date'] : false;
}

$executed_courses = $safe_obj->getUserLearningNeeds($user_id);
$tutors = $safe_obj->getTutorsByCompany($employee['user_company_id']);


// CORSI IN ELEARNING
$elearning_courses = $user_obj->getUserLearningActivity($user_id);
$delete_user = $employee['role'] == 1000 || $employee['role'] == 1 ? false : true;//$comp_obj->getCompanyByTutorAdmin($user_id) ? false : true;
if ($delete_user && $elearning_courses) {
    foreach ($elearning_courses as $s_course){
        if (!empty($s_course['learning_event_id'])){
            $delete_user = false;
            break;
        }
    }
}
?>
<!-- <h3 class="text-center">Dettaglio Dipendente <small class="text-right">*campi obbligatori</small></h3> -->

<div id="edit-employee" class="container-fluid">

    <div class="row">
        <form class="col-xs-6 employee-detail" id="edit-employee-detail" name="edit-employee-detail">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <span class="glyphicon glyphicon-user"></span>
                    DATI UTENTE
                    <span class="pull-right handwrite2 small" style="color: initial;">OBBLIGATORI</span>
                </div>
                <div class="panel-body">
                    
                    <!-- NOME -->
                    <div class="form-group">
                        <input type="text" name="name" class="form-control" placeholder="Nome" value="<?= ucwords(strtolower($employee['name'])) ?>">
                    </div>
                    <!-- /NOME -->


                    <!-- COGNOME -->
                    <div class="form-group">
                        <input type="text" name="surname" class="form-control" placeholder="Cognome" value="<?= ucwords(strtolower($employee['surname'])) ?>">
                    </div>
                    <!-- /COGNOME -->


                    <!-- CODICE FISCALE -->
                    <div class="form-group">
                        <input type="text" name="tax_code" class="form-control" placeholder="Codice Fiscale" value="<?= strtoupper($employee['tax_code']) ?>">
                    </div>
                    <!-- /CODICE FISCALE -->


                    <!-- EMAIL -->
                    <div class="form-group">
                        <input type="text" name="email" class="form-control" placeholder="indirizzo email" value="<?= strtolower($employee['email']) ?>">
                    </div>
                    <!-- /EMAIL -->

                    <!-- NOME UTENTE -->
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" name="nu" class="form-control" placeholder="Nome utente" value="<?= $employee['username'] ?>" autocomplete="off" autofill="off">
                            <div class="input-group-btn action">
                                <button class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                                <ul class="dropdown-menu pull-right">
                                    <!-- dropdown menu links -->

                                    <li>
                                        <a class="reset-password" tabindex="-1" href="javascript: void(0)" onClick="resetUserPassword(<?= $user_id ?>)">Reimposta password</a>
                                    </li>

                                    <li>
                                        <a class="send-user-name" tabindex="-1" href="javascript: void(0)" onClick="sendUserName(<?= $user_id ?>)">Invia nome utente</a>
                                    </li>

                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- /NOME UTENTE -->


                    <!-- FUNZIONE -->
                    <div class="form-group">
                        <select class="form-control" name="business_function">

                        <?php foreach ($business_functions as $single_buz_function) { ?>

                            <option value="<?= $single_buz_function['id'] ?>"<?= $single_buz_function['id'] == $employee['business_function_id'] ? ' selected' : '' ?>>
                            <?= $single_buz_function['name'] ?>
                            </option>

                        <?php } ?>

                        </select>
                    </div>
                    <!--
                    <p class="red handwrite2 form-control-static">Quale funzione ha questo dipendente in azienda?</p>
                    <?php foreach ($business_functions as $single_buz_function) { ?>

                    <div class="radio">
                        <label>
                            <input type="radio" name="business_function" value="<?= $single_buz_function['id'] ?>" 
                            <?= $single_buz_function['id'] == $employee['business_function_id'] ? ' checked' : '' ?>>
                            <?= $single_buz_function['name'] ?>
                        </label>
                    </div>

                    <?php } ?>
                    -->
                    <!-- /FUNZIONE -->

                    <!-- RUOLO -->
                    <!-- <div class="form-group">
                        <select name="role" class="form-control">
                            <option value="0"<?= $employee['role'] == 0 || !isset($employee['role']) ? ' selected' : '' ?>>Corsista</option>

                            <?php if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32) { ?>

                                <option value="1"<?= $employee['role'] == 1 ? ' selected' : '' ?>><?= $roles_name[1] ?></option>

                            <?php }
                            if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32 || $_SESSION['user']['role'] == 2) {
                                ?>

                                <option value="2"<?= $employee['role'] == 2 ? ' selected' : '' ?>><?= $roles_name[2] ?></option>

                            <?php }
                            if ($_SESSION['user']['role'] == 1000) {
                                ?>

                                <option value="1000"<?= $employee['role'] == 1000 ? ' selected' : '' ?>><?= $roles_name[1000] ?></option>

                            <?php } ?>

                        </select>
                    </div> -->
                    <div class="<?= $_SESSION['user']['role'] != 1000 ? 'hidden' : ''; ?>">
                    <p class="red handwrite2 form-control-static">Quale ruolo ha questo utente in piattaforma?</p>

                    <div class="radio">
                        <label>
                            <input type="radio" name="role" value="0" 
                            <?= $employee['role'] == 0 || !isset($employee['role']) ? ' checked' : '' ?>>
                            <?= $roles_name[0] ?>
                        </label>
                    </div>

                    <?php
                    if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32 || $_SESSION['user']['role'] == 2) {
                    ?>
                    
                    <div class="radio">
                        <label>
                            <input type="radio" name="role" value="2" 
                            <?= $employee['role'] == 2 || !isset($employee['role']) ? ' checked' : '' ?>>
                            <?= $roles_name[2] ?>
                        </label>
                    </div>

                    <?php }
                    if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32) {
                    ?>
                    
                    <div class="radio">
                        <label>
                            <input type="radio" name="role" value="1" 
                            <?= $employee['role'] == 1 || !isset($employee['role']) ? ' checked' : '' ?>>
                            <?= $roles_name[1] ?>
                        </label>
                    </div>

                    <?php }
                    if ($_SESSION['user']['role'] == 1000) {
                    ?>
                    
                    <div class="radio">
                        <label>
                            <input type="radio" name="role" value="32" 
                            <?= $employee['role'] == 32 || !isset($employee['role']) ? ' checked' : '' ?>>
                            <?= $roles_name[32] ?>
                        </label>
                    </div>
                    
                    <div class="radio">
                        <label>
                            <input type="radio" name="role" value="1000" 
                            <?= $employee['role'] == 1000 || !isset($employee['role']) ? ' checked' : '' ?>>
                            <?= $roles_name[1000] ?>
                        </label>
                    </div>
                    
                    <?php } ?>
                    </div>
                    <!-- /RUOLO -->
                </div>
                <div class="panel-footer text-right">
                    <button type="button" id="employee-delete" class="btn btn-danger <?= $delete_user ? '' : ' disabled' ?>">Elimina</button>
                    <button type="button" <?= $employee['deleted'] ? 'id="employee-enable"' : 'id="employee-disable"' ?>
                            class="btn btn-<?= $employee['deleted'] ? 'success' : 'warning' ?>">
                            <?= $employee['deleted'] ? 'Riattiva' : 'Sospendi' ?></button>
                </div>
            </div>
        </form>
        <!-- /form#edit-employee-detail -->

        <div class="col-xs-6">
            <h3 class="text-center">DATI FACOLTATIVI</h3>

            <table id="edit-employee-assignments" class="employee-assignments">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th>data nomina</th>
                        <th>data fine</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="incarico-rspp" data-assignment="1" data-user-assignment="<?= $is_RSPP ? $rspp_assign[0]['id_user_assign'] : '' ?>">
                        <td>
    <?= $is_RSPP ? '<i class="icon-ok"></i> ' : "" ?>RSPP
                        </td>
                        <td>
                            <input type="text" class="form-control" name="start-rspp" value="<?= $is_RSPP ? $start_date_rspp->format('d/m/Y') : '' ?>">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="end-rspp" value="<?= $is_RSPP && $rspp_assign[0]['assign_end_date'] ? $end_date_rspp->format('d/m/Y') : '' ?>">
                        </td>
                    </tr>
                    <tr class="incarico-aspp" data-assignment="5" data-user-assignment="<?= $is_ASPP ? $aspp_assign[0]['id_user_assign'] : '' ?>">
                        <td>
    <?= $is_ASPP ? '<i class="icon-ok"></i> ' : "" ?>ASPP
                        </td>
                        <td>
                            <input type="text" class="form-control" name="start-aspp" value="<?= $is_ASPP ? $start_date_aspp->format('d/m/Y') : '' ?>">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="end-aspp" value="<?= $is_ASPP && $aspp_assign[0]['assign_end_date'] ? $end_date_aspp->format('d/m/Y') : '' ?>">
                        </td>
                    </tr>
                    <tr class="incarico-rspp_dl" data-assignment="6" data-user-assignment="<?= $is_RSPP_DL ? $rspp_dl_assign[0]['id_user_assign'] : '' ?>">
                        <td>
    <?= $is_RSPP_DL ? '<i class="icon-ok"></i> ' : "" ?>RSPP-DL
                        </td>
                        <td>
                            <input type="text" class="form-control" name="start-rspp_dl" value="<?= $is_RSPP_DL ? $start_date_rspp_dl->format('d/m/Y') : '' ?>">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="end-rspp_dl" value="<?= $is_RSPP_DL && $rspp_dl_assign[0]['assign_end_date'] ? $end_date_rspp_dl->format('d/m/Y') : '' ?>">
                        </td>
                    </tr>
                    <tr class="incarico-rls" data-assignment="2" data-user-assignment="<?= $is_RLS ? $rls_assign[0]['id_user_assign'] : '' ?>">
                        <td>
    <?= $is_RLS ? '<i class="icon-ok"></i> ' : "" ?>RLS
                        </td>
                        <td>
                            <input type="text" class="form-control" name="start-rls" value="<?= $is_RLS ? $start_date_rls->format('d/m/Y') : '' ?>">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="end-rls" value="<?= $is_RLS && $rls_assign[0]['assign_end_date'] ? $end_date_rls->format('d/m/Y') : '' ?>">
                        </td>
                    </tr>
                    <tr class="incarico-antincendio" data-assignment="3" data-user-assignment="<?= $is_ANTINCENDIO ? $antincendio_assign[0]['id_user_assign'] : '' ?>">
                        <td>
    <?= $is_ANTINCENDIO ? '<i class="icon-ok"></i> ' : "" ?>Antincendio
                        </td>
                        <td>
                            <input type="text" class="form-control" name="start-antincendio" value="<?= $is_ANTINCENDIO ? $start_date_antincendio->format('d/m/Y') : '' ?>">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="end-antincendio" value="<?= $is_ANTINCENDIO && $antincendio_assign[0]['assign_end_date'] ? $end_date_antincendio->format('d/m/Y') : '' ?>">
                        </td>
                    </tr>
                    <tr class="incarico-soccorso" data-assignment="4" data-user-assignment="<?= $is_SOCCORSO ? $soccorso_assign[0]['id_user_assign'] : '' ?>">
                        <td>
    <?= $is_SOCCORSO ? '<i class="icon-ok"></i> ' : "" ?>Primo Soccorso
                        </td>
                        <td>
                            <input type="text" class="form-control" name="start-soccorso" value="<?= $is_SOCCORSO ? $start_date_soccorso->format('d/m/Y') : '' ?>">
                        </td>
                        <td>
                            <input type="text" class="form-control" name="end-soccorso" value="<?= $is_SOCCORSO && $soccorso_assign[0]['assign_end_date'] ? $end_date_soccorso->format('d/m/Y') : '' ?>">
                        </td>
                    </tr>
                </tbody>
            </table>
                
            <?php if ($_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 1000){ ?>

            <div>
                <h3 class="text-center">AZIENDA</h3>
                <form id="cambia_azienda" class="employee-change-company">

                    <div class="form-group">
                        <label class="control-label" for="company">cambia azienda</label>

                        <select name="company" class="form-control">

                            <?php foreach ($companies as $company) { ?>

                                <option value="<?= $company['id'] ?>"  
                                    <?= $company['id']==$employee['user_company_id'] ? 'selected' : ''?>>

                                <?= $company['business_name'] ?>

                                </option>

                            <?php } ?>

                        </select>

                    </div>

                </form><!-- /form#cambia_azienda -->
                
            </div>
            
            <?php } ?>
            
            <?php if ($department_types) { ?>

                <div class="employee-department" style="display:none;">

                    <?php if (isset($employee['id_dep_empl'])) { ?>


                        <table id="edit-employee-department" class="table tablesorter-greyT81">
                            <thead>
                                <tr>
                                    <th>Unità</th>
                                    <th>Reparto</th>
                                    <th>assunto</th>
                                    <th>dimesso</th>
                                </tr>
                            </thead>
                            <tbody>

                            <?php foreach ($employee_history as $history_item) { ?>

                                    <tr data-id_dep_empl="<?= $history_item['id_dep_empl'] ?>">
                                        <td><?= $history_item['short_desc_pu'] ?></td>
                                        <td><?= $history_item['short_desc_dep_type'] ?></td>
                                        <td class="hire_date"><?= date("d/m/y", strtotime($history_item['hire_date'])) ?></td>
                                        <td><?= isset($history_item['dismissal_date']) && $history_item['dismissal_date'] != "0000-00-00" ? date("d/m/y", strtotime($history_item['dismissal_date'])) : '' ?></td>
                                        <td>
                                            <div class="btn-group action">
                                                <button class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                                                <ul class="dropdown-menu pull-right">
                                                    <!-- dropdown menu links -->
                                                    <li>
                                                        <a class="elimina" tabindex="-1" href="javascript: void(0)">Elimina</a>
                                                    </li>
                                                    <li<?= isset($history_item['dismissal_date']) && $history_item['dismissal_date'] != "0000-00-00" ? ' class="disabled"' : '' ?>>
                                                        <a class="termina" tabindex="-1" href="javascript: void(0)">Termina</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                        <?php } // foreach $employee_history?>			

                            </tbody>
                        </table>

                    <?php }
                    if (!isset($employee['id_dep_empl']) || $employee['dismissal_date'] != "") {
                    ?>

                        <form id="assegna_reparto" class="employee-add-department">

                            <!-- UNITA' PRODUTTIVA -->
                            <div class="form-group product_unit">
                                <label class="control-label" for="product-unit">scegli un'unità produttiva</label>

                            <?php if ($product_units) { ?>

                                <select name="product_unit" class="form-control">
                                    <option value="0">Seleziona unità</option>

                                    <?php foreach ($product_units as $single_product_unit) { ?>

                                        <option value="<?= $single_product_unit['id_pu'] ?>">

                                        <?= $single_product_unit['short_desc_pu'] ?>

                                        </option>

                                    <?php } ?>

                                </select>

                             <?php } ?>
                                
                            </div><!-- /UNITA' PRODUTTIVA -->


                            <!-- REPARTO -->
                            <div class="form-group departments">

                                <label class="control-label" for="department">scegli un reparto</label>

                                <select name="dep_id" class="form-control" disabled>
                                    <option value="0">Seleziona un'unità produttiva</option>
                                </select>
                            </div>
                            <!-- /REPARTO -->


                            <!-- DATA ASSUNZIONE -->
                            <div class="form-group">
                                <label class="control-label" for="hire-date">Data assunzione</label>
                                <input type="text" name="hire-date" class="form-control datepicker" placeholder="Data assunzione" value="<?= $employee_hire_date ? $employee_hire_date->format('d/m/Y') : '' ?>">
                            </div>
                            <!-- /DATA ASSUNZIONE -->


                            <!-- <button type="button" class="btn btn-default" id="save-user-department" title="assegna l'utente al reparto">Salva dati reparto</button> -->

                        </form><!-- /form#assegna_reparto -->

        <?php } ?>

                </div>

    <?php }?>

        </div>
    </div>

    <div id="employee-dossier">

        <h3 class="text-center">Dossier Formativo</h3>

            <table class="table tablesorter tableaction">
                <thead>
                    <tr>
                        <th>Nome Corso</th>
                        <th style="min-width: 90px">Programmato</th>
                        <th style="min-width: 90px">In attività</th>
                        <th style="min-width: 90px">Completato</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>
                <tbody>


                    <?php if ($elearning_courses) { ?>

                        <!-- ---------------- CORSI IN ELEARNING  ---------------- -->

                        <?php
                        foreach ($elearning_courses as $s_course) {

                            $num_lo = $learn_obj->get_num_learning_objects($s_course['learning_project_id']);
                            $num_exe_lo = $learn_obj->get_num_lo_executed($s_course['id']);
                            if ($num_exe_lo != 0) {
                                $execution_percentage = round($num_exe_lo / $num_lo * 100);
                            } else {
                                $execution_percentage = 0;
                            }

                            if ($execution_percentage < 100) {
                                $genera = false;
                            } else {
                                $genera = true;
                            }

                            if (($user_id == 4330 || $user_id == 4341 || $user_id == 3470 || $user_id == 2558 || $user_id == 4414 || $user_id == 4428 || $user_id == 3680 || $user_id == 3684)) {
                                $genera = true;
                                $execution_percentage = 100;
                            }

                            $programmato = isset($s_course['starting_from']) && $s_course['starting_from'] != '0000-00-00' ?
                                    DateTime::createFromFormat('Y-m-d', $s_course['starting_from'], new DateTimeZone('Europe/Rome')) :
                                    DateTime::createFromFormat('Y-m-d H:i:s', $s_course['creation_date'], new DateTimeZone('Europe/Rome'));
                            $avviato = isset($s_course['start_date_time']) && $s_course['start_date_time'] != '0000-00-00 00:00:00' ?
                                    DateTime::createFromFormat('Y-m-d H:i:s', $s_course['start_date_time'], new DateTimeZone('Europe/Rome')) : false;
                            $completato = isset($s_course['end_date_time']) && $s_course['end_date_time'] != '0000-00-00 00:00:00' ?
                                    DateTime::createFromFormat('Y-m-d H:i:s', $s_course['end_date_time'], new DateTimeZone('Europe/Rome')) : false;
                            ?>

                            <tr class="on_elearning" 
                                data-license_id="<?= $s_course['id'] ?>" 
                                data-course_id="<?= $s_course['learning_project_id'] ?>"
                                data-accreditation_code="<?= $s_course['accreditation_code'] ?>">
                                <td><?= strtoupper(substr($s_course['title'], strpos($s_course['title'], ' - ') + 3)) ?></td>
                                <td><?= $programmato->format('d/m/Y') ?></td>
                                <td><?= $execution_percentage > 0 && !$completato ? "$execution_percentage %" : ($avviato ? $avviato->format('d/m/Y') : '&nbsp;') ?></td>
                                <td><?= $completato ? $completato->format('d/m/Y') : '&nbsp' ?></td>
                                <td>
                                    <div class="btn-group action">
                                        <button class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                                        <ul class="dropdown-menu pull-right">
                                            <!-- dropdown menu links -->
                                            <li<?= $completato ? ' class="disabled"' : '' ?>>
                                                <a class="send-alert" tabindex="-1" href="javascript: void(0)">Invia sollecito</a>
                                            </li>
                                            <li<?= !$completato ? ' class="disabled"' : '' ?>>
                                                <a class="attestato<?= !file_exists(BASE_MEDIA_PATH . "attestati/attestato_licenza_{$s_course['id']}.pdf") ? ' genera' : '' ?>" tabindex="-1" href="javascript: void(0)">Attestato</a>
                                            </li>
                                            <li>
                                                <a class="accreditation_code" href="javascript: void(0);">Modifica codice corso</a>
                                            </li>
                                            <?php if (!$completato && ($_SESSION['user']['role'] == 1000 || ($_SESSION['user']['role'] == 1 && (!$avviato || $execution_percentage == 0)))){?>
                                            
                                                <li<?= '';//$execution_percentage > 0 ? ' class="disabled"' : '' ?>>
                                                    <a class="elimina" tabindex="-1" href="javascript: void(0)">Elimina</a>
                                                </li>

                                                <li>
                                                    <a class="play-course" href="javascript: void(0)">Avvia corso</a>
                                                </li>

                                            <?php } ?>
                                        </ul>
                                    </div>			
                                </td>
                            </tr>

        <?php } // end foreach $elearning_courses
    }
    ?>


                    <!-- ---------------- CORSI ESTERNI  ---------------- -->

    <?php
    if ($executed_courses) {
        foreach ($executed_courses as $course) {
            $execution_date = new DateTime($course['execution_date'], new DateTimeZone('Europe/Rome'))
            ?>

                            <tr class="external" data-user_learning_need="<?= $course['id_user_learning_need'] ?>">
                                <td><?= ucwords(strtolower($course['short_desc_learning_need'])) ?></td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td><?= $execution_date->format('d/m/Y') ?></td>
                                <td>
                                    <div class="btn-group action">
                                        <button class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                                        <ul class="dropdown-menu pull-right">
                                            <!-- dropdown menu links -->
                                            <li class="disabled">
                                                <a class="attestato" tabindex="-1" href="javascript: void(0)">Attestato</a>
                                            </li>
                                            <li class="disabled">
                                                <a class="elimina" tabindex="-1" href="javascript: void(0)">Elimina</a>
                                            </li>
                                        </ul>
                                    </div>			
                                </td>
                            </tr>

                        <?php
                        }
                    }

                    /* corsi programmati
                    foreach ($learning_needs as $course) {
                        if ($course['business_function_id'] && $course['business_function_id'] != $employee['business_function_id']) {
                            if ($employee['business_function_id'] != 3)
                                continue;
                            elseif ($course['business_function_id'] != 1)
                                continue;
                        }
                        if ($course['fire_risk'] && $course['fire_risk'] != $employee['fire_risk'])
                            continue;
                        if ($course['first_aid_risk'] && $course['first_aid_risk'] != $employee['first_aid_risk'])
                            continue;
                        if ($course['50dip_risk'] && $course['50dip_risk'] != $employee['50dip_risk'])
                            continue;
                        if ($course['ateco_risk'] && $course['ateco_risk'] != $employee['ateco_risk'])
                            continue;
                        if ($course['assignments'] && !in_array($course['assignments'], $employee['assignments']))
                            continue;


                        if ($course['is_new']) {

                            if ($course['execution_date'])
                                continue;
                            if ($course['business_function_id']) {
                                $last_hire_date = new DateTime($employee['hire_date'], new DateTimeZone('Europe/Rome'));
                                $scadenza = $last_hire_date->add(new DateInterval('P2M'));
                            } elseif ($course['assignments']) {
                                if ($course['assignments'] == 1) {
                                    $scadenza = $start_date_rspp->add(new DateInterval('P3M'));
                                } elseif ($course['assignments'] == 2) {
                                    $scadenza = $start_date_rls->add(new DateInterval('P3M'));
                                } elseif ($course['assignments'] == 3) {
                                    $scadenza = $start_date_antincendio->add(new DateInterval('P3M'));
                                } elseif ($course['assignments'] == 4) {
                                    $scadenza = $start_date_soccorso->add(new DateInterval('P3M'));
                                } elseif ($course['assignments'] == 5) {
                                    $scadenza = $start_date_aspp->add(new DateInterval('P3M'));
                                } elseif ($course['assignments'] == 6) {
                                    $scadenza = $start_date_rspp_dl->add(new DateInterval('P3M'));
                                }
                            }
                        } else {

                            if ($course['execution_date']) {
                                $last_occurence = new DateTime($course['execution_date'], new DateTimeZone('Europe/Rome'));
                                $interval = $last_occurence->diff($now);
                                $expiring = $course['expiration_time'] - $interval->format('%r%y') > 1 ? false : true;
                                if (!$expiring)
                                    continue;
                                $scadenza = $last_occurence->add(new DateInterval('P' . $course['expiration_time'] . 'Y'));
                            } else {

                                $learning_need_new_version = $safe_obj->getLearningNeedNewVersion($course['id_learning_need']);

                                $user_learning_need = $safe_obj->getUserLearningNeeds($user_id, $course['id_learning_need']);
                                $exceution_date_new_version = $user_learning_need[0]['execution_date'];

                                if (!$exceution_date_new_version)
                                    continue;
                                $last_occurence = new DateTime($exceution_date_new_version, new DateTimeZone('Europe/Rome'));
                                $interval = $last_occurence->diff($now);
                                $expiring = $learning_need_new_version['expiration_time'] + $interval->format('%r%y') > 1 ? false : true;
                                if (!$expiring)
                                    continue;
                                $scadenza = $last_occurence->add(new DateInterval('P' . $learning_need_new_version['expiration_time'] . 'Y'));
                            }
                        }
                        ?>

                        <!-- ---------------- CORSI PROGRAMMATI  ----------------

        <tr class="planned" data-learning_need="<?= $course['id_learning_need'] ?>">
                <td><?= ucwords(strtolower($course['short_desc_learning_need'])) ?></td>
                <td><?= $scadenza->format('d/m/Y') ?></td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
        </tr>

                        -->



    <?php } */?>


                </tbody>
            </table>



            <a class="btn btn-default" href="#addExecutedCourseModal" data-toggle="modal"><span class="hlyphicon glyphicon-plus"></span> Aggiungi Corso Eseguito</a>


    </div><!-- /.row -->

</div>

<!-- ---- MODALS ---- -->

<div id="editModal" class="modal hide fade">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3>Chiudi associazione Dipendente - Reparto</h3>
    </div>
    <div class="modal-body">
        <p>Hai deciso di modificare un'associazione fra Dipendente e Reparto.</p>
        <p>Procedi con la dismissione del Dipendente dal Reparto indicando la data in cui avviene. Potrai poi definire una nuova associazione.</p>
        <br>
        <div style="text-align:center;">
            <input type="text" name="dismissal_date" class="form-control datepicker" value="<?= date('d/m/Y') ?>">
            <input type="hidden" name="hire_date" value="">
            <input type="hidden" name="id_dep_empl" value="">
        </div>
    </div>
    <div class="modal-footer">
        <a href="javascript: void(0)" class="btn" data-dismiss="modal">Close</a>
        <a href="javascript: void(0)" class="btn btn-primary submit">Save changes</a>
    </div>
</div>


<div id="addExecutedCourseModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h3 id="myModalLabel">Aggiungi Corso Eseguito</h3>
            </div>
            <div class="modal-body">
                <form id="add-executed-course" action="manage/safety.php" method="POST">

                    <div class="form-group">
                        <label class="control-label">Seleziona un tipo di corso</label>
                        <div class="controls">
                            <select class="form-control" name="learning_need_id">
                                <option value="0">Seleziona</option>

                                <?php foreach ($learning_needs as $course) { ?>

                                    <option value="<?= $course['id_learning_need'] ?>"><?= $course['short_desc_learning_need'] ?></option>

                                <?php } ?>

                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Ente Formativo</label>
                        <div class="controls">
                            <input class="form-control" type="text" name="tutor" list="tutors_list">
                            <datalist id="tutors_list">
        <?php if ($tutors) {
            foreach ($tutors as $tutor) {
                ?>
                                        <option data-tutor_id="<?= $tutor['id_tutor'] ?>"><?= $tutor['desc_tutor'] ?></option>

            <?php }
        }
        ?>

                            </datalist>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Data Attestato</label>
                        <div class="controls">
                            <input class="form-control datepicker" type="text" name="execution_date">
                        </div>
                    </div>


                    
                    
                    <input type="hidden" name="op_type" value="add_user_learning_need">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                </form>
                <form id="upload_attestato" enctype="multipart/form-data" action="__URL__" method="POST">
                    <!-- The data encoding type, enctype, MUST be specified as below -->
                        <!-- MAX_FILE_SIZE must precede the file input field -->
                        <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
                    <div class="form-group">
                        <label class="control-label">Upload Attestato</label>
                        <div class="controls">
                            <!-- Name of input element determines name in $_FILES array -->
                            <input class="btn btn-default" name="userfile" type="file" accept="application/pdf"/>
                            <!--<img src="img/course_archive.png" style="height:48px;">-->
                        </div>
                    </div>
                        <!--<input class="btn btn-default" type="submit" value="Send File" />-->
                </form>
            </div>
            <div class="modal-footer">
                <button id="add-user-course-type" class="btn btn-primary" onclick="addUserCourse(<?= $employee['user_company_id'] ?>,<?= $user_id ?>)"><span class="glyphicon glyphicon-ok"></span> Salva</button>
                <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Annulla</button>
            </div>
        </div>
    </div>
</div>


<div id="employeeDetailModal" class="modal fade" role="dialog" aria-labelledby="mySimpleModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                        aria-hidden="true">&times;</button>
                <h3>Titolo</h3>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>


<!-- ---- END MODALS ---- -->

<script>
  
<?php if ($_SESSION['user']['role'] == 1000){?>

        /* ************** AVVIA IL CORSO ****************** */
        $('#employee-dossier .action .play-course').click(function () {
            var license_id = $(this).parents('tr').data('license_id');
            console.log('license id: ' + license_id);
            $('#employeeDetailModal .modal-content')
                    .empty()
                    .load('modals/avviacorso.php?learning_project_user_id=' + license_id)
                    .parents('#employeeDetailModal')
                    .modal();
        });

<?php } ?>
    
    
<?php if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1) { ?>

        /* ************** ELIMINA LICENZA ****************** */
        $('#employee-dossier tr.on_elearning li:not(.disabled) a.elimina').click(function (e) {
            var licence_id = $(this).parents('tr').data('license_id');
            var res = removeLicenceAndPurchase(licence_id);
            if ( res ) {
                $("#edit-employee")
                    .parent()
                    .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                    .load("pages/sections/employee-detail.php?user_id=<?= $user_id ?>");
            }
        });

<?php } ?>
    
    function changeCompany(company_id){
        //change company
        $.post("manage/user.php",
            {
                op_type: "change_user_company",
                user_id: <?= $user_id ?>,
                company_id: company_id
            }, function(success){
                if (!isNaN(success)){
                    //reset Departments Assignments
                    $('#edit-employee-department tbody tr').each( function(index) {
                        employeeDetailDeleteAssignation($(this).data('id_dep_empl'),<?= $user_id ?>);
                    });
                    alert("La modifica è stata apportata.");
                } else {
                    alert("Errore! La modifica non è stata apportata.");
                }
        });
    }
    
        function loadDepartments(product_unit_id) {
            $.post("manage/department.php",
                    {
                        op_type: "get_pu_departments",
                        pu_id: product_unit_id
                    }, function (departments) {
                departments = $.parseJSON(departments);
                if (departments != 0) {
                    $('#edit-employee .departments select')
                            .prop('disabled', false)
                            .children().first().text('Seleziona un reparto');
                    $.each(departments, function (i, item) {
                        var options = '<option value="' + item.id_dep + '">' + item.short_desc_dep_type + '</option>';
                        $('#edit-employee .departments select').append(options);
                    });
                }
            }
            );
        }
        
        
        /* ************** ASSEGNA UTENTE A REPARTO ****************** */
        function assignDepartment() {
            var dep_id = $('#edit-employee .employee-department select[name="dep_id"]').val();
            if (dep_id == 0)
                alert('Scegli un reparto');
            else if ($('#edit-employee .employee-department input[name="hire-date"]').val() == "")
                alert('Inserisci una data di assunzione');
            else {
                var hire_date = $('#edit-employee .employee-department input[name="hire-date"]').datepicker('getDate');
                $.post("manage/department.php", {
                    op_type: "add_employee",
                    hire_date: $.datepicker.formatDate('yy-mm-dd', hire_date),
                    dep_id: dep_id,
                    user_id: <?= $user_id ?>
                }, function (data) {
                    if (data > 0) {
                        $("#edit-employee")
                                .parent()
                                .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                                .load("pages/sections/employee-detail.php?user_id=<?= $user_id ?>");
                    } else {
                        alert("Errore nell'assegnazione del reparto");
                    }
                });
            }
        }
        
        $('#addExecutedCourseModal .datepicker').datepicker({
            format: "dd/mm/yyyy",
            language: "it",
            autoclose: true,
            todayHighlight: true
        });
        
        $('#upload_attestato input[name="userfile"]').click(function() {
        
            $( "#upload_attestato" ).isLoading({
                                text:       "caricamento",
                                position:   "overlay"
                            });

            // Re-enabling event
            setTimeout( function(){ 
                $( "#load-overlay-elt .demo" ).isLoading( "hide" );
                $( "#load-overlay-elt .demo p" ).html( "Content Loaded" )
                                            .addClass("alert-success"); 
            }, 2000 );

        });
        
        $('#upload_attestato input[name="userfile"]').on('change', function(event) {
            var file = event.target.files[0];
            if(file.size>=2*1024*1024) {
                alert("Il file PDF non deve essere maggiore di 2MB");
                $("#upload_attestato").get(0).reset(); //the tricky part is to "empty" the input file here I reset the form.
                $("#upload_attestato").isLoading( "hide" );
                return;
            }

            if(!file.type.match('application/pdf.*')) {
                alert("Caricare solo file PDF");
                $("#upload_attestato").get(0).reset(); //the tricky part is to "empty" the input file here I reset the form.
                $("#upload_attestato").isLoading( "hide" );
                return;
            }

            var fileReader = new FileReader();
            fileReader.onload = function(e) {
                var int32View = new Uint8Array(e.target.result);
                //verify the magic number
                // for PDF is 0x25 0x50 0x44 0x46 0x2D (see https://en.wikipedia.org/wiki/List_of_file_signatures)
                if(int32View.length>5 && int32View[0]==0x25 && int32View[1]==0x50 && int32View[2]==0x44 && int32View[3]==0x46 && int32View[4]==0x2D) {
                    $("#upload_attestato").isLoading( "hide" );
                    alert("File PDF caricato");
                } else {
                    alert("Caricare solo file PDF");
                    $("#upload_attestato").get(0).reset(); //the tricky part is to "empty" the input file here I reset the form.
                    $("#upload_attestato").isLoading( "hide" );
                    return;
                }
            };
            fileReader.readAsArrayBuffer(file);
        });
        
        /* ************* CAMBIA AZIENDA *********** */
        $('#cambia_azienda').on('change', 'select', function (e) {
            var company_id = $('#cambia_azienda select[name="company"]').val();
            if (company_id == 0)
                alert("Scegli un'azienda");
            else if (confirm("Sei sicuro di voler cambiare l'azienda di questo utente? Questo eliminerà definitivamente anche le assegnazioni ai reparti. Procedo?")) {
                changeCompany(company_id);
            }
        });

        /* ************** CARICA I REPARTI AL CAMBIARE DI UNITA' PRODUTTIVA ****************** */
        $('#edit-employee .employee-add-department select[name="product_unit"]').on('change', function (e) {
            var product_unit_id = $('#edit-employee .employee-add-department select[name="product_unit"]').val();
            if (product_unit_id == 0)
                $('#edit-employee .departments select').prop('disabled', 'disabled').html('<option value="0">Seleziona l&amp;unità produttiva</option>');
            else
                loadDepartments(product_unit_id);
        });

        $('#edit-employee .datepicker').datepicker({
            format: "dd/mm/yyyy",
            language: "it",
            autoclose: true,
            todayHighlight: true
        });

        $('#edit-employee .employee-assignments input').datepicker({
            format: "dd/mm/yyyy",
            language: "it",
            autoclose: true,
            todayHighlight: true
        });
        
        if ($('#edit-employee .employee-assignments .incarico-rspp').data("user-assignment") > 0) {
            $('#edit-employee .employee-assignments .incarico-aspp input').prop("disabled", true);
            $('#edit-employee .employee-assignments .incarico-rspp_dl input').prop("disabled", true);
            $('#edit-employee .employee-assignments .incarico-rls input').prop("disabled", true);
        } else if ($('#edit-employee .employee-assignments .incarico-assp').data("user-assignment") > 0) {
            $('#edit-employee .employee-assignments .incarico-rspp input').prop("disabled", true);
            $('#edit-employee .employee-assignments .incarico-rspp_dl input').prop("disabled", true);
            $('#edit-employee .employee-assignments .incarico-rls input').prop("disabled", true);
        } else if ($('#edit-employee .employee-assignments .incarico-rspp_dl').data("user-assignment") > 0) {
            $('#edit-employee .employee-assignments .incarico-rspp input').prop("disabled", true);
            $('#edit-employee .employee-assignments .incarico-aspp input').prop("disabled", true);
            $('#edit-employee .employee-assignments .incarico-rls input').prop("disabled", true);
        } else if ($('#edit-employee .employee-assignments .incarico-rls').data("user-assignment") > 0) {
            $('#edit-employee .employee-assignments .incarico-rspp input').prop("disabled", true);
            $('#edit-employee .employee-assignments .incarico-aspp input').prop("disabled", true);
            $('#edit-employee .employee-assignments .incarico-rspp_dl input').prop("disabled", true);
        }

        $('form[name="edit-employee-detail"]').on('change', 'input, select', function (e) {
            e.preventDefault();
            editUser(<?= $user_id ?>);
        });



        /* ************** MODIFICA DATA INCARICHI ****************** */
        $('#edit-employee .employee-assignments input').on('input changeDate', function (e) {
            $(this).datepicker('hide');

            if (confirm("Vuoi modificare l'incarico assegnato?")) {
                var row = $(this).parents("tr");
                var id_user_assign = row.data("user-assignment");

                $.post("manage/safety.php",
                        {
                            op_type: id_user_assign ? "edit_user_assignment" : "add_user_assignment",
                            user_id: <?= $user_id ?>,
                            assign_id: row.data("assignment"),
                            assign_start_date: row.find('input[name|="start"]').val(),
                            assign_end_date: row.find('input[name|="end"]').val(),
                            id_user_assign: id_user_assign
                        }, function (data) {
                    if (data > 0) {
                        alert('Assegnazione incarico modificata con successo');
                        $("#edit-employee")
                                .parent()
                                .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                                .load("pages/sections/employee-detail.php?user_id=<?= $user_id ?>");
                    } else {
                        alert('Errore assegnazione: \n' + data);
                    }
                }
                );

            } else {
                $(this).datepicker('update', $(this).attr("value"));
            }

        });

        
        $('#assegna_reparto').on('change', 'select', function (e) {
            assignDepartment();
        });

        $('#edit-employee .employee-department input[name="hire-date"]').datepicker().on('changeDate', function (e) {
            assignDepartment();
        });

        /* ************** ELIMINA ASSEGNAZIONE REPARTO ****************** */
        $('#edit-employee .employee-department a.elimina').click(function (e) {
            if (confirm("Hai deciso di eliminare un'associazione fra Dipendente e Reparto. Verrà eliminata definitivamente, " +
            "non risulterà più nella storia del Dipendente e non sarà più possibile recuperarla.\nVuoi procedere?")) {
                employeeDetailDeleteAssignation($(this).parents('tr').data('id_dep_empl'),<?= $user_id ?>);
            }
        });


        /* ************** MOSTRA OVERLAY TERMINA ASSEGNAZIONE REPARTO ****************** */
        $('#edit-employee .employee-department a.termina').click(function (e) {
            var hire_date = $(this).parents('tr').find('.hire_date').text();
            var id_dep_empl = $(this).parents('tr').data('id_dep_empl');
            $('#editModal').modal().find('input[name="hire_date"]').val(hire_date).parent().find('input[name="id_dep_empl"]').val(id_dep_empl);
            $('#editModal .datepicker').datepicker('setStartDate', $.datepicker.parseDate('dd/mm/yy', hire_date));
        });


        /* ************** TERMINA ASSEGNAZIONE REPARTO (OVERLAY) ****************** */
        $('#editModal .submit').on('click', function (e) {
            var id_dep_empl = $('#editModal input[name="id_dep_empl"]').val();
            var dismissal_date = $('#editModal .datepicker').datepicker('getDate');
            employeeDetailDismissEmployee(id_dep_empl, $.datepicker.formatDate('yy-mm-dd', dismissal_date),<?= $user_id ?>);
            $('#editModal').modal('hide');
        });



//        /* ************** MOSTRA OVERLAY AVANZAMENTO CORSO ****************** */
//        $('#employee-dossier tr.on_elearning li:not(.disabled) a.stato').click(function (e) {
//            var license_id = $(this).parents('tr').data('license_id');
//            var title = $(this).parents('tr').children().first().text();
//            $('#mySimpleModal')
//                    .find('div.modal-header h3')
//                    .text('Avanzamento corso ' + title)
//                    .parent()
//                    .next()
//                    .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
//                    .load('course_detail.php?learning_project_user_id=' + license_id)
//                    .parent('#mySimpleModal')
//                    .modal();
//        });


        /* ************** MOSTRA ATTESTATO ****************** */
        $('#employee-dossier tr.on_elearning li:not(.disabled) a.attestato').click(function (e) {
            var license_id = $(this).parents('tr').data('license_id');
            if ($(this).hasClass('genera')) {
                window.open('lib/genera.php?course_id=' + license_id, '_blank');
            } else {
                window.open('manage/render_document.php?doc_type=attestato_elearning&license_id=' + license_id, '_blank');
            }
        });


        /* ************** INVIA ALERT ****************** */
        $('#employee-dossier tr.on_elearning li:not(.disabled) a.send-alert').click(function (e) {
            var title = $(this).parents('tr').children().first().text();
            if (confirm("Vuoi inviare un sollecito al corsista selezionato per il corso " + title + "?")) {
                var license_id = $(this).parents('tr').data('license_id');
                sendAlertCourse(license_id, '');
            }
        });
        
        $('#employee-disable').click(function(){
            if (confirm("Hai chiesto di sospendere questo utente. Procedo?")) {
                var disabled = disableUser(<?= $user_id ?>);
                if (disabled) {
                    alert("Utente sospeso.");
                    if ($('.area-company').length) {
                        location.href = "company/home/employees?company_id=<?= $_SESSION['company']['id'] ?>";
                    } else {
                        location.reload();
                    }
                };
            }
        });
        
        $('#employee-enable').click(function(){
            if (confirm("Hai chiesto di riattivare questo utente. Procedo?")) {
                var enabled = enableUser(<?= $user_id ?>);
                if (enabled) {
                    alert("Utente attivato.");
                    if ($('.area-company').length) {
                        location.href = "company/home/employees?company_id=<?= $_SESSION['company']['id'] ?>";
                    } else {
                        location.reload();
                    }
                };
            }
        });
        
        $('#employee-delete:not(.disabled)').click(function(){
            if (confirm("Hai chiesto di eliminare questo utente. "
                    + "Verranno eliminati anche le licenze eventualmente assegnate. "
                    + "I dati non saranno più recuperabili. Procedo?")) {
                
                var id_dep_empl = $('#edit-employee-department tbody > tr:first-child').data("id_dep_empl");
                // cancellazione assegnazione a reparto
                if (id_dep_empl > 0) {
                    employeeDetailDeleteAssignation(id_dep_empl,<?= $user_id ?>);
                }
                // cancellazione licenze
                $('#employee-dossier tr.on_elearning li:not(.disabled)').each(function (e) {
                    var license_id = $(this).data('license_id');
                    $.post("manage/user.php", {op_type: "remove_license", purchase_id: license_id, });
                });
                //cancellazione utente
                var deleted = deleteUser(<?= $user_id ?>);
                if (deleted) {
                    alert("Utente eliminato.");
                    if ($('.area-company').length) {
                        location.href = "company/home/employees?company_id=<?= $_SESSION['company']['id'] ?>";
                    } else {
                        location.reload();
                    }
                };
            }
        });
        
        /* ************** MODIFICA CODICE CORSO ****************** */
        $('#employee-dossier tr.on_elearning li:not(.disabled) a.accreditation_code').click(function (e) {
            var row = $(this).parents('tr');
            var accreditation_code = row.data('accreditation_code');
            var license_id = row.data('license_id');
            var new_accr_code = prompt("Modifica il codice del corso assegnato", accreditation_code);
            if (new_accr_code != null && new_accr_code != accreditation_code) {
                $.post("ecommerce/license.php", 
                    {
                        op_type: "edit_accreditation_code", 
                        license_id: license_id, 
                        accreditation_code: new_accr_code
                    }, 
                    function (data) {
                        if (data > 0) {
                            row.data('accreditation_code', new_accr_code);
                            alert('Codice corso modificato');
                        } else {
                            alert('Errore. Il codice del corso non è stato modificato.');
                        }
                    }
                ); 
            }
        });

</script>