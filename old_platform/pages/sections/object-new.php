<?php

if (!isset($_SESSION)) session_start();
    $created_by = $_SESSION['user_id'];
    require_once 'lib/class_om.php';
    
    $om = new T81DOM();
    $category_list = $om->getCategories();
    $types = $om->getTypes();

    $path = "media/user_store/{$_SESSION['user_id']}/learning_objects/temp";
    if(!dir($path)){
    	mkdir($path);
    }
    	
    $file_list = scandir($path);
?>
<?php require_once 'header.php'; ?>
<script>
    function updateStat(id){
        var number = 0;
        $(".sub_checkbox_"+id).each(function () {
            if (this.checked){
               number++;
            }
        });
        $("#counter_"+id).html(number);
    }
    
    function loadWebTemplate(){
      var dirname = $('.single-web-container.active').data("dirpath");
			$.post('lib/manage_learning_object.php', 
				{
					op_type: "load_web_template",
					dirname: dirname,
					user_id: <?=$_SESSION['user_id']?>
				},
				function(data)
				{
					if (data) {
						$('#loadWebTemplateModal').modal('hide');
						$('#om-web .om-container').append('<div class="pull-left single-om-container" data-filepath="'+data+'" data-type="web">'+
					  			'<div id="web_' + ($('#om-web .single-om-container').length + 1) + '">'+
				  					'<div class="wrap-frame" style="width: 240px; height: 180px; border: 1px solid #c3c3c3;">'+
				  					'<iframe class="frame" style="width: 800px; height: 600px;-ms-zoom: 0.3; -ms-transform-origin: 0 0; -moz-transform: scale(0.3); -moz-transform-origin: 0px 50px; -o-transform: scale(0.3); -o-transform-origin: 0px 50px; -webkit-transform: scale(0.3); -webkit-transform-origin: 0 0;" src="<?=$path?>/'+data+'/index.html"></iframe>'+
				  				'</div>'+
				  			'</div>'+
				  				'<h6 title="click to select" data-path="<?=$path?>/'+data+'">'+data+' <i class="icon-pencil"></i> <i class="icon-eye-open"></i></h6>'+
				  		'</div>');
					}
				}
			);
    }
    
    function showSubCategory(id){
        $(".sub_category").hide();
        $(".category_box li").removeClass('li_active');
        $("#li_"+id).addClass('li_active');
        $("#subcategory_box_"+id).show();
    }

		function checkVideo(){
			if ($('#om-video .single-om-container').length == 0) {
				alert("Per creare un oggetto è necessario caricare i file e selezionarli");
				return false;
			} else if ($('#om-video .single-om-container[data-type="mp4"].active').length == 0) {
				alert("Caricare e selezionare un video in formato mp4");
				return false;
			} else if ($('#om-video .single-om-container[data-type="webm"].active').length == 0) {
				alert("Caricare e selezionare un video in formato webm");
				return false;
			}
			return true;
		}
		
		function checkDoc(){
			if ($('#om-doc .single-om-container').length == 0) {
				alert("Per creare un oggetto è necessario caricare i file e selezionarli");
				return false;
			} else if ($('#om-doc .single-om-container[data-type="pdf"].active').length == 0) {
				alert("Caricare e selezionare un documento in formato pdf");
				return false;
			}
			return true;
		}

		function checkWeb(){
			if ($('#om-web .single-om-container').length == 0) {
				alert("Per creare un oggetto è necessario caricarlo e selezionarlo");
				return false;
			} else if ($('#om-web .single-om-container[data-type="web"].active').length == 0) {
				alert("Caricare e selezionare un oggetto web");
				return false;
			}
			return true;
		}
		
    function saveMultiObject(){

				var obj_om = new Array();
				
				if ($("#type_obj").val() == 1){
					if (!checkVideo()) {
						return false;
					} else {
						$('#om-video .single-om-container.active').each(function(){
							obj_om.push($(this).data('filepath'));
						});
					}
				} else if($("#type_obj").val() == 2 || $("#type_obj").val() == 3) {
					if (!checkDoc()) {
						return false;
					} else {
						$('#om-doc .single-om-container.active').each(function(){
							obj_om.push($(this).data('filepath'));
						});
					}
				} else if ($("#type_obj").val() == 4) {
					if (!checkWeb()) {
						return false;
					} else {
						$('#om-web .single-om-container.active').each(function(){
							obj_om.push($(this).data('filepath'));
						});
					}
				}

			  if (!($("#title_obj").val() != "")){
			  	alert('Inserire il titolo dell\'oggetto');
					return false;
				}
        
				var obj_cat = $('#cat_obj').val();
		  	if (obj_cat == null){
					alert('Selezionare almeno una categoria');
					return false;
        }
	        
				var type_id = $('#type_id').val();
	 			if (type_id == 0){
					alert('Selezionare almeno un tipo');
					return false;
	    	}

		  	if ($("#duration_obj").val() == 0){
		  		alert('Inserire la durata in minuti');
					return false;
			  }
			  
        var oEditor = CKEDITOR.instances['editor'];
        var obj_descr = oEditor.getData();

        $.post("lib/manage_learning_object.php",{
        		op_type: "creat_new",
          	owner_user_id: <?=$_SESSION['user_id']?>,
          	obj_type: $("#type_obj").val(),
          	obj_arg: $("#arg_obj").val(),
          	obj_lang: $("#language").val(),
          	obj_dura: $("#duration_obj").val(),
          	obj_perc: $("#perc_obj").val(),
          	obj_descr: obj_descr,
          	obj_cat: obj_cat,
            obj_level: $("#level_obj").val(),
            obj_title: $("#title_obj").val(),
            obj_battute: $('#battute_obj').val(),
            type_id: type_id,
            obj_om: obj_om,
            custom: $('#custom:checked').length
           },function(data){
           	//alert(data);
            if(data > 0){
            	alert("Caricamento dell'oggetto OM"+data+" eseguito con successo");
            	window.location ='om-management.php?id='+data;
            }
        	});
    }

