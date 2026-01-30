<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 28-set-2015
 * File: pages/sections/purchase-pack.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_pack.php';
$pack_obj = new T81Pack();

$tutor_id = filter_input(INPUT_GET, 'tutor_id', FILTER_SANITIZE_NUMBER_INT) ? : $_SESSION['tutor']['id'];
$pack_types = $pack_obj->getPackTypes();
?>

<div id="purchase-pack" class="container-fluid">
    <form class="form-horizontal">
        
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th>&nbsp</th>
                    <th>&nbsp;</th>
                    <th>Nome</th>
                    <th>Descrizione</th>
                    <th>Contenuto</th>
                    <th>Quantità</th>
                    <th>Prezzo</th>
                </tr>
            </thead>
            <tbody>
                
            <?php foreach ($pack_types as $pack) { ?>
        
                <tr data-id_pack_type="<?= $pack['id_pack_type'] ?>">
                    <td>
                        <input type="number" class="form-control" name="qty_purchased" min="1" value="1" style="width: 50px; visibility: hidden;">
                    </td>
                    <td>
                        <input type="radio" name="pack-type">
                    </td>
                    <td><strong><?= $pack['short_desc_pack_type'] ?></strong></td>
                    <td><?= $pack['long_desc_pack_type'] ?></td>
                    <td><?= $pack['content_type'] ?></td>
                    <td><?= $pack['content_amount'] ?></td>
                    <td><?= $pack['pack_type_price'] ?></td>
                </tr>
                    
            <?php } ?>
                
            </tbody>
        </table>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Salva</button>
        </div>
    </form>
</div>

<script>
    $('#purchase-pack form input[name="pack-type"]').change(function(){
        var selected_pack = $('#purchase-pack form input[name="pack-type"]:checked').parents('tr');
        selected_pack.find('input[name="qty_purchased"]').css('visibility', 'visible');
        selected_pack.siblings()
        selected_pack.siblings().find('input[name="qty_purchased"]').css('visibility', 'hidden');
    });
    
    $('#purchase-pack form').submit(function(e){
        e.preventDefault();
        var selected_pack = $('#purchase-pack form input[name="pack-type"]:checked').parents('tr');
        $.post('manage/pack.php', {
            op_type: 'purchase_pack',
            tutor_id: <?= $tutor_id ?>,
            pack_type_id: selected_pack.data("id_pack_type"),
            qty_purchased: selected_pack.find('input[name="qty_purchased"]').val()
        }, function(res){
            if (res > 0) {
                var the_modal = $('#purchase-pack form').parents('.modal');
                if (the_modal.length > 0) the_modal.modal('hide');
            }
        });
    });
</script>