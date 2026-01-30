<?php
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

$scelta = filter_input(INPUT_GET, 'scelta', FILTER_SANITIZE_STRING) == 'tutors' ? 'tutors' : 'companies';
$new = filter_input(INPUT_GET, 'new', FILTER_SANITIZE_STRING);

if (($scelta === 'tutors' && $_SESSION['user']['role'] != 1000) || 
        ($scelta === 'companies' && $_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32)) {
    require_once '403.php';
    exit();
}

require_once BASE_LIBRARY_PATH . 'class_company.php';
$comp_obj = new T81Company();

$companies = $scelta === 'tutors' ? $comp_obj->getBusinessTutor() : 
    ($_SESSION['user']['role'] == 1000 ? $comp_obj->getAllCompanies(): $comp_obj->getCompanyByTutorCompany($_SESSION['tutor']['id']));

require_once 'ecommerce/bk/header.php'; ?>

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
                                    <strong>
                                
                                <?php if ($scelta == "tutors") {
                                    echo "Elenco enti formativi";
                                } elseif ($scelta == "companies") {
                                    echo "Elenco clienti";
                                }?>
                                        
                                    </strong>
                                </h2>
                            
                   
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END Timeline Widget -->
                
                <!-- All Orders Block -->
                <div class="block full">
                   <table id="companies-table" class="table-bordered table-striped">
                       <thead>
                            <tr>
                                <th class="text-center">Ragione sociale</th>
                                <th class="text-center" data-orderable="false">Indirizzo</th>
                                <th class="text-center" data-orderable="false">Telefono</th>
                                <th class="text-center" data-orderable="false">Email</th>
                                <th class="text-center" data-orderable="false">Abbonamento</th>
                                <th data-orderable="false">Invia Attestato</th>
                                <th class="text-center" data-orderable="false">
                                    <span class="glyphicon glyphicon-plus"></span>
                                    <br>
                                    <a href="javascript: void(0);" id="add-company">azienda</a>
                                    
                                <?php if ($_SESSION['user']['role'] == 1000) { ?>
                                    
                                    <br>
                                    <a href="javascript: void(0);" id="add-tutor">ente</a>
                                    
                                <?php } ?>
                                    
                                </th>
                            </tr>
                        </thead>
                        <tbody>

                    <?php
                    if ($companies) {
                        foreach ($companies as $company){
                            //if ($company['suspended'] >= 1 || strtotime('now') > strtotime($company['validity_end'])) continue;
                            if ($company['suspended'] >= 1){ 
                                continue; 
                                
                            }
                            $delete = false;
                            $users = $comp_obj->getAllUsersCompanyByID($company['id']);
                            if (!$users) {
                                $companies = $comp_obj->getCompanyByTutorCompany($company['id']);
                                if (!$companies) {
                                    $purchases = $comp_obj->getPurchaseByCompany($company['id']);
                                    if (!$purchases) $delete = true;
                                }
                            }?>

                            <tr data-company_id="<?= $company['id'] ?>">
                                <td><?= strtoupper($company['business_name']) ?></td>
                                <td><?= $company['address'] ?></td>
                                <td><?= $company['telephone'] ?></td>
                                <td><?= $company['email'] ?></td>
                                <td><?= $company['short_desc_plan'] ?></td>
                                <td class="send_certificate <?= $company['send_certificate'] > 0 ? 'send' : 'unsend' ?>">
                                    <a href="javascript: void(0)">
                                        <span class="glyphicon glyphicon-<?= $company['send_certificate'] > 0 ? 'check' : 'unchecked' ?>"></span>
                                    </a>
                                </td>
                                <td class="action text-right" style="width: 40px;">
                                    <?= $delete ? '<a href="javascript: void(0)" class="delete"><span class="glyphicon glyphicon-remove red"></span></a>' : '' ?>

                                    <?php if ($_SESSION['user']['company']['is_tutor'] == "1") { ?>

                                    <a href="javascript: void(0)" class="edit"><span class="glyphicon glyphicon-pencil"></span></a>

                                    <?php } ?>

                                </td>
                            </tr>

                    <?php                     
                        } 
                    }
                    ?>

                        </tbody>
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
<!--
<!-- ------------------------------------------------------------------------------------------------------------->
<!-- MODAL CREA COMPANY -- MODAL CREA COMPANY -- MODAL CREA COMPANY -- MODAL CREA COMPANY -- MODAL CREA COMPANY -->
<!-- ---------------------------------------------------------------------------------------------------------- -->
<div id="companyModal" class="modal fade" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        </div>
    </div>
    