</script>
<style>
    .sub_category{display:none; margin-left:160px; border:1px solid #cccccc; width:337px; height: 210px; overflow: auto}
</style>
<h4>Nuovo oggetto multimediale</h4>

<form id="form_new_user" class="form-horizontal">

	<div style="float:left">
  	<div class="control-group">
    	<label class="control-label">Media*</label>
    	<div class="controls">
    		<select id="type_obj">
    			<option value="1">Video</option>
    			<option value="2">Slide</option>
    			<option value="3">Document</option>
    			<option value="4">Web</option>
    		</select>
    	</div>
  	</div>

  		
  		
  <div id="om-video" class="control-group">
  	<p>Oggetto formativo*</p>
  	<div class="om-container">
  	<?php
  		$n = 0;
  		foreach ($file_list as $filename){
				if (is_file($path.'/'.$filename)){
					$path_parts = pathinfo($path.'/'.$filename);
					if ($path_parts['extension'] == "mp4" || $path_parts['extension'] == "webm"){
						$n++;
					?>
  		<div class="pull-left single-om-container" data-filepath="<?=$filename?>" data-type="<?=$path_parts['extension']?>">
  			<div id="video_<?=$n?>" >
  			
  			</div>
  			<h6 title="click to select"><?=$filename?></h6>
  		</div>
  		<script>
  			jwplayer("video_<?=$n?>").setup({
    			autostart: false,
    			width: "240",
    			height: "180",
					file: "<?=$path.'/'.$filename?>",
					title: "<?=$filename?>"
				});
  		</script>	
  			
  	<?php
  				}
  			}
			}
			if(!$n) echo '<h5>Caricare i file prima di procedere</h5>';
  	?>
  	</div>
  </div> 
  
  <div id="om-doc" class="control-group" style="display: none;">
  	<p>Oggetto formativo*</p>
  	<div class="om-container">
  	<?php
  		$n = 0;
  		foreach ($file_list as $filename){
				if (is_file($path.'/'.$filename)){
					$path_parts = pathinfo($path.'/'.$filename);
					if ($path_parts['extension'] == "pdf"){
						$n++;
					?>
  		<div class="pull-left single-om-container" data-filepath="<?=$filename?>" data-type="<?=$path_parts['extension']?>">
  			<div id="doc_<?=$n?>" >
  				<iframe width="240" height="180" src="<?=$path.'/'.$filename?>"></iframe>
  			</div>
  				<h6 title="click to select"><?=$filename?></h6>
  		</div>
  	<?php
  				}
  			}
			}
			if(!$n) echo '<h5>Caricare i file prima di procedere</h5>';
  	?>
  	</div>
  </div>
  
  <div id="om-web" class="control-group" style="display: none;">
  
  	<p>Oggetto formativo* (<a href="#loadWebTemplateModal" data-toggle="modal">seleziona template</a>)</p>
  	<div class="om-container">
  	<?php
  		$n = 0;
  		foreach ($file_list as $filename){
				if (is_dir($path.'/'.$filename) && strpos($filename, "web_") === 0){
					$n++;
					?>
  		<div class="pull-left single-om-container" data-filepath="<?=$filename?>" data-type="web">
  			<div id="web_<?=$n?>">
  				<!-- <img style="width: 240px; height: 180px; border: 1px solid #c3c3c3;" src="<?=$path.'/'.$filename.'/thumbnail.png'?>"> -->
  				<div class="wrap-frame" style="width: 240px; height: 180px; border: 1px solid #c3c3c3;">
  					<iframe class="frame" style="width: 800px; height: 600px;-ms-zoom: 0.3; -ms-transform-origin: 0 0; -moz-transform: scale(0.3); -moz-transform-origin: 0px 50px; -o-transform: scale(0.3); -o-transform-origin: 0px 50px; -webkit-transform: scale(0.3); -webkit-transform-origin: 0 0;" src="<?=$path.'/'.$filename.'/index.html'?>"></iframe>
  				</div>
  			</div>
  				<h6 title="click to select" data-path="<?=$path.'/'.$filename?>"><?=$filename?> <i class="icon-pencil"></i> <i class="icon-eye-open"></i></h6>
  		</div>
  	<?php
  				
  			}
			}
			if(!$n) { ?>
			
			<h5><a href="#loadWebTemplateModal" data-toggle="modal">Caricare i file prima di procedere</a></h5>';
    	<?php }
  	?>
  	</div>
  	
  	<div id="editWebObjectModal" class="modal hide fade" style="width: 802px; margin-left: -420px; top: 0;">
  		<div class="modal-body" style="max-height: none; padding:0;">
  		</div>
  		<div class="modal-footer">
    		<a href="javascript:void(0)" class="btn" onclick="$('#editWebObjectModal').modal('hide');">Chiudi</a>
    		<a href="javascript:void(0)" class="btn btn-primary save" onclick="$('#update_web_object').trigger('click').parents('#editWebObjectModal').modal('hide');">Salva modifiche</a>
  		</div>
		</div>
		
		<div id="showWebObjectModal" class="modal hide fade" style="width: 802px; margin-left: -420px; top: 0;">
  		<div class="modal-body" style="max-height: none; padding:0;">
  		</div>
  		<div class="modal-footer">
    		<a href="javascript:void(0)" class="btn" onclick="$('#showWebObjectModal').modal('hide');">Chiudi</a>
  		</div>
		</div>
  	
 	</div>
  
  <div class="control-group">
  	<label class="control-label" for="title_obj">Title*</label>
  	<div class="controls">
  		<input type="text" id="title_obj" placeholder="Titolo oggetto" required style="width: 600px;">
  	</div>
  </div>  		
  		
  <div class="control-group">
  	<label class="control-label">Categorie*</label>
  	<div class="controls">
  		<select id="cat_obj" multiple size="12" title="clicca ctrl(cmd) o shift per selezioni multiple">
  				
  		<?php foreach ($category_list as $single){ ?>
  				
  			<optgroup label="<?=$single['name']?>">
  				
  			<?php $res = $om->getSubCategories($single['id']);
       	foreach($res as $single_sub){?>
          
       		<option value="<?=$single_sub['id']?>"/><?=$single_sub['name']?></option>
          
      	<?php }?>
          
       	</optgroup>
        	
     	<?php }?>
     	</select>
    </div>
  </div>
  
  <div class="control-group">
  	<label class="control-label" for="custom">Personalizzato</label>
  	<div class="controls">
  		<input type="checkbox" id="custom">
  	</div>
  </div>
  
  <div class="control-group">
  	<label class="control-label" for="type_id">Tipo*</label>
  	<div class="controls">
  		<select id="type_id" required>
  			<option value="0" selected>Seleziona un tipo</option>
  		<?php foreach ($types as $type){?>
  			<option value="<?=$type['id']?>"><?=$type['description']?></option>
  		<?php }?>
  		</select>
  	</div>
  </div> 
  
  <div class="control-group">
  	<label class="control-label" for="arg_obj">Argomenti</label>
    <div class="controls">
      <select id="arg_obj">
  			<option value="0" selected>Seleziona un argomento</option>
            <?php
            $group_arg = 0;
            $arg_list = $om->getArguments();
            foreach ($arg_list as $arg){
				if ($arg['argument_group_id'] != $group_arg){
					if ($group_arg){ ?>
						</optgroup>
					<?php }
					$group_arg = $om->getGroupArgumentByID($arg['argument_group_id']) ?>
					<optgroup label="<?=$group_arg['title_group_arg']?>">
					<?php $group_arg = $arg['argument_group_id'];
				} ?>
                	<option value="<?=$arg['id']?>"><?=$arg['title_arg']?></option>
            <?php }?>
             	</select>
    		</div>
  		</div>
  		
  		<div class="control-group">
    		<label class="control-label">Livello*</label>
    		<div class="controls">
            	<select id="level_obj">
            <?php
            $level_list = $om->getLevels();
            foreach ($level_list as $level){?>
            		<option value="<?=$level['id']?>"><?=$level['title']?></option>
            <?php }?>
            	</select>
    		</div>
  		</div>
  		
  		<div class="control-group">
    		<label class="control-label" for="language">Lingua*</label>
    		<div class="controls">
        		<?=$om->getSelectLanguage()?>
    		</div>
  		</div>
  		<div class="control-group">
                    <label class="control-label" for="perc_obj">Percentuale di risposte esatte*</label>
                    <div class="controls">
                        <select id="perc_obj">
                            <option value="10">10</option><option value="15">15</option>
                            <option value="20">20</option><option value="25">25</option>
                            <option value="30">30</option><option value="35">35</option>
                            <option value="40">40</option><option value="45">45</option>
                            <option value="50">50</option><option value="55">55</option>
                            <option value="60">60</option><option value="65">65</option>
                            <option value="70" selected="selected">70</option><option value="75">75</option>
                            <option value="80">80</option><option value="85">85</option>
                            <option value="90">90</option><option value="95">95</option>
                            <option value="100">100</option>
                        </select>
                    </div>
  		</div>
			<div class="control-group">
				<label class="control-label" for="duration_obj">Durata*</label>
				<div class="controls">
					<input type="number" id="duration_obj" required value="0" min="1"><span> minuti</span>
				</div>
			</div>
			
			<div class="control-group">
				<label class="control-label" for="battute_obj">Battute</label>
				<div class="controls">
					<input type="number" id="battute_obj" value="0" min="0">
				</div>
			</div>
      
     	<div class="control-group">
     		<label class="control-label" for="new_nome">Descrizione*</label>
     		<div class="controls">
     			<div id="editor" name="editor" >
     			
     			</div>
     		</div>
  		</div>
  	</div>
 
	<div class="controls" style="clear:both">
    	<button type="button" onclick="saveMultiObject()" class="btn">Salva</button>
      <button type="reset" class="btn">Annulla</button>
  </div>
    
</form>
<div id="loadWebTemplateModal" class="modal hide fade" style="width: 802px; margin-left: -420px; top: 0;">
  <div class="modal-header">
  	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
	   <h3 id="myModalLabel">Template oggetti web</h3>
  </div>
  <div class="modal-body">
  <?php
  $path_template = "media/template/web_objects";
   	 
  $template_web_list = scandir($path_template);
  $n = 0;
  foreach ($template_web_list as $dirname){
		if (is_dir($path_template.'/'.$dirname) && $dirname != "." && $dirname != ".."){
			$n++;
			?>
  	<div class="pull-left single-web-container" data-dirpath="<?=$dirname?>" data-path="<?=$path_template.'/'.$dirname?>" data-type="<?="web"?>">
  		<div id="web_template<?=$n?>">
  			<img style="width: 240px; height: 180px; border: 1px solid #c3c3c3;" src="<?=$path_template.'/'.$dirname.'/thumbnail.png'?>">
  			<!-- <div class="wrap-frame" style="width: 240px; height: 180px; border: 1px solid #c3c3c3; overflow: hidden;">
  				<iframe class="frame" style="width: 800px; height: 600px;-ms-zoom: 0.3; -ms-transform-origin: 0 0; -moz-transform: scale(0.3); -moz-transform-origin: 0px 50px; -o-transform: scale(0.3); -o-transform-origin: 0px 50px; -webkit-transform: scale(0.3); -webkit-transform-origin: 0 0;" src="<?=$path_template.'/'.$dirname.'/index.html'?>"></iframe>
  			</div> -->
  		</div>
  		<h6 title="click to select" ><?=$dirname?></h6>
  	</div>
  	<?php
  			
  	}
	}?>
  </div>
  <div class="modal-footer">
  	<button class="btn" data-dismiss="modal" aria-hidden="true">Chiudi</button>
  	<button class="btn btn-primary" onclick="loadWebTemplate()">Seleziona</button>
	</div>
</div>
<script>
$(document).ready(function() {
        
        if (CKEDITOR.instances['editor'] != undefined) {  CKEDITOR.remove(CKEDITOR.instances['editor']); }
    
        CKEDITOR.replace('editor',
        {		
                width:500,
                height:100,
                toolbarCanCollapse: false,						
                resize_enabled: false,
                language : 'it',
                toolbar :
				[
					['Bold', 'Italic','Underline','Strike','-', 'Cut','Copy','Paste', '-', 'Redo','Undo', '-', 'NumberedList', 'BulletedList', '-', 'JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock']								
        ]
        } );        



    	$('#type_obj').change(function(){
    		var type = $(this).val();
    		if(type == 1) { 
    			$('#om-video').show();
    			$('#om-doc').hide();
    			$('#om-web').hide();
    		} else if(type == 2 || type == 3) {
    			$('#om-doc').show();
    			$('#om-video').hide();
    			$('#om-web').hide();
    		} else if (type = 4){
    			$('#om-doc').hide();
    			$('#om-video').hide();
    			$('#om-web').show();
    		}
    	});

    	$('.om-container').on('click','.single-om-container h6',function(e){
    		var selection = $(this).parent();
    		var type = selection.data('type');
    		if (selection.hasClass('active')) {
    			selection.removeClass('active');
    		} else {
    			$('.single-om-container[data-type="'+type+'"]').removeClass('active');
    			selection.addClass('active');
    		}
    	});

    	$('#loadWebTemplateModal').on('click','.single-web-container',function(e){
    		var selection = $(this);
    		if (selection.hasClass('active')) {
    			selection.removeClass('active');
    		} else {
    			$('.single-web-container').removeClass('active');
    			selection.addClass('active');
    		}
    	});
    	
		$('#om-web').on('click','.single-om-container i.icon-pencil', function(e){
			var path = $(this).parent().data("path");
			$('#editWebObjectModal div.modal-body').load(path+'/edit.php').parents('div.modal').modal("show");
		});

		$('#om-web').on('click','.single-om-container i.icon-eye-open', function(e){
			var path = $(this).parent().data("path");
			$('#showWebObjectModal div.modal-body').empty();
			$('#showWebObjectModal div.modal-body').append('<iframe style="width: 800px; height: 600px;" src="'+path+'/index.html"></iframe>').parents('div.modal').modal("show");
		});
	
});
</script>
<?php require_once 'footer.php'; ?>