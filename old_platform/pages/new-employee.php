<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32 && $_SESSION['user']['role'] != 2 ) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_safety.php';
require_once BASE_LIBRARY_PATH . 'class_departments.php';
require_once BASE_LIBRARY_PATH . 'class_permissions.php';

$safe_obj = new Safety();
$dep_obj = new Departments();
$perm_obj = new Permissions();
$role = filter_input(INPUT_GET, 'role', FILTER_SANITIZE_NUMBER_INT) ? : 0;
$company_id = $_SESSION['company']['id'];
$department_types = $dep_obj->getDepartmentTypes($company_id);
$business_functions = $safe_obj->getBusinessFunctions();
$product_units = $dep_obj->getProductUnits($company_id);
$roles = $perm_obj->getRoles();
$roles_name = array();
foreach ($roles as $single_role) {
    $roles_name[$single_role['id_role']] = $single_role['short_desc_role'];
}
?>
<script>

function loadDepartmentsForNewEmployee() {
    $.post("manage/department.php",
            {
                op_type: "get_pu_departments",
                pu_id: $('#new-employee .employee-add-department select[name="product_unit"]').val()
            }, function (departments) {
        var controls = '<select class="form-control input-medium" name="department">';
        departments = $.parseJSON(departments);
        if (departments == 0) {
            $('#new-employee .departments > div.controls').empty().append(controls + '</select> <a href="#addDepartmentModalForNewEmployee" data-toggle="modal"><span class="glyphicon glyphicon-plus" title="aggiungi reparto"></span></a>');
        } else {
            $.each(departments, function (i, item) {
                controls += '<option value="' + item.id_dep + '">' + item.short_desc_dep_type + '</option>';
                if (i == Object.keys(departments).length - 1)
                    $('#new-employee .departments > div.controls').empty().append(controls + '</select> <a href="#addDepartmentModalForNewEmployee" data-toggle="modal"><span class="glyphicon glyphicon-plus" title="aggiungi reparto"></span></a>');
            });
        }
    }
    );
}


</script>

