<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 27-lug-2015
 * File: report/tracks.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_report.php';
require_once BASE_LIBRARY_PATH . 'class_departments.php';
$report_obj = new Report();
$dep_obj = new Departments();

$company_id = filter_input(INPUT_GET, 'company_id', FILTER_SANITIZE_NUMBER_INT) ? : $_SESSION['company']['id'];

$learning_events = $report_obj->getLeaningEventsClosedByCompany($company_id);
if (!$learning_events) {
    echo ('<h3>Nessun utente ha completato un corso</h3>');
    return false;
}

$product_units = $dep_obj->getProductUnits($company_id);
?>
<div class="row nav-filter">

    <div id="users-list-container">

        <ul class="filter-button filter-category nav nav-pills pull-left">
            <li>
                <a class="active" href="javascript: void(0)" data-target-subcategories="filter-by-function">Funzioni</a>
                <a href="javascript: void(0)" data-target-subcategories="filter-by-pu">Unità Produttive</a>
                <ul id="filter-by-function" class="filter-button filter-category nav nav-pills">
                    <li class="filter-arrow">
                        <span class="glyphicon glyphicon-arrow-right"></span>
                    </li>
                    <li>
                        <a href="javascript: void(0)" data-filter-column="2" data-filter-text="lavoratore">lavoratori</a>
                        <a href="javascript: void(0)" data-filter-column="2" data-filter-text="preposto">preposti</a>
                        <a href="javascript: void(0)" data-filter-column="2" data-filter-text="dirigente">dirigenti</a>
                    </li>
                </ul>
                <?php if ($product_units) { ?>

                    <ul id="filter-by-pu" class="filter-button filter-category nav nav-pills" style="display:none;">
                        <li class="filter-arrow">
                            <span class="glyphicon glyphicon-arrow-right"></span>
                        </li>
                        <li>

                            <?php foreach ($product_units as $pu) { ?>

                                <a href="javascript: void(0)" data-filter-column="4" data-filter-text="<?= $pu['id_pu'] ?>"><?= $pu['short_desc_pu'] ?></a>

                                <?php
                            }
                            foreach ($product_units as $pu) {
                                $departments = $dep_obj->getDepartmentsByProductUnit($pu['id_pu']);
                                if ($departments) {
                                    ?>

                                    <ul<?= ' id="filter-by-dep-of-' . $pu['id_pu'] . '"' ?> class="filter-by-dep filter-button filter-category nav nav-pills" style="display:none;">
                                        <li class="filter-arrow">
                                            <span class="glyphicon glyphicon-arrow-right"></span>
                                        </li>
                                        <li>

                                            <?php foreach ($departments as $dep) { ?>

                                                <a href="javascript: void(0)" data-filter-column="5" data-filter-text="<?= $dep['id_dep'] ?>"><?= $dep['short_desc_dep_type'] ?></a>

                                            <?php } ?>

                                        </li>
                                    </ul>

                                <?php
                                }
                            }
                            ?>

                        </li>
                    </ul>

<?php } ?>

            </li>
        </ul>


        <form class="form-inline pull-right">
            <div class="form-group search hidden-print">
                <div class="input-group">
                    <input type="text" class="form-control search-query" name="search" placeholder="Cerca...">
                    <div class="input-group-addon"><span class="glyphicon glyphicon-search"></span></div>
                </div>
            </div>
        </form>

    </div>
    
    
    <div class="row">
        <table id="users-list-table" class="table table-sorter">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cognome Nome</th>
                    <th>Funzione</th>
                    <th style="display:none">Unità Produttiva</th>
                    <th style="display:none">Reparto</th>
                    <th>Titolo corso</th>
                    <th class="{sorter: false}">&nbsp;</th>
                </tr>
            </thead>
            <tbody>

                <?php
                foreach ($learning_events as $single) {
                    $user_dep_detail = $dep_obj->getEmployeeDetail($single['user_id']);
                    ?>

                    <tr data-user_id="<?= $single['user_id'] ?>">
                        <td>(id: <?= $single['user_id'] ?>)</td>
                        <td><?= ucwords(strtolower("{$single['surname']} {$single['name']}")) ?></td>
                        <td><?= $single['function'] ?>
                        <td style="display:none;"><?= $user_dep_detail ? $user_dep_detail[0]['id_pu'] : 0 ?></td>
                        <td style="display:none;"><?= $user_dep_detail ? $user_dep_detail[0]['id_dep'] : 0 ?></td>
                        <td><?= strtoupper($single['title']) ?></td>
                        <td>
                            <?php
                            if (file_exists(BASE_MEDIA_PATH . "attestati/attestato_licenza_" . $single['user_id'] . ".pdf")) {
                                ?>

                                <a target="_blank" href="manage/render_document.php?doc_type=attestato_elearning&license_id=<?= $single['license_id'] ?>">
                                    <span class="glyphicon glyphicon-file"></span>
                                </a>

                            <?php } else { ?>

                                <a target="_blank" href="lib/genera.php?course_id=<?= $single['user_id'] ?>.pdf">
                                    <span class="glyphicon glyphicon-save"></span>
                                </a>

                                <?php 
                            }
                            ?>
                            </td>
                    </tr>

                <?php } ?>

            </tbody>
        </table>
    </div>
</div>
<script type="text/javascript">

    $(function () {

        $('#users-list-table').tablesorter({
            theme: 'greyT81',
            sortList: [[1, 0]],
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

        $('#users-list-container > ul > li > a').click(function () {
            var filter = $(this);
            filter.addClass('active').siblings('a.active').removeClass('active').siblings('ul').hide().siblings('#' + filter.data('target-subcategories')).show();
            $('#users-list-container > ul > li > ul a.active').removeClass('active');
            $('#users-list-table').trigger('filterReset');
        });

        $('#filter-by-pu > li > a').click(function () {
            var filter = $(this);
            var columns = [];
            filter.addClass('active').siblings('a.active').removeClass('active');
            $('.filter-by-dep a.active').removeClass('active');
            $('#filter-by-dep-of-' + filter.data('filter-text')).show().siblings('.filter-by-dep').hide();
            if (filter.data("filter-text") != "") {
                columns[filter.data("filter-column")] = String(filter.data("filter-text"));
                $('#users-list-table').trigger('filterReset').trigger('search', [columns]);
            } else {
                $('#users-list-table').trigger('filterReset');
            }
        });

        $('#users-list-container').on('click', '#filter-by-function a[data-filter-column], .filter-by-dep a[data-filter-column]', function () {
            var filter = $(this);
            var columns = [];
            filter.addClass('active').siblings('a.active').removeClass('active');
            if (filter.data("filter-text") != "") {
                columns[filter.data("filter-column")] = String(filter.data("filter-text"));
                $('#users-list-table').trigger('filterReset').trigger('search', [columns]);
            } else {
                $('#users-list-table').trigger('filterReset');
            }
        });
        
        $('.show-nav-filter').click(function(){
            $(this).hide();
            $('#users-list-table tbody > tr:not(.filtered').show();
            $('.nav-filter').show();
        });

        $('input[name="search"]').on('input propertychange', function () {
            var columns = [];
            columns [1] = $(this).val();
            $('#users-list-table').trigger('search', [columns]);
        });

    });

</script>