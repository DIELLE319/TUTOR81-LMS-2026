<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_course.php';

$course_obj = new iWDCourse();

$categories = $course_obj->getCategories();
$course_available = $course_obj->getCourseDetailedListOfAvailableLearningProject($_SESSION['company']['id']);
?>

<div id="courses-selection">

    <!-- -------- COURSES -------- -->

    <div id="courses_table">

        <ul id="nav-course-category" class="filter-button filter-category nav-filter nav nav-pills">
            <li>
                Categoria:
                <a href="javascript: void(0)" data-filter-text="SICUREZZA">SICUREZZA</a>
                <a href="javascript: void(0)" data-filter-text="ECM">ECM</a>
                <a href="javascript: void(0)" data-filter-text="VARIE">VARIE</a>
                <ul class="filter-button filter-subcategory nav nav-pills">
                    <li class="filter-arrow">
                        <i class="icon-arrow-right"></i>
                    </li>

                    <?php
                    foreach ($categories as $category) {
                        $subcategories = $course_obj->getSubCategories($category['id']);
                        ?>

                        <li<?= ' id="filter-subcategory_' . strtoupper($category['name']) . '"' ?> style="display:none;">

                            <?php foreach ($subcategories as $subcategory) { ?>

                                <a href="javascript: void(0)" data-filter-text="<?= strtoupper($subcategory['name']) ?>"><?= strtoupper($subcategory['name']) ?></a>

                            <?php } ?>

                        </li>

                    <?php } ?>

                </ul>
            </li>
        </ul>

        <table class="table tablesorter table-condensed single-row-selectable" id="courses_list_selectable">
            <thead>
                <tr>
                    <th class="{sorter: false}">&nbsp;</th>
                    <th>Destinatario</th>
                    <th>Corso</th>
                    <th style="display:none">Categoria</th>
                    <th>Durata</th>
                    <th>Tipo</th>
                    <th>Disponibilità</th>
                    <th class="{sorter: false}">&nbsp;</th>
                </tr>
            </thead>
            <tbody id="courses">

                <?php foreach ($course_available as $course) { ?>

                    <tr<?= ' id="link_crs_' . $course['learning_project_id'] . '"' ?> data-learn_id="<?= $course['learning_project_id'] ?>" data-course_id="<?= $course['course_id'] ?>"	class="link_crs">
                        <td class="{sorter: false}"><input type="radio" name="course-selection"></td>
                        <td><?= strtoupper($course['subcategory']) ?></td>
                        <td class="course-title"><?= strtoupper(substr($course['title'], strpos($course['title'], ' - ') + 3)) ?></td>
                        <td style="display:none"><?= strtoupper($course['category']) ?></td>
                        <td><?= $course['duration'] ?> ORE</td>
                        <td><?= isset($course['Tipo']) ? strtolower($course['Tipo']) : '&nbsp;' ?></td>
                        <td>
                            <span class="free-seat label label-as-badge
                                    <?= $course['qty_purchased'] > 0 ? ($course['qty_purchased'] - $course['qty_licensed'] > 0 ? " label-success" : " label-warning") : "" ?>
                                    <?= $course['qty_purchased'] - $course['qty_licensed'] == 0 ? ' hidden' : ''?>">
                                <?= $course['qty_purchased'] - $course['qty_licensed'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group action">
                                <button class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                                <ul class="dropdown-menu pull-right">
                                    <!-- dropdown menu links -->
                                    
                                    <li>
                                        <a class="description-course" tabindex="-1" href="javascript: void(0)">Descrizione</a>
                                    </li>
                                    <li<?= file_exists(BASE_MEDIA_PATH . "user_store/{$course['owner_user_id']}/courses/brochures/{$course['course_id']}.pdf") ? '' : ' class="disabled"' ?>>
                                        <a class="brochure-course" tabindex="-1" href="<?= "media/user_store/{$course['owner_user_id']}/courses/brochures/{$course['course_id']}.pdf" ?>" target="_blank">Dispensa</a>
                                    </li>
                                    <li>
                                        <a class="questions-course" tabindex="-1" href="javascript: void(0)">Magazzino domande</a>
                                    </li>
                                    <li>
                                        <a class="demo-course" tabindex="-1" href="javascript: void(0)">Demo</a>
                                    </li>

                                </ul>
                            </div>
                        </td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div><!--/#courses_table-->

    <!-- ------ END COURSES ------ -->



    <form id="play-course" action="<?= URL_PLAYER . 'lib/ec-login.php' ?>" method="POST">
        <input type="hidden" name="mode" value="demo">
        <input type="hidden" name="learn_id" value="">
        <input type="hidden" name="tos_authorized" value="on">
        <div></div>
    </form>



    <!-- ------ MODALS ------ -->

    <div id="myModal" class="modal fade" tabindex="-1" role="dialog"
         aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            </div>
        </div>
    </div>

    <!-- ---- END MODALS ---- -->

</div><!--/#courses-selection-->

<script>
    $(function () {

        $('#courses_list_selectable').tablesorter({
            theme: 'greyT81',
            sortList: [[2, 0]],
            // hidden filter input/selects will resize the columns, so try to minimize the change
            widthFixed: true,
            // initialize zebra striping and filter widgets
            widgets: ["filter"],
            // headers: { 5: { sorter: false, filter: false } },
            widgetOptions: {
                filter_columnFilters: false,
                // extra css class applied to the table row containing the filters & the inputs within that row
                filter_cssFilter: '',
                // If there are child rows in the table (rows with class name from "cssChildRow" option)
                // and this option is true and a match is found anywhere in the child row, then it will make that row
                // visible; default is false
                filter_childRows: false,
                // if true, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
                // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus
                filter_hideFilters: true,
                // Set this option to false to make the searches case sensitive
                filter_ignoreCase: true,
                // class added to filtered rows (rows that are not showing); needed by pager plugin
                filter_filteredRow: 'filtered',
                // jQuery selector string of an element used to reset the filters
                filter_reset: '.reset',
                // Delay in milliseconds before the filter widget starts searching; This option prevents searching for
                // every character while typing and should make searching large tables faster.
                filter_searchDelay: 300,
                // Set this option to true to use the filter to find text from the start of the column
                // So typing in "a" will find "albert" but not "frank", both have a's; default is false
                filter_startsWith: false,
                // if false, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
                // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus
                filter_hideFilters : true

            }

        });

        /* Filters */
        $('#courses_table .filter-category > li > a').click(function (e) {
            $(this).addClass('active').siblings('.active').removeClass('active');
            $('#courses_table .filter-subcategory > li:not(.filter-arrow)').hide();
            $('#filter-subcategory_' + $(this).data("filter-text")).show();
            $('#courses_table .filter-subcategory a.active').removeClass('active');
            var columns = [];
            columns[3] = $(this).data("filter-text");
            $('#courses_list_selectable').trigger('search', [columns]);

            return false;
        });

        $('#courses_table .filter-subcategory a').click(function (e) {
            $(this).addClass('active').siblings('.active').removeClass('active').promise().done(function () {
                var category = $('#courses_table .filter-category a.active').data("filter-text");
                var subcategory = $('#courses_table ul.filter-subcategory > li:visible > a.active').data("filter-text");
                var columns = [];
                columns[3] = category;
                columns[1] = subcategory;
                $('#courses_list_selectable').trigger('search', [columns]);
            });

            return false;
        });
        
        $('#courses_table .filter-category a[data-filter-text="SICUREZZA"]').click().
                parent().find('.filter-subcategory a[data-filter-text="LAVORATORE"]').click();
        
        /* ******* OPEN MODAL DESCRIPTION COURSE ******* */
        $('#courses_table .action .description-course').click(function (e) {
            var course_id = $(this).parents('tr').data('course_id');
            $('#myModal')
                .find('div.modal-content')
                .html('<img src="img/loading_gif.gif" />')
                .load("modals/show-course.php", {course_id: course_id, setting: 'description'})
                .parents('#myModal')
                .modal('show');
        });

        /* ******* OPEN MODAL QUESTIONS COURSE ******* */
        $('#courses_table .action .questions-course').click(function (e) {
            var course_id = $(this).parents('tr').data('course_id');
            $('#myModal')
                .find('div.modal-content')
                .html('<img src="img/loading_gif.gif" />')
                .load("modals/course_questions.php", {course_id: course_id})
                .parents('#myModal')
                .modal('show');
        });

        /* ******* OPEN DEMO COURSE ******* */
        $('#courses_table .action .demo-course').click(function (e) {
            var learn_id = $(this).parents('tr').data('learn_id');

            $('#play-course input[name="learn_id"]').val(learn_id).siblings('div').load('lib/input_demo.php', function () {
                $('#play-course').submit();
            });
        });

    });
</script>