<div id="new-employee" class="container-fluid">
    <form class="form-horizontal" action="javascript: void(0)" method="POST">
        <div class="row">
            <div class="employee-detail col-md-4">

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


            <div class="employee-assignments col-md-4">

                <table id="add-employee-assignments" style="width: 100%">
                    <thead>
                        <tr>
                            <th class="col-xs-4">Incarichi</th>
                            <th class="col-xs-4">data inizio</th>
                            <th class="col-xs-4">data fine</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="incarico-rspp reserved" data-assignment="1">
                            <td>RSPP</td>
                            <td>
                                <input type="text" class="form-control start_date input-small" name="start-rspp">
                            </td>
                            <td>
                                <input type="text" class="form-control end_date input-small" name="end-rspp">
                            </td>
                        </tr>
                        <tr class="incarico-aspp reserved" data-assignment="5">
                            <td>ASPP</td>
                            <td>
                                <input type="text" class="form-control start_date input-small" name="start-aspp">
                            </td>
                            <td>
                                <input type="text" class="form-control end_date input-small" name="end-aspp">
                            </td>
                        </tr>
                        <tr class="incarico-rspp_dl reserved" data-assignment="6">
                            <td>RSPP-DL</td>
                            <td>
                                <input type="text" class="form-control start_date input-small" name="start-rspp_dl">
                            </td>
                            <td>
                                <input type="text" class="form-control end_date input-small" name="end-rspp_dl">
                            </td>
                        </tr>
                        <tr class="incarico-rls reserved" data-assignment="2">
                            <td>RLS</td>
                            <td>
                                <input type="text" class="form-control start_date input-small" name="start-rls">
                            </td>
                            <td>
                                <input type="text" class="form-control end_date input-small" name="end-rls">
                            </td>
                        </tr>
                        <tr class="incarico-antincendio" data-assignment="3">
                            <td>Antincendio</td>
                            <td>
                                <input type="text" class="form-control start_date input-small" name="start-antincendio">
                            </td>
                            <td>
                                <input type="text" class="form-control end_date input-small" name="end-antincendio">
                            </td>
                        </tr>
                        <tr class="incarico-soccorso" data-assignment="4">
                            <td>Primo Soccorso</td>
                            <td>
                                <input type="text" class="form-control start_date input-small" name="start-soccorso">
                            </td>
                            <td>
                                <input type="text" class="form-control end_date input-small" name="end-soccorso">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="employee-department employee-add-department col-md-4">
                <p class="text-center"><strong>Unità produttiva e reparto</strong></p>

                <!-- UNITA' PRODUTTIVA -->
                <div class="product_unit form-group">
                    <label class="col-xs-4 control-label" for="product-unit">Unità produttiva</label>
                    <div class="col-xs-8 controls">

                        <?php if ($product_units) { ?>

                            <select name="product_unit" class="form-control input-medium">
                                <option value="0">Seleziona unità</option>

                                <?php foreach ($product_units as $single_product_unit) { ?>

                                    <option value="<?= $single_product_unit['id_pu'] ?>">

                                        <?= $single_product_unit['short_desc_pu'] ?>

                                    </option>

                                <?php } ?>

                            </select>

                            <a href="#addProductUnitModalForNewEmployee" data-toggle="modal"><span class="glyphicon glyphicon-plus" title="aggiungi unità produttiva"></span></a>

                        <?php } else { ?>

                            <button type="button" class="btn btn-default" data-toggle="modal" data-target="#addProductUnitModalForNewEmployee">Crea Unità Produttiva</button>

                        <?php } ?>

                    </div>
                </div>																																	<!-- /UNITA' PRODUTTIVA -->


                <!-- REPARTO -->
                <div class="departments form-group">
                    <label class="col-xs-4 control-label" for="department">Reparto</label>
                    <div class="col-xs-8 controls">

                    </div>
                </div>																																	<!-- /REPARTO -->


                <!-- DATA ASSUNZIONE -->
                <div class="form-group">
                    <label class="col-xs-4 control-label" for="hire-date">Data assunzione</label>
                    <div class="col-xs-8 controls">
                        <input type="text" name="hire-date" class="form-control input-small datepicker">
                    </div>
                </div>																																	<!-- /DATA ASSUNZIONE -->


            </div>
            
        </div>

    </form><!-- /form#new-employee -->

    <p class="text-center">*campi obbligatori</p>
    <div class="row">
        <!-- RUOLO -->
        <div class="control-role pull-left" style="margin: 0 20px;">
            <fieldset>
                <legend>Quali funzioni deve avere questo utente in piattaforma?</legend>
                <div class="radio<?= isset($role) && $role != 0 ? ' hidden' : '' ?>" >
                    <label><input type="radio" name="role" value="0" <?= $role == 0 ? ' checked' : ''?>><?= $roles_name[0] ?></label>
                </div>

            <?php if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32 || $_SESSION['user']['role'] == 2) { ?>

                <div class="radio<?= isset($role) && $role != 2 ? ' hidden' : '' ?>">
                    <label><input type="radio" name="role" value="2" <?= $role == 2 ? ' checked' : ''?>><?= $roles_name[2] ?></label>
                </div>

            <?php }

             if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32) { ?>

                <div class="radio<?= isset($role) && $role != 1 ? ' hidden' : '' ?>">
                    <label><input type="radio" name="role" value="1" <?= $role == 1 ? ' checked' : ''?>><?= $roles_name[1] ?></label>
                </div>

            <?php }

                if ($_SESSION['user']['role'] == 1000) {
                    ?>


                <div class="radio<?= isset($role) && $role != 32 ? ' hidden' : '' ?>">
                    <label><input type="radio" name="role" value="32" <?= $role == 32 ? ' checked' : ''?>><?= $roles_name[32] ?></label>
                </div>
                <div class="radio<?= isset($role) && $role != 1000 ? ' hidden' : '' ?>">
                    <label><input type="radio" name="role" value="1000" <?= $role == 1000 ? ' checked' : ''?>><?= $roles_name[1000] ?></label>
                </div>

            <?php } ?>  		

            </fieldset>
        </div><!-- /RUOLO -->

        <!-- INVIO MAIL REGISTRAZIONE -->
        <div class="control-send-email hidden" style="padding: 0;">
            <fieldset>
                <legend>Invio email di registrazione?</legend>
                <div class="radio">
                    <label><input type="radio" name="send-mail" value="true" <?= $role != 0 ? ' checked' : ''; ?>>Si, invia immediatamente</label>
                </div>
                <div class="radio">
                    <label><input type="radio" name="send-mail" value="false" <?= $role == 0 ? ' checked' : ''; ?>>No, non inviare</label>
                </div>
            </fieldset>
        </div><!-- /INVIO MAIL REGISTRAZIONE -->

    </div>
        <button id="save-new-employee" class="btn btn-primary pull-right"><span class="glyphicon glyphicon-ok"></span> Salva</button>

