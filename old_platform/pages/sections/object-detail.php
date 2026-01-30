<?php

    require_once 'config.php';
    require_once 'lib/class_om.php';
    require_once 'lib/class_learning_question.php';
    //$learn_obj_id = sanitize($_POST['id'], INT);
    $learn_obj = new T81DOM();
    $question_obj = new Tutor81QuestionObj();
    $learn_obj_elem = $learn_obj->getLearningObjectByID($learn_obj_id);
    $category_list = $learn_obj->getCategoryByObjectID($learn_obj_id);
    //$question_list = $learn_obj->getQuestionsByObjectID($learn_obj_id);
		$learn_in_use = $learn_obj->checkInUse($learn_obj_id);
    
    if($learn_obj_elem['learning_object_type_id'] == 1){
        $question_list = $learn_obj->getQuestionsByObjectID($learn_obj_id);
        $learn_obj_type = 1;
        $icon = "img/video48.png";
        $path = "video_test/";
        //$file_path = $base_media_path."user_store/".$learn_obj_elem['owner_user_id']."/learning_objects/".$path.$learn_obj_elem['server_video_filename'];
        $file_path_orig = "media/user_store/".$learn_obj_elem['owner_user_id']."/learning_objects/".$path.$learn_obj_elem['server_video_filename'];
        $file_path_webm = "media/video/web/".$learn_obj_elem['server_video_filename'].".webm";
        $file_path_mp4 = "media/video/mp4/".$learn_obj_elem['server_video_filename'].".mp4";
        $file_path_ogg = "media/video/ogg/".$learn_obj_elem['server_video_filename'].".ogv";
    
    }elseif($learn_obj_elem['learning_object_type_id'] == 2){
    	
    	// ----- Lista slide ------------------------------------------------------------------------------------
    	$slide_list = $learn_obj->getSlideImageByObjectID($learn_obj_id);
    	// ------------------------------------------------------------------------------------------------------
    	
        $question_list = $learn_obj->getQuestionsBySlideID($learn_obj_id);
        $get_slide_filename =  $learn_obj->getSlideImageByObjectID($learn_obj_id);
        $icon = "img/slide48.png";
        $path = "slide_test/";
        $learn_obj_type = 2;
        $file_path = "media/user_store/".$learn_obj_elem['owner_user_id']."/learning_objects/".$path."images_of_".$learn_obj_elem['server_filename_slide_pdf']."/";
        //$file_encoded = base64_encode($file_path);
    }elseif($learn_obj_elem['learning_object_type_id'] == 3){
        $question_list = $learn_obj->getDocQuestions($learn_obj_id);
        $icon = "img/doc48.png";
        $path = "documents/";
        $file_path = "media/user_store/".$learn_obj_elem['owner_user_id']."/learning_objects/".$path.$learn_obj_elem['server_document_filename'];
        $learn_obj_type = 3;
        //$file_encoded = base64_encode($file_path);
    }elseif($learn_obj_elem['learning_object_type_id'] == 4){
        $question_list = $learn_obj->getWebQuestions($learn_obj_id);
        $icon = "img/web48.png";
        $path = "web/";
        $file_path = "media/user_store/".$learn_obj_elem['owner_user_id']."/learning_objects/".$path.$learn_obj_elem['server_web_name'];
        $learn_obj_type = 4;
        $file_encoded = base64_encode($file_path);
    }
    
    if($learn_obj_elem['published_in_ecommerce'] == 1){
    	$icon_ecommerce = "img/ecommerce.png";
    } else {
    	$icon_ecommerce = "img/ecommerce-disabled.png";
    }
    
    if($learn_obj_elem['language_id'] == 1){
    	$icon_language = "img/flag/it.png";
    } elseif($learn_obj_elem['language_id'] == 2) {
    	$icon_language = "img/flag/gb.png";
    }
    
    
?>
		
<script type="text/javascript">
	$(function(){
		
	<?php if ($learn_obj_type == 2){?>

		$('#slideGallery').galleryView({
			panel_width: 480,
			panel_height: 360,
			frame_width: 120,
			frame_height: 90,
			show_captions: true,
			transition_speed: 500
		});
		
	<?php }?>
		
	});
