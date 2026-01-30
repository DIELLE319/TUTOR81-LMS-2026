<?php
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

$dep_obj = new Departments();
$safe_obj = new Safety();
$perm_obj = new Permissions();
$user_obj = new T81User();
$learn_obj = new T81LearningProject();

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
?>
<!-- <h3 class="text-center">Dettaglio Dipendente <small class="text-right">*campi obbligatori</small></h3> -->

<div id="edit-employee">

    <div>

        <form class="employee-detail form-horizontal" id="edit-employee-detail" name="edit-employee-detail">
            <p>Dati personali</p>

            <!-- NOME -->
            <div class="form-group">
                <label class="col-xs-3 control-label" for="name">Nome*</label>
                <div class="col-xs-9 controls">
                    <input type="text" name="name" class="form-control" placeholder="Nome" value="<?= ucwords(strtolower($employee['name'])) ?>">
                </div>
            </div><!-- /NOME -->


            <!-- COGNOME -->
            <div class="form-group">
                <label class="col-xs-3 control-label" for="surname">Cognome*</label>
                <div class="col-xs-9 controls">
                    <input type="text" name="surname" class="form-control" placeholder="Cognome" value="<?= ucwords(strtolower($employee['surname'])) ?>">
                </div>
            </div><!-- /COGNOME -->


            <!-- CODICE FISCALE -->
            <div class="form-group">
                <label class="col-xs-3 control-label" for="tax_code">Cod. Fiscale*</label>
                <div class="col-xs-9 controls">
                    <input type="text" name="tax_code" class="form-control" placeholder="Codice Fiscale" value="<?= strtoupper($employee['tax_code']) ?>">
                </div>
            </div><!-- /CODICE FISCALE -->


            <!-- EMAIL -->
            <div class="form-group">
                <label class="col-xs-3 control-label" for="email">Email*</label>
                <div class="col-xs-9 controls">
                    <input type="text" name="email" class="form-control" placeholder="indirizzo email" value="<?= strtolower($employee['email']) ?>">
                </div>
            </div><!-- /EMAIL -->


            <!-- FUNZIONE -->
            <div class="form-group">
                <label class="col-xs-3 control-label" for="business_function">Funzione</label>
                <div class="col-xs-9 controls">  			
                    <select class="form-control" name="business_function">

<?php foreach ($business_functions as $single_buz_function) { ?>

                            <option value="<?= $single_buz_function['id'] ?>"<?= $single_buz_function['id'] == $employee['business_function_id'] ? ' selected' : '' ?>>
    <?= $single_buz_function['name'] ?>
                            </option>

<?php } ?>

                    </select>
                </div>
            </div><!-- /FUNZIONE -->


            <!-- RUOLO -->
            <div class="form-group">
                <label class="col-xs-3 control-label" for="role">Ruolo</label>
                <div class="col-xs-9 controls">
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
                </div>
            </div><!-- /RUOLO -->

            <!-- NOME UTENTE -->
            <div class="form-group">
                <label class="col-xs-3 control-label" for="username">Nome utente*</label>
                <div class="col-xs-9 controls input-group">
                    <input type="text" name="username" class="form-control" placeholder="Nome utente" value="<?= $employee['username'] ?>">

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

                    <!-- <button type="button" class="btn btn-warning btn-mini" id="reset-user-password"
                            onClick="resetUserPassword(<?= $user_id ?>)" title="Rigenera e reinvia password" style="margin-top: 10px;">Rigenera password</button> -->
                </div>
            </div><!-- /NOME UTENTE -->
            
            <!-- <button type="submit" class="btn btn-default" id="save-user-data" title="salva i dati personali dell'utente">Salva dati personali</button> -->

        </form><!-- /form#edit-employee-detail -->

    </div>

    <div>

        <table id="edit-employee-assignments" class="employee-assignments">
            <thead>
                <tr>
                    <th>Incarichi</th>
                    <th>data inizio</th>
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

            <?php if ($department_types) { ?>

            <div class="employee-department">

                <p>Unità produttive e reparti</p>

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

                    <form id="assegna_reparto" class="employee-add-department form-horizontal">

                        <h4>Assegna reparto</h4>
                        <!-- UNITA' PRODUTTIVA -->
                        <div class="form-group product_unit">
                            <label class="col-xs-4 control-label" for="product-unit">Unità produttiva</label>
                            <div class="col-xs-6 controls">

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

                            </div>
                                
                                    <div class="col-xs-2">
                                        <a data-target="#addProductUnitModal" data-toggle="modal" class="btn btn-default" title="aggiungi unità produttiva"><span class="glyphicon glyphicon-plus"></span></a>
                                    </div>
                        </div><!-- /UNITA' PRODUTTIVA -->


                        <!-- REPARTO -->
                        <div class="form-group departments">

                        </div><!-- /REPARTO -->


                        <!-- DATA ASSUNZIONE -->
                        <div class="form-group">
                            <label class="col-xs-4 control-label" for="hire-date">Data assunzione</label>
                            <div class="col-xs-6 controls">
                                <input type="text" name="hire-date" class="form-control datepicker" placeholder="Data assunzione" value="<?= $employee_hire_date ? $employee_hire_date->format('d/m/Y') : '' ?>">
                            </div>
                        </div><!-- /DATA ASSUNZIONE -->


                        <!-- <button type="button" class="btn btn-default" id="save-user-department" title="assegna l'utente al reparto">Salva dati reparto</button> -->

                    </form><!-- /form#assegna_reparto -->

    <?php } ?>

            </div>