<!-- ---- MODALS ---- -->
    <div id="addProductUnitModalForNewEmployee" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="addProductUnitLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close close-modal" aria-hidden="true">×</button>
                    <h3 id="addProductUnitLabelLabel">Aggiungi Unità Produttiva</h3>
                </div>
                <div class="modal-body">

                    <form class="form-horizontal" action="manage/department.php" method="POST">

                        <div class="form-group">
                            <label class="col-xs-3 control-label">Nome</label>
                            <div class="col-xs-9 controls">
                                <input class="form-control input-xlarge" type="text" name="short_desc">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-3 control-label">Descrizione</label>
                            <div class="col-xs-9 controls">
                                <input class="form-control input-xlarge" type="text" name="long_desc">
                            </div>
                        </div>

                    </form>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-default close-modal" aria-hidden="true">Annulla</button>
                    <button class="btn btn-primary submit"><span class="glyphicon glyphicon-ok"></span> Salva</button>
                </div>
            </div>
        </div>

    </div>



    <div id="addDepartmentModalForNewEmployee" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 id="myModalLabel">Seleziona o aggiungi Reparto</h3>
                </div>
                <div class="modal-body">

                    <form class="form-horizontal" action="manage/department.php" method="POST">

                        <div class="form-group">
                            <label class="col-xs-3 control-label">Nome reparto</label>
                            <div class="col-xs-9 controls">
                                <input class="form-control input-xlarge" type="text" name="short_desc" list="dep_type">
                                <datalist id="dep_type">

                                    <?php foreach ($department_types as $dep_type) { ?>

                                        <option data-id_dep_type="<?= $dep_type['id_dep_type'] ?>" data-long_desc_dep_type="<?= $dep_type['long_desc_dep_type'] ?>"><?= $dep_type['short_desc_dep_type'] ?></option>

                                    <?php } ?>

                                </datalist>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-3 control-label">Descrizione</label>
                            <div class="col-xs-9 controls">
                                <input class="form-control input-xlarge" type="text" name="long_desc">
                            </div>
                        </div>

                    </form>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-default close-modal" aria-hidden="true">Annulla</button>
                    <button class="btn btn-primary submit"><span class="glyphicon glyphicon-ok"></span> Salva</button>
                </div>
            </div>
        </div>
    </div>
<!-- ---- END MODALS ---- -->

</div>

