<?php
/**
 * Created by PhpStorm.
 * User: Davide
 * Date: 01/02/2017
 * Time: 15.29
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';

$course_obj = new iWDCourse();
$course_type_obj = new T81CourseType();
$course_types = $course_obj->getCourseDetailedListOfAvailableLearningProjectByCompany($_SESSION['user']['company']['id']);
$subcategories = array (
    "lavoratore"    => array(   "order" =>  1,
                                "label" =>  "LAVORATORE",
                                "icon"  =>  "fa fa-user"
                        ),
    "preposto"      => array(   "order" =>  2,
                                "label" =>  "PREPOSTO",
                                "icon"  =>  "fa fa-file-text"
                        ),
    "dirigente"     => array(   "order" =>  3,
                                "label" =>  "DIRIGENTE",
                                "icon"  =>  "gi gi-briefcase"
                        ),
    "rspp dl"       => array(   "order" =>  4,
                                "label" =>  "RSPP DL",
                                "icon"  =>  "gi gi-old_man"
                        ),
    "rspp"          => array(   "order" =>  5,
                                "label" =>  "RSPP",
                                "icon"  =>  "gi gi-pencil"
                        ),
    "aspp"          => array(   "order" =>  6,
                                "label" =>  "ASPP",
                                "icon"  =>  "gi gi-pencil"
                        ),
    "rls"           => array(   "order" =>  7,
                                "label" =>  "RLS",
                                "icon"  =>  "gi gi-group"
                        ),
    "datore di lavoro"=> array(   "order" =>  8,
                                "label" =>  "DATORE DI LAVORO",
                                "icon"  =>  "gi gi-tie"
                        ),
    "antincendio"   => array(   "order" =>  9,
                                "label" =>  "ANTINCENDIO",
                                "icon"  =>  "gi gi-fire"
                        ),
    "primo soccorso"=> array(   "order" =>  10,
                                "label" =>  "PRIMO SOCCORSO",
                                "icon"  =>  "fa fa-plus-square"
                        ),
    "privacy"       => array(   "order" =>  11,
                                "label" =>  "PRIVACY",
                                "icon"  =>  "fa fa-lock"
                        ),
    "anticorruzione"=> array(   "order" =>  12,
                                "label" =>  "ANTICORRUZIONE",
                                "icon"  =>  "fa fa-user-secret"
                        ),
    "non definito"  => array(   "order" =>  13,
                                "label" =>  "NON DEFINITO",
                                "icon"  =>  "fa fa-question"
                        ),
    "customized"    => array(   "order" => 14,
                                "label" => "PERSONALIZZATO",
                                "icon"  => "fa fa-edit"
                        )
);
$types = array (
    "base"          => array (  "order" => 1,
                                "label" => "base",
                                "color" => "#394263"
                        ),
    "aggiornamento" => array (  "order" => 2,
                                "label" => "agg.",
                                "color" => "#1bbae1"
                        )
);
$rischio_azienda = array (
    "Tutti" => array (  "order" => 1,
                        "label" => "Tutti",
                        "color" => "#00de44"
                ),
    "basso" => array (  "order" => 2,
                        "label" => "basso",
                        "color" => "#ded300"
                ),
    "medio" => array (  "order" => 3,
                        "label" => "medio",
                        "color" => "#de6200"
                ),
    "alto"  => array (  "order" => 4,
                        "label" => "alto",
                        "color" => "#de1a00"
                )
);

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

            <header class="navbar navbar-default">
                <?php require "ecommerce/bk/search-form-header.php" ?>
            </header>
            <!-- END Header -->


            <!-- Page content -->
            <div id="page-content" style="padding-top: 20px;">

                <!-- Timeline Widget -->
                <div class="widget" style="margin: 0;">
                    <div class="widget-extra themed-background-dark">
                        <div class="row">
                            <div class="col-sm-12 text-center">
                                <h2 style="margin-bottom: 5px;"><strong> Catalogo dei corsi e-learning </strong>
                                </h2>
                                <small><strong> Scegli un corso e assegnalo a uno o pìu utenti</strong></small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->

                <!-- All Orders Block -->
                <div class="block full">
                    <div id="search_by_select">
                        <h3 class="text-center"><strong>CHE CORSO STAI CERCANDO?</strong></h3>
                        <div class="row">
                            <div class="col-sm-4">
                                <form>
                                    <div class="form-group">
                                        <label for="subcategories">Chi seguirà il corso?</label>
                                        <select class="form-control" id="filter_subcategories">
                                            <option value=""></option>
                                            <?php foreach ($subcategories as $subcategory) {?>
                                            
                                            <option value="<?=$subcategory['label']?>"><?=$subcategory['label']?></option>
                                                
                                            <?php }?>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="col-sm-4">
                                <form>
                                    <div class="form-group">
                                        <label for="types">Aggiornamento o base?</label>
                                        <select class="form-control" id="filter_types">
                                            <option value=""></option>
                                            <?php foreach ($types as $type) {?>
                                            
                                            <option value="<?=$type['label']?>"><?=$type['label']?></option>
                                                
                                            <?php }?>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="col-sm-4">
                                <form>
                                    <div class="form-group">
                                        <label for="rischio_azienda">Grado di rischio?</label>
                                        <select class="form-control" id="filter_rischio_azienda">
                                            <option value=""></option>
                                            <?php foreach ($rischio_azienda as $risk) {?>
                                            
                                            <option value="<?=$risk['label']?>"><?=$risk['label']?></option>
                                                
                                            <?php }?>
                                        </select>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- All Orders Content -->
                    <div class="table-responsive">
                        <table id="ecom-orders"
                               class="table table-responsive table-bordered table-vcenter compact">
                            <thead>
                            <tr style="color: #000000;">
                                <th>Per</th>
                                <th>Tipo</th>
                                <th>Rischio</th>
                                <th>ID</th>
                                <th>Nome Corso</th>
                                <th>Ore</th>
                                <th line-height: 15px;">Listino <?= $_SESSION['user']['role'] == 2 || $_SESSION['tutor']['is_tutor_with_single_company'] ? '' : '<br/><span style="font-size:12px;">prezzo di vendita consigliato</span>'; ?></th>
                                <th><?= $_SESSION['user']['role'] == 2 ? 'Prezzo ' : 'Tuo Costo'; ?> €</th>
                                <th><?= $_SESSION['user']['role'] == 2 || $_SESSION['tutor']['is_tutor_with_single_company'] ? 'Assegna' : 'Vendi'; ?></th>
                            </tr>
                            </thead>
                            <tbody style="font-size: 13px;">
                                
                            <?php
                            if ($course_types) {
                                foreach ($course_types as $index_row => $single) {
                                    $subcategory = $single['reserved_to'] != '' ? $subcategories['customized'] : $subcategories[strtolower($single["subcategory"])];
                                    if (!isset($single["Tipo"])) {
                                        $tipo = array ("order" => 3, "label" => "non definito", "color" => "#bfbfbf");
                                    } else {
                                        $tipo = $types[$single["Tipo"]];
                                    }
                                    if (!isset($single["Rischio Azienda"])) {
                                        $rischio = array ("order" => 5, "label" => "non definito", "color" => "#bfbfbf");
                                    } else {
                                        $rischio = $rischio_azienda[$single["Rischio Azienda"]];
                                    }
                            ?>
                                
                                <tr class="prototype">
                                    <td class="text-left courseCategory" style="background-color: #fff;" data-sort="<?= $subcategory['order'] ?>">
                                        <i style="font-size: x-large; padding: 5px;" class="<?= $subcategory['icon'] ?>"></i>
                                        <strong> <?= $subcategory['label'] ?></strong>
                                    </td>

                                    <?php 

                                    $price_list_single = $course_obj->getPriceList($single['course_id']);
                                    $price_value = isset($price_list_single[0]["price"]) ? number_format($price_list_single[0]["price"], 2, ',', ' ') : 0;

                                    try {
                                        require 'ecommerce/bk/catalog-table-columns.php';
                                    } catch (Exception $e) {
                                        echo $e->getMessage();
                                        exit(0);
                                    }

                                ?>
                                    
                                </tr>
                                
                            <?php }
                            
                            } ?>
                                
                            </tbody>
                        </table>
                    </div>
                    <!-- END All Orders Content -->
                </div>
                <!-- END All Orders Block -->

            </div>
            <!-- END Page Content -->

        </div>
        <!-- END Main Container -->

    </div>
    <!-- END Page Container -->

</div>
<!-- END Page Wrapper -->

<?php require 'ecommerce/bk/send_licences_modal.php'; ?>

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-double-up"></i></a>


<!-- Load and execute javascript code used only in this page -->
<script src="js/vendor/datatables.min.js"></script>
<script src="js/vendor/plugins.js"></script>
<script type="application/javascript" src="js/pages/bk-index.js"></script>
<script src="js/pages/tablesDatatables.js"></script>
<script>
    var ecomOrders;
    $(function () {
        TablesDatatables.init();
        var groupColumn = 0;
        ecomOrders = $('#ecom-orders').DataTable({
            "columnDefs": [
                { "visible": false, "targets": groupColumn }
            ],
            "language": {
                    "search": "<h4><strong>FILTRA I CORSI</strong> <small><em>inserisci delle parole chiave per filtrare i corsi</em></small></h4>"
                },
            "order": [[ groupColumn, 'asc' ], [1, 'asc'], [2, 'asc']],
            "paging": false,
            "autoWidth": false,
            "column": [
                { "width": "75px"  },
                { "width": "75px"  },
                null,
                null,
                { "width": "40px"  },
                { "width": "150px" },
                { "width": "150px" },
                null
            ],
            "drawCallback": function ( settings ) {
                var api = this.api();
                var rows = api.rows( {page:'current'} ).nodes();
                var last=null;

                api.column(groupColumn, {page:'current'} ).data().each( function ( group, i ) {
                    if ( last !== group ) {
                        $(rows).eq( i ).before(
                            '<tr class="group"><td colspan="8">'+group+'</td></tr>'
                        );

                        last = group;
                    }
                } );
            }
        });
        
        // Order by the grouping
        $('#ecom-orders tbody').on( 'click', 'tr.group', function () {
            var currentOrder = ecomOrders.order()[0];
            if ( currentOrder[0] === groupColumn && currentOrder[1] === 'asc' ) {
                ecomOrders.order( [ groupColumn, 'desc' ] ).draw();
            }
            else {
                ecomOrders.order( [ groupColumn, 'asc' ] ).draw();
            }
        } );
    });
    
    // filter by select
    
    
    $("#search_by_select select").on("change", function (){
        var search_value = '';
        $("#search_by_select select").each(function(){search_value += $(this).val() + ' ';});
        $("#ecom-orders_filter input").val(search_value);
        ecomOrders.search(search_value).draw(); 
    });

</script>
<!-- Load and execute javascript code used only in this page -->


</body>
</html>