<?php } ?>

    </div>
</div>

<div id="employee-dossier">

    <h4 class="text-center">Dossier Formativo</h4>

        <table class="table tablesorter tableaction">
            <thead>
                <tr>
                    <th>Nome Corso</th>
                    <th style="min-width: 90px">Programmato</th>
                    <th style="min-width: 90px">In attività</th>
                    <th style="min-width: 90px">Completato</th>
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

                        <tr class="on_elearning" data-license_id="<?= $s_course['id'] ?>" data-course_id="<?= $s_course['learning_project_id'] ?>">
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
                                        <li<?= $execution_percentage > 0 ? ' class="disabled"' : '' ?>>
                                            <a class="elimina" tabindex="-1" href="javascript: void(0)">Elimina</a>
                                        </li>
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



<?php } ?>


            </tbody>
        </table>



        <a class="btn btn-default" href="#addExecutedCourseModal" data-toggle="modal"><span class="hlyphicon glyphicon-plus"></span> Aggiungi Corso Eseguito</a>


</div><!-- /.row -->



<!-- ---- MODALS ---- -->

<div id="addProductUnitModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="myModalLabel">Aggiungi Unità Produttiva</h3>
            </div>
            <div class="modal-body">

                <form class="form-horizontal" action="manage/department.php" method="POST">

                    <div class="form-group">
                        <label class="col-xs-4 control-label">Nome</label>
                        <div class="col-xs-8 controls">
                            <input class="form-control" type="text" name="short_desc">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-xs-4 control-label">Descrizione</label>
                        <div class="col-xs-8 controls">
                            <input class="form-control" type="text" name="long_desc">
                        </div>
                    </div>

                </form>

            </div>
            <div class="modal-footer">
                <button class="btn btn-primary submit"><span class="glyphicon glyphicon-ok"></span> Salva</button>
                <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Annulla</button>
            </div>
        </div>
    </div>
</div>



<div id="addDepartmentModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h3 id="myModalLabel">Seleziona o aggiungi Reparto</h3>
            </div>
            <div class="modal-body">

                <form class="form-horizontal" action="manage/department.php" method="POST">

                    <div class="form-group">
                        <label class="col-xs-4 control-label">Nome reparto</label>
                        <div class="col-xs-8 controls">
                            <input class="form-control" type="text" name="short_desc" list="dep_type">
                            <datalist id="dep_type">

        <?php foreach ($department_types as $dep_type) { ?>

                                    <option data-id_dep_type="<?= $dep_type['id_dep_type'] ?>" data-long_desc_dep_type="<?= $dep_type['long_desc_dep_type'] ?>"><?= $dep_type['short_desc_dep_type'] ?></option>

        <?php } ?>

                            </datalist>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-xs-4 control-label">Descrizione</label>
                        <div class="col-xs-8 controls">
                            <input class="form-control" type="text" name="long_desc">
                        </div>
                    </div>

                </form>

            </div>
            <div class="modal-footer">
                <button class="btn btn-primary submit"><span class="glyphicon glyphicon-ok"></span> Salva</button>
                <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Annulla</button>
            </div>
        </div>
    </div>
