<?php
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ( $_SESSION['user']['role'] != 1000 ) {
    require_once '403.php';
    exit();
}

require_once 'ecommerce/bk/header.php'; ?>

<body style="background-color: white;">

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
                                    <strong>
                                        Piani di abbonamento delle aziende
                                    </strong>
                                </h2>
                            
                   
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->
                
                <!-- All Orders Block -->
                <div class="block full">
                    <table id="company-plans-table" cellpadding="0" cellspacing="0" border="0" class="table table-bordered table-stripped display" width="100%">
                        <thead>
                            <tr>
                                <th>Azienda</th>
                                <th>Tipo Azienda</th>
                                <th>Piano</th>
                                <th>Data inizio</th>
                                <th>Data fine</th>
                                <th>Sconto</th>
                                <th>Ecommerce</th>
                                <th>Corsi pers.</th>
                                <th>N. max amm.</th>
                                <th>N. max corsisti cont.</th>
                                <th>Sospeso</th>
                                <th>Prezzo</th>
                                <th>Fatturato</th>
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
<!--<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>-->
<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>

<script src="js/vendor/datatables.min.js"></script>
<!--<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.22/b-1.6.5/b-colvis-1.6.5/b-html5-1.6.5/b-print-1.6.5/cr-1.5.2/fc-3.3.1/fh-3.1.7/r-2.2.6/sc-2.0.3/sl-1.3.1/datatables.min.js"></script>

<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/responsive/2.2.6/js/dataTables.responsive.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.4/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/select/1.3.1/js/dataTables.select.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/fixedheader/3.1.7/js/dataTables.fixedHeader.min.js"></script>-->
<script type="text/javascript" language="javascript" src="/js/vendor/dataTables.editor.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/plug-ins/1.10.21/dataRender/datetime.js"></script>

