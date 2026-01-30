<?php
/**
 * Created by PhpStorm.
 * User: Davide
 * Date: 01/02/2017
 * Time: 15.29
 */
 // 2 minutes execution time
@set_time_limit(2 * 60);
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] == 0 || $_SESSION['user']['role'] == 8 || $_SESSION['user']['role'] == 16) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';

$course_obj = new iWDCourse();
$report_obj = new Report();

require_once 'ecommerce/bk/header.php'; ?>
<body style="background-color: white;">

<!-- Page Wrapper -->
<!-- In the PHP version you can set the following options from inc/config file -->
<!--
    Available classes:

    'page-loading'      enables page preloader
-->
<div id="page-wrapper">

    <div id="page-container" class="sidebar-partial sidebar-visible-lg sidebar-no-animations">

        <!-- Main Sidebar -->
        <?php require_once "ecommerce/bk/menu-left.php" ?>
        <!-- END Main Sidebar -->

        <?php require "ecommerce/bk/modal-user-profile-settings.php" ?>

        <!-- Main Container -->
        <div id="main-container">

            <header class="navbar navbar-default" >
                <?php require "ecommerce/bk/search-form-header.php" ?>
            </header>

            <!-- Page content -->
            <div id="page-content" style="padding-top: 20px; min-height: 821px;" >

                <!-- Timeline Widget -->
                <div class="widget" style="margin:  0;">
                    <div class="widget-extra themed-background-dark">
                        <div class="row">
                            <div class="col-sm-12">
                                <h2 class="text-center">
                                    <strong>Corsi <?= $_SESSION['user']['role'] == 2 || $_SESSION['tutor']['is_tutor_with_single_company'] ? 'Acquistati' : 'Venduti' ?>
                                    </strong>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->

                <!-- Datatables Content -->
                <div class="block full" >

                    <div id="bk-sold-courses-table-responsive" class="table-responsive">
                        <!--
                        Available Table Classes:
                            'table'             - basic table
                            'table-bordered'    - table with full borders
                            'table-borderless'  - table with no borders
                            'table-striped'     - striped table
                            'table-condensed'   - table with smaller top and bottom cell padding
                            'table-hover'       - rows highlighted on mouse hover
                            'table-vcenter'     - middle align content vertically
                        -->

                        <table <?= $_SESSION['user']['role'] != 2 ? 'id="bk-sold-courses-table"' : 'id="bk-purchase-courses-table"' ?> 
                            class="corsi-venduti-ec-table table table-bordered table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th>N° Ordine</th>
                                <th style="min-width: 120px;">Utente</th>
                                <?php if ($_SESSION['user']['role'] != 2) { ?>
                                <th>Cliente</th>
                                <?php } ?>
                                <th style="min-width: 120px;"> Data <?= $_SESSION['user']['role'] == 2 || $_SESSION['tutor']['is_tutor_with_single_company'] ? 'Acquisto' : 'Vendita' ?></th>
                                <th style="min-width: 150px;"> Nome Corso</th>
                                <th>Quantit&agrave;</th>
                                <?php if ($_SESSION['user']['role'] != 2) { ?>
                                <th style="line-height: 15px">Listino <br><span style="font-size:12px;">prezzo di <?= $_SESSION['tutor']['is_tutor_with_single_company'] ? 'acquisto' : 'vendita consigliato' ?></th>
                                <th>Tuo Costo</th>
                                <th data-orderable="false">Fatturato</th>
                                <?php } ?>
                            </tr>
                            </thead>
                            <tbody style="font-size: 13px;">

                            <?php

                            if ($_SESSION['user']['role'] == 2) {
                                $company_id_purchases = $_SESSION['company']['id'];
                                $purchases = $course_obj->getPurchasesCompany($company_id_purchases);
                            }
                            elseif ($_SESSION['user']['role'] == 1) {
                                $company_id_purchases = $_SESSION['tutor']['id'];
                                $purchases = $course_obj->getPurchasesTutor($company_id_purchases);
                            } elseif ($_SESSION['user']['role'] == 1000) {
                                $purchases = $course_obj->getAllPurchases();
                            }


                            foreach ($purchases as $index_row => $purchase) {

                                $isEcommerce = $purchase["tutor_id"] == "0";//$purchase["user_company_ref"] == "0" && $purchase["tutor_id"] == "0";
                                $isCompanyPurchease = $purchase["user_company_ref"] != "0";
                                
                                $invoice_date = isset($purchase['invoice_date']) && $purchase['invoice_date'] != '0000-00-00' ? $report_obj->formatDate($purchase['invoice_date'], 'd/m/Y') : '';
                                //$purchase_code = $purchase['code'] != '' ? '<br><span style="smaller">(' . $purchase['code'] . ')</span>' : ''; 
                                ?>
                                <tr data-purchase_id="<?=$purchase["id"]?>">
                                    <td> <?=$purchase["id"]?></td>
                                    <td>

                                    <?php if ($isEcommerce) {?>
                                        
                                        <span class="label label-info" style="margin:0; font-size: 13px;">ecommerce</span>
                                        <?=$purchase['code'] != '' ? '<br><span class="small">(' . $purchase['code'] . ')</span>' : ''; ?>
                                        
                                    <?php /*} elseif ($isCompanyPurchease) {?>
                                        
                                        <?=$purchase["name"]?> <?=$purchase["surname"] */?>
                                        
                                    <?php } else {?>
                                        
                                        <?=$purchase["name_tutor"]?> <?=$purchase["surname_tutor"]?>
                                        
                                    <?php }?>
                                        
                                    </td>
                                    
                                    <?php if ($_SESSION['user']['role'] != 2) {
                                        if ($_SESSION['user']['role'] == 1000) {?>
                                    
                                    <td><?= strtoupper($purchase["tutor_company"])?><br />
                                        <span style="font-size: smaller">(<?=$purchase["business_name"]?>)</span></td>
                                        
                                        <?php } else {?>
                                        
                                    <td><?=$purchase["business_name"]?></td>
                                            
                                        <?php }
                                    } ?>
                                        
                                    <td><?=date('d-m-Y H:i:s',strtotime($purchase["creation_date"]))?></td>
                                    <td class="text-left" style="color: #00a7d0;"> <?= T81LearningProject::formatTitle($purchase["title"])?></td>
                                    <td> <?=$purchase["qta"]?></td>
                                    
                                    <?php if ($_SESSION['user']['role'] != 2) { ?>
                                    
                                    <td style="text-align: right;">&euro; <?=number_format($purchase["course_price"], 2, ',', ' ')?></td>
                                    <td style="text-align: right;">&euro; <?=number_format($purchase["price"]*$purchase["qta"], 2, ',', ' ')?>
                                    </td>
                                    <td class="<?= $purchase['invoiced'] > 0 ? 'invoiced' : 'uninvoiced' ?>">
                                        <?= $invoice_date ?>
                                        <?php if ($_SESSION['user']['role'] == 1000) { ?>
                                        <a href="javascript: void(0)">
                                            <span class="glyphicon glyphicon-<?= $purchase['invoiced'] > 0 ? 'check' : 'unchecked' ?>"></span>
                                        </a>
                                        <?php } ?>
                                    </td>
                                    
                                    <?php } ?>
                                    
                                </tr>

                            <?php } ?>

                            </tbody>
                        </table>
                    </div>
                    <!-- END Table Styles Content -->

                </div>
                <!-- END Datatables Content -->

            </div>
            <!-- END Page Content -->

        </div>
        <!-- END Main Container -->
    </div>
    <!-- END Page Container -->
