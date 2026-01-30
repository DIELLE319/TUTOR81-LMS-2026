<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 14-lug-2015
 * File: pages/classroom-manager.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

// 5 minutes execution time
@set_time_limit(5 * 60);

require_once BASE_LIBRARY_PATH . 'class_om.php';
$learn_obj = new T81DOM();

if (!isset($om_list) || !count($om_list)){
	$om_list = $om->getList(0);
}
?>
<h3 style="text-align: center;">Oggetti Multimediali</h3>
<ul class="nav nav-pills" style="font-size: 14px;">
    <li  <?php if($filter_list == 1){?>class="active"<?php }?>><a href="admin/objects?filter=1">Attivi</a></li>
    <li  <?php if($filter_list == 2){?>class="active"<?php }?>><a href="admin/objects?filter=2">Sospesi</a></li>
    <li  <?php if($filter_list == 0){?>class="active"<?php }?>><a href="admin/objects?filter=0">Tutti</a></li>
</ul>
<!-- 
<div id="loading">
    <p>Attendere il caricamento della tabella degli oggetti multimediali <img src="img/loading_gif.gif"></p>
</div>
-->
<div id="table_om">
    <p class="tip">
        <span class="label label-info">TIP!</span>
        ordina più colonne contemporaneamente premendo il tasto <kbd>Shift</kbd> 
        e cliccando in sequenza sull'intestazione delle colonne scelte!
    </p>
    <table class="tablesorter">
        <thead>
            <tr>
                <th style="width:55px;">Media</th>
                <th style="width:125px;">Data di crezione</th>
                <th style="width:55px;">ID</th>
                <th style="width:55px;">Pers.</th>
                <th style="width:175px;">Tipo</th>
                <th class="filter-match">Categorie</th>
                <th class="filter-select" style="width:175px;">Gruppo Argomomenti</th>
                <th class="filter-select" style="width:175px;">Argomento</th>
                <th>Titolo</th>
                <th style="max-width:175px;">Progetti attivi</th>
                <th style="width:65px">Domande</th>
                <th style="width:65px">Durata</th>
                <th class="filter-select" style="width:175px;">Livello</th>
                <th class="filter-false sorter-false">Azioni</th>
            </tr>
        </thead>
        <tbody>

<?php 
foreach ($om_list as $single) {
    $argument = $om->getArgumentByID($single['argument_id']);
    $level = $om->getLevelByID($single['level_id']);
    $categories = $om->getCategoryByObjectID($single['id']);

    if ($single['learning_object_type_id'] == 1) {
        $icon = "img/video48.png";
        $type_of = "Video";
        $questions = $om->getQuestionsByObjectID($single['id']);
    } elseif ($single['learning_object_type_id'] == 2) {
        $icon = "img/slide48.png";
        $type_of = "Slide";
        $questions = $om->getQuestionsBySlideID($single['id']);
    } elseif ($single['learning_object_type_id'] == 3) {
        $icon = "img/doc48.png";
        $type_of = "Documento";
        $questions = $om->getDocQuestions($single['id']);
    } elseif ($single['learning_object_type_id'] == 4) {
        $icon = "img/web48.png";
        $type_of = "Web";
        $questions = $om->getWebQuestions($single['id']);
    }
    ?>
                <tr data-learning_object_id="<?= $single['id'] ?>">
                    <td>
                        <img src="<?= $icon ?>" alt="<?= $type_of ?>" title="<?= $type_of ?>">
                    </td>
                    <td><?= date_format(date_create($single['creation_date']), 'Y-m-d') ?></td>
                    <td><?= $single['id'] ?></td>
                    <td class="custom"><?= $single['custom'] ? 'SI' : 'NO' ?></td>
                        <?php 
                        $type = $learn_obj->getTypeByID($single['type_id']);
                        ?>
                    <td><?= $type['description'] ?></td>
                    <td>
    <?php
    if (!empty($categories)) {
        $cat = "";
        foreach ($categories as $category) {
            if ($category['category_name'] != $cat) {
                if (!empty($cat))
                    echo "), ";
                $cat = $category['category_name'];
                echo strtoupper($category['category_name']) . "(";
            } else {
                echo ", ";
            }
            echo $category['sub_category_name'];
        }
        echo ")";
    } else {
        echo "&nbsp";
    }
    ?>
                    </td>
    <?php if (!$argument) { ?>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
    <?php } else { ?>
                        <td><?= $argument['title_group_arg'] ?></td>
                        <td><?= $argument['title_arg'] ?></td>
    <?php } ?>
                    <td>
    <?php  if ($_SESSION['user']['role'] == 1000) { ?>
                            <a href="om-management.php?id=<?= $single['id'] ?>" target="_blank" title="visualizza dettaglio"><?= $single['title'] ? $single['title'] : '- senza titolo -'; ?></a>
    <?php
    } else {
        echo $single['title'];
    }
    ?>
                    </td>
                    <td class="learning-project"><img src="img/loading_gif.gif"></td>
                    <td><?= count($questions)? : ' - ' ?></td>
                    <td><?= $single['duration'] ?></td>
                    <td><?= $level['title'] ?></td>
                    <td>
                        
                        <?php if ($single['deleted'] == 0) { ?>
                        
                        <a href="javascript: void(0)" class="delete-object"><span class="red glyphicon glyphicon-remove"></span></a>
                        
                        <?php } else { ?>
                        
                        <a href="javascript: void(0)" class="undelete-object"><span class="glyphicon glyphicon-ok"></span></a>
                                                
                        <?php } ?>
                    
                    </td>
                </tr>
<?php } ?>
        </tbody>
    </table>
