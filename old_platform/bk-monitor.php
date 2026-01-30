<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';

$report_obj = new Report();

if (!in_array( $_SESSION['user']['role'], array(1000,1,32))) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

$online_users = $_SESSION['user']['role'] == 1000 ? $report_obj->getOnlineUsersByTutorCompany(): $report_obj->getOnlineUsersByTutorCompany($_SESSION['tutor']['id']);

?>
<?php require_once 'ecommerce/bk/header.php'; ?>
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
            <div id="page-content" style="padding-top: 0;" >
                
                <!-- Timeline Widget -->
                <div class="widget" style="margin:  0;">
                    <div class="widget-extra themed-background-dark">
                        <div class="row">
                            <div class="col-sm-12">
                                <h2 class="text-center">
                                    <strong>
                                        Utenti online
                                    </strong>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->

                <!-- All Orders Block -->
                <div class="block full">
                    
                    <!-- ---- SCHEDA MONITOR ---- -->

                    <div id="monitor-report" class="text-center">
                        <h3>MONITOR CORSI</h3>
                        <div class="report-container">

                        <?php if (!$online_users){ ?>

                            <h4>Nessun utente collegato.</h4>

                        <?php } else { ?>
                            
                            <table class="table table-sorter">
                                <thead>
                                    <tr>
                                        <th>Cognome Nome</th>
                                        <?= $_SESSION['tutor']['is_tutor_with_single_company'] ? '': '<th>Azienda</th>'?>
                                        <th>Progetto formativo</th>
                                        <th>Oggetto formativo</th>
                                    </tr>
                                </thead>
                                <tbody>

                                <?php if ($online_users) foreach($online_users as $user){?>

                                    <tr>
                                        <td><?=ucwords("{$user['surname']} {$user['name']}")?></td>
                                        <?php if (!$_SESSION['tutor']['is_tutor_with_single_company']) { ?>
                                        <td><?= $user['business_name'] ?></td>
                                        <?php } ?>
                                        <td><?=strtoupper($user['title_project'])?></td>
                                        <td><?=strtoupper($user['title_object'])?></td>
                                    </tr>

                                <?php } ?>

                                </tbody>
                            </table>

                        <?php } ?>
                            
                        </div>
                    </div>

                    </div>
                <!-- END All Orders Block -->

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

<!-- Load and execute javascript code used only in this page -->
<script type="application/javascript" src="js/pages/bk-index.js"></script>

</body>
</html>