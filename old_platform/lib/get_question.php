<?php
require_once 'sanitize.php';
require_once 'class_learning_question.php';

$question_id = sanitize($_POST['question_id'], INT);
$learn_obj_type = sanitize($_POST['learn_obj_type'], INT);
$quest_obj = new Tutor81QuestionObj();
$question = $quest_obj->getQuestionDetail($question_id);
$answers_list = $quest_obj->getAnswersByQuestionID($question_id);

?>
<script>
    function Close(){
        
    }
</script>


<h3>Domanda:</h3>
<p class="lead">
	<?=$question['text']?>
</p>
<?php
$i = 0;
    foreach($answers_list as $answer){?>
<label class="radio"> <input type="radio" name="optionsRadios"
	id="optionsRadios<?=$i?>" value="<?=$answer['id']?>"> <?=$answer['text']?>
</label>
<?php      $i++;
    }   ?>
<br />
<br />
<p>
	<a class="btn btn-primary" href="#" id="btn_answer" onclick="Close()"><i
		class="icon-eye-close icon-white"></i> Chiudi</a>
</p>