</div>



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
                <form id="add-executed-course" class="form-horizontal" action="manage/safety.php" method="POST">

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


                    <div class="form-group">
                        <label class="control-label">Upload Attestato</label>
                        <div class="controls">
                            <a href="javascript: void(0)"><img src="img/course_archive.png" style="height:48px;"></a>
                        </div>
                    </div>

                    <input type="hidden" name="op_type" value="add_user_learning_need">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                </form>

            </div>
            <div class="modal-footer">
                <button id="add-user-course-type" class="btn btn-primary" onclick="addUserCourse(<?= $employee['user_company_id'] ?>,<?= $user_id ?>)"><span class="glyphicon glyphicon-ok"></span> Salva</button>
                <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Annulla</button>
            </div>
        </div>
    </div>
</div>


<div id="mySimpleModal" class="modal hide fade">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3>Titolo</h3>
    </div>
    <div class="modal-body">
    </div>
    <div class="modal-footer">
        <a href="javascript: void(0)" class="btn" data-dismiss="modal">Chiudi</a>
    </div>
</div>


<!-- ---- END MODALS ---- -->

<script>

    $(function () {

        function editUser() {
            var name = $('#edit-employee .employee-detail input[name="name"]').val();
            var surname = $('#edit-employee .employee-detail input[name="surname"]').val();
            var tax_code = $('#edit-employee .employee-detail input[name="tax_code"]').val();
            var email = $('#edit-employee .employee-detail input[name="email"]').val();
            var username = $('#edit-employee .employee-detail [name="username"]').val();
            var hire_date = $('#edit-employee .employee-detail input[name="hire_date"]').datepicker('getDate');

            var pattern;
            if (name != "" && surname != "" && email != "" && tax_code != "" && username != "") {
                pattern = /^([0-9a-zA-Z]+[-._+&amp;])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$/;
                if (pattern.test(email)) {
                    $.post("manage/user.php",
                            {
                                op_type: 'edit_utente',
                                user_id: <?= $user_id ?>,
                                name: name,
                                surname: surname,
                                email: email,
                                tax_code: tax_code,
                                username: username,
                                role: $('#edit-employee .employee-detail select[name="role"]').val(),
                                func_id: $('#edit-employee .employee-detail select[name="business_function"]').val()
                            }, function (data) {
                        if (data == "UTENTE") {
                            alert("Il codice fiscale immesso è uguale a quello di un altro utente. Modificalo e salva nuovamente.");
                        } else if (data > 0) {
                            alert("Modifiche salvate correttamente");
                            $('#single_user')
                                    .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                                    .load("pages/sections/employee-detail.php", {user_id: <?= $user_id ?>});
                        } else if (data == 0) {
                            alert('Non è stato modificato nessun dato');
                        } else {
                            alert('Errore nella registrazione dei dati. Riprova.\n' + data);
                        }
                    });
                } else {
                    alert('Indirizzo email non valido.');
                }
            } else {
                alert('Compila tutti i campi obbligatori *.');
            }
        }

        function loadDepartments() {
            $.post("manage/department.php",
                    {
                        op_type: "get_pu_departments",
                        pu_id: $('#edit-employee .employee-add-department select[name="product_unit"]').val()
                    }, function (departments) {
                var controls = '<label class="col-xs-4 control-label" for="department">Reparto</label>'+
                                '<div class="col-xs-6 controls">';
                departments = $.parseJSON(departments);
                if (departments == 0) {
                    $('#edit-employee .departments').empty().append(controls + '<a class="btn btn-default" data-target="#addDepartmentModal" data-toggle="modal" title="aggiungi reparto"><span class="glyphicon glyphicon-plus"></span></a></div>');
                } else {
                    $('#edit-employee .departments').empty().append(controls + '<select class="form-control" name="dep_id"></select></div>').promise().done(function(){
                        var options = '';
                        $.each(departments, function (i, item) {
                            options += '<option value="' + item.id_dep + '">' + item.short_desc_dep_type + '</option>';
                            if (i == Object.keys(departments).length - 1)
                                $('#edit-employee .departments > div.controls > select').append(options)
                                    .parents('.departments').append('<div class="col-xs-2"><a class="btn btn-default" data-target="#addDepartmentModal" data-toggle="modal" title="aggiungi reparto"><span class="glyphicon glyphicon-plus"></span></a></div>');
                        });
                    });
                }
            }
            );
        }

        /*	************** CARICA I REPARTI AL CAMBIARE DI UNITA' PRODUTTIVA ****************** */
        $('#edit-employee .employee-add-department select[name="product_unit"]').on('change', function (e) {
            if ($('#edit-employee .employee-add-department select[name="product_unit"]').val() == 0)
                $('#edit-employee .departments').empty();
            else
                loadDepartments();
        });


        $.fn.datepicker.defaults.format = "dd/mm/yyyy";
        $.fn.datepicker.defaults.language = "it";
        $.fn.datepicker.defaults.autoclose = true;
        $.fn.datepicker.defaults.todayHighlight = true;

        $('#edit-employee .datepicker').datepicker();

<?php if ($employee_hire_date) { ?>

            $('#edit-employee .employee-assignments input').datepicker({startDate: '<?= $employee_hire_date->format('d-m-Y'); ?>'});

<?php } else { ?>

            $('#edit-employee .employee-assignments input').datepicker();

<?php } ?>

        $('#employee-dossier > table').tablesorter({
            theme: 'greyT81',
            dateFormat: "ddmmyyyy", // set the default date format

            sortList: [[0, 0]],
            // hidden filter input/selects will resize the columns, so try to minimize the change
            widthFixed: true,
            // initialize zebra striping and filter widgets
            widgets: ["filter"],
            // headers: { 5: { sorter: false, filter: false } },
            widgetOptions: {
                filter_columnFilters: false,
                // extra css class applied to the table row containing the filters & the inputs within that row
                filter_cssFilter: '',
                // If there are child rows in the table (rows with class name from "cssChildRow" option)
                // and this option is true and a match is found anywhere in the child row, then it will make that row
                // visible; default is false
                filter_childRows: false,
                // if true, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
                // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus
                filter_hideFilters: true,
                // Set this option to false to make the searches case sensitive
                filter_ignoreCase: true,
                // jQuery selector string of an element used to reset the filters
                filter_reset: '.reset',
                // Delay in milliseconds before the filter widget starts searching; This option prevents searching for
                // every character while typing and should make searching large tables faster.
                filter_searchDelay: 300,
                // Set this option to true to use the filter to find text from the start of the column
                // So typing in "a" will find "albert" but not "frank", both have a's; default is false
                filter_startsWith: false,
                // if false, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
                // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus
                filter_hideFilters :true,
                        filter_functions: {}
            }
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
            editUser();
        });



        /*	************** MODIFICA DATA INCARICHI ****************** */
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
                        $('#single_user').load("pages/sections/employee-detail.php", {user_id: <?= $user_id ?>});
                    } else {
                        alert('Errore assegnazione: \n' + data);
                    }
                }
                );

            } else {
                $(this).datepicker('update', $(this).attr("value"));
            }

        });


        /* ************** CREA NUOVA UNITA' PRODUTTIVA (IN OVERLAY) ****************** */
        $("#addProductUnitModal .submit").on('click', function (e) {
            $.post("manage/department.php", {
                op_type: "add_product_unit",
                short_desc: $('#addProductUnitModal input[name="short_desc"]').val(),
                long_desc: $('#addProductUnitModal input[name="long_desc"]').val(),
                company_id: <?= $_SESSION['company']['id'] ?>
            }, function (data) {
                if (data > 0) {
                    if ($('#assegna_reparto .product_unit select').length != 1) {
                        $('#assegna_reparto .product_unit .controls')
                                .empty()
                                .append('<select name="product_unit" class="form-control">' +
                                        '<option value="0">Seleziona unità</option>' +
                                        '<option value="' + data + '" selected>' + $('#addProductUnitModal input[name="short_desc"]').val() + '</option>' +
                                        '</select>' +
                                        '<a class="btn btn-default" href="#addProductUnitModal" data-toggle="modal" title="aggiungi unità produttiva"><span class="glyphicon glyphicon-plus"></span></a>'
                                        );
                        $('#edit-employee .departments > div.controls')
                                .empty()
                                .append('<select class="form-control" name="dep_id"></select>' + 
                                '<a class="btn btn-default" href="#addDepartmentModal" data-toggle="modal" '+
                                'title="aggiungi reparto"><span class="glyphicon glyphicon-plus" title="aggiungi reparto"></span></a>');
                    } else {
                        $('#edit-employee .employee-department select[name="product_unit"]')
                        .append('<option value="' + data + '" selected>' + 
                            $('#addProductUnitModal input[name="short_desc"]').val() + '</option>');
                    }
                    $("#addProductUnitModal").modal('hide');
                } else {
                    alert("Errore nella creazione dell'unità prduttiva: " + data);
                }
            });
        });


        /* ************** CARICA I TIPI DI REPARTO NELLA DATALIST (IN OVERLAY) ****************** */
        $('#edit-employee .departments').on('click', 'a', function () {
            $.post("manage/department.php",
                    {
                        op_type: "get_pu_departments",
                        pu_id: "all",
                        company_id: <?= $_SESSION['company']['id'] ?>
                    }, function (departments) {
                var options = '';
                departments = $.parseJSON(departments);
                if (departments == 0) {
                    $('#addDepartmentModal > datalist').empty();
                } else {
                    $.each(departments, function (i, item) {
                        options += '<option value="' + item.id_dep + '">' + item.short_desc_dep_type + '</option>';
                        if (i == Object.keys(departments).length - 1)
                            $('#addDepartmentModal > datalist').empty().append(options);
                    });
                }
            }
            );
        });


        /* ************** MODIFICA DESCRIZIONE REPARTO (IN OVERLAY) ****************** */
        $('#addDepartmentModal').on('change', 'input[name="short_desc"]', function (e) {
            var long_desc = "";
            var num = $('#dep_type option').length;
            $('#dep_type option').each(function () {
                num = num - 1;
                if ($('#addDepartmentModal input[name="short_desc"]').val() == $(this).val()) {
                    long_desc = $(this).data("long_desc_dep_type");
                }
                if (num == 0)
                    $('#addDepartmentModal input[name="long_desc"]').val(long_desc);
            });
        });


        /* ************** CREA NUOVO REPARTO (IN OVERLAY) ****************** */
        $("#addDepartmentModal .submit").on('click', function (e) {
            var id_dep_type = 0;
            var num = $('#dep_type option').length;
            if (num == 0 && $('#addDepartmentModal input[name="short_desc"]').val() != "") {
                $.post("manage/department.php",
                        {
                            op_type: "add_department_type",
                            short_desc: $('#addDepartmentModal input[name="short_desc"]').val(),
                            long_desc: $('#addDepartmentModal input[name="long_desc"]').val(),
                            company_id: <?= $_SESSION['company']['id'] ?>
                        }, function (data) {
                    if (data > 0) {
                        $.post("manage/department.php",
                                {
                                    op_type: "add_department",
                                    dep_type_id: data,
                                    pu_id: $('#edit-employee .employee-department select[name="product_unit"]').val()
                                }, function (data) {
                            $("#addDepartmentModal").modal('hide');
                            loadDepartments();
                        }
                        );
                    }
                    ;
                }
                );
            } else {
                $('#dep_type option').each(function () {
                    num = num - 1;
                    if ($('#addDepartmentModal input[name="short_desc"]').val() == $(this).val()) {
                        id_dep_type = $(this).data("id_dep_type");
                    }
                    if (num == 0) {
                        if (id_dep_type == 0) {
                            $.post("manage/department.php",
                                    {
                                        op_type: "add_department_type",
                                        short_desc: $('#addDepartmentModal input[name="short_desc"]').val(),
                                        long_desc: $('#addDepartmentModal input[name="long_desc"]').val(),
                                        company_id: <?= $_SESSION['company']['id'] ?>
                                    }, function (data) {
                                if (data > 0) {
                                    $.post("manage/department.php",
                                            {
                                                op_type: "add_department",
                                                dep_type_id: data,
                                                pu_id: $('#edit-employee .employee-department select[name="product_unit"]').val()
                                            }, function (data) {
                                        $("#addDepartmentModal").modal('hide');
                                        loadDepartments();
                                    }
                                    );
                                }
                                ;
                            }
                            );
                        } else {
                            $.post("manage/department.php",
                                    {
                                        op_type: "add_department",
                                        dep_type_id: id_dep_type,
                                        pu_id: $('#edit-employee .employee-department select[name="product_unit"]').val()
                                    }, function (data) {
                                $("#addDepartmentModal").modal('hide');
                                loadDepartments();
                            }
                            );
                        }

                    }
                });
            }
        });


        /* ************** ASSEGNA UTENTE A REPARTO ****************** */
        function assignDepartment() {
            var dep_id = $('#edit-employee .employee-department select[name="dep_id"]').val();
            if (!dep_id)
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
                        $("#single_user").html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>');
                        $("#single_user").load("pages/sections/employee-detail.php", {user_id: <?= $user_id ?>});
                    } else {
                        alert("Errore nell'assegnazione del reparto");
                    }
                });
            }
        }

        $('#assegna_reparto').on('change', 'select', function (e) {
            assignDepartment();
        });

        $('#edit-employee .employee-department input[name="hire-date"]').datepicker().on('changeDate', function (e) {
            assignDepartment();
        });

        /* ************** ELIMINA ASSEGNAZIONE REPARTO ****************** */
        $('#edit-employee .employee-department a.elimina').click(function (e) {
            employeeDetailDeleteAssignation($(this).parents('tr').data('id_dep_empl'),<?= $user_id ?>);
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



        /* ************** MOSTRA OVERLAY AVANZAMENTO CORSO ****************** */
        $('#employee-dossier tr.on_elearning li:not(.disabled) a.stato').click(function (e) {
            var license_id = $(this).parents('tr').data('license_id');
            var title = $(this).parents('tr').children().first().text();
            $('#mySimpleModal')
                    .find('div.modal-header h3')
                    .text('Avanzamento corso ' + title)
                    .parent()
                    .next()
                    .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                    .load('course_detail.php?learning_project_user_id=' + license_id)
                    .parent('#mySimpleModal')
                    .modal();
        });


        /* ************** MOSTRA ATTESTATO ****************** */
        $('#employee-dossier tr.on_elearning li:not(.disabled) a.attestato').click(function (e) {
            var license_id = $(this).parents('tr').data('license_id');
            if ($(this).hasClass('genera')) {
                window.open('lib/genera.php?course_id=' + license_id, '_blank');
            } else {
                window.open('manage/render_document.php?doc_type=attestato_elearning&license_id=' + license_id, '_blank');
            }
        });



        /* ************** ELIMINA LICENZA ****************** */
        $('#employee-dossier tr.on_elearning li:not(.disabled) a.elimina').click(function (e) {
            if (confirm("Hai scelto di eliminare una licenza. Questa operazione non è reversibile. Sei certo di voler procedere?")) {
                var license_id = $(this).parents('tr').data('license_id');
                $.post("manage/user.php", {op_type: "remove_license", purchase_id: license_id, }, function (data) {
                    if (data > 0) {
                        $("#single_user")
                                .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                                .load("pages/sections/employee-detail.php", {user_id: <?= $user_id ?>});
                    }
                });
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



        // prevent enter key press
        document.onkeypress = stopRKey;

    });

</script>