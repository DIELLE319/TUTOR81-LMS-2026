<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 30-giu-2015
 * File: graphs/tutor-total-purchases-bar.php 
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
	"type": "serial",
	"categoryField": "category",
	"autoMarginOffset": 0,
	"autoMargins": false,
	"marginBottom": 0,
	"marginLeft": 0,
	"marginRight": 0,
	"marginTop": 0,
	"categoryAxis": {
		"autoGridCount": false,
		"axisThickness": 0,
		"gridColor": "#FFFFFF",
		"gridThickness": 0,
		"labelsEnabled": false,
		"minHorizontalGap": 0,
		"minVerticalGap": 0,
		"tickLength": 0

	},
	"trendLines": [],
	"graphs": [
		{
			"balloonText": "[[title]] :[[value]]",
			"fillAlphas": 1,
			"fillColors": "#5BC0DE",
			"id": "AmGraph-1",
			"lineColor": "#5BC0DE",
			"title": "In corso",
			"type": "column",
			"valueField": "column-1"
		},
		{
			"balloonText": "[[title]] :[[value]]",
			"fillAlphas": 1,
			"fillColors": "#43ac6a",
			"id": "AmGraph-2",
			"lineColor": "#43ac6a",
			"title": "Completati",
			"type": "column",
			"valueField": "column-2"
		},
		{
			"balloonText": "[[title]] :[[value]]",
			"fillAlphas": 1,
			"fillColors": "#f04124",
			"id": "AmGraph-3",
			"lineColor": "#f04124",
			"title": "Non avviati",
			"type": "column",
			"valueField": "column-3"
		}
	],
	"guides": [],
	"valueAxes": [
		{
			"id": "ValueAxis-3",
			"autoGridCount": false,
			"axisThickness": 0,
			"gridThickness": 0,
			"labelsEnabled": false,
			"tickLength": 0
		}
	],
	"allLabels": [],
	"balloon": {},
	"legend": {
		"useGraphSettings": true
	},
	"titles": [],
	"dataProvider": [
		{
			"category": "category 1",
			"column-1": <?= $learning_status['started'] - $learning_status['finished'] ?>,
			"column-2": <?= $learning_status['finished'] ?>,
			"column-3": <?= $learning_status['total'] - $learning_status['started']?>
		}
	]
    });
});

</script>