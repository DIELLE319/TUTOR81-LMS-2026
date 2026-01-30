<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32 && $_SESSION['user']['role'] != 2 ) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

$company_id = filter_input(INPUT_GET, 'company_id', FILTER_SANITIZE_NUMBER_INT);

require_once BASE_LIBRARY_PATH . 'class_safety.php';
require_once BASE_LIBRARY_PATH . 'class_departments.php';
require_once BASE_LIBRARY_PATH . 'class_permissions.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';

$safe_obj = new Safety();
$dep_obj = new Departments();
$perm_obj = new Permissions();
$comp_obj = new T81Company();

$company = $comp_obj->getBusinessDetail($company_id);

if ($company['is_tutor']) {
    // gli amministratori dell'azienda
    $administrators = $comp_obj->getUsersCompanyByID($company_id, array(1,32,1000));
    $company_plan = $comp_obj->getCompanyPlan($company_id);
}
$department_types = $dep_obj->getDepartmentTypes($company_id);
$business_functions = $safe_obj->getBusinessFunctions();
$product_units = $dep_obj->getProductUnits($company_id);
$roles = $perm_obj->getRoles();
$roles_name = array();
foreach ($roles as $single_role) {
    $roles_name[$single_role['id_role']] = $single_role['short_desc_role'];
}
?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3>Crea nuovo dipendente</h3>
</div>
<div class="modal-body" style="padding: 15px 0; min-height: 500px;">
    <div id="new-employee" class="container-fluid">
        <form class="form-horizontal" action="javascript: void(0)" method="POST">
            <input type="hidden" id="company_id" value="<?= $company_id ?>">
            <div class="employee-detail">
                <h3><span class="small">azienda: </span><?= strtoupper($company['business_name']) ?></h3>

                <p class="text-center"><strong>Dati personali</strong></p>

                <!-- NOME -->
                <div class="form-group">
                    <label class="col-xs-4 control-label" for="name">Nome*</label>
                    <div class="col-xs-8 controls">
                        <input type="text" name="name" class="form-control" placeholder="Nome" required>
                    </div>
                </div><!-- /NOME -->

                <!-- COGNOME -->
                <div class="form-group">
                    <label class="col-xs-4 control-label" for="surname">Cognome*</label>
                    <div class="col-xs-8 controls">
                        <input type="text" name="surname" class="form-control" placeholder="Cognome" required>
                    </div>
                </div><!-- /COGNOME -->

                <!-- CODICE FISCALE -->
                <div class="form-group">
                    <label class="col-xs-4 control-label" for="tax_code">Cod. Fiscale*</label>
                    <div class="col-xs-8 controls">
                        <input type="text" name="tax_code" class="form-control" placeholder="Codice Fiscale" pattern="[a-zA-Z0-9]{16}" required>
                    </div>
                </div><!-- /CODICE FISCALE -->

                <!-- EMAIL -->
                <div class="form-group">
                    <label class="col-xs-4 control-label" for="email">Email*</label>
                    <div class="col-xs-8 controls">
                        <input type="text" name="email" class="form-control" placeholder="indirizzo email" required>
                    </div>
                </div><!-- /EMAIL -->


                <!-- FUNZIONE -->
                <div class="form-group">
                    <label class="col-xs-4 control-label" for="business_function">Funzione</label>
                    <div class="col-xs-8 controls">
                        <select class="form-control" name="business_function">

                            <?php foreach ($business_functions as $single_buz_function) { ?>

                                <option value="<?= $single_buz_function['id'] ?>">
                                    <?= $single_buz_function['name'] ?>
                                </option>

                            <?php } ?>

                        </select>
                    </div>
                </div><!-- /FUNZIONE -->
            </div>

            <p class="text-center">*campi obbligatori</p>
                <!-- RUOLO -->
                <div class="control-role col-sm-8">
                    <fieldset>
                        <legend>Quali funzioni deve avere questo utente in piattaforma?</legend>
                        <div class="radio">
                            <label><input type="radio" name="role" value="0" checked><?= $roles_name[0] ?></label>
                        </div>

                    <?php if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32 || $_SESSION['user']['role'] == 2) { ?>

                        <div class="radio">
                            <label><input type="radio" name="role" value="2"><?= $roles_name[2] ?></label>
                        </div>

                    <?php }
                    if ($company['is_tutor'] && ( count($administrators) < $company_plan['max_admin'] ) && 
                            ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32)) { ?>

                        <div class="radio">
                            <label><input type="radio" name="role" value="1"><?= $roles_name[1] ?></label>
                        </div>

                    <?php }

                    if ($company_id == 2 && $_SESSION['user']['role'] == 1000) { ?>

                        <div class="radio">
                            <label><input type="radio" name="role" value="1000"><?= $roles_name[1000] ?></label>
                        </div>

                    <?php } ?>  		

                    </fieldset>
                </div><!-- /RUOLO -->

                <!-- INVIO MAIL REGISTRAZIONE -->
                <div class="control-send-email col-sm-4">
                    <fieldset>
                        <legend>Invio email di registrazione?</legend>
                        <div class="radio">
                            <label><input type="radio" name="send-mail" value="true">Si, invia immediatamente</label>
                        </div>
                        <div class="radio">
                            <label><input type="radio" name="send-mail" value="false" checked>No, non inviare</label>
                        </div>
                    </fieldset>
                </div><!-- /INVIO MAIL REGISTRAZIONE -->
        
        </form><!-- /form#new-employee -->    
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
    <button type="button" class="btn btn-primary save-modal"><span class="glyphicon glyphicon-ok"></span> Salva</button>
</div>
<script>

$(function(){
    /* ******** SALVA CREA NUOVO UTENTE ********** */
        $('#createEmployeeModal').on('click', '.save-modal', function(){
            $.isLoading({text: "Attendere ..."});
            var user = newUser($('#company_id').val(), <?= $_SESSION['user']['id'] ?>);
            if (user) {
                $('#createEmployeeModal').removeClass('fade').modal('hide'); // rimuove la classe fade per eliminare l'animazione che si blocca
                alert("Dipendente creato correttamente");
                $("#single-user").html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>').load("pages/sections/employee-detail.php", {
                    user_id: user.id
                });
                $("#cerca-utenti-modal").modal();
            } else {
                alert("Errore nella creazione dell'utente");
            }
            $.isLoading("hide");
        });
});

</script>