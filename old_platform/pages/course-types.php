<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 25-lug-2015
 * File: pages/course-types.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';

$course_obj = new iWDCourse();
$course_type_obj = new T81CourseType();

$categories = $course_obj->getCategories();
$course_types = $course_type_obj->getCourseTypesList();
?>
<div id="workspace">
    <!-- -------- CLASSROOM COURSES -------- -->
    <div id="course-types">

        <ul class="filter-button filter-category nav nav-pills">
            <li>
                Categoria:
                <a href="javascript: void(0)" data-filter-text="SICUREZZA">SICUREZZA</a>
                <a href="javascript: void(0)" data-filter-text="ECM">ECM</a>
                <a href="javascript: void(0)" data-filter-text="VARIE">VARIE</a>
                <ul class="filter-button filter-subcategory nav nav-pills">
                    <li class="filter-arrow">
                        <span class="glyphicon glyphicon-arrow-right"></span>
                    </li>

    <?php foreach ($categories as $category){
            $subcategories = $course_obj->getSubCategories($category['id']);
    ?>

                    <li <?= 'id="filter-subcategory_' . strtoupper($category['name']) . '"'?> style="display:none;">

                    <?php foreach ($subcategories as $subcategory){?>

                        <a href="javascript: void(0)" data-filter-text="<?=strtoupper($subcategory['name'])?>"><?=strtoupper($subcategory['name'])?></a>

                    <?php } ?>

                    </li>

    <?php } ?>

                </ul>
            </li>
        </ul>
        <table class="table tablesorter" id="classroom-course-table">
            <thead>
                <tr>
                    <th style="display:none">Destinatario</th>
                    <th style="display:none">Categoria</th>
                    <th>Codice Corso</th>
                    <th>Descrizione</th>
                    <th>Durata</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody id="courses">

            <?php foreach ($course_types as $course){ ?>

                <tr <?= 'id="link_crs_' . $course['id_course_type'] .'"'?> data-course_id="<?=$course['id_course_type']?>" class="link_crs">
                    <td style="display:none"><?=strtoupper($course['subcategory'])?></td>
                    <td style="display:none"><?=strtoupper($course['category'])?></td>
                    <td class="course-code"><?=strtoupper($course['course_code'])?></td>
                    <td class="course-description"><?=$course['course_description']?></td>
                    <td><?=$course['duration']?> ORE</td>
                    <td><?=isset($course['type']) ? strtolower($course['type']) : '&nbsp;'?></td>
                </tr>

            <?php }?>

            </tbody>
        </table>
    </div><!--/#course-types-->

    <!-- ------ END COURSES ------ -->
</div><!-- /#workspace -->
<script>
    $(function(){
        $('#classroom-course-table').tablesorter({
            theme: 'greyT81',

            sortList: [[2,0]],

            // hidden filter input/selects will resize the columns, so try to minimize the change
            widthFixed : true,

            // initialize zebra striping and filter widgets
            widgets: ["filter"],

            // headers: { 5: { sorter: false, filter: false } },
            widgetOptions : {

                    filter_columnFilters : false,

                    // extra css class applied to the table row containing the filters & the inputs within that row
                    filter_cssFilter   : '',

                    // If there are child rows in the table (rows with class name from "cssChildRow" option)
                    // and this option is true and a match is found anywhere in the child row, then it will make that row
                    // visible; default is false
                    filter_childRows   : false,

                    // if true, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
                    // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus
                    filter_hideFilters : true,

                    // Set this option to false to make the searches case sensitive
                    filter_ignoreCase  : true,

                    // class added to filtered rows (rows that are not showing); needed by pager plugin
                    filter_filteredRow   : 'filtered',

                    // jQuery selector string of an element used to reset the filters
                    filter_reset : '.reset',

                    // Delay in milliseconds before the filter widget starts searching; This option prevents searching for
                    // every character while typing and should make searching large tables faster.
                    filter_searchDelay : 300,

                    // Set this option to true to use the filter to find text from the start of the column
                    // So typing in "a" will find "albert" but not "frank", both have a's; default is false
                    filter_startsWith  : false,

                    // if false, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
                    // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus
                    filter_hideFilters : true

            }

        });

        /* Filters */  
        $('#course-types .filter-category > li > a').click(function(e){
            $(this).addClass('active').siblings('.active').removeClass('active');
            $('#course-types .filter-subcategory > li:not(.filter-arrow)').hide();
            $('#filter-subcategory_'+$(this).data("filter-text")).show();
            $('#course-types .filter-subcategory a.active').removeClass('active');
            var columns = [];
            columns[1] = $(this).data("filter-text");
            $('#classroom-course-table').trigger('search', [ columns ]);
            return false;
        });

        $('#course-types .filter-subcategory a').click(function(e){
            $(this).addClass('active').siblings('.active').removeClass('active').promise().done(function(){
                var category = $('#course-types .filter-category a.active').data("filter-text");
                var subcategory = $('#course-types ul.filter-subcategory > li:visible > a.active').data("filter-text");
                var columns = [];
                columns[1] = category;
                columns[0] = subcategory;
                $('#classroom-course-table').trigger('search', [columns]);
             });

            return false;
        });

        $('#course-types .filter-category a[data-filter-text="SICUREZZA"]').click();


    });

</script>