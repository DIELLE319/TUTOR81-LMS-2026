<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 30-giu-2015
 * File: graphs/company-progress-hbar.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
$comp_obj = new T81Company();
$report_obj = new Report();

$company = $_SESSION['company'];
$learning_status = $report_obj->getLearningStatus($company['id']);
if (!$learning_status) {
    echo '<h4>Nessun corso assegnato.</h4><br>';
    return false;
}
$num_ls = count($learning_status);
$max_status_length = 1;
foreach ($learning_status as $single_ls) {
    $max_status_length = $single_ls['total'] > $max_status_length ? $single_ls['total'] : $max_status_length;
}
?>
<div id="chartdiv" class="category-label-link" style="width: 100%; height: <?= $num_ls ? 60 + 20 * $num_ls : 0 ?>px; background-color: #FFFFFF;" ></div>
<!-- amCharts javascript code -->
<script type="text/javascript">
    
    function learningTableProgress(learning_project_id){
        $('#progress .export-pdf').addClass('disabled');
        $('#progress .export-xls').addClass('disabled');
        $('#progress-list')
            .html('<img src="img/preloader-snake-blue.gif">')
            .load('pages/sections/progress.php?learning_project_id=' + learning_project_id, function(){
                $('#progress .export-pdf').removeClass('disabled');
                $('#progress .export-xls').removeClass('disabled');
            });
        $('#collapse-progress').animate({scrollTop: $('#chartdiv').height()}, 'slow');
    }
    
    $(function(){
        var chart = AmCharts.makeChart("chartdiv",
            {
            "type": "serial",
            "pathToImages": "js/amcharts/images/",
            "categoryField": "category",
            "columnWidth": 0.65,
            "rotate": true,
            "startDuration": 1,
            "addClassNames": true,
            "theme": "light",
            "categoryAxis": {
                "axisThickness": 0,
                "gridPosition": "start",
                "gridThickness": 0,
            },
            "trendLines": [],
            "graphs": [
            {
            "balloonText": "[[title]]:[[value]]",
                    "bulletBorderThickness": 0,
                    "fillAlphas": 1,
                    "fillColors": "#5BC0DE",
                    "lineColor": "#5BC0DE",
                    "id": "finished",
                    "title": "In corso",
                    "type": "column",
                    "valueField": "in corso"
            },
            {
            "balloonText": "[[title]]:[[value]]",
                    "fillAlphas": 1,
                    "fillColors": "#43ac6a",
                    "lineColor": "#43ac6a",
                    "id": "completed",
                    "title": "Completati",
                    "type": "column",
                    "valueField": "completati"
            },
            {
            "balloonText": "[[title]]:[[value]]",
                    "bulletBorderThickness": 0,
                    "fillAlphas": 1,
                    "fillColors": "#f04124",
                    "lineColor": "#f04124",
                    "id": "not_started",
                    "title": "Non avviati",
                    "type": "column",
                    "valueField": "non avviati"
            }
            ],
            "guides": [],
            "valueAxes": [
            {
            "id": "ValueAxis-1",
                    "stackType": "regular",
			"axisThickness": 0,
			"fontSize": 0,
			"gridThickness": 0,
			"ignoreAxisWidth": true,

                    
                    <?php if ($max_status_length < 5){ ?>
                                    
                    "autoGridCount": false,
                    "gridCount": <?= $max_status_length + 1 ?>,
                    
                    <?php } ?>
                    
                    "title": "",
                    "titleBold": false
            }
            ],
            "allLabels": [],
            "balloon": {},
            "legend": {
            "useGraphSettings": true,
            "position": "right",
            "verticalGap": 5
            },
            "titles": [/*
            {
            "id": "Title-1",
                    "size": 15,
                    "text": "Stato avanzamento corsi"
            }
            */],
            "dataProvider": [

        <?php
        $num_ls = count($learning_status);
        $ls = 0;
        foreach ($learning_status as $single_ls) {
        ?>

                {
                "category": "<?=$single_ls['title']?>",
                "url": "javascript: learningTableProgress(<?= $single_ls['learning_project_id']?>);",
                "in corso": <?= $single_ls['started'] - $single_ls['finished'] ?>,
                "completati": <?= $single_ls['finished'] ?>,
                "non avviati": <?= $single_ls['total'] - $single_ls['started'] ?>
                }<?php if ($num_ls > ++$ls) echo ','; ?>

        <?php } ?>

            ]
        }
    );

    

    chart.addListener("rendered", function() {
      chart.categoryAxis.addListener("clickItem", function(event) {
        window.location.href = event.serialDataItem.dataContext.url;
      });
    });
    
});
</script>