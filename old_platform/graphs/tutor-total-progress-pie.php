<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 30-giu-2015
 * File: tutor-total-purchases-pie.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

$tutor_id = filter_input(INPUT_GET, 'tutor_id', FILTER_SANITIZE_NUMBER_INT) ? : $_SESSION['tutor']['id'];

if ($_SESSION['user']['role'] != 1000 && (($_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) || $_SESSION['user']['tutor_id'] != $tutor_id)) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_report.php';
$report_obj = new Report();

$learning_status = $report_obj->getLearningStatusByTutorCompany($tutor_id, true);
?>
<div id="chart-total-progress-pie" class="chart"></div>

<script>

$(function(){
    AmCharts.makeChart("chart-total-progress-pie",
    {
	"type": "pie",
	"balloonText": "[[title]]<br><span style='font-size:14px'><b>[[value]]</b> ([[percents]]%)</span>",
        "labelRadius": -20,
	"labelText": "[[title]]<br>[[percents]]%",
        "pullOutRadius": 10, //"5%",
        "labelsEnabled": true,
	"colors": [
		"#23ABDD",
		"#FDD243",
		"#C3C3C3"
	],
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
			"category": "completati",
			"column-1": <?= $learning_status['finished'] ?>
		},
		{
			"category": "in corso",
			"column-1": <?= $learning_status['started'] - $learning_status['finished'] ?>
		},
		{
			"category": "non avviati",
			"column-1": <?= $learning_status['total'] - $learning_status['started']?>
		}
	]
    });
});

</script>