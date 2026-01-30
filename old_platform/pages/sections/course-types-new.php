<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 24-ago-2015
 * File: pages/sections/course-types-new.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 * Content: Create new type of course for classroom
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';

$course_obj = new iWDCourse();

$categories = $course_obj->getCategories();
?>
<div id="workspace">
    <form id="new-course-types" class="form-horizontal">
        <div class="form-group">
            <label for="course_code" class="col-sm-3 control-label">Codice</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" name="course_code" placeholder="codice del corso">
            </div>
        </div>
        <div class="form-group">
            <label for="course_description" class="col-sm-3 control-label">Descrizione</label>
            <div class="col-sm-9">
                <textarea class="form-control" name="course_description" placeholder="descrizione del corso"></textarea>
            </div>
        </div>
        <div class="form-group">
            <label for="duration" class="col-sm-3 control-label">Durata <small>(ore)</small></label>
            <div class="col-sm-9">
                <input type="number" class="form-control" name="duration" min="1" value="1" style="width: 7s0px;">
            </div>
        </div>
        <div class="form-group">
            <label for="subcategory_id" class="col-sm-3 control-label">Categoria</label>
            <div class="col-sm-9">
                <select class="form-control" name="subcategory_id">
                    
                    <option value="0" disabled selected>Seleziona una categoria</option>
            <?php foreach ($categories as $category){
                $subcategories = $course_obj->getSubCategories($category['id']);?>
                    
                    <optgroup label="<?= $category['name'] ?>">
                    
                    <?php foreach ($subcategories as $subcategory) { ?>
                    
                        <option value="<?= $subcategory['id'] ?>"><?= $subcategory['name'] ?></option>
                    
                    <?php } // end foreach subcategories in category ?>
                    
                    </optgroup>
                    
            <?php } // end foreach subcategory in subcategories ?>
                    
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="custom_category_id" class="col-sm-3 control-label">Tipo</label>
            <div class="col-sm-9">
                <select class="form-control" name="custom_category_id">
                    <option value="1" selected>Nuovo</option>
                    <option value="2">Aggiornamento</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
                <button type="submit" class="btn btn-primary">Salva</button>
            </div>
        </div>
    </form>
</div><!-- /#workspace -->
<script>
    $('#new-course-types').submit(function(e){
        e.preventDefault();
        $('#new-course-types button[type="submit"]').prop('disabled', true);
        var this_form = $(this);
        $.post('manage/course_type.php',
            {
                op_type: 'add_course_type',
                course_code: this_form.find('input[name="course_code"]').val(),
                course_description: this_form.find('textarea[name="course_description"]').val(),
                duration: this_form.find('input[name="duration"]').val(),
                subcategory_id: this_form.find('select[name="subcategory_id"]').val(),
                custom_category_id: this_form.find('select[name="custom_category_id"]').val()
            },
            function(data){
                if (data > 0) {
                    alert('Aggiunto nuovo corso.');
                    location.reload();
                } else {
                    alert('Errore nella creazione del corso. Verifica i dati inseriti e riprova.');
                    $('#new-course-types button[type="submit"]').prop('disabled', false);
                }
            }
        );
    });
</script>