<!-- Load and execute javascript code used only in this page -->
<!-- <script type="application/javascript" src="js/pages/bk-index.js"></script> -->
<script>
    $(document).ready(function() {
        var editor_company_plans = new $.fn.dataTable.Editor( {
                ajax: "manage/company_plans.php?suspended=<?= $selected === "company-plans-suspended" ? "true" : "false" ?>",
                table: "#company-plans-table",
                idSrc: "company_plans.id",
                fields: [ {
                        label: "Piano:",
                        name: "company_plans.plan_id",
                        type: "select",
                        placeholder: "Seleziona un piano"
                    }, {
                        label:"Data inizio:",
                        name: "company_plans.validity_start",
                        type: 'datetime',
                        def: function () { return new Date(); },
                        displayFormat: 'DD/MM/YYYY',
                        wireFormat: 'YYYY-MM-DD',
                        keyInput: false
                    }, {
                        label: "Data fine:",
                        name: "company_plans.validity_end",
                        type: 'datetime',
                        def: function () { return new Date(); },
                        displayFormat: 'DD/MM/YYYY',
                        wireFormat: 'YYYY-MM-DD',
                        keyInput: false
                    }, {
                        label: "Sconto:",
                        name: "company_plans.discount",
                        def: 0
                    }, {
                        label: "ecommerce:",
                        name: "company_plans.ecommerce",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }, {
                        label: "Corsi pesonalizzati:",
                        name: "company_plans.customized_courses",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }, {
                        label: "N. max amministratori:",
                        name: "company_plans.max_admin",
                        def: 1
                    }, {
                        label: "N. max corsisti contemporanei:",
                        name: "company_plans.max_concurrent_users",
                        def: 0
                    }, {
                        label: "Sospesa:",
                        name: "company_plans.suspended",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }, {
                        label: "Prezzo:",
                        name: "company_plans.price"
                    }, {
                        label: "Fatturato:",
                        name: "company_plans.invoiced",
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
                    },
                    remove: {
                        button: "Elimina",
                        title:  "Eliminare il piano",
                        submit: "Eliminare",
                        confirm: {
                            1: "Sei sicuro di eliminare il piano di abbonamento selezionato?"
                        }
                    }
                } 
            } );
            
        var create_company_plans = new $.fn.dataTable.Editor( {
                ajax: "manage/company_plans.php?suspended=<?= $selected === "company-plans-suspended" ? "true" : "false" ?>",
                table: "#company-plans-table",
                idSrc: "company_plans.id",
                fields: [ {
                        label: "Azienda:",
                        name: "company_plans.company_id",
                        type: "select",
                        placeholder: "Seleziona un azienda"
                    }, {
                        label: "Piano:",
                        name: "company_plans.plan_id",
                        type: "select",
                        placeholder: "Seleziona un piano"
                    }, {
                        label:"Data inizio:",
                        name: "company_plans.validity_start",
                        type: 'datetime',
                        def: function () { return new Date(); },
                        displayFormat: 'DD/MM/YYYY',
                        wireFormat: 'YYYY-MM-DD',
                        keyInput: false
                    }, {
                        label: "Data fine:",
                        name: "company_plans.validity_end",
                        type: 'datetime',
                        def: function () { return new Date(); },
                        displayFormat: 'DD/MM/YYYY',
                        wireFormat: 'YYYY-MM-DD',
                        keyInput: false
                    }, {
                        label: "Sconto:",
                        name: "company_plans.discount",
                        def: 0
                    }, {
                        label: "ecommerce:",
                        name: "company_plans.ecommerce",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }, {
                        label: "Corsi pesonalizzati:",
                        name: "company_plans.customized_courses",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }, {
                        label: "N. max amministratori:",
                        name: "company_plans.max_admin",
                        def: 1
                    }, {
                        label: "N. max corsisti contemporanei:",
                        name: "company_plans.max_concurrent_users",
                        def: 0
                    }, {
                        label: "Sospesa:",
                        name: "company_plans.suspended",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }, {
                        label: "Prezzo:",
                        name: "company_plans.price"
                    }, {
                        label: "Fatturato:",
                        name: "company_plans.invoiced",
                        type: "radio",
                        options: [
                            { label: "NO", value: 0 },
                            { label: "SI", value: 1 }
                        ],
                        def: 0
                    }     
                ],
                i18n: {
                    create: {
                        submit: "Aggiungi"
                    }
                } 
            } );
            
            // Activate an inline edit on click of a table cell
            $('#company-plans-table').on( 'click', 'tbody td:not(.noEditable)', function (e) {
                // Ignore the Responsive control and checkbox columns
                if ( $(this).hasClass( 'control' ) || $(this).hasClass('select-checkbox') ) {
                    return;
                }
                editor_company_plans.inline( this, {
                    onBlur: 'submit'
                } );
            } );
 
            // Inline editing in responsive cell
            $('#company-plans-table').on( 'click', 'tbody ul.dtr-details li', function (e) {
                // Edit the value, but this selector allows clicking on label as well
                editor_company_plans.inline( $('span.dtr-data', this),{
                    onBlur: 'submit'
                } );
            } );
            
            companyPlansTable = $("#company-plans-table").DataTable({
                responsive: true,
                dom: "Bfrtip",
                ajax: "manage/company_plans.php?suspended=<?= $selected === "company-plans-suspended" ? "true" : "false" ?>",
                columns: [
                    {data: "companies.business_name", className: "noEditable"},
                    {data: "companies.is_tutor", className: "noEditable",
                        render: function (val, type, row) {
                            return val === 0 ? "Azienda" : "Ente formativo";
                        }
                    },
                    {data: "plans.short_desc_plan", editField: "company_plans.plan_id"},
                    {data: "company_plans.validity_start", render: $.fn.dataTable.render.moment( 'DD/MM/YYYY' ) },
                    {data: "company_plans.validity_end", render: $.fn.dataTable.render.moment( 'DD/MM/YYYY' ) },
                    {data: "company_plans.discount"},
                    {data: "company_plans.ecommerce",
                        render: function (val, type, row) {
                            return val === 0 ? "NO" : "SI";
                        }},
                    {data: "company_plans.customized_courses",
                        render: function (val, type, row) {
                            return val === 0 ? "NO" : "SI";
                        }},
                    {data: "company_plans.max_admin"},
                    {data: "company_plans.max_concurrent_users"},
                    {data: "company_plans.suspended",
                        render: function (val, type, row) {
                            return val === 0 ? "NO" : "SI";
                        },
                        className: 'editable'},
                    {data: "company_plans.price"},
                    {data: "company_plans.invoiced",
                        "render": function (val, type, row) {
                            return val === 0 ? "NO" : "SI";
                        }}
                ],
                select: true,
                buttons: [
                    {
                        extend: "create", 
                        editor: create_company_plans ,
                        text: "Nuovo",
                        formTitle: "Associa nuovo piano ad azienda"
                    },
                    {
                        extend: "edit", 
                        editor: editor_company_plans ,
                        text: "Modifica",
                        formTitle: function ( editor, dt ) {
                            // Get the data for the row and use a property from it in the
                            // form title
                            var rowData = dt.row({selected:true}).data();

                            return 'Modifica abbonamento di '+rowData.companies.business_name;
                        }
                    },
                    { extend: "remove", editor: editor_company_plans }
                ],
                fixedHeader: true,
                pageLength: 20,
                lengthMenu: [[10, 20, 30, -1], [10, 20, 30, "All"]]
            });
    });
</script>
</body>
</html>