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
require_once BASE_LIBRARY_PATH . 'class_company.php';

$comp_obj = new T81Company();

$learn_id = filter_input(INPUT_POST, 'learn_id', FILTER_SANITIZE_NUMBER_INT);
$learn_title = filter_input(INPUT_POST, 'learn_title', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

$cost_centre = $comp_obj->getCostCentre($_SESSION['tutor']['id']);

?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="myModalLabel">Acquista licenze</h3>
</div>
<div class="modal-body">
    <div class="text-center">
        <h4 class="course-title"><?= $learn_title ?></h4>
        <form id="purchase-course" class="form-inline">
            <div class="form-group">
                <p class="form-control-static">acquista </p>
            </div>
            <div class="form-group">
                <input type="number" class="form-control" name="num_licenses" min="1" value="1" style="width: 70px;">
            </div>
            <div class="form-group">
                <p class="form-control-static"> licenza</p>
            </div>
            
        <?php if ($cost_centre) { ?>
            
            <div class="form-group">
                <p class="form-control-static"> per il centro di costo </p>
            </div>
            <div class="form-group">
                <select class="form-control" id="cost-centre">
                
                    <?php foreach ($cost_centre as $single_cost_centre) { ?>

                        <option value="<?= $single_cost_centre['id_cost_centre'] ?>"><?= $single_cost_centre['cost_centre'] ?></option>

                    <?php } ?>
                
                </select>
            </div>
            
        <?php } ?>
            
            <input type="hidden" name="learn_id" value="<?= $learn_id ?>">
        </form>
    </div>
</div>
<div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Chiudi</button>
        <button class="btn btn-primary" id="save-purchase-course">Salva</button>
</div>
<script>
    /* ****** PURCHASE COURSE ****** */
    $('#save-purchase-course').click(function(e){
        $(this).prop('disabled', true);
        var qta = $('#purchase-course input[name="num_licenses"]').val();
        var arr_id = new Array();
        arr_id.push($('#purchase-course input[name="learn_id"]').val());
        var arr_qta = new Array();
        arr_qta.push(qta);
        var arr_ref = new Array();
        arr_ref.push(<?=$_SESSION['company']['owner_user_id']?>);
        var arr_tutor = new Array();
        arr_tutor.push(<?=$_SESSION['user']['id']?>);
        var arr_ext_po_number = new Array();
        arr_ext_po_number.push($('#ext-po-number').val());
        var arr_cost_centre_id = new Array();
        arr_cost_centre_id.push($('#cost-centre').val());
        $.post("manage/license.php",
            {
                op_type: "new_purchase",
                comp_id: <?=$_SESSION['company']['id']?>,
                arr_id: arr_id,
                arr_qta : arr_qta,
                arr_ref : arr_ref,
                arr_tutor: arr_tutor,
                arr_ext_po_number: arr_ext_po_number,
                arr_cost_centre_id: arr_cost_centre_id
            },
            function(data){
                if (data > 0) {
                    alert("Hai acquistato n° " + qta + " licenze per il corso <?= $learn_title ?>");
                    location.reload();
                } else {
                    alert("Errore nell'acquisto delle licenze");
                    $('#save-purchase-course').prop('disabled', false);
                }
            }
        );
    });
    
</script>