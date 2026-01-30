<?php
 // 2 minutes execution time
@set_time_limit(2 * 60);
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] == 0 || $_SESSION['user']['role'] == 2 || $_SESSION['user']['role'] == 8 || $_SESSION['user']['role'] == 16) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_learning_event.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

$learning_event_obj = new Tutor81LearningEvt();
if ($_SESSION['user']['role'] == 1) {
    $update_needs = $learning_event_obj->getUpdateNeedsByTutorCompany($_SESSION['tutor']['id']);
} elseif ($_SESSION['user']['role'] == 1000) {                            
    $update_needs = $learning_event_obj->getUpdateNeedsByTutorCompany();
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
                <div class="widget" style="margin:  0;">
                    <div class="widget-extra themed-background-dark">
                        <div class="row">
                            <div class="col-sm-12">
                                <h2 class="text-center">
                                    <strong>Necessità Formative</strong>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->

                <!-- Datatables Content -->
                <div class="block full" >

                    <div class="table-responsive">
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

                        <table id="<?= ($_SESSION['user']['role'] == 1000) ? 'all_' : ''?>update_needs_table"
                            class="bk-update-needs-table table table-bordered table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th class="text-center">Data corso</th>
                                    <th class="text-center">Corso</th>
                                    <th class="text-center">Nome Corsista</th>
                                    <th class="text-center">Azienda</th>
                                    <?php if ($_SESSION['user']['role'] == 1000) { ?>
                                    <th class="text-center">Ente Formativo</th>
                                    <?php } ?>
                                    <th class="text-center">Costo</th>
                                    <th class="text-center noExport"><?= $selected == "update_done_false" ? 'Da ripetere' : 'Ripetuto</br>N.C.' ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($update_needs as $single_needs){ 
                                $end_date_time = date("d/m/Y", strtotime($single_needs['end_date_time']));
                                if ($selected == 'update_done_true') {
                                    if ($single_needs['update_done'] == '0') {
                                        continue;
                                    }
                                } else {
                                    if ($single_needs['update_done'] == '1') {
                                        continue;
                                    }
                                }
                                ?>
                                
                                <tr data-event_id="<?= $single_needs['learning_event_id'] ?>">
                                    <td data-order="<?= $single_needs['end_date_time'] ?>"><?= $end_date_time ?></td>
                                    <td><?= T81LearningProject::formatTitle($single_needs['title']) ?></td>
                                    <td><?= $single_needs['user_name'] ?></td>
                                    <td><?= $single_needs['business_name'] ?></td>
                                    <?php if ($_SESSION['user']['role'] == 1000) { ?>
                                    <td><?= $single_needs['tutor_business_name'] ?></th>
                                    <?php } ?>
                                    <td>&euro; <?=number_format($single_needs['price'], 2, ',', ' ')?></td>
                                    <td class="update_done"
                                        data-update_done="<?= $single_needs['update_done']?>">
                                        <a href="javascript: void(0);">
                                            <span class="glyphicon glyphicon-eye-<?= $single_needs['update_done'] == 0 ? 'open' : 'close' ?>"></span>
                                        </a>
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

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-double-up"></i></a>
<script src="js/vendor/datatables.min.js"></script>
<!-- Load and execute javascript code used only in this page -->
<script src="js/pages/tablesDatatables.js"></script>
<script>$(function(){ TablesDatatables.init(); });</script>
<!-- Load and execute javascript code used only in this page -->
<script type="application/javascript" src="js/pages/bk-index.js"></script>
<script>
$(function(){
    
    $('#<?= ($_SESSION['user']['role'] == 1000) ? 'all_' : ''?>update_needs_table').on('click', 'td > a', function(){
        var selected = $(this);
        var update_done = selected.parents('td').data('update_done') == '1' ? '0' : '1';
        var glyphicon = update_done == 0 ? 'open' : 'close';
        var message = "Vuoi impostare questo evento come ";
        if (update_done == '1') {
            message = message + "già aggiornato oppure non interessato all'aggiornamento?";
        } else if (update_done == '0') {
            message = message + "da aggiornare?";
        }
        var learning_event_id = selected.parents('tr').data('event_id');
        bootbox.confirm(message,
            function(result){
                if (result) {
                    $.post('manage/event.php',
                    {
                        op_type: 'change update_done',
                        learning_event_id: learning_event_id,
                        update_done: update_done
                    }, function(data){
                        if (data > 0) {
                            location.reload();
//                            selected.parent()
//                                .attr('data-update_done', update_done)
//                                .html('<a href="javascript: void(0)">' +
//                                        '<span class="glyphicon glyphicon-eye-'+ glyphicon +'"></span>' + 
//                                    '</a>');
                        }
                    })
                }
            });
    });
    
});

</script>

</body>
</html>