</div>
<!-- END Page Wrapper -->

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-double-up"></i></a>
<script src="js/vendor/datatables.min.js"></script>
<!-- <script src="js/vendor/plugins.js"></script> -->
<!-- Load and execute javascript code used only in this page -->
<script src="js/pages/tablesDatatables.js"></script> -->
<script>$(function(){ TablesDatatables.init(); });</script>
<!-- Load and execute javascript code used only in this page -->
<script type="application/javascript" src="js/pages/bk-index.js"></script>
<script>
$(function(){
    
<?php if ($_SESSION['user']['role'] == 1000) { ?>
    
    $('#bk-sold-courses-table').on('click', 'td.invoiced > a', function(){
        var selected = $(this);
        var tutor_purchase_id = selected.parents('tr').data('purchase_id');
        bootbox.confirm("Vuoi impostare come non fatturato l'ordine " + tutor_purchase_id + "? (la data fattura verrà cancellata!)",
            function(result){
                if (result) {
                    $.post('manage/purchase.php',
                    {
                        op_type: 'uninvoice_purchase',
                        tutor_purchase_id: tutor_purchase_id
                    }, function(data){
                        if (data > 0) {
                            selected.parent()
                                .removeClass('invoiced')
                                .addClass('uninvoiced')
                                .empty()
                                .html('<a href="javascript: void(0)">' +
                                        '<span class="glyphicon glyphicon-unchecked"></span>' + 
                                    '</a>');
                        }
                    })
                }
            });
    });
    
    $('#bk-sold-courses-table').on('click', 'td.uninvoiced > a', function(){
        var selected = $(this);
        var tutor_purchase_id = selected.parents('tr').data('purchase_id');
        var today = new Date();
        bootbox.prompt({
            size: 'small',
            title: "Inserisci la data della fattura per l'ordine " + tutor_purchase_id + ".",
            value: today.getFullYear() + '-' + (today.getMonth()+1) + '-' +today.getDate(), //today.getDate() + '/' + today.getMonth() + '/' +today.getFullYear(),
            inputType: "date",
            callback: function(result){
                if (result != "") {
                    var invoice_date = new Date(result);
                    $.post('manage/purchase.php',
                    {
                        op_type: 'update_invoice_date',
                        tutor_purchase_id: tutor_purchase_id,
                        invoice_date: invoice_date.toISOString()
                    }, function(data){
                        if (data > 0) {
                            selected.parent()
                                .removeClass('uninvoiced')
                                .addClass('invoiced')
                                .empty()
                                .html(invoice_date.getDate() + '/' +
                                    (invoice_date.getMonth()+1) + '/' +
                                    invoice_date.getFullYear() + 
                                    ' <a href="javascript: void(0)">' +
                                        '<span class="glyphicon glyphicon-check"></span>' + 
                                    '</a>');
                        }
                    })
                } else {
                    bootbox.alert("La data inserita non è valida.");
                }
            }
        });
    });
    
<?php } ?>

});

</script>





</body>
</html>
