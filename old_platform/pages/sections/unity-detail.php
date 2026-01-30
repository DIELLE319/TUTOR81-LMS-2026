<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_departments.php';
require_once BASE_LIBRARY_PATH . 'class_custom_category.php';

$dep_obj = new Departments();
$ccat_obj = new CustomCategory();

$pu_id = filter_input(INPUT_GET, 'pu_id', FILTER_SANITIZE_NUMBER_INT);
if (empty($pu_id)) {
    echo "<h3>Seleziona una unità produttiva</h3>";
    exit();
} else {
    $pu = $dep_obj->getProductUnitDetail($pu_id);
}

$custom_categories = $dep_obj->getProductUnitCustomCategories($pu['id_pu']);

$list_fire_risk = $ccat_obj->getCustomCategoriesByLev1(2);
$pu_fire_risk = $dep_obj->getProductUnitSpecificCustomCategories($pu['id_pu'], 2);

$list_first_aid_risk = $ccat_obj->getCustomCategoriesByLev1(3);
$pu_first_aid_risk = $dep_obj->getProductUnitSpecificCustomCategories($pu['id_pu'], 3);

$list_50dip_risk = $ccat_obj->getCustomCategoriesByLev1(4);
$pu_50dip_risk = $dep_obj->getProductUnitSpecificCustomCategories($pu['id_pu'], 4);

$list_ateco_sectors = $dep_obj->getAtecoList();
$pu_ateco_sector = $dep_obj->getProductUnitAteco($pu['id_pu']);

$ateco_risks = $dep_obj->getAtecoRisks();
$pu_ateco_risk = $dep_obj->getProductUnitAtecoRisk($pu['id_pu']);
?>
<h3 class="text-center">Dettaglio Unità Produttiva</h3>
<p class="text-center"><em>(le modifiche apportate sono salvate automaticamente)</em>

