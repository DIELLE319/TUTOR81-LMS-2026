<?php
/**
 * Created by PhpStorm.
 * User: endrit
 * Date: 3/29/2017
 * Time: 2:16 PM
 */
?>

<?php

require_once 'config.php';
//require_once BASE_LIBRARY_PATH . 'check_session.php';

//if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32 && $_SESSION['user']['role'] != 2) {
//    require_once BASE_ROOT_PATH . '403.php';
//    return false;
//}

require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';

$company_obj = new T81Company ();
$course_obj = new iWDCourse();
$course_type_obj = new T81CourseType();

$learning_project_id = filter_input(INPUT_GET, 'learning_project_id', FILTER_SANITIZE_NUMBER_INT);

$categories = $course_obj->getCategories();
$course_for_menu = 'elearning';
$prov = $company_obj->getProvinces();

// Point to right tutor in base of domain tutor81 alias add in any ecommerce page
$tutor = $company_obj->getCompanyByServerAlias($_SERVER['SERVER_NAME']);
if (!isset($tutor) || $tutor == false) {
    $tutor = $company_obj->getCompanyByServerAlias("pre-amm.tutor81.com"); //hub.tutor81.local
}
$tutor["logo"] = T81Company::getEcommerceLogo($tutor);
$tutor["admin_id"] = $company_obj->getMainAdminOfCompany($tutor["id"]);

$wordpress_url = "{$_SERVER['REQUEST_URI']}";
?>

<?php require_once 'ecommerce/header.php'; ?>


<?php
//$escaped_url = htmlspecialchars($wordpress_url, ENT_QUOTES, 'UTF-8');
//echo '<a href="' . $escaped_url . '">' . $escaped_url . '</a>';
//echo '<p style="color:#fff;">' . $tutor["id"] . '</p>';

if ($wordpress_url == "/ec-wordpress.php") {
    echo '<link href="css/wordpressCSS/' . $tutor["id"] . '.css" rel="stylesheet"/>';
}
?>