</script>
<script type="text/javascript">

    function saveQuestion(){
        var txt_question = $("#question_txt").val();
        var rowCount = $('#table_answers tr').length;
        var count_right = 0;
        answers = new Array();
        answers_correct = new Array();
        for (var i = 1; i < rowCount + 1; i++){ 
            answers.push($("#answer_"+i).val());
            if ($("#correct_"+i).prop('checked')){
                answers_correct.push(1);
                count_right++;
            }else{
                answers_correct.push(0);
            }
        }
        
        if((txt_question != "") && (count_right > 0)){
            $("#save_question").hide();
            var question_time = 0;
            <?php if ($learn_obj_type ==1){?>
                question_time = $("#sectostart").html()*1000;
            <?php } elseif ($learn_obj_type ==2){ ?>
                question_time = $("#sectostart").val();
            <?php } ?>
            $.post('lib/manage_learning_object.php',{op_type : "new_question", 
                learn_obj_type: <?=$learn_obj_type?>,
                learn_obj_id: <?=$learn_obj_id?>,
                question_time: question_time,
                question_txt: txt_question,
                answers_txt: answers,
                answers_correct_txt: answers_correct},function(data){
                if(data > 0){
                    alert('L\'inserimento è avvenuto con successo');
                    window.location.href = 'om-management.php?id=<?=$learn_obj_id?>';
                }
            });
        }else{
            $("#save_question").show();
            alert('Si prega di inserire il testo della domanda e almeno una risposta esatta');
        }
    }
    
    function addNewAnswer(){
        var rowCount = $('#table_answers tr').length;
        rowCount = rowCount + 1;
        $('#table_answers').append('<tr id="row_new_'+rowCount+'"><td><input style="width:420px; margin-bottom: 10px !important;" type="text" id="answer_'+rowCount+'" /></td><td><input id="correct_'+rowCount+'" type="checkbox" style="margin-left:10px; vertical-align: top;"/> corretta</td></tr>');
    }
    
    function addNewQuestion(){
        <?php if($learn_obj_elem['learning_object_type_id'] == 1){?>

        var myPlayer = jwplayer($('#video-preview div[id|="video"].active').attr("id"));
				
        <?php } ?>
        if($("#add_question").hasClass('btn_active')){
            $("#add_question").removeClass('btn_active');
            $("#add_question").html("Aggiungi domanda");
            $("#answer_box").slideUp();
            <?php if($learn_obj_elem['learning_object_type_id'] == 1){?>
            
            myPlayer.play();
            
        		<?php } ?>
        }else{
            $("#add_question").addClass('btn_active');
            $("#add_question").html("Annulla inserimento");
            $("#answer_box").slideDown();
            <?php if($learn_obj_elem['learning_object_type_id'] == 1){?>

            $("#sectostart").html(Math.floor(myPlayer.getPosition()));
            myPlayer.pause();
            
            <?php } ?>
        }
    }
    
    function fancybox(elem,n) {
        selectedSlide(n);
        elem = $(elem);
        if (!elem.data("fancybox")) {
            elem.data("fancybox", true);
            elem.fancybox({
                'overlayColor' : '#000',
                'overlayOpacity' : 0.5
            });
            elem.fancybox().trigger('click');
        }
         return false; 
    }

    function updateTimeQuestion(quest_id){
        var time = $("#time_"+quest_id).val()*1000;
        $.post("lib/manage_learning_object.php",{op_type: "update_question_time", quest_id: quest_id, time_question :time},function(data){
            alert("Tempo aggiornato con successo");
            window.location.href = 'om-management.php?id=<?=$learn_obj_id?>';
        }); 
    }

	// ----- FUNZIONE PER SLIDE ---------------------------------------------------------------------------------------
	/* Funzione per modificare la posizione delle slide.
	 * verifica che la posizione scelta sia compatibile con il numero totale di slide+domande
	*/
    function updateSlideQuestionPosition(slide_id, max_slides){
    	var position = $("#position_"+slide_id).val();
        if (position<1 || position><?=count($slide_list)-1?>) {
			alert("Posizione selta non corretta");
        } else {
			$.post("lib/manage_learning_object.php",{op_type: "update_slide_question_position", slide_id:slide_id, position:position},function(data){
	            alert("Posizione domanda aggiornata con successo");
	        });
        } 
    }
	// -----------------------------------------------------------------------------------------------------------------
	
    function selectedSlide(n){
        $(".all_slide").removeClass("slide_selected");
        $(".slide_"+n).addClass("slide_selected");
        $("#sectostart").html(n);
    }

	// ----- EDIT TAGS -----------------------------------------------------------------------------------------------
	function showEditTags(){
		$('.def-list').hide();
		$('.edit-list').show();
	}

	function showDefTags(){
		$('.def-list').show();
		$('.edit-list').hide();
	}

	function validateForm(){
		if(!$('#cat_obj').val()){
			alert('Selezionare almeno una categoria');
			return false;
		} else if (!$('#type_obj').val()){
			alert('Selezionare almeno un tipo');
			return false;
		} else {
			return true;
		}
		
	}
	
	function saveModifiedTags(){
		if (!validateForm()){
			return false;
		}
		var argument_id = $('#arg_obj').val();
		var level_id = $('#level_obj').val();
		var obj_cat = $('#cat_obj').val();
		var type_id = $('#type_obj').val();
		var custom = $('#custom:checked').length;
		$.post("lib/manage_learning_object.php",{op_type: "edit_learn_tags",
																						id: <?=$learn_obj_id?>,
																						argument_id: argument_id,
																						level_id: level_id,
																						obj_cat: obj_cat,
																						type_id: type_id,
																						custom: custom},function(data){
            if (data > 0){
                alert('L\'oggetto formativo è stato modificato con successo');
                window.location.href = 'om-management.php?id=<?=$learn_obj_id?>';
            }
        });    
		
		}

	// ------ EDIT OBJECT -------------------------------------------------------------------------------------------------
	function showEditObject(){
		$('.show-obj').hide();
		$('.edit-obj').show();
	}

	function showObject(){
		$('.show-obj').show();
		$('.edit-obj').hide();
	}

	function saveModifiedObject(){
		if (!validateForm()){
			return false;
		}
		var title = $('#title_obj').val();
		var duration = $('#dura_obj').val();
		var battute = $('#battute_obj').val();
		var percentage = $('#perc_obj').val();
		var published = $('input[name="published"]').prop('checked') ? 1 : 0;
		var language_id = $('input[name="language_id"]:checked').val();
		var description = $('#descr_obj').val();
		var argument_id = $('#arg_obj').val();
		var level_id = $('#level_obj').val();
		var obj_cat = $('#cat_obj').val();
		var type_id = $('#type_obj').val();
		var custom = $('#custom:checked').length;
		$.post("lib/manage_learning_object.php",{op_type: "edit_learn",
				id: <?=$learn_obj_id?>,
				language_id: language_id,
				title: title,
				duration: duration,
				battute: battute,
				percentage: percentage,
				description: description,
				argument_id: argument_id,
				level_id: level_id,
				obj_cat: obj_cat,
				type_id: type_id,
				custom: custom},function(data){
					if (data > 0){
						alert('L\'oggetto formativo è stato modificato con successo');
						window.location.href = 'om-management.php?id=<?=$learn_obj_id?>';
					}
		});    
	}
    
