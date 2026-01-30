<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 20-lug-2015
 * File: feedback-pie-text.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
$report_obj = new Report();

$is_tutor = filter_input(INPUT_POST, 'is_tutor', FILTER_VALIDATE_BOOLEAN);
$question_sentence_id = filter_input(INPUT_POST, 'question_sentence_id', FILTER_SANITIZE_NUMBER_INT);
$company_id = filter_input(INPUT_POST, 'company_id', FILTER_SANITIZE_NUMBER_INT);
$learning_project_id = filter_input(INPUT_POST, 'learning_project_id', FILTER_SANITIZE_NUMBER_INT);

if ($is_tutor)
    $feedback = $report_obj->getFeedbackAnswerByTutorAndLearningProject ($question_sentence_id, $company_id, $learning_project_id);
else
    $feedback = $report_obj->getFeedbackAnswerByCompanyAndLearningProject($question_sentence_id, $company_id, $learning_project_id);

$num_f = $feedback ? count($feedback) : 0;
if ($feedback){
?>
<div<?= ' id="chartdiv_feedback_' . $_POST['question_sentence_id'] . '"'?> style="width: 100%; height: 300px;"></div>

<!-- amCharts javascript code -->
<script type="text/javascript">
$(function(){
	AmCharts.makeChart("chartdiv_feedback_<?=$_POST['question_sentence_id']?>",{
		"type": "pie",
		"pathToImages": "http://cdn.amcharts.com/lib/3/images/",
		"balloonText": "[[title]]<br><span style='font-size:14px'><b>[[value]]</b> ([[percents]]%)</span>",
		"innerRadius": "50%",
		"titleField": "Valutazione",
		"valueField": "Quantità",
		"maxLabelWidth":100,
		"allLabels": [],
		"balloon": {},
		"legend": {
			"align": "center",
			"markerType": "circle"
		},
		"titles": [],
		"dataProvider": [

		<?php
		$f = 0;
		foreach($feedback as $answer){?>

			{
				"Valutazione": "<?= $answer['text']?>",
				"Quantità": <?=$answer['qta']?>
			}<?= $num_f > ++$f ? ',': '';?>
			
		<?php }?>
			
		]
	});
	
});
</script>
<?php } else {
    if ($is_tutor)
        $feedback = $report_obj->getFeedbackCommentByTutorAndLearningProject ($question_sentence_id, $company_id, $learning_project_id);
    else 
        $feedback = $report_obj->getFeedbackCommentByCompanyAndLearningProject($question_sentence_id, $company_id, $learning_project_id);
    if ($feedback){
?>

<div<?= ' id="comment_feedback_' . $_POST['question_sentence_id'] . '"'?>>
    <p>Numero di commenti: <?= $feedback ? count($feedback) : 0?></p>
    <ol reversed>
        
    <?php foreach ($feedback as $single_feedback){?>
        
        <li><?= $single_feedback['comment']?></li>
        
    <?php } ?>
        
    </ol>

</div>

    <?php } 
}