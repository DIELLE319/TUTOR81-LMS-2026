<?php
/**
 * Created by Evolvia.
 * User: Davide
 * Date: 12/16/2016
 * Time: 4:59 PM
 */
require_once 'config.php';
//require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_safety.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';

$course_obj = new iWDCourse();
$comp_obj = new T81Company();// Point to right tutor in base of domain tutor81 alias add in any ecommerce page
$tutor = $comp_obj->getCompanyByServerAlias($_SERVER['SERVER_NAME']);
if (!isset($tutor) || $tutor == false) {
    $tutor = $comp_obj->getBusinessDetail(2); //hub.tutor81.local
}

$category_id = key_exists('cat_id', $_GET) ? $_GET["cat_id"] : NULL;
$subcategory_id = key_exists('sub_id', $_GET) ? $_GET["sub_id"] : NULL;

if (isset($_GET["counter"])) {

    $counter = $course_obj->getCourseCourseEcommerce ($tutor['id'], $subcategory_id);
    echo json_encode($counter);
}
else {
    $course_type_obj = new T81CourseType();
    $course_types = $course_obj->getCourseDetailedListOfAvailableLearningProjectByCompany ($tutor['id'], $category_id, $subcategory_id);
    if ($course_types) {
        foreach ($course_types as $index => $single) {
            $index_single = 1;
            $price_list_single = $course_obj->getPriceList($single['course_id']);
            $price_value = "???";
            //print_r($price_list_single);
            //print_r($single);
            if ($index % 2 === 0) { 
                ?>

                <div class="row store-items">

                <?php 
                require 'ecommerce/product-box.php'; 
                continue;
            } else { 
                require 'ecommerce/product-box.php'; 
                ?>

                </div>

            <?php }
        }
        if (sizeof($course_types) % 2 !== 0) {
            echo "</div>";
        }
    }
}