<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 30-giu-2015
 * File: home-tutor.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';
if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once '403.php';
    exit();
}
require_once 'lib/class_company.php';
require_once 'lib/class_report.php';
$comp_obj = new T81Company();
$report_obj = new Report();

$companies = $comp_obj->getCompanyByTutorCompany($_SESSION['tutor']['id']);
?>
<div id="home-tutor" class="home">
    <div id="home-graphs">
        <div class="graph-progress col-sm-6">
            <h3 class="text-center">Stato di avanzamento <small><a href="javascript: void(0)"><span class="glyphicon glyphicon-refresh"></span></a></small></h3>
            <div class="graph">
                <img class="preloader" src="img/preloader-snake-blue.gif">
            </div>
        </div>
        <div class="graph-purchases col-sm-6">
            <h3 class="text-center">Acquisti <small><a href="javascript: void(0)"><span class="glyphicon glyphicon-refresh"></span></a></small></h3>
            <div class="graph">
                <img class="preloader" src="img/preloader-snake-blue.gif">
            </div>
        </div>
    </div>

    <div id="companies">
        <table class="table table-striped table-condensed">
            <thead>
                <tr>
                    <th>Ragione Sociale</th>
                    <th>telefono</th>
                    <th>email</th>
                    <th class="hidden">action<th>
                </tr>
            </thead>
            <tbody>

        <?php foreach ($companies as $company){?>

                <tr data-id="<?= $company['id'] ?>">
                    <td><?= strtoupper($company['business_name']) ?></td>
                    <td><?= $company['telephone'] ?></td>
                    <td><?= $company['email'] ?></td>
                    <td class="action">
                        <a href="company/edit?company_id=<?= $company['id'] ?>"><span class="glyphicon glyphicon-pencil"></span></a>
                        <!--
                        <a href="javascript: void(0)" class="orange"><span class="glyphicon glyphicon-ban-circle"></span></a>
                        <a href="javascript: void(0)" class="red"><span class="glyphicon glyphicon-remove-circle"></span></a>
                        -->
                    </td>
                </tr>

        <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<script>
$(function(){

    $('.graph-progress .graph').load('graphs/tutor-total-progress-pie.php');
    
    $('.graph-purchases .graph').load('graphs/member-total-purchases-pie.php');
    
    $('.graph-progress h3 a').click(function(){
        $('.graph-progress .graph')
            .html('<img class="preloader" src="img/preloader-snake-blue.gif">')
            .load('graphs/tutor-total-progress-pie.php');
    });
    
    $('.graph-purchases h3 a').click(function(){
        $('.graph-purchases .graph')
            .html('<img class="preloader" src="img/preloader-snake-blue.gif">')
            .load('graphs/member-total-purchases-pie.php');
    });
    
    $('#companies td.action > a').click(function(e){
       e.stopPropagation(); 
    });

    $('#companies tbody > tr').click(function(){
        location.href = 'company/home?company_id=' + $(this).data('id');
    });

});
</script>