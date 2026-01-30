<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
    require_once 'class_om.php';
    $learn_obj = new T81DOM();
    $learn_obj_id = sanitize($_POST['id'], INT);
    $learn_obj_elem = $learn_obj->getLearningObjectByID($learn_obj_id);
    if($learn_obj_elem['learning_object_type_id'] == 1){
        $icon = "img/video48.png";
    }elseif($learn_obj_elem['learning_object_type_id'] == 2){
        $icon = "img/slide48.png";
    }elseif($learn_obj_elem['learning_object_type_id'] == 3){
        $icon = "img/doc48.png";
    }elseif($learn_obj_elem['learning_object_type_id'] == 4){
        $icon = "img/web48.png";
    }
?>
	<img src="<?=$icon?>"/>
	 <a href="om-management.php?id=<?= $learn_obj_id?>" target="_blank"><span ><?=strtoupper($learn_obj_elem['title'])?></span></a>
	<img style="cursor: pointer" class="remove-object" src="img/delete.gif"/>                        