<body>
<!-- Page Container -->
<!-- In the PHP version you can set the following options from inc/config file -->
<!-- 'boxed' class for a boxed layout -->
<div id="page-container">

    <!-- Product List -->
    <section class="site-content site-section">
        <div class="container">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-4 col-lg-3">
                    <aside class="sidebar site-block">
                        <!-- Store Menu -->
                        <!-- Store Menu functionality is initialized in js/app.min.js -->
                        <div class="sidebar-block" style="border: 1px solid #eeeeee;">
                            <!-- Navigation Tabs Title -->
                            <div class="block-title">
                                <ul class="nav nav-tabs" data-toggle="tabs">
                                    <li id="sicurezza-tab" class="cat-tab active"><a
                                            href="#courses-categories-sicurezza"> Sicurezza</a></li>
                                    <li id="ecm-tab" class="cat-tab"><a href="#courses-categories-haccp"> Ecm</a></li>
                                    <li id="varie-tab" class="cat-tab"><a href="#courses-categories-varie"> Varie</a>
                                    </li>
                                </ul>
                            </div>
                            <!-- END Navigation Tabs Title -->

                            <!-- Navigation Tabs Content -->
                            <div class="tab-content">
                                <div class="tab-pane active" id="courses-categories-sicurezza">
                                    <ul class="nav nav-pills nav-stacked">
                                        <li id="tutti-sicurezza" class="all active"><a id="tuttiLink" class="btnCourse"
                                                                                       href="/ec-course-list.php?id=A"><span
                                                    id="tuttiSicurezzaCounter" class="badge pull-right">0</span><b>
                                                    Tutti</b></a></li>
                                        <li>
                                            <hr>
                                        </li>
                                        <li><a class="btnCourse" href="/ec-course-list.php?id=8" ref="8"><span
                                                    class="badge pull-right">0</span><i
                                                    class="gi gi-user fa-fw themed-color-default "></i><b>
                                                    Lavoratore</b> </a></li>
                                        <li><a class="btnCourse" href="/ec-course-list.php?id=9" ref="9"><span
                                                    class="badge pull-right">0</span><i
                                                    class="fa fa-file-text fa-fw themed-color-default"></i><b>
                                                    Preposto</b></a></li>
                                        <li><a class="btnCourse" href="/ec-course-list.php?id=10" ref="10"><span
                                                    class="badge pull-right">0</span><i
                                                    class="gi gi-briefcase fa-fw themed-color-default"></i><b>
                                                    Dirigente</b></a></li>
                                        <li><a class="btnCourse" href="/ec-course-list.php?id=11" ref="11"><span
                                                    class="badge pull-right">0</span><i
                                                    class="gi gi-old_man fa-fw themed-color-default"></i><b> Rspp Dl</b></a>
                                        </li>
                                        <li><a class="btnCourse" href="/ec-course-list.php?id=20" ref="20"><span
                                                    class="badge pull-right">0</span><i
                                                    class="gi gi-old_man fa-fw themed-color-default"></i><b>
                                                    Aspp</b></a></li>
                                        <li><a class="btnCourse" href="/ec-course-list.php?id=12" ref="12"><span
                                                    class="badge pull-right">0</span><i
                                                    class="gi gi-pencil fa-fw themed-color-default"></i><b> Rspp</b></a>
                                        </li>
                                        <li><a class="btnCourse" href="/ec-course-list.php?id=13" ref="13"><span
                                                    class="badge pull-right">0</span><i
                                                    class="gi gi-group fa-fw themed-color-default"
                                                    style="margin-bottom: 4px;"></i><b> Rls</b></a></li>
                                        <li><a class="btnCourse" href="/ec-course-list.php?id=14" ref="14"><span
                                                    class="badge pull-right">0</span><i
                                                    class="gi gi-fire fa-fw themed-color-default"></i><b>
                                                    Antincendio</b></a></li>
                                        <li><a class="btnCourse" href="/ec-course-list.php?id=15" ref="15"><span
                                                    class="badge pull-right">0</span><i
                                                    class="fa fa-plus-square fa-fw themed-color-default "></i><b> Primo.
                                                    Socc</b></a></li>
                                    </ul>
                                </div>
                                <div class="tab-pane" id="courses-categories-haccp">
                                    <ul class="nav nav-pills nav-stacked">
                                        <li id="tutti-ecm" class="all active"><a class="btnCourse"
                                                                                 href="/ec-course-list.php?id=B"><span
                                                    id="tuttiEcmCounter" class="badge pull-right">0</span> Tutti</a>
                                        </li>
                                        <li>
                                            <hr>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-pane" id="courses-categories-varie">
                                    <ul class="nav nav-pills nav-stacked">
                                        <li id="tutti-varie" class="all active"><a class="btnCourse"
                                                                                   href="/ec-course-list.php?id=C"><span
                                                    id="tuttiVarieCounter" class="badge pull-right">0</span> Tutti</a>
                                        </li>
                                        <li>
                                            <hr>
                                        </li>
                                        <li><a class="btnCourse varie" href="/ec-course-list.php?id=18" ref="18"><span
                                                    class="badge pull-right">0</span><i
                                                    class="fa fa-user-secret fa-fw themed-color-dark"></i>
                                                Anticorruzione</a></li>
                                        <li><a class="btnCourse varie" href="/ec-course-list.php?id=17" ref="17"><span
                                                    class="badge pull-right">0</span><i
                                                    class="gi gi-lock fa-fw themed-color-dark"></i> Privacy</a></li>
                                        <li><a class="btnCourse varie" href="/ec-course-list.php?id=19" ref="19"><span
                                                    class="badge pull-right">0</span><i
                                                    class="gi gi-shield fa-fw themed-color-dark"></i> Legge 231</a></li>
                                    </ul>
                                </div>
                            </div>
                            <!-- END Navigation Tabs Content -->
                        </div>
                        <!-- END Store Menu -->
                    </aside>
                </div>
                <!-- END Sidebar -->

                <!-- Products -->
                <div class="col-md-8 col-lg-9">
                    <div id="catalogContent"></div>
                </div>
                <!-- END Products -->
            </div>
        </div>
    </section>
    <!-- END Product List -->

</div>
<!-- END Page Container -->

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-up"></i></a>

<script src="js/pages/ecomDetail.js"></script>

</body>


































