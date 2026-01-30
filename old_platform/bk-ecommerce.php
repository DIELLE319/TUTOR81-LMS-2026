<?php
/**
 * Created by PhpStorm.
 * User: Davide
 * Date: 01/02/2017
 * Time: 15.29
 */

require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

$course_obj = new iWDCourse();

$filter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_STRING) == 'all' ? 'all' : 'not_expired';

if($_SESSION['user']['role'] == 2) {
    $learning_project_user = $course_obj->getLearningProjectUsersCompany($_SESSION['company']['id']);
} else if ($_SESSION['user']['role'] == 1000) {
    $learning_project_user = $course_obj->getLearningProjectUsers();
} else {
    $finish_within = $filter == 'all' ? FALSE : date("Y-m-d H:i:s");
    $learning_project_user = $course_obj->getLearningProjectUsersByTutor($_SESSION['tutor']['id'], $finish_within);
}

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
                <div class="widget" style="margin: 0;">
                    <div class="widget-extra themed-background-dark">
                        <div class="row">
                            <div class="col-sm-12"><h2 class="text-center"><strong>Qui puoi trovare tutti i codici dei corsi</strong>
                            <a href="/bk-ecommerce.php?scelta=ecommerce&filter=<?= $filter == "all" ? 'not_expired' : 'all' ?>" title="Codici corso">
                                <?= $filter == "all" ? 'Visualizza non scaduti' : 'Visualizza tutti'; ?></a></h2></div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->

                <!-- Datatables Content -->
                <div class="block full" >

                    <div id="bk-ecommerce-table-responsive" class="table-responsive">
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

                        <table id="bk-ecommerce-table" class="corsi-venduti-ec-table table table-bordered table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th> N° Ordine</th>
                                <th style="min-width: 120px;"> Data Vendita</th>
                                <?php if ($_SESSION['user']['role'] != 2) {?><th> Cliente</th><?php }?>
                                <th> Corso</th>
                                <?php if ($_SESSION['user']['role'] != 2) {?><th style="width:25px;"> Stato</th><?php }?>
                                <?php if ($_SESSION['user']['role'] != 2) {?><th> Corsista</th><?php } else { ?><th>Intestatario</th><?php } ?>
                                <th style="width:99px;" data-orderable="false"> Codice Corso</th>
                            </tr>
                            </thead>
                            <tbody style="font-size: 13px;">
                            <?php foreach ($learning_project_user as $index_row => $lpuser) {
                                $isAssignedToUser = $lpuser["user_id"] != "0";
                                $isAssigned = $lpuser["assigned"] == true;
                                $isEcommerce = ($_SESSION['tutor']['id'] == $lpuser['id_company'] );
                                ?>
                                <tr data-license_id="<?= $lpuser['id'] ?>">
                                    <td><span><?=$lpuser["tutor_purchase_id"]?></span></td>
                                    <td data-order="<?=$lpuser["creation_date"]?>"><?=date('d-m-Y H:i:s',strtotime($lpuser["creation_date"]))?></td>
                                    <?php if ($_SESSION['user']['role'] != 2) {?>
                                        <?php if ($isEcommerce) {?><td><span class="label label-info" style="margin:0;font-size: 13px;"> ecommerce</span></td>
                                        <?php } else {?>
                                        <td><a href="mailto:<?=$lpuser["email"]?>" style="color: #000;"> <?=$lpuser["business_name"]?></a></td>
                                            <?php } ?>
                                    <?php }?>
                                        <td class="text-left" style="color: #00a7d0;"> <?= T81LearningProject::formatTitle($lpuser["title"])?></td>


                                    <?php if (!$isAssignedToUser) {?>
                                        <td>
                                            
                                            <?php if ($isAssigned) {
                                                if ($isAssignedToUser) {?>
                                            
                                                    <span class="label label-default sold" style="font-size: 13px;"> Intestata</span>
                                                    
                                                <?php } else {?>
                                                    
                                                    <span class="label label-success sold" style="font-size: 13px;"> Sbloccata</span>
                                                    
                                                <?php }
                                            } else {?>
                                                    
                                                <button type="button" class="btn btn-warning btn-xs blockedKey" style=""> Sblocca</button>
                                                
                                            <?php }?>
                                                
                                        </td>
                                        <td>&nbsp;</td>
                                        
                                    <?php } else {
                                        if ($_SESSION['user']['role'] != 2) {?>
                                        
                                            <td>
                                            <?php if ($isAssigned) {
                                                if ($isAssignedToUser) {?>
                                                
                                                    <span class="label label-default sold" style="font-size: 13px;"> Intestata</span>
                                                    
                                                <?php } else {?>
                                                    
                                                    <span class="label label-success sold" style="font-size: 13px;"> Sbloccata</span>
                                                    
                                                <?php }
                                            } else {?>
                                                    
                                                <button type="button" class="btn btn-warning btn-xs blockedKey" style=""> Sblocca</button>
                                                
                                            <?php }?>

                                            </td>
                                            
                                        <?php }?>
                                            
                                        <td style="text-align: left;"> 
                                            <?php if ($isAssignedToUser) { ?>
                                            <a style="color: #000;" href="mailto:<?=$lpuser["email_accountholder"]?>"><?=$lpuser["name"]?> <?=$lpuser["surname"]?></a> 
                                            <?php } else {?>
                                                <?= $lpuser["email"] ?>
                                            <?php } ?></td>
                                        
                                    <?php }?>

                                    <td style="text-align: center;">
                                        <?php if ($lpuser["assigned"] == true) { 
                                            echo $lpuser["learning_project_pwd"]; ?>
                                        
                                        <br>
                                        <?php if ($_SESSION['user']['role'] == 1000) {
                                            ?>
                                        <button type="button" class="btn btn-danger btn-xs remove-course" data-toggle="tooltip" title="Rimuovi">
                                            <span class="glyphicon glyphicon-remove"></span>
                                        </button>
                                        
                                        <?php } ?>
                                        
                                        <button type="button" class="btn btn-info btn-xs blockedKey" data-toggle="tooltip" title="Reinvia email">
                                            <span class="glyphicon glyphicon-send"></span>
                                        </button>
                                        
                                            <?php if ($_SESSION['user']['role'] == 1000 && $isAssignedToUser) { ?>
                                        
                                        <button type="button" class="btn btn-default btn-xs play-course" data-toggle="tooltip" title="Avvia corso">
                                            <span class="glyphicon glyphicon-play"></span>
                                        </button>
                                            <?php }
                                            
                                            } ?>
                                        
                                        <input type="hidden" value="<?=$lpuser["id"]?>" class="lpuid">
                                    </td>
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
<div id="avviaCorsoModal" class="modal fade" role="dialog" aria-labelledby="avviaCorsoModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        </div>
    </div>
</div>

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-double-up"></i></a>

<!-- Load and execute javascript code used only in this page -->
<script type="application/javascript" src="js/pages/bk-index.js"></script>

<script src="js/vendor/datatables.min.js"></script>
<!--<script src="js/vendor/plugins.js"></script>-->
<!-- Load and execute javascript code used only in this page -->
<script src="js/pages/tablesDatatables.js"></script>
<script>
    $(function(){ 
        TablesDatatables.init(); 
        
<?php if ($_SESSION['user']['role'] == 1000){?>

        /* ************** AVVIA IL CORSO ****************** */
        $('#bk-ecommerce-table .play-course').click(function () {
            var license_id = $(this).parents('tr').data('license_id');
            $('#avviaCorsoModal .modal-content')
                    .empty()
                    .load('modals/avviacorso.php?learning_project_user_id=' + license_id)
                    .parents('#avviaCorsoModal')
                    .modal();
        });

<?php } 
if ($_SESSION['user']['role'] == 1000){?>      
    
        /* ************** ELIMINA LICENZA ****************** */
        $('#bk-ecommerce-table .remove-course').click(function (e) {
            var licence_id = $(this).parents('tr').data('license_id');
            var row = bkEcommerceTable.row($(this).parents('tr'));
            var res = removeLicenceAndPurchase(licence_id);
            if ( res ) {
                row.remove().draw();
            }
        });

<?php } ?>
    });
</script>


</body>
</html>









