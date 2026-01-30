<?php
/**
 * Created by PhpStorm.
 * User: Davide
 * Date: 09/01/2016
 * Time: 15.26
 */

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
    $tutor = $company_obj->getBusinessDetail(2); //hub.tutor81.local
}
$tutor["logo"] = T81Company::getEcommerceLogo($tutor);
$tutor["admin_id"] = $company_obj->getMainAdminOfCompany($tutor["id"]);
$color_dark = $tutor['id'] == 688 ? '#101313' : '#394263';
$color_light = $tutor['id'] == 688 ? '#ED1C23' : '#1BBAE1';
foreach ($categories as $key => $category){
    $categories[$key]['counter'] = 0;
    $subcategories = $course_obj->getSubCategories($category['id']);
    foreach ($subcategories as $subcategory){
        $courses = $course_obj->getCourseDetailedListOfAvailableLearningProjectByCompany ($tutor['id'], NULL, $subcategory['id']);
        $categories[$key]['subcategories'][$subcategory['position']]['subcategory'] = $subcategory;
        $categories[$key]['subcategories'][$subcategory['position']]['courses'] = $courses;
        $categories[$key]['subcategories'][$subcategory['position']]['counter'] = $courses ? count($courses) : 0;
        $categories[$key]['counter'] += $categories[$key]['subcategories'][$subcategory['position']]['counter'];
    }
}
require_once 'ecommerce/header.php'; ?>

<body>
<!-- Page Container -->
<!-- In the PHP version you can set the following options from inc/config file -->
<!-- 'boxed' class for a boxed layout -->
<div id="page-container">

    <?php require_once 'ecommerce/site-header.php'; ?>

    <!-- Intro -->
    <section class="site-section site-section-light site-section-top" style="background-color: <?= $color_light ?>;">
        <div class="container">
            <div class="row text-center">
                <div class="col-sm-3 text-left animation-fadeIn"><h2 style="margin-top: 0;"><b style="color: <?= $color_dark ?>;"> Benvenuto</b></h2> <p style="margin-bottom: 0;">Scegli uno dei nostri corsi online, siamo qui per assisterti e per certificare la formazione svolta.</p> </div>
                <div class="col-sm-3 animation-fadeIn">
                    <a href="http://www.tutor81.com/la-piattaforma/manuale-uso-piattaforma/" target="_blank" class="circle" style="background-color: <?= $color_dark ?>;">
                        <i class="gi gi-facetime_video"></i>
                    </a>
                    <h4><strong> Guarda come funziona</strong></h4>
                </div>
                <div class="col-sm-3 animation-fadeIn">
                    <a href="mailto:assistenza@tutor81.it" class="circle" style="background-color: <?= $color_dark ?>;">
                        <i class="gi gi-envelope"></i>
                    </a>
                    <h4>
                        <strong> Supporto tecnico</strong>
                    </h4>
                </div>
                <div class="col-sm-3 animation-fadeIn">
                    <a href="javascript:void(0)" class="circle" style="background-color: <?= $color_dark ?>;">
                        <i class="fa fa-share-square-o "></i>
                    </a>
                    <h4>
                        <strong> Come si ottiene l'attestato</strong>
                    </h4>
                </div>
            </div>
        </div>
    </section>

    <!-- END Intro -->

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
                                    
                                    <?php
                                    $first = true;
                                    foreach ($categories as $key => $category){
                                        if ($category['counter'] != 0) {
                                            $active = $first ? " active" : "";
                                            $first = false;
                                            echo '<li id="'.$category['name'].'" class="cat-tab'.$active.'"><a href="#courses-categories-'.str_replace(' ', '_',$category['name']).'">'.$category['name'].'</a></li>';
                                        }
                                     }
                                    
                                    ?>
                                
                                </ul>
                            </div>
                            <!-- END Navigation Tabs Title -->

                            <!-- Navigation Tabs Content -->
                            <div class="tab-content">
                                
                                    <?php 
                                    $first = true;
                                    foreach ($categories as $key => $category){
                                        if ($category['counter'] != 0) {
                                            $active = $first ? " active" : "";
                                            $first = false;
                                    ?>    
                                <div class="tab-pane<?= $active ?>" id="courses-categories-<?= str_replace(' ', '_',$category['name']) ?>">
                                    <ul class="nav nav-pills nav-stacked">
                                        <li id="tutti-<?= str_replace(' ', '_',$category['name']) ?>" class="all<?= $active ?>"><a id="tuttiLink" class="btnCourse" href="/ec-course-list.php?cat_id=<?= $category['id'] ?>"><span id="tutti<?=str_replace(' ', '_',$category['name'])?>Counter" class="badge pull-right"><?= $category['counter'] ?></span><b> Tutti <?= $category['name'] ?></b></a></li>
                                        <li><hr></li>
                                        
                                        <?php 
                                        foreach ($category['subcategories'] as $subcategory ){ 
                                            if ($subcategory['counter'] != 0) {?>
                                        
                                        <li><a class="btnCourse" href="/ec-course-list.php?sub_id=<?= $subcategory['subcategory']['id'] ?>" ref="<?= $subcategory['subcategory']['id'] ?>"><span class="badge pull-right"><?= $subcategory['counter'] ?></span><i class="gi gi-user fa-fw themed-color-default "></i><b><?= $subcategory['subcategory']['name'] ?></b> </a></li>
                                        
                                            <?php }
                                             
                                        } ?>
                                        
                                    </ul>
                                </div>
                                
                                    <?php
                                    }
                                }
                                ?>
                                
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

    <?php include_once 'ecommerce/modal-checkout.php'; ?>

    <?php require_once 'ecommerce/footer.php'; ?>

</div>
<!-- END Page Container -->

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-up"></i></a>

<script src="js/pages/ecomDetail.js"></script>

</body>