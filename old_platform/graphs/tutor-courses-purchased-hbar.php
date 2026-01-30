<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 20-lug-2015
 * File: tutor-courses-purchased-hbar.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
$report_obj = new Report();
$company_obj = new T81Company();
/*
if ($_POST['type'] === 'admin') {
    $lp_purchased = $report_obj->getLearningProjectPurchasedByTutorAdmin($_POST['id']);
    $companies = $company_obj->getCompanyByTutorAdmin($_POST['id']);
} elseif ($_POST['type'] === 'tutor') {
    $lp_purchased = $report_obj->getLearningProjectPurchasedByTutorCompany($_POST['id']);
    $companies = $company_obj->getCompanyByTutorCompany($_POST['id']);
}
*/
$lp_purchased = $report_obj->getLearningProjectPurchasedByTutorCompany($_SESSION['tutor']['id']);
$companies = $company_obj->getCompanyByTutorCompany($_SESSION['tutor']['id']);

if ($lp_purchased) {

    for ($i = 0; $i < count($companies); $i++) {
        foreach ($lp_purchased as $single_lp) {
            $companies[$i]["lp_{$single_lp['id']}"] = $report_obj->getNumLearningProjectPurchasedByCompany($single_lp['id'], $companies[$i]['id']);
        }
    }


    $num_lp = $lp_purchased ? count($lp_purchased) : 0;
    $num_c = $companies ? count($companies) : 0;
    ?>

    <div id="chartdiv" style="width: 100%; height: <?= $num_c ? 140 + 40 * $num_c : 0 ?>px; background-color: #FFFFFF;" ></div>

    <!-- amCharts javascript code -->
    <script type="text/javascript">

        $(function(){
        AmCharts.makeChart("chartdiv",
            {
            "type": "serial",
            "pathToImages": "js/amcharts/images/",
            "categoryField": "category",
            "rotate": true,
            "startDuration": 0,
            "theme": "light",
            "categoryAxis": {
                "ignoreAxisWidth": true,
                "minVerticalGap": 0,
                "inside": true
            },
            "trendLines": [],
            "graphs": [

    <?php
    $lp = 0;
    foreach ($lp_purchased as $single_lp) {
        ?>

                {
                "balloonText": "[[title]]:[[value]]",
                        "fillAlphas": 1,
                        "id": "lp_<?= $single_lp['id'] ?>",
                        "title": "<?= strtoupper(substr($single_lp['title'], strpos($single_lp['title'], ' - ') + 3)) ?>",
                        "type": "column",
                        "valueField": "<?= strtoupper(substr($single_lp['title'], strpos($single_lp['title'], ' - ') + 3)) ?>"
                }<?php if ($num_lp > ++$lp) echo ','; ?>

    <?php } ?>
            ],
            "guides": [],
            "valueAxes": [
            {
            "id": "ValueAxis-1",
                    "stackType": "regular",
                    "title": "",
                    "titleBold": false
            }
            ],
            "allLabels": [],
            "balloon": {},
            "legend": {
            "useGraphSettings": true
            },
            "titles": [
            {
            "id": "Title-1",
                    "size": 14,
                    "text": ""
            }
            ],
            "dataProvider": [

    <?php
    $c = 0;
    foreach ($companies as $company) {
        ?>

                    {
                    "category": "<?= strtoupper($company['business_name']) ?>",
        <?php
        $lp = 0;
        foreach ($lp_purchased as $single_lp) {
            ?>

                        "<?= strtoupper(substr($single_lp['title'], strpos($single_lp['title'], ' - ') + 3)) ?>": <?= $company["lp_{$single_lp['id']}"] ?><?php if ($num_lp > ++$lp) echo ','; ?>

        <?php } ?>

                    }<?php if ($num_c > ++$c) echo ','; ?>

    <?php } ?>

                ]
            }
        );
    });
    </script>
<?php } ?>