</script>
<style>
    .slide_selected{border:3px solid #bb0c2a !important}
</style>
 
<div class="clearfix show-obj">
    <button class="btn btn-default" onclick="showEditObject()">Modifica</button>
    <?php if ($learn_obj_elem['deleted']){?>
    	<button class="btn btn-default" onclick="enableLearningOjb()" >Abilita</button>
    <?php } else {?>
    	<button class="btn btn-danger" onclick="disableLearningOjb()">Disabita</button>
    <?php }?>
</div>

<div class="clearfix edit-obj" style="display:none">
  <button class="btn btn-primary" onclick="saveModifiedObject()">Salva</button>
	<button class="btn btn-default" onclick="showObject()">Annulla</button>
</div>
  	
<h1 class="title_detail show-obj">
	<img src="<?=$icon?>"/> 	 
	(id: <?=$learn_obj_elem['id']?>)
	<?=$learn_obj_elem['title']?>
	<img src="img/duration.png"/>
	<span>(<?=$learn_obj_elem['duration']?> min)</span>
	<span>(<?=$learn_obj_elem['battute']?> battute)</span>
	<img src="img/percentage_correct.png"/>
	<span>(<?=$learn_obj_elem['percentage_correct_answer_to_pass']?> % )</span>
	<img src="<?=$icon_ecommerce?>"/>
	<img src="<?=$icon_language?>"/>
	
</h1>
    
<div class="show-obj">
	<?=$learn_obj_elem['description']?>
</div>

<div class="control-group edit-obj" style="display: none;">
 	<div class="controls">
		<img src="<?=$icon?>"/> 
		<span class="title_detail">(id: <?=$learn_obj_elem['id']?>)</span>
    <input type="text" id="title_obj" name="obj_title" value="<?=$learn_obj_elem['title']?>" style="width: 300px;" title="titolo">
		<img src="img/duration.png"/>
    <input type="text" id="dura_obj" name="obj_dura" value="<?=$learn_obj_elem['duration']?>" style="width: 30px;" title="durata (in minuti)">
    <span> min</span>
    &nbsp;
    <input type="text" id="battute_obj" name="obj_battute" value="<?=$learn_obj_elem['battute']?>" style="width: 30px;" title="battute">
    <span> battute</span>
		<img src="img/percentage_correct.png"/>
    <select id="perc_obj" style="width: 70px;" title="% risposte corrette">
    	<option value="10"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 10 ? ' selected' : ''?>>10</option>
    	<option value="15"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 15 ? ' selected' : ''?>>15</option>
      <option value="20"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 20 ? ' selected' : ''?>>20</option>
      <option value="25"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 25 ? ' selected' : ''?>>25</option>
      <option value="30"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 30 ? ' selected' : ''?>>30</option>
      <option value="35"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 35 ? ' selected' : ''?>>35</option>
      <option value="40"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 40 ? ' selected' : ''?>>40</option>
      <option value="45"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 45 ? ' selected' : ''?>>45</option>
      <option value="50"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 50 ? ' selected' : ''?>>50</option>
      <option value="55"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 55 ? ' selected' : ''?>>55</option>
      <option value="60"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 60 ? ' selected' : ''?>>60</option>
      <option value="65"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 65 ? ' selected' : ''?>>65</option>
      <option value="70"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 70 ? ' selected' : ''?>>70</option>
      <option value="75"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 75 ? ' selected' : ''?>>75</option>
      <option value="80"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 80 ? ' selected' : ''?>>80</option>
      <option value="85"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 85 ? ' selected' : ''?>>85</option>
      <option value="90"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 90 ? ' selected' : ''?>>90</option>
      <option value="95"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 95 ? ' selected' : ''?>>95</option>
      <option value="100"<?=$learn_obj_elem['percentage_correct_answer_to_pass'] == 100 ? ' selected' : ''?>>100</option>
    </select>
		<span> %</span>
		<!-- <input type="checkbox"<?=$learn_obj_elem['published_in_ecommerce']?' checked':''?> name="published"><span>pubblicato</span> -->
		<input type="radio" name="language_id" value="1"<?=$learn_obj_elem['language_id'] == 1 ? ' checked' : ''?>><span>Italiano</span>
		<input type="radio" name="language_id" value="2"<?=$learn_obj_elem['language_id'] == 2 ? ' checked' : ''?>><span>Inglese</span>
	</div>
	<br>
	<div>
    <textarea id="descr_obj" name="obj_descr" cols="500" rows="5" style="width: 800px;" title="descrizione"><?=$learn_obj_elem['description']?></textarea>
	</div>
</div>

<div id="div_player">
	
	<!-- ---- VIDEO	---- VIDEO ---- VIDEO ---- VIDEO ---- VIDEO ---- VIDEO ---- VIDEO ---- -->

	 <?php if($learn_obj_elem['learning_object_type_id'] == 1){?>
	<div id="video-preview">
		<div>
   		<h6>Webm</h6>
   		<div class="pull-left" data-filepath="<?=$file_path_webm?>" data-type="webm">
  			<div id="video-webm" >
  			
  			</div>
  		</div>
      <script>      	
  			jwplayer("video-webm").setup({
    			autostart: false,
    			width: "240",
    			height: "175",
					file: "<?=$file_path_webm?>"
					<?=(!file_exists($file_path_webm)) ? ', image: "img/404-file-not-found.gif"':''?>
				});

				jwplayer("video-webm").onPlay(function(){
					jwplayer('video-mp4').stop();
					jwplayer('video-ogv').stop();
					$('#video-preview div[id|="video"]').removeClass('active');
					$('#video-webm').addClass('active');
					$('.question_box[id|="question_box"]').hide();
					$("#add_question").show();
				});

				jwplayer('video-webm').onTime(function(event){
					current_time = Math.round(event.position);
					<?php	$i = 0;
	      	foreach($question_list as $single){
	       		$time_question = ($single['time'] / 1000); ?>

	        if((current_time == <?=$time_question?>)){
	        	jwplayer('video-webm').pause(true);
						$("#add_question").hide();
	  	  		$("#question_box-"+<?=$single['id']?>).fadeIn();
	  	  	}
	       		
	       	<?php } ?>
	     	});
  		</script>	
  	</div>
    <div>
   		<h6>Mp4</h6>
   		<div class="pull-left" data-filepath="<?=$file_path_mp4?>" data-type="mp4">
  			<div id="video-mp4" >
  			
  			</div>
  		</div>
      <script>
  			jwplayer("video-mp4").setup({
    			autostart: false,
    			width: "240",
    			height: "175",
					file: "<?=$file_path_mp4?>"
					<?=(!file_exists($file_path_mp4)) ? ', image: "img/404-file-not-found.gif"':''?>
				});

				jwplayer("video-mp4").onPlay(function(){
					jwplayer('video-webm').stop();
					jwplayer('video-ogv').stop();
					$('#video-preview div[id|="video"]').removeClass('active');
					$('#video-mp4').addClass('active');
					$('.question_box[id|="question_box"]').hide();
					$("#add_question").show();
				});

				jwplayer('video-mp4').onTime(function(event){
					current_time = Math.round(event.position);
					<?php	$i = 0;
	      	foreach($question_list as $single){
	       		$time_question = ($single['time'] / 1000); ?>

	        if((current_time == <?=$time_question?>)){
	        	jwplayer('video-mp4').pause(true);
						$("#add_question").hide();
	  	  		$("#question_box-"+<?=$single['id']?>).fadeIn();
	  	  	}
	       		
	       	<?php } ?>
	     	});
  		</script>
    </div>
    <div>
   		<h6>Ogv</h6>
   		<div class="pull-left" data-filepath="<?=$file_path_ogg?>" data-type="ogv">
  			<div id="video-ogv" >
  			
  			</div>
  		</div>
      <script>
  			jwplayer("video-ogv").setup({
    			autostart: false,
    			width: "240",
    			height: "175",
					file: "<?=$file_path_ogg?>"
					<?=(!file_exists($file_path_ogg)) ? ', image: "img/404-file-not-found.gif"':''?>
				});

				jwplayer("video-ogv").onPlay(function(){
					jwplayer('video-webm').stop();
					jwplayer('video-mp4').stop();
					$('#video-preview div[id|="video"]').removeClass('active');
					$('#video-ogv').addClass('active');
					$('.question_box[id|="question_box"]').hide();
					$("#add_question").show();
				});

				
				jwplayer('video-ogv').onTime(function(event){
					current_time = Math.round(event.position);
					<?php	$i = 0;
	      	foreach($question_list as $single){
	       		$time_question = ($single['time'] / 1000); ?>

	        if((current_time == <?=$time_question?>)){
	        	jwplayer('video-ogv').pause(true);
						$("#add_question").hide();
	  	  		$("#question_box-"+<?=$single['id']?>).fadeIn();
	  	  	}
	       		
	       	<?php } ?>
	     	});

		  </script>
		</div>
		<script>
			
			jwplayer($('#video-preview div[id|="video"].active').attr("id")).onTime(function(event){
				current_time = Math.round(event.position);
        $('.time').html(current_time);
				<?php	$i = 0;
      	foreach($question_list as $single){
       		$time_question = ($single['time'] / 1000); ?>

        if((current_time == <?=$time_question?>)){
        	jwplayer($('#video-preview div[id|="video"].active').attr("id")).pause(true);
					$("#add_question").hide();
  	  		$("#question_box-"+<?=$single['id']?>).fadeIn();
  	  	}

        jwplayer($('#video-preview div[id|="video"].active').attr("id")).onPlay(function(){
					$('.question_box[id|="question_box"]').hide();
					$("#add_question").show();
  	  	});
       		
       	<?php } ?>
     	});

		</script>
  </div>
  <div>
  
  <?php foreach ($question_list as $single){?>
	
		<div class="question_box" id="question_box-<?=$single['id']?>" style="display:none">
			<h5><?=$single['text']?></h5>
      <ol id="question_answer_<?=$single['id']?>" class="answer_box">
  			
  		<?php $answers_list = $question_obj->getAnswersByQuestionID($single['question_sentence_id']);
    	foreach($answers_list as $answer){ ?>
    			
    		<li><?=$answer['text']?><?=$answer['is_correct'] == 1 ? "<strong> - Corretta</strong>" : ""?></li>
    		
    	<?php }?>
  
      </ol>
		</div>
	
	<?php }?>
	
	</div>
	
		<!-- ---- SLIDE ---- SLIDE ---- SLIDE ---- SLIDE ---- SLIDE ---- SLIDE ---- SLIDE ---- -->
		
    <?php }elseif($learn_obj_elem['learning_object_type_id'] == 2){?>
       
    <div id="slide-preview">
    	<ul id="slideGallery">
        <?php
        $total = count($get_slide_filename);
        $x = 0;
        foreach ($get_slide_filename as $single){
			$x++;
			$file_encoded = base64_encode('../'.$file_path.$single['image_filename']);?>
			<li>
			<?php if (trim($single['image_filename']) == ""){?>
				<img title="Slide <?=$x?> di <?=$total?>" src="lib/generate_slide_box.php?slide_id=<?=$single['id']?>"/>
			<?php }else{?>
				<img title="Slide <?=$x?> di <?=$total?>" src="lib/render_image.php?file=<?=$file_encoded?>"/>
			<?php }
         }?>
        	<br style="clear: both"/>
        	<span style="color:#aaaaaa; font-family: Arial; font-size: 12px">(Clicca sull'anteprima per vedere la slide.)</span>
    	</ul>
    	
     <p>percorso file: <?=$file_path?></p>
    	
    </div>
	
		<!-- ---- DOC ---- DOC ---- DOC ---- DOC ---- DOC ---- DOC ---- DOC ---- -->
		
    <?php }elseif($learn_obj_elem['learning_object_type_id'] == 3){?>
         <iframe width="300" height="400" src="<?=$file_path?>"></iframe>
         <a href="lib/render_pdf.php?file=<?=base64_encode('../'.$file_path)?>" target="_blank">Apri il documento</a>
    	
     <p>percorso file: <?=$file_path?></p>
         
		<!-- ---- WEB ---- WEB ---- WEB ---- WEB ---- WEB ---- WEB ---- WEB ---- -->
		
    <?php }elseif($learn_obj_elem['learning_object_type_id'] == 4){?>
         <iframe width="800" height="600" src="<?=$file_path.'/index.html'?>" style="border: 1px solid #C3C3C3; box-shadow: 5px 5px 5px #C3C3C3;"></iframe>
         <!-- <a href="lib/render_pdf.php?file=<?=base64_encode($file_path)?>" target="_blank">Apri il documento</a> -->
    	
     <p>percorso file: <?=$file_path?></p>
    <?php } ?>
    
 		<!-- ---- ADD QUESTION ---- ADD QUESTION ---- ADD QUESTION ---- ADD QUESTION ---- ADD QUESTION ---- -->   
    
     <?php if($learn_obj_elem['learning_object_type_id'] > 0 && !$learn_in_use){?>
    <div>
    	<br style="clear: both"/> <br/>
        <span onclick="addNewQuestion()" id="add_question" class="btn">Aggiungi domanda</span><br/>
    </div>
    <div id="answer_box" style="display:none;">
         <?php if($learn_obj_elem['learning_object_type_id'] == 1 || $learn_obj_elem['learning_object_type_id'] == 2){?>La domanda sar&agrave; visualizzata dopo 
        	 <?php if($learn_obj_elem['learning_object_type_id'] == 2){?>
        		la slide <input type="number" id="sectostart" min="1" max="<?=$total?>" style="width:50px">
        	<?php } elseif($learn_obj_elem['learning_object_type_id'] == 1){?>
        		<span id="sectostart"></span> secondi
        	<?php }?>
        	<br/>
        <?php }?>
        <br/>
        <label for="question">Domanda:</label>
        <input type="text" id="question_txt" style="width:800px;" />
        <label>Risposte:</label>
        <table id="table_answers" style="padding: 0px; margin-bottom: 10px">
            <tr>
            	<td>
            		<input type="text" style="width:720px; margin-bottom: 10px !important;" id="answer_1" />
            	</td>
            	<td>
            		<input id="correct_1" type="checkbox" style="margin-left:10px; vertical-align: top;" /> corretta
            	</td>
            </tr>
        </table>
        <span onclick="addNewAnswer()" id="add_question" class="btn">Aggiungi nuova risposta</span>&nbsp;&nbsp;<span onclick="saveQuestion()" id="save_question" class="btn">Salva domanda</span><br/>
    </div>
    <?php }?>
</div>

<div id="learning_obj_detail" style="margin-top: 10px">
     <?php if($learn_obj_elem['learning_object_type_id'] == 1){?>
    <p class="tag_categories">
    <span style="font-weight: bold">Percorso:</span> 
         <?=$file_path_orig?><br/>
         <a href="<?=$file_path_mp4?>" target="_blank"><?=$file_path_mp4?></a>
         <?php if (file_exists($file_path_mp4)){?>
         	<img src="img/valid.png" /><br/>
         <?php } else {?>
         	<img src="img/invalid.png" /><br/>
         <?php }?>
         <a href="<?=$file_path_ogg?>" target="_blank"><?=$file_path_ogg?></a>
         <?php if (file_exists($file_path_ogg)){?>
         	<img src="img/valid.png" /><br/>
         <?php } else {?>
         	<img src="img/invalid.png" /><br/>
         <?php }?>
         <a href="<?=$file_path_webm?>" target="_blank"><?=$file_path_webm?></a>
         <?php if (file_exists($file_path_webm)){?>
         	<img src="img/valid.png" /><br/>
         <?php } else {?>
         	<img src="img/invalid.png" /><br/>
         <?php }?>
    </p>
    <p class="tag_categories">
    	<span style="font-weight: bold">Nome file:</span> 
      <?=  pathinfo($file_path_mp4,PATHINFO_FILENAME)?>
    </p>
    <?php }?>
    
    <div class="clearfix def-list show-obj">
  		<button class="btn btn-default" onclick="showEditTags()">Modifica Tags</button>
  		<br>
  		<br>
  	</div>
 
 		<div class="tag_group">
    	<p class="tag_categories def-list show-obj"><strong>Personalizzato:</strong>
    		<span>[<?=$learn_obj_elem['custom'] ? 'SI':'NO'?>]</span>
    	</p>
    	
    	
  		<div class="control-group edit-list edit-obj" style="display: none">
    		<div class="controls">
    			<label class="control-label"><strong>Personalizzato:</strong>
          	<input id="custom" type="checkbox"<?=$learn_obj_elem['custom'] ? ' checked':''?>>
          </label>
    		</div>
  		</div>
    	
    </div>
 
    <div class="tag_group">
    	<p class="tag_categories def-list show-obj"><strong>Tipo:</strong>
    		<?php if ($learn_obj_elem['type_id']){
					$type = $learn_obj->getTypeByID($learn_obj_elem['type_id'])?>
    		<span>[<?=$type['description']?>]</span>
    		<?php }?>
    	</p>
    	
    	
  		<div class="control-group edit-list edit-obj" style="display: none">
    		<div class="controls">
    			<label class="control-label"><strong>Tipo:</strong>
          	<select id="type_obj">
            <?php
            $types_list = $om->getTypes();
            foreach ($types_list as $type){?>
            	<option value="<?=$type['id']?>"<?=$type['id'] == $learn_obj_elem['type_id'] ? ' selected' : ''?>><?=$type['description']?></option>
            <?php }?>
            </select>
        	</label>
    		</div>
  		</div>
    	
    </div>
    
    <div class="tag_group">
    	
    	<p class="tag_categories def-list show-obj">
    		<span style="font-weight: bold">Categorie:</span> 
    		<?php
    		$cat_array = array();
    		foreach ($category_list as $single){
        	echo "[".$single['category_name']." > ".$single['sub_category_name']."] ";
        	$cat_array[] = $single['sub_id'];
    		}
    		?>
    	</p>
    	
    	<div class="control-group edit-list edit-obj" style="display: none">
    		<label class="control-label"><strong>Categorie:</strong></label>
    		<div class="controls">
    		
    			<select id="cat_obj" multiple size="12">
        	<?php
       		$group_cat = 0;
        	$cat_list = $learn_obj->getCategories();
        	foreach ($cat_list as $cat){
						//if ($cat['id'] != 5) continue;
						if ($cat['id'] != $group_cat){
							if ($group_cat){ ?>
							</optgroup>
							<?php }
							$group_subcat = $learn_obj->getSubCategories($cat['id']) ?>
							<optgroup label="<?=$cat['name']?>">
							<?php $group_cat = $cat['id'];
							}
							foreach ($group_subcat as $subcat){?>
            	<option value="<?=$subcat['id']?>"<?=in_array($subcat['id'], $cat_array) ? ' selected' : ''?>><?=$subcat['name']?><?=array_search($subcat['id'], $category_list)?></option>
            	<?php }?>
          	<?php }?>
          </select>
    		</div>
    	</div>
    	
    </div>
    
    
 
  		
    <div class="tag_group">
     	<p id="def-args" class="tag_categories def-list show-obj"><strong>Argomenti:</strong>
    		<?php if ($learn_obj_elem['argument_id']){
    		$arg = $learn_obj->getArgumentByID($learn_obj_elem['argument_id'])?>
    		<span>[<?=$arg['title_group_arg']?> > <?=$arg['title_arg']?>]</span>
    		<?php }?>
    	</p>
    
    	<div class="control-group edit-list edit-obj" style="display: none">
    		<div class="controls">
    			<label class="control-label" for="new_cognome"><strong>Argomenti:</strong>
          	<select id="arg_obj">
          		<option value="0"<?=!$learn_obj_elem['argument_id'] ? ' selected' : ''?>>Seleziona un argomento</option> 
        	<?php
       		$group_arg = 0;
        	$arg_list = $learn_obj->getArguments();
        	foreach ($arg_list as $arg){
					if ($arg['argument_group_id'] != $group_arg){
						if ($group_arg){ ?>
							</optgroup>
						<?php }
						$group_arg = $learn_obj->getGroupArgumentByID($arg['argument_group_id']) ?>
						<optgroup label="<?=$group_arg['title_group_arg']?>">
						<?php $group_arg = $arg['argument_group_id'];
						} ?>
            <option value="<?=$arg['id']?>"<?=$arg['id'] == $learn_obj_elem['argument_id'] ? ' selected' : ''?>><?=$arg['title_arg']?></option>
          <?php }?>
          	</select>
        	</label>
    		</div>
  		</div>
    </div>
    
    <div class="tag_group">
    	<p class="tag_categories def-list show-obj"><strong>Livello:</strong>
    		<?php if ($learn_obj_elem['level_id']){
					$lev = $learn_obj->getLevelByID($learn_obj_elem['level_id'])?>
    		<span>[<?=$lev['title']?>]</span>
    		<?php }?>
    	</p>
    	
    	
  		<div class="control-group edit-list edit-obj" style="display: none">
    		<div class="controls">
    			<label class="control-label"><strong>Livello:</strong>
          	<select id="level_obj">
            <?php
            $level_list = $om->getLevels();
            foreach ($level_list as $level){?>
            	<option value="<?=$level['id']?>"<?=$level['id'] == $learn_obj_elem['level_id'] ? ' selected' : ''?>><?=$level['title']?></option>
            <?php }?>
            </select>
        	</label>
    		</div>
  		</div>
    	
    </div>
    
    <div class="clearfix edit-list" style="display:none">
  		<button class="btn btn-primary" onclick="saveModifiedTags()">Salva</button>
  		<button class="btn btn-default" onclick="showDefTags()">Annulla</button>
  		<br>
  		<br>
  	</div>
  	
  	<div>	
    <p class="tag_categories">
    <span><strong>Data di creazione:</strong></span>
    <?=date('d/m/Y',strtotime($learn_obj_elem['creation_date'])) ?>
    </p>
    </div>
    <h2 class="subtitle_detail">Domande inserite</h2>
    <?php foreach($question_list as $single){?>
    <div class="question_box">
    	<h5><?=$single['text']?></h5>
    
    	
    	
    	<?php if($learn_obj_elem['learning_object_type_id'] == 1){?>
    	<p>La domanda sar&agrave; generata dopo 
    		<input id="time_<?=$single['video_test_interruption_point_id']?>" type="text" value="<?=$single['time']/1000?>"/> secondi
    		<input type="button" class="btn" onclick="updateTimeQuestion(<?=$single['video_test_interruption_point_id']?>)" value="modifica secondi" />
    	</p>
      <?php }elseif($learn_obj_elem['learning_object_type_id'] == 2){?>
      
      <p>La domanda sar&agrave; generata alla slide
        <input id="position_<?=$single['slide_id']?>" type="text" value="<?=$single['position']?>"/>
        <input type="button" onclick="updateSlideQuestionPosition(<?=$single['slide_id']?>,<?=count($slide_list)-1?>)"class="btn" value="modifica posizione (min. 1 - max <?=count($slide_list)-1?>)" />
      </p>
      <?php }?>
    	
    
    	<p>Risposte:</p>
    		<ol id="question_answer_<?=$single['question_sentence_id']?>" class="answer_box">
            
      	<?php $answers_list = $question_obj->getAnswersByQuestionID($single['question_sentence_id']);
      	foreach($answers_list as $answer){?>
					<li><?=$answer['text']?><?=$answer['is_correct'] == 1 ? "<strong> - Corretta</strong>" : ""?></li>
				<?php } ?>
				
				</ol>
        
        <?php if(!$learn_in_use){?>

        <input type="button" class="btn" onclick="editQuestion(<?=$single['question_sentence_id']?>,<?=$learn_obj_id?>)" value="Modifica" disabled="disabled"/>
        <input type="button" class="btn" onclick="removeQuestion(<?=$single['question_sentence_id']?>,<?=$learn_obj_id?>,<?=$learn_obj_elem['learning_object_type_id']?>)" value="Rimuovi" disabled="disabled"/>
        <br>
        <br>
        <?php } ?>
        
    </div>
    
   	<?php }?>
</div>


