<?php
/**
 * Created by PhpStorm.
 * User: endrit
 * Date: 3/13/2017
 * Time: 10:24 AM
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
require_once BASE_LIBRARY_PATH . 'class_user.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';

$report_obj = new Report();
$user_obj = new T81User();
$comp_obj = new T81Company();

$status = $_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 ||$_SESSION['user']['role'] == 32 ? 
        $report_obj->getLearningStatusByTutorCompany($_SESSION['tutor']['id'], true) 
        : $report_obj->getLearningStatusByCompany($_SESSION['company']['id'], true);
$status["active"] = $status["total"] - $status["finished"];
$status["notfinished"] = $status["started"] - $status["finished"];
$status["alert"] = $_SESSION['user']['role'] != 2 ? 
        $report_obj->getLearningStatusByTutorCompanyOnlyAlertEndDate($_SESSION['company']['id'], true)["alert"] 
        : $report_obj->getLearningStatusByCompanyOnlyAlertEndDate($_SESSION['company']['id'], true)["alert"];


$status_tutor_admin = $report_obj->getLearningStatusByTutorAdmin($_SESSION['tutor']['id']);

$purchases = $_SESSION['user']['role'] == 1000 ? $report_obj->countAllPurchase() 
        : 
        ($_SESSION['user']['role'] == 1 ||$_SESSION['user']['role'] == 32 ? 
            $report_obj->countPurchasesByTutor($_SESSION['tutor']['id'])
            :
            $report_obj->countPurchasesByCompany($_SESSION['company']['id'])
        );

$edit = filter_input(INPUT_GET, 'edit', FILTER_SANITIZE_STRING);
$password = filter_input(INPUT_POST, 'user-edit-pswd', FILTER_SANITIZE_STRING);
if ($edit === "pswd" && strlen($password) >= 6 ) {
    $user_obj->setUserPassword($_SESSION['user']['id'], $password);
}
require_once 'ecommerce/bk/header.php'; ?>

<body style="background-color: white;">

<div id="page-wrapper">

    <div id="page-container" class="sidebar-partial sidebar-visible-lg sidebar-no-animations">

        <!-- Main Sidebar -->
        <?php require "ecommerce/bk/menu-left.php" ?>
        <!-- END Main Sidebar -->

        <?php require "ecommerce/bk/modal-user-profile-settings.php" ?>

        <!-- Main Container -->
        <div id="main-container">

            <header class="navbar navbar-default" >
                <?php require "ecommerce/bk/search-form-header.php" ?>
            </header>
            <!-- Intro -->
            <section class="site-section site-section-light site-section-top themed-background-dark"
                     style="padding:0 0 38px;">
                <div class="container">
                    <div class="row">
                        <div class="col-sm-12 text-center animation-slideDown">
                            <h1 style="color: #fff; font-size: 120px;">
                                <strong>Tutor81</strong>
                                </h1>
                            <span style="color: #fff; font-size: 30px;">Learning Management System</span>
                        </div>
                    </div>
                </div>
            </section>
            <!-- END Intro -->

            <!-- Page content -->
            <div id="page-content" style="padding-top:20px; min-height: 650px;">
                <!-- Mini Top Stats -->
                <div class="row">
                    <div class="col-sm-6 col-lg-3">
                        <!-- Widget -->
                        <a href="bk-employee.php?scelta=attestati" class="widget widget-hover-effect1 themed-background-dark">
                            <div class="widget-simple">
                                <div class="widget-icon pull-left themed-background-spring animation-fadeIn">
                                    <!--<i class="fa fa-file-text"></i>-->
                                    <?= number_format($status["finished"],0,',','.') ?>
                                </div>
                                <h3 class="text-center animation-pullDown"
                                    style="font-size: 20px; margin: 20px 0; color: #fff;">
                                    <strong> Attestati</strong>
                                </h3>
                            </div>
                        </a>
                        <!-- END Widget -->
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <!--  Widget -->
                        <a href="bk-progress.php?scelta=corsiAttivi" class="widget widget-hover-effect1 themed-background-dark">
                            <div class="widget-simple">
                                <div class="widget-icon pull-left themed-background animation-fadeIn">
                                    <!--<i class="gi gi-usd"></i>-->
                                    <?= number_format($status["active"],0,',','.')?>
                                </div>
                                <h3 class="text-center animation-pullDown"
                                    style="font-size: 20px; margin: 20px 0; color: #fff;">
                                    <strong> Attivi</strong>
                                </h3>
                            </div>
                        </a>
                        <!-- END Widget -->
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <!-- Widget -->
                        <a href="bk-progress.php?scelta=corsiAttivi" class="widget widget-hover-effect1 themed-background-dark">
                            <div class="widget-simple">
                                <div class="widget-icon pull-left themed-background-fire animation-fadeIn">
                                    <!--<i class="gi gi-envelope"></i>-->
                                    <?= number_format($status["total"] - $status["started"],0,',0','.') ?>
                                </div>
                                <h3 class="text-center animation-pullDown"
                                    style="font-size: 20px; margin: 20px 0; color: #fff;">
                                    <strong> Non avviati</strong>
                                </h3>
                            </div>
                        </a>
                        <!-- END Widget -->
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <!-- Widget -->
                        <a href="bk-progress.php?scelta=corsiAttivi" class="widget widget-hover-effect1 themed-background-dark">
                            <div class="widget-simple">
                                <div class="widget-icon pull-left themed-background-muted animation-fadeIn">
                                    <!--<i class="gi gi-picture"></i>-->
                                    <?= number_format($status["alert"],0,',','.') ?>
                                </div>
                                <h3 class="text-center animation-pullDown"
                                    style="font-size: 20px; margin: 20px 0; color: #fff;">
                                    <strong> In scadenza</strong>
                                </h3>
                            </div>
                        </a>
                        <!-- END Widget -->
                    </div>
                </div>
                <!-- End Mini Top Stats -->
                <div class="row">
                    <div class="col-md-4">
                        <!-- Large Widget (Active Color Theme Light) -->
                        <div class="widget" style="min-height: 260px;">
                            <div class="widget-advanced widget-advanced-alt">
                                <!-- Widget Header -->
                                <div class="widget-header text-center themed-background" style="min-height: 0">
                                    <h3 class="widget-content-light text-center animation-pullDown">
                                        <strong>AIUTO</strong><br>
                                    </h3>
                                </div>
                                <!-- END Widget Header -->

                                <!-- Widget Main -->
                                <div class="widget-main">
                                    <div class="row text-center">
                                        <h5><a href="https://www.tutor81.it/istruzioni-uso-tutor81/" target="_blank">Istruzioni uso piattaforma Tutor81</a></h5>
                                        <h5><a href="https://www.tutor81.it/piattaforma-lms-sicurezza/requisiti-tecnici-per-avviare-il-corso/" target="_blank">Requisiti tecnici per i corsi</a></h5>
                                        <h5><a href="https://www.tutor81.it/piattaforma-lms-sicurezza/come-si-avvia-un-corso-online/" target="_blank">Come si avvia un corso</a></h5>
                                        <h5><a href="https://www.tutor81.it/come-si-vende-un-corso-online-sicurezza/" target="_blank">Come si assegna un corso</a></h5>
                                    </div>
                                </div>
                                <!-- END Widget Main -->
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <!-- Large Widget (Active Color Theme Light) -->
                        <div class="widget" style="min-height: 260px;">
                            <div class="widget-extra-full">
                                <div>
                                    <h3 class="text-center" style="margin-top: 0;">I tuoi dati</h3>
                                    <h4 style="margin-top: 0;">
                                        <strong><?= $_SESSION['company']['business_name'] ?></strong><br>
                                    </h4>
                                    <p>
                                        <?= $_SESSION['company']['address'] ?>
                                        <br><?= $_SESSION['company']['city'] ?>
                                        <br>Tel: <?= $_SESSION['company']['telephone'] ?>
                                        <br>Fax: <?= $_SESSION['company']['telephone'] ?>
                                        <br><?= $_SESSION['company']['email'] ?>
                                        <br><?= $_SESSION['user']['email'] ?>
                                    </p>
                                    <p style="margin-bottom: 0;">
                                        IBAN: <?= $_SESSION['company']['iban'] ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Large Widget (Active Color Theme Light) -->
                        <div class="widget" style="min-height: 260px;">
                            <div class="widget-extra-full">
                                <h3 class="text-center" style="margin-top: 0;">La tua licenza</h3>


                                <h4 class="text-center" style="margin-top: 0;">
                                    <strong><?= $_SESSION['user']['plan']['short_desc_plan']; ?></strong>
                                </h4>
                                <dl class="dl-horizontal">
                                    <dt>Prezzo:</dt>
                                    <dd><?= number_format($_SESSION['user']['plan']['price'], 2, ',', '.'); ?> Euro</dd>
                                    <dt>Scade il:</dt>
                                    <dd>
                                        <?php { 
                                            $validity_end = new DateTime($_SESSION['user']['plan']['validity_end']); 
                                            echo $validity_end->format('d/m/Y');
                                        }?>
                                    </dd>
                                    <dt>Sconto:</dt>
                                    <dd><?= $_SESSION['user']['plan']['discount'] ?> %</dd>
                                </dl>
                                <h5 class="text-center">Listino corsi</h5>
                                <dl class="dl-horizontal">
                                    <dt>Corsi personalizzati:</dt>
                                    <dd><?= $_SESSION['user']['plan']['customized_courses'] ? 'SI' : 'NO';?></dd>
                                    <dt>E-commerce:</dt>
                                    <dd><?= $_SESSION['user']['plan']['ecommerce'] ? '<i class="badge label-danger">' . $not_assigned_ecommerce_licences . '</i>' : 'NO';?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <!-- END Large Widget (Active Color Theme Light) -->
                </div>
            </div>

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
