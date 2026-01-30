<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 30-ott-2015
 * File: pages/departments.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_departments.php';

$dep_obj = new Departments();

$pu_id = filter_input(INPUT_GET, 'pu_id', FILTER_SANITIZE_NUMBER_INT);
$dep_id = filter_input(INPUT_GET, 'dep_id', FILTER_SANITIZE_NUMBER_INT);

$product_units = $dep_obj->getProductUnits($_SESSION['company']['id']);
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-4">
            <div class="panel-group" id="departments-menu" role="tablist">
                <div class="text-right">
                    <a href="javascript: void(0)" class="add-product-unit" title="crea una unità produttiva"><span class="glyphicon glyphicon-plus"></span> unità produttiva</a>
                </div>
    <?php if ($product_units) { 
        foreach ($product_units as $pu) {
            $departments = $dep_obj->getDepartmentsByProductUnit($pu['id_pu']);
            ?>

                <div class="panel panel-default pu <?= !empty($pu_id) && $pu_id == $pu['id_pu'] ? ' active' : '' ?>" data-pu_id="<?= $pu['id_pu'] ?>">
                    <div class="panel-heading" role="tab" id="headingPU<?= $pu['id_pu'] ?>">
                        <h4 class="panel-title">
                            <a class="pu_id"
                               role="button" data-toggle="collapse" 
                               data-parent="#departments-menu" 
                               href="#collapsePU<?= $pu['id_pu'] ?>" 
                               aria-expanded="false" 
                               aria-controls="collapsePU<?= $pu['id_pu'] ?>">
                                <?= $pu['short_desc_pu'] ?>
                            </a>
                            <span class="pull-right glyphicon"></span>
                        </h4>
                    </div>
                    <div id="collapsePU<?= $pu['id_pu'] ?>" class="panel-collapse collapse <?= !empty($pu_id) && $pu_id == $pu['id_pu'] ? ' in' : '' ?>" 
                         role="tabpanel" aria-labelledby="headingPU<?= $pu['id_pu'] ?>" <?= !empty($pu_id) && $pu_id == $pu['id_pu'] ? ' aria-expanded="true"' : '' ?>>
                        <div class="panel-body">

                            <ul class="nav nav-pills nav-stacked">
                                <li class="text-right">
                                    <a href="javascript: void(0)" class="add-department"><span class="glyphicon glyphicon-plus"></span> reparto</a>
                                </li>

            <?php if ($departments)
                foreach ($departments as $dep) { ?>

                                <li class="dep <?= !empty($dep_id) && $dep_id == $dep['id_dep'] ? ' active' : '' ?>" data-dep_id="<?= $dep['id_dep'] ?>" data-dep_type_id="<?= $dep['dep_type_id'] ?>">
                                    <a href="javascript: void(0)"><?= $dep['short_desc_dep_type'] ?></a>
                                </li>

            <?php } ?>
                            </ul>
                        </div>
                    </div>
                </div>

        <?php }
            
    } ?>
                
            </div><!-- /#departments-menu -->
            
        </div><!--/#.col-sm-4-->
        <div id="department-detail" class="col-sm-8">
            
            <?php 
            if (empty($pu_id)){
            ?>
            
            <div id="start">
                <p>Seleziona un'unità produttiva o un reparto per visualizzarne il dettaglio</p>
                <img src="img/mansione-example.png" alt="mansione">
            </div>
            
            <?php } ?>
            
            <div id="add-product-unit" style="display: none;">
                <h3 class="text-center">Nuova unità produttiva</h3>
                <form class="form-horizontal" id="new-product-unit">
                    <input type="hidden" name="pu_id" value="">

                    <div class="form-group">
                        <label class="col-xs-3 control-label">Nome</label>
                        <div class="col-xs-9 controls">
                            <input class="form-control" type="text" name="short_desc">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-xs-3 control-label">Descrizione</label>
                        <div class="col-xs-9 controls">
                            <input class="form-control" type="text" name="long_desc">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="controls col-xs-9 col-xs-offset-3">
                            <button type="submit" class="btn btn-primary">Salva</button>
                        </div>
                    </div>

                </form>
            </div>
            
            <div id="add-department" style="display: none;">
                <h3 class="text-center">Nuovo reparto</h3>
                <form class="form-horizontal" id="new-department">
                    <input type="hidden" name="pu_id" value="">
                    
                    <div class="form-group">
                        <label class="col-xs-3 control-label">Nome reparto</label>
                        <div class="col-xs-9 controls">
                            <input class="form-control" type="text" name="short_desc_dep_type" list="dep_type_datalist" maxlength="30"> <small>(max 30 caratt.)</small>
                            <datalist id="dep_type_datalist">

                            </datalist>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-xs-3 control-label">Descrizione</label>
                        <div class="col-xs-9 controls">
                            <textarea class="form-control" name="long_desc_dep_type" maxlength="256"></textarea> <small>(max 256 caratt.)</small>
                            <p style="display:none;"></p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="controls col-xs-9 col-xs-offset-3">
                            <button type="submit" class="btn btn-primary">Salva</button>
                        </div>
                    </div>

                </form>

            </div>
            
            <div id="detail" style="<?= !empty($pu_id) ? '' : 'display: none;';?>">
            <?php 
            if (!empty($pu_id)){
                if (!empty($dep_id)){
                    require_once BASE_ROOT_PATH . "pages/sections/department-detail.php";
                } else {
                    require_once BASE_ROOT_PATH . "pages/sections/unity-detail.php";
                }
            }
            ?>
            </div>
            
        </div>
    </div>