</div>

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-double-up"></i></a>
<script src="js/vendor/datatables.min.js"></script>
<script src="js/vendor/plugins.js"></script>
<script src="js/pages/bk-search-form-header.js"></script>
<!-- Load and execute javascript code used only in this page -->
<!-- <script type="application/javascript" src="js/pages/bk-index.js"></script> -->
<script src="js/pages/tablesDatatables.js"></script>
<script>
    $(function(){
        TablesDatatables.init();
    
<?php if (!empty($new)) { ?>

        $('#companyModal').modal().find('.modal-content').load('modals/new-<?= $new ?>.php');

<?php } ?>
    
        /* ******** MODAL CREA NUOVA COMPANY ********** */
        $('#add-company').click(function(e){
           e.preventDefault();
           e.stopPropagation();
           $('#companyModal').modal().find('.modal-content').load('modals/new-company.php');
        });
        
        /* ******** MODAL CREA NUOVO ENTE ********** */
        $('#add-tutor').click(function(e){
           e.preventDefault();
           e.stopPropagation();
           $('#companyModal').modal().find('.modal-content').load('modals/new-tutor.php');
        });
    
        /* Apre la modal per la modifica dell'azienda */
        $('#companies-table').on('click', 'tbody > tr .action .edit', function(){
            var company_id = $(this).parents('tr').data('company_id');
            $('#companyModal').modal()
                    .find('.modal-content')
                    .html('<img src="img/loading_gif.gif" />')
                    .load('modals/edit-company.php?company_id=' + company_id);
        }); 

        /* Elimina l'azienda */
        $('#companies-table').on('click', 'tbody > tr .action .delete',function(){
            var company_id = $(this).parents('tr').data('company_id');
            var company_name = $(this).parents('tr').children().first().text();
            var deleted = false;
            if (confirm("vuoi eliminare l'azienda " + company_name + "?")) { 
                deleted = deleteCompany(company_id);
            }
            if (deleted) location.reload();
        });
        
        $('#companyModal').on('hidden.bs.modal', function (e) {
            $(this).find('.modal.content').empty();
        });
        
        $('#companies-table').on('click', 'td.send_certificate > a',function(){
            var selected = $(this);
            var send_certificate = selected.parents('td').hasClass('send');
            var box_text = send_certificate ? "impedire l'invio dell'" : "inviare l'";
            var company_id = selected.parents('tr').data('company_id');
            
            bootbox.confirm("Vuoi "+box_text+"attestato direttamente al corsista?",
                function(result){
                    if (result) {
                        $.post('manage/company.php',
                        {
                            op_type: 'set_send_certificate',
                            comp_id: company_id,
                            send_certificate: !send_certificate
                        }, function(data){if (data > 0) {
                            if (send_certificate) {
                                selected.parents('td')
                                    .removeClass('send')
                                    .addClass('unsend');
                                selected.replaceWith('<a href="javascript: void(0)"><span class="glyphicon glyphicon-unchecked"></span></a>');
                            } else {
                                selected.parents('td')
                                    .removeClass('unsend')
                                    .addClass('send');
                                selected.replaceWith('<a href="javascript: void(0)"><span class="glyphicon glyphicon-check"></span></a>');
                            }
                        }


                    });
                }
            });
        });
    });
</script>
</body>
</html>