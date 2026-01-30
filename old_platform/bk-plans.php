<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000) {
    require_once '403.php';
    exit();
}

?>
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
            <div id="page-content" style="padding-top: 20px;">

                <!-- Timeline Widget -->
                <div class="widget" style="margin:  0;">
                    <div class="widget-extra themed-background-dark">
                        <div class="row">
                            <div class="col-sm-12">
                                <h2 class="text-center">
                                    <strong>Piani di abbonamento</strong>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->
                
                <!-- All Orders Block -->
                <div class="block full">
                   <table id="plans-table" cellpadding="0" cellspacing="0" border="0" class="table table-bordered table-stripped display" width="100%">
                       <thead>
                            <tr>
                                <th>Piano</th>
                                <th>Descrizione</th>
                                <th>Senza scadenza</th>
                                <th>Riservato a enti</th>
                                <th>Prezzo</th>
                                <th>Sconto</th>
                                <th>Ecommerce</th>
                                <th>Corsi personalizzati</th>
                                <th>Max ammin.</th>
                                <th>Max corsisti</th>
                                <th>Attivo</th>
                            </tr>
                        </thead>
                    </table>
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

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-double-up"></i></a>
<script src="js/vendor/plugins.js"></script>
<script src="js/pages/bk-search-form-header.js"></script>

<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
<!--<script type="text/javascript" language="javascript" src="js/vendor/datatables.min.js"></script>-->
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.22/af-2.3.5/b-1.6.5/b-colvis-1.6.5/b-html5-1.6.5/b-print-1.6.5/fc-3.3.1/fh-3.1.7/r-2.2.6/rg-1.1.2/rr-1.2.7/sc-2.0.3/sl-1.3.1/datatables.min.js"></script>

<script type="text/javascript" language="javascript" src="/js/vendor/dataTables.editor.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/plug-ins/1.10.21/dataRender/datetime.js"></script>

<script>
    $(document).ready(function() {
        var editor_plans = new $.fn.dataTable.Editor( {
                ajax: "manage/plans.php",
                table: "#plans-table",
                idSrc: "plans.id",
                fields: [ {
                        label: "Nome piano:",
                        name: "plans.short_desc_plan"
                    }, {
                        label: "Descrizione piano:",
                        name: "plans.long_desc_plan"
                    }, {
                        label: "Senza scadenza:",
                        name: "plans.no_expiration",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }, {
                        label: "Per Enti:",
                        name: "plans.for_tutor",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }, {
                        label: "Prezzo:",
                        name: "plans.plan_price"
                    }, {
                        label: "Sconto:",
                        name: "plans.discount",
                        def: 0
                    }, {
                        label: "ecommerce:",
                        name: "plans.ecommerce",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }, {
                        label: "Corsi pesonalizzati:",
                        name: "plans.customized_courses",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }, {
                        label: "N. max amministratori:",
                        name: "plans.max_admin",
                        def: 1
                    }, {
                        label: "N. max corsisti contemporanei:",
                        name: "plans.max_concurrent_users",
                        def: 0
                    }, {
                        label: "Attivo:",
                        name: "plans.active",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }   
                ],
                i18n: {
                    edit: {
                        submit: "Aggiorna"
                    }
                }  
            } );
            
            // Activate an inline edit on click of a table cell
            $('#plans-table').on( 'click', 'tbody td:not(.noEditable)', function (e) {
                // Ignore the Responsive control and checkbox columns
                if ( $(this).hasClass( 'control' ) || $(this).hasClass('select-checkbox') ) {
                    return;
                }
                editor_plans.inline( this, {
                    onBlur: 'submit'
                } );
            } );
 
            // Inline editing in responsive cell
            $('#plans-table').on( 'click', 'tbody ul.dtr-details li', function (e) {
                // Edit the value, but this selector allows clicking on label as well
                editor_plans.inline( $('span.dtr-data', this),{
                    onBlur: 'submit'
                } );
            } );
            
            companyPlansTable = $("#plans-table").DataTable({
                responsive: true,
                dom: "Bfrtip",
                ajax: "manage/plans.php",
                columns: [
                    {data: "plans.short_desc_plan"},
                    {data: "plans.long_desc_plan"},
                    {data: "plans.no_expiration",
                        render: function (val, type, row) {
                            return val === 0 ? "NO" : "SI";
                        }
                    },
                    {data: "plans.for_tutor",
                        render: function (val, type, row) {
                            return val === 0 ? "NO" : "SI";
                        }
                    },
                    {data: "plans.plan_price"},
                    {data: "plans.discount"},
                    {data: "plans.ecommerce",
                        render: function (val, type, row) {
                            return val === 0 ? "NO" : "SI";
                        }},
                    {data: "plans.customized_courses",
                        render: function (val, type, row) {
                            return val === 0 ? "NO" : "SI";
                        }},
                    {data: "plans.max_admin"},
                    {data: "plans.max_concurrent_users"},
                    {data: "plans.active",
                        render: function (val, type, row) {
                            return val === 0 ? "NO" : "SI";
                        }
                    }
                ],
                select: true,
                buttons: [
                    { extend: "edit", editor: editor_plans },
                    { extend: "create", editor: editor_plans }
                ],
                fixedHeader: true,
                order: [[3, 'asc'], [4, 'asc']],
                pageLength: 20,
                lengthMenu: [[10, 20, 30, -1], [10, 20, 30, "All"]]
            });
    });
</script>
</body>
</html>