</div>
<script>
    function getFilterInput(){
        var filters = [];
        $('.tablesorter-filter').each(function(index){
                filters[$(this).data("column")] = $(this).val();
        });
        return filters;
    }

    $('#table_om').on('click', '.delete-object', function(){
        var object_row = $(this).parents('tr');
        if (disableLearningOjb(object_row.data('learning_object_id'))) {
        
        <?php if ($filter_list == 0) { ?>
                
            $(this)
                .parent()
                .append('<a href="javascript: void(0)" class="undelete-object"><span class="glyphicon glyphicon-ok"></span></a>')
                .find('.delete-object')
                .remove();
    
        <?php } else { ?>
            
            object_row.remove();
            
        <?php } ?>
            
        }    
    });

    $('#table_om').on('click', '.undelete-object', function(){
        var object_row = $(this).parents('tr');
        if (enableLearningOjb(object_row.data('learning_object_id'))) {
        
        <?php if ($filter_list == 0) { ?>
                
            $(this)
                .parent()
                .append('<a href="javascript: void(0)" class="delete-object"><span class="red glyphicon glyphicon-remove"></span></a>')
                .find('.undelete-object')
                .remove();
    
        <?php } else { ?>
            
            object_row.remove();
            
        <?php } ?>
            
        }    
    });
    
    function resetEditable(){
        var editable = $('#table_om .editable');
        editable.html(editable.data('old-value')).removeClass('editable');
    }
    
    $('#table_om').on('click', '.custom:not(".editable")', function(){
        resetEditable();
        
        $(this).addClass('editable')
                .data('old-value', $(this).text())
                .html('<select class="form-control">'
                        + '<option value="1">SI</option>'
                        + '<option value="0">NO</option>'
                        + '</select>');
    });
    
    $('#table_om .custom').on('change', 'select', function(){
        alert($(this).val());
    });
    
    $(function(){
        $('#table_om > table').tablesorter({
            theme: 'blue',
            sortList: [[1,1]],
            // hidden filter input/selects will resize the columns, so try to minimize the change
            widthFixed : true,
            // initialize zebra striping and filter widgets
            widgets: ["zebra", "filter"],
            // headers: { 5: { sorter: false, filter: false } },
            widgetOptions : {
                //filter_columnFilters : false,
                // extra css class applied to the table row containing the filters & the inputs within that row
                filter_cssFilter   : '',
                // If there are child rows in the table (rows with class name from "cssChildRow" option)
                // and this option is true and a match is found anywhere in the child row, then it will make that row
                // visible; default is false
                filter_childRows: false,
                // Set this option to false to make the searches case sensitive
                filter_ignoreCase: true,
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
                filter_hideFilters : false,
                filter_functions: {
                    0: {
                        "Video": function (e, n, f, i, $r) {
                            return $r.children().eq(0).children('img').attr('alt') == "Video";
                        },
                        "Slide": function (e, n, f, i, $r) {
                            return $r.children().eq(0).children('img').attr('alt') == "Slide";
                        },
                        "Pdf": function (e, n, f, i, $r) {
                            return $r.children().eq(0).children('img').attr('alt') == "Documento";
                        },
                        "Web": function (e, n, f, i, $r) {
                            return $r.children().eq(0).children('img').attr('alt') == "Oggetto Web";
                        }

                    },
                    2: {
                        "< 100": function (e, n, f, i, $r) {
                            return n < 100;
                        },
                        "100 - 200": function (e, n, f, i, $r) {
                            return n >= 100 && n <= 200;
                        },
                        "200 - 300": function (e, n, f, i, $r) {
                            return n >= 200 && n <= 300;
                        },
                        "300 - 400": function (e, n, f, i, $r) {
                            return n >= 300 && n <= 400;
                        },
                        "400 - 500": function (e, n, f, i, $r) {
                            return n >= 400 && n <= 500;
                        },
                        "500 - 600": function (e, n, f, i, $r) {
                            return n >= 500 && n <= 600;
                        },
                        "600 - 700": function (e, n, f, i, $r) {
                            return n >= 600 && n <= 700;
                        },
                        "700 - 800": function (e, n, f, i, $r) {
                            return n >= 700 && n <= 800;
                        },
                        "800 - 900": function (e, n, f, i, $r) {
                            return n >= 800 && n <= 900;
                        },
                        "900 - 1000": function (e, n, f, i, $r) {
                            return n >= 900 && n <= 1000;
                        },
                        "> 1000": function (e, n, f, i, $r) {
                            return n > 1000;
                        }
                    },
                    3: {
                        "SI": function (e, n, f, i, $r) {
                            return e === "SI";
                        },
                        "NO": function (e, n, f, i, $r) {
                            return e === "NO";
                        }
                    },
                    4: {
                        "Generico": function (e, n, f, i, $r) {
                            return e === "Generico";
                        },
                        "Specifico": function (e, n, f, i, $r) {
                            return e === "Specifico";
                        },
                        "Demo": function (e, n, f, i, $r) {
                            return e === "Demo";
                        },
                        "Test": function (e, n, f, i, $r) {
                            return e === "Test";
                        },
                        "Generico|Specifico": function (e, n, f, i, $r) {
                            return (e === "Generico" || e === "Specifico");
                        },
                    }
                }
            }
        });

        //$('#loading').remove();
        //$('#table_om').show();

        // COLONNA 3 - PERSONALIZZATI
        $('.filter-om[data-filter-column="3"]').click(function () {
                var filters = getFilterInput();
            //$t = $(this),
            //col = $t.data('filter-column'), 
            //txt = $t.data('filter-text');
            if ($(this).is(':checked')) {
                filters["3"] = "SI";
            } else {
                filters["3"] = "NO";
            }

            $.tablesorter.setFilters($('table.hasFilters'), filters, true);
        });

        $('.tablesorter-filter[data-column="3"]').change(function () {
            $t = $('.filter-om[data-filter-column="3"]');
            if ($(this).val() === "NO") {
                $t.prop('checked', false);
            } else {
                $t.prop('checked', true);
            }
        });

        // COLONNA 4 - TIPO
        $('.filter-om[data-filter-column="4"]').click(function () {
            var filters = getFilterInput();

            if ($(this).is(':checked')) {
                filters["4"] = "Demo";
            } else {
                filters["4"] = "Generico|Specifico";
            }

            $.tablesorter.setFilters($('table.hasFilters'), filters, true);
        });

        $('.tablesorter-filter[data-column="4"]').change(function () {
            $t = $('.filter-om[data-filter-column="4"]');
            if ($(this).val() === "Demo" || !$(this).val()) {
                $t.prop('checked', true);
            } else {
                $t.prop('checked', false);
            }
        });

        // COLONNA 5 - CATEGORIE
        $('.filter-om[data-filter-column="5"]').click(function () {
            var filters = getFilterInput();
            var txt = "";

            $('.filter-om[data-filter-column="5"]').each(function () {
                if ($(this).is(':checked')) {
                    if (txt) {
                        txt += "|";
                    }
                    txt += $(this).data('filter-text');
                }
            });

            filters["5"] = txt;

            $.tablesorter.setFilters($('table.hasFilters'), filters, true);
        });

        /**
         * Carica gli id dei progetti formativi collegati agli oggetti
         */
        $.post('manage/learning_object.php',
            {
                op_type: 'get_learning_project_by_om'
            }, function(data){
                $('#table_om tbody tr .learning-project').html(' - ');
                var learning_objects = $.parseJSON(data);
                for(var index in learning_objects){
                    for (var lp in learning_objects[index]) {
                        var class_lp = learning_objects[index][lp].learning_project_published == 1 ? 'published' : '';
                        var tag_lp = '<span class="' + class_lp + '" title="' 
                                + learning_objects[index][lp].learning_project_title + '"  data-toggle="tooltip">' 
                                + learning_objects[index][lp].learning_project_id + '</span>';
                        if (lp == 0)
                            $('#table_om tbody tr[data-learning_object_id="' + index + '"] .learning-project').html(tag_lp);
                        else
                            $('#table_om tbody tr[data-learning_object_id="' + index + '"] .learning-project').append(', ' + tag_lp);
                    }
                }
                $('#table_om tbody tr .learning-project span').tooltip();
            }
        );
        

    });

</script>