<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 03-lug-2015
 * File: member-total-purchases-pie.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

$member_id = filter_input(INPUT_GET, 'tutor_id', FILTER_SANITIZE_NUMBER_INT) ? : $_SESSION['tutor']['id'];

if (($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 32) || ($_SESSION['user']['role'] == 32 && $_SESSION['user']['tutor_id'] != $member_id)) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_report.php';
$report_obj = new Report();

$companies_purchases = $report_obj->getAllPurchasesByTutor($member_id);
$tutors_purchases = $report_obj->getAllPurchasesByMemberTutors($member_id);

$qta_direct = 0;
$qta_partner = 0;
$qta_tutor = 0;
if ($companies_purchases) {
    foreach ($companies_purchases as $purchase){
        if ($purchase['is_partner']) $qta_partner += $purchase['qta'];
        else $qta_direct += $purchase['qta'];
    }
}
if ($tutors_purchases) {
    foreach ($tutors_purchases as $tutor){
        foreach ($tutor as $purchase){
            $qta_tutor += $purchase['qta'];
        }
    }
}
?>
<div id="chart-total-purchases-pie" class="chart"></div>

<script>

$(function(){
    
    AmCharts.makeChart("chart-total-purchases-pie",
    {
	"type": "pie",
	"balloonText": "[[title]]<br><span style='font-size:14px'><b>[[value]]</b> ([[percents]]%)</span>",
        "labelRadius": -20,
        "pullOutRadius": 10, //"5%",
        "labelsEnabled": true,
	"labelText": "[[title]]<br>[[percents]]%",
	"marginBottom": 0,
	"marginTop": 0,
	"titleField": "category",
	"valueField": "column-1",
	"addClassNames": true,
	"classNamePrefix": "progress",
	"allLabels": [],
	"balloon": {},
	/*"legend": {
		"align": "center",
		//"position": "right",
                "valueAlign": "right",
		"markerType": "circle"
	},*/
	"titles": [],
	"dataProvider": [
		{
			"category": "diretti",
			"column-1": <?= $qta_direct ?>
		},
		{
			"category": "partner",
			"column-1": <?= $qta_partner ?>
		},
		{
			"category": "enti formativi",
			"column-1": <?= $qta_tutor ?>
		}
	]
    });
    
});

</script>