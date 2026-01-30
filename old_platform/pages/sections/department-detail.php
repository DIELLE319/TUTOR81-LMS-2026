<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}
require_once BASE_LIBRARY_PATH . 'class_departments.php';

$dep_obj = new Departments();

$dep_id = filter_input(INPUT_GET, 'dep_id', FILTER_SANITIZE_NUMBER_INT);

if (empty($dep_id)) {
    echo "<h3>Seleziona una reparto</h3>";
    exit();
}

$dep = $dep_obj->getDepartmentDetail($dep_id);
$dep_risk = explode(',', $dep['risks']);
$department_types = $dep_obj->getDepartmentTypes($_SESSION['company']['id']);
$risks = $dep_obj->getRisksList();
?>
<h3 class="text-center">Dettaglio Reparto</h3>
<p class="text-center"><em>(le modifiche apportate sono salvate automaticamente)</em>

<div id="dep-detail" class="form-horizontal">

    <div class="form-group">
        <label class="col-xs-3 control-label" for="short_desc_pu">Reparto</label>
        <div class="col-xs-9 controls">
            <input type="text" class="form-control" name="short_desc_dep_type" 
                   id="short_desc_dep_type" value="<?= $dep['short_desc_dep_type'] ?>" 
                   maxlength="30" list="dep_type"> <small>(max. 30 caratt.)</small>

            <datalist id="dep_type">

                <?php foreach ($department_types as $dep_type) { ?>

                    <option data-id_dep_type="<?= $dep_type['id_dep_type'] ?>" data-long_desc_dep_type="<?= $dep_type['long_desc_dep_type'] ?>"><?= $dep_type['short_desc_dep_type'] ?></option>

                <?php } ?>

            </datalist>
        </div>
    </div>

    <div class="form-group hidden">
        <label class="col-xs-3 control-label" for="long_desc_dep_type">Descrizione </label>
        <div class="col-xs-9 controls">
            <textarea class="form-control" name="long_desc_dep_type" id="short_desc_dep_type" maxlength="256"><?= $dep['long_desc_dep_type'] ?></textarea> <small>(max. 256 caratt.)</small>
        </div>
    </div>
    <div id="risks">
        
    <?php foreach ($risks as $risk) { ?>
        
        <div class="checkbox">
            <label>
                <input type="checkbox" name="risks" value="<?= $risk['id_risk'] ?>" <?= in_array($risk['id_risk'], $dep_risk) ? ' checked' : ''?>> 
                <?= $risk['risk_desc'] ?>
            </label>
        </div>
        
    <?php } ?>
        
        <div class="checkbox">
            <label>
                <input type="checkbox" name="other_risk" <?= !empty($dep['other_risk']) ? ' checked' : '' ?>> 
                Altri rischi
            </label>
            
        </div>
        <div id="other_risk" class="form-group" <?= !empty($dep['other_risk']) ? : ' style="display:none;"' ?>>
            <div class="col-xs-offset-1 col-xs-11 controls">
                <textarea class="form-control" name="other_risk" maxlength="1000"><?= html_entity_decode($dep['other_risk']) ?></textarea>
                <small>(max. 1000 caratt.)</small>
            </div>
        </div>
     
    </div>
    
</div>

<script>

    $(function () {

        $('#dep-detail').on('change', 'input[name="short_desc_dep_type"]', function (e) {
            var short_desc_dep_type = $('#dep-detail input[name="short_desc_dep_type"]').val();
            var long_desc_dep_type = $('#dep-detail textarea[name="long_desc_dep_type"]').val();
            if (confirm("Vuoi apportare la modifica a tutti i reparti con lo stesso nome in tutte le unità produttive?\n" +
                    "Scegliendo Cancel o Annulla le modifiche verranno apportate solo a questo reparto in questa unità produttiva\n\n" +
                    "Scegliendio OK il nome del raparto verrà modificato per tutti i reparti con avevano lo stesso nome di questo.\n" +
                    "Se esiste già un reparto con questo nome verrà utilizzato come riferimento.") == true) {
                $.post("manage/department.php", {
                    op_type: 'edit_department_type',
                    id_dep_type: <?= $dep['id_dep_type'] ?>,
                    company_id: <?= $_SESSION['company']['id'] ?>,
                    short_desc_dep_type: short_desc_dep_type,
                    long_desc_dep_type: long_desc_dep_type
                }, function (data) {
                    if (data > 0) {
                        var oldtext = $('#departments-list li[data-dep_id="<?= $dep['id_dep'] ?>"] a').text();
                        $('#departments-list li[data-dep_id] a').filter(function () {
                            return $(this).text() === oldtext;
                        }).text(short_desc_dep_type);
                    }
                });
            } else {
                $.post("manage/department.php", {
                    op_type: 'edit_department',
                    id_dep: <?= $dep['id_dep'] ?>,
                    company_id: <?= $_SESSION['company']['id'] ?>,
                    short_desc_dep_type: short_desc_dep_type,
                    long_desc_dep_type: long_desc_dep_type
                }, function (data) {
                    if (data > 0) {
                        $('#departments-list li[data-dep_id="<?= $dep['id_dep'] ?>"] a').text(short_desc_dep_type);
                    }
                });
            }

        });

        $('#dep-detail').on('change', 'textarea[name="long_desc_dep_type"]', function (e) {
            $.post("manage/department.php", {
                op_type: 'edit_long_description_department_type',
                id_dep_type: <?= $dep['id_dep_type'] ?>,
                short_desc_dep_type: $('#dep-detail input[name="short_desc_dep_type"]').val(),
                long_desc_dep_type: $('#dep-detail textarea[name="long_desc_dep_type"]').val()
            });

        });
        
        $('#dep-detail').on('change', 'input[name="risks"],textarea[name="other_risk"]', function (e) {
            var checked = [];
            $('#dep-detail input[name="risks"]:checked').each(function ()
                {
                    checked.push(parseInt($(this).val()));
                });     
            $.post("manage/department.php", {
                op_type: 'edit_dep_risks',
                id_dep: <?= $dep['id_dep'] ?>,
                risks: checked,
                other_risk: $('#dep-detail textarea[name="other_risk"]').val().replace(new RegExp("\n", 'g'), "<br />")
            });

        });
        
        $('#dep-detail').on('click', 'input[name="other_risk"]', function (e) {
            if (!$(this).prop('checked')) {
                if ($('#dep-detail textarea[name="other_risk"]').val() != "") {
                    if (confirm('Elimino gli altri rischi?')){
                        $('#dep-detail textarea[name="other_risk"]').val("");
                        var checked = [];
                        $('#dep-detail input[name="risks"]:checked').each(function ()
                            {
                                checked.push(parseInt($(this).val()));
                            });     
                        $.post("manage/department.php", {
                            op_type: 'edit_dep_risks',
                            id_dep: <?= $dep['id_dep'] ?>,
                            risks: checked,
                            other_risk: $('#dep-detail textarea[name="other_risk"]').val().replace(new RegExp("\n", 'g'), "<br />")
                        });
                        $('#other_risk').hide();
                    } else {
                        $('#dep-detail input[name="other_risk"]').prop('checked', true);
                    }
                } else {
                    $('#other_risk').hide();                
                }
            } else {
                $('#other_risk').show();
            }
        });

    });

</script>