<div id="pu-detail" class="form-horizontal">

    <div class="form-group">
        <label class="col-xs-5 control-label" for="short_desc_pu">Unità produttiva</label>
        <div class="col-xs-7 controls">
            <input type="text" class="form-control" name="short_desc_pu" id="short_desc_pu" value="<?= $pu['short_desc_pu'] ?>" maxlength="30"> <small>(max. 30 caratt.)</small>
        </div>
    </div>

    <div class="form-group">
        <label class="col-xs-5 control-label" for="long_desc_pu">Descrizione </label>
        <div class="col-xs-7 controls">
            <textarea class="form-control" name="long_desc_pu" id="short_desc_pu" maxlength="256"><?= $pu['long_desc_pu'] ?></textarea> <small>(max. 256 caratt.)</small>
        </div>
    </div>

    <div class="form-group">
        <label class="col-xs-5 control-label" for="fire_risk">Livello di Rischio INCENDIO</label>
        <div class="col-xs-7 controls">
            <select class="form-control" name="fire_risk" data-fire_risk="<?= $pu_fire_risk['id_pu_ccat'] ?>" onchange="onChangeFireRisk(<?= $pu['id_pu'] ?>)">

                <?php for ($lev_3 = 1; $lev_3 < count($list_fire_risk); $lev_3++) {
                    $selected = $list_fire_risk[$lev_3]['id'] == $pu_fire_risk['ccat_id'] ? "selected" : "";
                    ?>

                    <option <?= $selected ?> value="<?= $list_fire_risk[$lev_3]['id'] ?>"><?= $list_fire_risk[$lev_3]['definition'] ?></option>

                <?php } ?> 

            </select>
            <input type="hidden" name="new_fire_risk" value="<?= $selected ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="col-xs-5 control-label" for="first_aid_risk">Livello di Rischio PRIMO SOCCORSO</label>
        <div class="col-xs-7 controls">
            <select class="form-control" name="first_aid_risk" data-first_aid_risk="<?= $pu_first_aid_risk['id_pu_ccat'] ?>" onchange="onChangeFirstAidRisk(<?= $pu['id_pu'] ?>)">

                <?php for ($lev_3 = 1; $lev_3 < count($list_first_aid_risk); $lev_3++) {
                    $selected = $list_first_aid_risk[$lev_3]['id'] == $pu_first_aid_risk['ccat_id'] ? "selected" : "";
                    ?>

                    <option <?= $selected ?> value="<?= $list_first_aid_risk[$lev_3]['id'] ?>"><?= $list_first_aid_risk[$lev_3]['definition'] ?></option>

                <?php } ?> 

            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="col-xs-5 control-label" style="margin-top: 10px;">Numero dipendenti</label>
        <div class="col-xs-7 controls">
            <fieldset id="50dip_risk" data-50dip_risk="<?= $pu_50dip_risk['id_pu_ccat'] ?>">

                <?php for ($lev_3 = 1; $lev_3 < count($list_50dip_risk); $lev_3++) {
                    $checked = $list_50dip_risk[$lev_3]['id'] == $pu_50dip_risk['ccat_id'] ? "checked" : "";
                    ?>
                <div class="radio">
                    <label>
                        <input type="radio" name="pu_5<label>0dip" 
                               value="<?= $list_50dip_risk[$lev_3]['id'] ?>" <?= $checked ?> 
                               onclick="onChange50dipRisk(<?= $pu['id_pu'] ?>,<?= $list_50dip_risk[$lev_3]['id'] ?>)">
                                <?= $list_50dip_risk[$lev_3]['definition'] ?>
                    </label>
                </div>

                <?php } ?> 

            </fieldset>
        </div>
    </div>

    <div class="form-group hidden">
        <label class="col-xs-5 control-label" for="ateco_id">Settore Ateco 2007</label>
        <div class="col-xs-7 controls">
            <select class="form-control" name="ateco_id" 
                    data-pu_ateco_id="<?= $pu_ateco_sector['id_pu_ateco'] ?>" 
                    onchange="onChangeAtecoSector(<?= $pu['id_pu'] ?>)" 
                    style="width: 100%">

                <option <?= empty($pu_ateco_sector['id_pu_ateco']) ? ' selected' : ''?> value="0">Seleziona un settore Ateco</option>
                
                <?php foreach ($list_ateco_sectors as $ateco_sector) {
                    $selected = $ateco_sector['id'] == $pu_ateco_sector['ateco_id'] ? "selected" : "";
                    ?>

                    <option <?= $selected ?> value="<?= $ateco_sector['id'] ?>"><?= $ateco_sector['name'] ?></option>

                <?php } ?> 

            </select>
        </div>
    </div>
    
    <div class="form-group">
        <label class="col-xs-5 control-label" for="ateco_risk_id">LIVELLO DI RISCHIO
            <br>v. settore Ateco</label>
        <div class="col-xs-7 controls">
            <select class="form-control" name="ateco_risk_id" 
                    data-pu_ateco_risk_id="<?= $pu_ateco_risk['id_pu_ateco_risk'] ?>" 
                    onchange="onChangeAtecoRisk(<?= $pu['id_pu'] ?>)" 
                    style="width: 100%">
                
                <option <?= empty($pu_ateco_risk['id_pu_ateco_risk']) ? ' selected' : ''?> value="0">Seleziona un livello di rischio</option>

                <?php foreach ($ateco_risks as $ateco_risk) {
                    $selected = $ateco_risk['id_ateco_risk'] == $pu_ateco_risk['ateco_risk_id'] ? "selected" : "";
                    ?>

                    <option <?= $selected ?> value="<?= $ateco_risk['id_ateco_risk'] ?>"><?= $ateco_risk['short_desc_ateco_risk'] ?></option>

                <?php } ?> 

            </select>
        </div>
    </div>
    
</div>

<script>

    $(function () {

        $('#pu-detail').on('change', 'input[name="short_desc_pu"], textarea[name="long_desc_pu"]', function (e) {
            var short_desc_pu = $('#pu-detail input[name="short_desc_pu"]').val();
            var long_desc_pu = $('#pu-detail textarea[name="long_desc_pu"]').val();
            $.post("manage/department.php", {
                op_type: 'edit_product_unit',
                id_pu: <?= $pu['id_pu'] ?>,
                short_desc_pu: short_desc_pu,
                long_desc_pu: long_desc_pu
            }, function (data) {
                if (data > 0) {
                    $('#departments-list li[data-pu_id="<?= $pu['id_pu'] ?>"]').html('<a href="javascript: void(0)"><i class="icon-chevron-up"></i>' + short_desc_pu + '</a>');
                }
            });
        });

    });

</script>