<script>
    $(function () {

        /* ************** CARICA I REPARTI AL CAMBIARE DI UNITA' PRODUTTIVA ****************** */
        $('#new-employee .employee-add-department select[name="product_unit"]').on('change', function (e) {
            if ($('#new-employee .employee-add-departments select[name="product_unit"]').val() == 0)
                $('#new-employee .departments > div.controls').empty();
            else
                loadDepartmentsForNewEmployee();
        });


        $.fn.datepicker.defaults.format = "dd/mm/yyyy";
        $.fn.datepicker.defaults.language = "it";
        $.fn.datepicker.defaults.autoclose = true;
        $.fn.datepicker.defaults.todayHighlight = true;

        $('#new-employee .datepicker').datepicker();

        $('#new-employee .employee-assignments input').datepicker();


        /* ************** GESTIONE DATE ASSEGNAZIONE INCARICHI RISERVATI ****************** */
        $('#new-employee .employee-assignments input.start_date').on('input changeDate', function (e) {
            $(this).datepicker('hide');
            if ($(this).parents('tr').hasClass('reserved')) {
                t = $(this);
                var other = $(this).parents('tr').siblings('.reserved');
                var start_date = $(this).datepicker('getDate');

                if ($(this).val() != "" && start_date instanceof Date && !isNaN(start_date.valueOf())) {
                    if ($(this).parents('tr').find('input.end_date').datepicker('getDate') < start_date)
                        $(this).parents('tr').find('input.end_date').datepicker('setDate', start_date);
                    $(this).parents('tr').find('input.end_date').datepicker('setStartDate', start_date);
                    other.each(function () {
                        $(this).find('input').prop("disabled", true);
                    });
                } else {
                    $(this).parents('tr').find('input.end_date').datepicker('update', '');
                    other.each(function () {
                        $(this).find('input').prop("disabled", false);
                    });
                }
            }
        });

        /* ************* CHIUDE LA MODAL DELLE UNITA' PRODUTTIVE ************* */
        $('#addProductUnitModalForNewEmployee .close-modal').click(function (e) {
            $('#addProductUnitModalForNewEmployee').modal('hide');
        });

        /* ************** CREA NUOVA UNITA' PRODUTTIVA (IN OVERLAY) ****************** */
        $("#addProductUnitModalForNewEmployee .submit").on('click', function (e) {
            $.post("manage/department.php", {
                op_type: "add_product_unit",
                short_desc: $('#addProductUnitModalForNewEmployee input[name="short_desc"]').val(),
                long_desc: $('#addProductUnitModalForNewEmployee input[name="long_desc"]').val(),
                company_id: <?= $company_id ?>
            }, function (data) {
                if (data > 0) {
                    if ($('#new-employee .employee-department .product_unit select').length != 1) {
                        $('#new-employee .employee-department .product_unit .controls')
                                .empty()
                                .append('<select name="product_unit" class="form-control input-medium">' +
                                        '<option value="0">Seleziona unità</option>' +
                                        '<option value="' + data + '" selected>' + $('#addProductUnitModalForNewEmployee input[name="short_desc"]').val() + '</option>' +
                                        '</select>' +
                                        '<a href="#addProductUnitModalForNewEmployee" data-toggle="modal"><span class="glyphicon glyphicon-plus" title="aggiungi unità produttiva"></span></a>'
                                        );
                        $('#new-employee .employee-department .departments > div.controls')
                                .empty()
                                .append('<select class="form-control input-medium" name="dep_id"></select> <a href="#addDepartmentModalForNewEmployee" data-toggle="modal"><span class="glyphicon glyphicon-plus" title="aggiungi reparto"></span></a>');
                    } else {
                        $('#new-employee .employee-department select[name="product_unit"]').append('<option value="' + data + '" selected>' + $('#addProductUnitModalForNewEmployee input[name="short_desc"]').val() + '</option>');
                    }
                    $("#addProductUnitModalForNewEmployee").modal('hide');
                } else {
                    alert("Errore nella creazione dell'unità prduttiva: " + data);
                }
            });
        });


        /* ************** CARICA I TIPI DI REPARTO NELLA DATALIST (IN OVERLAY) ****************** */
        $('#new-employee .departments').on('click', 'a', function () {
            $.post("manage/department.php",
                    {
                        op_type: "get_pu_departments",
                        pu_id: "all",
                        company_id: <?= $company_id ?>
                    }, function (departments) {
                var options = '';
                departments = $.parseJSON(departments);
                if (departments == 0) {
                    $('#addDepartmentModalForNewEmployee > datalist').empty();
                } else {
                    $.each(departments, function (i, item) {
                        options += '<option value="' + item.id_dep + '">' + item.short_desc_dep_type + '</option>';
                        if (i == Object.keys(departments).length - 1)
                            $('#addDepartmentModalForNewEmployee > datalist').empty().append(options);
                    });
                }
            }
            );
        });


        /* ************** MODIFICA DESCRIZIONE REPARTO (IN OVERLAY) ****************** */
        $('#addDepartmentModalForNewEmployee').on('change', 'input[name="short_desc"]', function (e) {
            var long_desc = "";
            var num = $('#dep_type option').length;
            $('#dep_type option').each(function () {
                num = num - 1;
                if ($('#addDepartmentModalForNewEmployee input[name="short_desc"]').val() == $(this).val()) {
                    long_desc = $(this).data("long_desc_dep_type");
                }
                if (num == 0)
                    $('#addDepartmentModalForNewEmployee input[name="long_desc"]').val(long_desc);
            });
        });


        /* ************* CHIUDE LA MODAL DEI REPARTI ************* */
        $('#addDepartmentModalForNewEmployee .close-modal').click(function (e) {
            $('#addDepartmentModalForNewEmployee').modal('hide');
        });

        /* ************** CREA NUOVO REPARTO (IN OVERLAY) ****************** */
        $("#addDepartmentModalForNewEmployee .submit").on('click', function (e) {
            var id_dep_type = 0;
            var num = $('#dep_type option').length;
            if (num == 0 && $('#addDepartmentModalForNewEmployee input[name="short_desc"]').val() != "") {
                $.post("manage/department.php",
                        {
                            op_type: "add_department_type",
                            short_desc: $('#addDepartmentModalForNewEmployee input[name="short_desc"]').val(),
                            long_desc: $('#addDepartmentModalForNewEmployee input[name="long_desc"]').val(),
                            company_id: <?= $company_id ?>
                        }, function (data) {
                    if (data > 0) {
                        $.post("manage/department.php",
                                {
                                    op_type: "add_department",
                                    dep_type_id: data,
                                    pu_id: $('#new-employee .employee-department select[name="product_unit"]').val()
                                }, function (data) {
                            $("#addDepartmentModalForNewEmployee").modal('hide');
                            loadDepartmentsForNewEmployee();
                        }
                        );
                    }
                    ;
                }
                );
            } else {
                $('#dep_type option').each(function () {
                    num = num - 1;
                    if ($('#addDepartmentModalForNewEmployee input[name="short_desc"]').val() == $(this).val()) {
                        id_dep_type = $(this).data("id_dep_type");
                    }
                    if (num == 0) {
                        if (id_dep_type == 0) {
                            $.post("manage/department.php",
                                    {
                                        op_type: "add_department_type",
                                        short_desc: $('#addDepartmentModalForNewEmployee input[name="short_desc"]').val(),
                                        long_desc: $('#addDepartmentModalForNewEmployee input[name="long_desc"]').val(),
                                        company_id: <?= $company_id ?>
                                    }, function (data) {
                                if (data > 0) {
                                    $.post("manage/department.php",
                                            {
                                                op_type: "add_department",
                                                dep_type_id: data,
                                                pu_id: $('#new-employee .employee-department select[name="product_unit"]').val()
                                            }, function (data) {
                                        $("#addDepartmentModalForNewEmployee").modal('hide');
                                        loadDepartmentsForNewEmployee();
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
                                        pu_id: $('#new-employee .employee-department select[name="product_unit"]').val()
                                    }, function (data) {
                                $("#addDepartmentModalForNewEmployee").modal('hide');
                                loadDepartmentsForNewEmployee();
                            }
                            );
                        }

                    }
                });
            }
        });

    });

</script>