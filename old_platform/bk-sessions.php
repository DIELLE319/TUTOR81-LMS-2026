<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 08-ott-2017
 * File: bk-sessions.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once '403.php';
    exit();
}
require_once 'lib/class_company.php';
$comp_obj = new T81Company();

$users_have_sessions = $comp_obj->getUsersHaveSessionsByTutorCompany($_SESSION['tutor']['id']);

require_once 'ecommerce/bk/header.php'; ?>

<body style="background-color: white;">
    <link href="css/report.css" rel="stylesheet"/>
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

            <!-- Page content -->
            <div id="page-content" style="padding-top: 20px; min-height: 821px;" >

                <!-- Timeline Widget -->
                <div class="widget" style="margin:  0;">
                    <div class="widget-extra themed-background-dark">
                        <div class="row">
                            <div class="col-sm-12"><h2 class="text-center"><strong>Sessioni</strong></h2></div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->

                <!-- Content -->
                <div class="block full" id="sessions">

                <?php if ($users_have_sessions) { ?>
                    
                    <div class="row">
                        <div class="col-sm-8 ">
                            <div class="form-group" id="userSessionsSelectContainer" style="display: flex;">
                                <select title="Scegli Cliente" id="userSessionsSelect"
                                                class="companySelect form-control input-sm"
                                                style="margin-top: 10px; padding: 3px; width: 100%; max-width: 100%; text-align-last: center!important; font-size: 15px; font-weight: bold;"
                                                name="select_user">
                                    <option value="0">----- Scegli l'utente al quale applicare il filtro -----</option>

                                <?php foreach ($users_have_sessions as $user) { ?>

                                    <option value="<?= $user['id'] ?>">
                                        <?= ucwords("{$user['surname']} {$user['name']}") ?>
                                    </option>

                                <?php } ?>

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">                            
                        <div class="user-sessions">                            
                        </div>
                    </div>
                            
                <?php } else { ?>
                    
                    <div class="row">
                        <h3>Nessun sessione esistente</h3>
                    </div>
                    
                <?php } ?>

                </div>
                <!-- END Content -->

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
<script src="js/vendor/plugins.js"></script>
<!-- Load and execute javascript code used only in this page -->
<script>
 
    $('#sessions select').change(function(){
        $('#sessions .user-sessions')
            .html('<img style="height: 50px; display: block;" src="img/loading_gif.gif">')
            .load('report/sessions.php?user_id=' + $(this).val());
    });
    
    $(function(){
        
    });

</script>

</body>
</html>