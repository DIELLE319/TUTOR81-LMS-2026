<?php
/**
 * Created by PhpStorm.
 * User: Davide
 * Date: 01/02/2017
 * Time: 15.29
 */

require_once 'lib/check_session.php';
/*
require_once 'lib/class_permissions.php';
require_once 'lib/class_company.php';
//require_once 'lib/class_messages.php';
require_once 'lib/class_user.php';

$user_obj = new T81User();
$perm_obj = new Permissions();
$company_obj = new T81Company();

$company = $user_obj->getUserCompany($_SESSION['user']['id']);
$_SESSION['company'] = $company;
//$message_obj = new Tutor81Messages();

$areas = array(0 => 'user',
    1 => 'tutor',
    2 => 'company',
    8 => 'user',
    16 => 'user',
    32 => 'member',
    1000 => 'admin');
//$pages = array('home');
//$sections = array();

 = isset($_REQUE$argsST['request']) ? explode('/', rtrim($_REQUEST['request'], '/')) : array();

if (array_key_exists(0, $args)) {
    $area = array_shift($args);
} else {
    $area = $areas[$_SESSION['user']['role']];
}
if (array_key_exists(0, $args)) {
    $page = array_shift($args);
} else {
    $page = 'home';
}
if (array_key_exists(0, $args)) {
    $section = array_shift($args);
} else {
    $section = FALSE;
}

$role = $perm_obj->getRoleById($_SESSION['user']['role']);



$companies = $company_obj->getCompanyByTutorCompany($_SESSION['tutor']['id']);
$users_have_sessions = $company_obj->getUsersHaveSessionsByTutorCompany($_SESSION['tutor']['id']);
$tutors = $company_obj->getUsersCompanyByID($_SESSION['tutor']['id'], 1);
$didactic_tutor = $company_obj->getDidacticTutor($_SESSION['tutor']['id']);
if (isset($didactic_tutor['user_id']) && $didactic_tutor['user_id'] != 6) {
    $didactic_tutor_name = ucwords("{$didactic_tutor['name']} {$didactic_tutor['surname']}");
} else {
    $didactic_tutor_name = "Luca Pedretti";
}
$license_detail = $company_obj->getCompanyLicense($_SESSION['tutor']['id']);

$brand = file_exists("media/img/company/{$_SESSION['tutor']['id']}.png") ?
    '<img src="media/img/company/' . $_SESSION['tutor']['id'] . '.png" alt="logo Ente Formativo" style="height: 44px;"/>' :
    '<span style="line-height: 44px;">' . strtoupper($_SESSION['tutor']['business_name']) . '</span>';


require_once 'config.php';
//require_once BASE_LIBRARY_PATH . 'check_session.php';
//require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_safety.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';


$course_obj = new iWDCourse();
$sub_category = $_GET["id"];

$comp_obj = new T81Company();
$course_type_obj = new T81CourseType();

$course_types = $course_obj->getCourseDetailedListOfAvailableLearningProjectEcommerce (6, $sub_category);

require_once BASE_LIBRARY_PATH . 'class_report.php';
$report_obj = new Report();

$status_tutor_company = $report_obj->getLearningStatusByTutorCompany($_SESSION['company']['id'], true);
$status_tutor_company["notfinished"] = $status_tutor_company["started"] - $status_tutor_company["finished"];
$status_tutor_company["alert"] = $report_obj->getLearningStatusByTutorCompanyOnlyAlertEndDate($_SESSION['company']['id'], true)["alert"];


$status_tutor_admin = $report_obj->getLearningStatusByTutorAdmin($_SESSION['tutor']['id']);

//echo "status tutor company: <br>";
//print_r($status_tutor_company);
//echo "<br>";
//echo "<br>";
*/

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
                            <div class="col-sm-12"><h2 class="text-center"><strong> Aggiungi una notizia</strong> </h2></div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->

                <!-- Datatables Content -->
                <div class="block full" >

                    <!-- CKEditor Content -->
                    <form action="#" method="post" class="form-horizontal form-bordered" onsubmit="return false;">
                        <!-- CKEditor, you just need to include the plugin (see at the bottom of this page) and add the class 'ckeditor' to your textarea -->
                        <!-- More info can be found at http://ckeditor.com -->
                        <fieldset>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <div class="col-sm-2"> <label for="imageAttach"><b> Titolo: </b></label> </div>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control input-sm" title="Title Text" style="width:300px">
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <div class="col-sm-2"> <label for="imageAttach"><b> Testo: </b></label> </div>
                                    <div class="col-sm-10">
                                        <!--<div id="inplace-ckeditor" contenteditable="true">-->
                                        <textarea id="textarea-ckeditor" title="textarea-ckeditor" class="ckeditor" contenteditable="true"> Clicka qui per scrivere</textarea>
                                        <!--</div>-->
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <div class="col-sm-2"> <label for="imageAttach"><b> Immagine: </b></label> </div>
                                    <div class="col-sm-10">
                                        <p><b> Attualmente: </b> <span> image path here</span> <span> &nbsp</span><span> &nbsp</span> <input type="checkbox" id="imageAttach"> Svuota </p>
                                        <p>
                                            <b> Modifica: </b><button type="button" class="btn btn-sm btn-default" style="display: inline;"> Sfoglia...</button>
                                            <input type="text" title="Selected File" placeholder="Nessun file selezionato" class="form-control" style="border: none; width: 200px; display: inline;">
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <div class="col-sm-2">
                                        <input type="checkbox" title="visibleCheckbox"> Visibile
                                    </div>
                                    <div class="col-sm-10">
                                        <input type="checkbox" title="primaPaginaCheckbox"> Prima Pagina
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <div class="col-md-2"> <label for="endDate"><b> Data Scadenza: </b></label> </div>
                                    <div class="col-md-10">
                                        <input type="date" class="form-control input-sm" id="endDate" data-placement="right" style="width: 200px;">
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="form-group">
                                <div class="notifySMS">
                                    <div class="col-xs-12">
                                        <button type="button" class="btn btn-default"> Notifica e notizia per E-Mail ed SMS (Mostra)</button>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <div class="form-group form-actions">
                                <div class="col-xs-12">
                                    <div class="cancelButton pull-left">
                                        <a href="#" type="button" class="btn btn-danger"><i class="fa fa-times"></i> Cancella</a>
                                    </div>
                                    <div class="cancelButton pull-right">
<!--                                        <button type="submit" class="btn btn-default"> Salva e aggiungi un altro</button>-->
<!--                                        <button type="submit" class="btn btn-default"> Salva e continua le modifiche</button>-->
                                        <button type="submit" class="btn btn-primary"> Salva</button>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                    <!-- END CKEditor Content -->
                </div>
                <!-- END CKEditor Block -->

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


<!-- Load and execute javascript code used only in this page -->
<script type="application/javascript" src="js/pages/bk-index.js"></script>
<script src="js/vendor/plugins.js"></script>
<script src="js/vendor/datatables.min.js"></script>
<!-- ckeditor.js, load it only in the page you would like to use CKEditor (it's a heavy plugin to include it with the others!) -->
<script src="ckeditor/ckeditor.js"></script>
</body>
</html>