</div><!--/.container-->

<script>
    
    $('body').on('click', '#departments-menu a.add-product-unit', function (e) {
        $('#new-product-unit').trigger('reset');
        $("#add-product-unit").show().siblings().hide();
    });

    $('body').on('click', '#departments-menu a.pu_id', function (e) {
        var unity = $(this).parents('[data-pu_id]');
        unity.addClass('active').siblings().removeClass('active').find('li').removeClass('active');
        unity.find('li').removeClass('active');
        var pu_id = unity.data('pu_id');
        $("#detail")
                .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                .load("pages/sections/unity-detail.php?pu_id=" + pu_id)
                .show().siblings().hide();
    });
    
    $('body').on('click', '#departments-menu li a', function (e) {
        $(this).parent().addClass('active').siblings().removeClass('active').parents('[data-pu_id]').removeClass('active');
        if ($(this).hasClass('add-department')) {
            var pu_id = $(this).parents('[data-pu_id]').data('pu_id');
            $('#new-department').trigger('reset').find('input[name="pu_id"]').val(pu_id);
            $("#add-department").show().siblings().hide();
        } else {
            var dep_id = $(this).parent().data('dep_id');
            $("#detail")
                .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                .load("pages/sections/department-detail.php?dep_id=" + dep_id)
                .show().siblings().hide();
        }
    });



    /* ************** CREA NUOVA UNITA' PRODUTTIVA ****************** */
    $("#new-product-unit").submit(function (e) {
        e.preventDefault();
        var modal_departments = $(this).parents('.modal');
        $.isLoading({text: "Attendere ..."});
        $.post("manage/department.php", {
            op_type: "add_product_unit",
            short_desc: $(this).find('input[name="short_desc"]').val(),
            long_desc: $(this).find('input[name="long_desc"]').val(),
            company_id: <?= $_SESSION['company']['id'] ?>
        }, function (data) {
            $.isLoading("hide");
            if (data > 0) {
                // add pu in menu
                //document.location.reload();
                modal_departments.find('.modal-body')
                .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                .load("pages/departments.php?pu_id=" + data);
            } else {
                bootbox.alert("Errore nella creazione dell'unità produttiva: " + data);
            }
        });
    });


    /* ************** CARICA I TIPI DI REPARTO NELLA DATALIST ****************** */
    $('.add-department').click(function () {
        $.post("manage/department.php",
                {
                    op_type: "get_pu_departments",
                    pu_id: "all",
                    company_id: <?= $_SESSION['company']['id'] ?>
                }, function (departments) {
            var options = '';
            departments = $.parseJSON(departments);
            if (departments == 0) {
                $('#new-department datalist').empty();
            } else {
                $.each(departments, function (i, item) {
                    options += '<option  data-id_dep_type="' + item.id_dep_type +
                            '" value="' + item.short_desc_dep_type +
                            '" data-long_desc_dep_type="' + item.long_desc_dep_type +
                            '">' + item.short_desc_dep_type +
                            '</option>';
                    if (i == Object.keys(departments).length - 1)
                        $('#new-department datalist').html(options);
                });
            }
        });
    });

    /* ************** CREA NUOVO REPARTO ****************** */
    $("#new-department").submit(function(e){
        e.preventDefault();
        var modal_departments = $(this).parents('.modal');
        var pu_id = modal_departments.find('.pu.active').data('pu_id');
        $.isLoading({text: "Attendere ..."});
        var selected = $('#dep_type_datalist option').filter(function () {
            return $(this).val() === $('#addDepartmentModal input[name="short_desc_dep_type"]').val();
        });
        if (selected.length == 0) {
            $.post("manage/department.php",
                    {
                        op_type: "add_department_type",
                        short_desc: $('#new-department input[name="short_desc_dep_type"]').val(),
                        long_desc: $('#new-department textarea[name="long_desc_dep_type"]').val(),
                        company_id: <?= $_SESSION['company']['id'] ?>
                    }, function (data) {
                $.isLoading("hide");
                if (data > 0) {
                    $.post("manage/department.php",
                        {
                            op_type: "add_department",
                            dep_type_id: data,
                            pu_id: $('#new-department input[name="pu_id"]').val()
                        }, function (data) {
                            // add department in menu
                            //document.location.reload();
                            modal_departments.find('.modal-body')
                            .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                            .load("pages/departments.php?pu_id=" + pu_id + "&dep_id=" + data);
                    });
                } else {
                    // add datalist option
                    //document.location.reload();
                    modal_departments.find('.modal-body')
                    .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                    .load("pages/departments.php?pu_id=" + pu_id + "&dep_id=" + data);
                }
            });
        } else {
            $.post("manage/department.php",
                {
                    op_type: "add_department",
                    dep_type_id: selected.data("id_dep_type"),
                    pu_id: $('#new-department input[name="pu_id"]').val()
                }, function (data) {
                    // add department in menu
                    document.location.reload();
            });
        }
    });

    /* ************** AGGIORNA DESCRIZIONE REPARTO IN CREAZIONE NUOVO REPARTO ****************** */
    /*
    $('#new-department').on('change', 'input[name="short_desc_dep_type"]', function (e) {
        var name = $(this).val();
        var selected = $('#dep_type_datalist option').filter(function () {
            return $(this).text() === name;
        });
        if (selected.length == 1) {
            $('#detail textarea').text(selected.data('long_desc_dep_type')).prop('disabled', true);
        } else
            $('#detail textarea').prop('disabled', false).text('');
    });
    */

</script>