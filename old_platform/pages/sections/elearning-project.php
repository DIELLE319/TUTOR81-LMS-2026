<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 18-nov-2015
 * File: pages/sections/elearning-project.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_om.php';

$learning_project_id = filter_input(INPUT_GET, 'learning_project_id', FILTER_SANITIZE_NUMBER_INT);

$learn_obj = new T81LearningProject();
$comp_obj = new T81Company();
$course_obj = new iWDCourse();

$company_list = $comp_obj->getBusiness();
$learning_project = $learn_obj->getDetail($learning_project_id);
$course = $learn_obj->getCourseDetailFromLearningProject($learning_project_id);
$course_custom_categories = $course_obj->getCourseCustomCategories($course['id']);
$course_modules = $course_obj->getCourseModules($course['id']);
$is_published = $course_obj->is_published($course['id']);
?>

<script>

    function toggleObject() {
        if ($('#toggle-object').text() == 'mostra oggetti') {
            $('.lesson-content').show();
            $('#toggle-object').text('nascondi oggetti');
            $('.lesson span').each(function () {
                if ($(this).hasClass('glyphicon glyphicon-menu-down')) {
                    $(this).removeClass('glyphicon glyphicon-menu-down').addClass('glyphicon glyphicon-menu-up');
                }
            });
        } else {
            $('.lesson-content').hide();
            $('#toggle-object').text('mostra oggetti');
            $('.lesson span').each(function () {
                if ($(this).hasClass('glyphicon glyphicon-menu-up')) {
                    $(this).removeClass('glyphicon glyphicon-menu-up').addClass('glyphicon glyphicon-menu-down');
                }
            });
        }
    }

</script>

<form id="play-course" target="_blank" action="<?= URL_PLAYER . 'lib/ec-login.php' ?>" method="POST">
    <input type="hidden" name="username" value="admin_demo">
    <input type="hidden" name="password" value="ZXcv1712">
    <input type="hidden" name="mode" value="demo">
    <input type="hidden" name="learn_id" value="<?= $learning_project_id ?>">
    <input type="hidden" name="tos_authorized" value="on">
    <input type="hidden" name="in_working_time" value="1">
</form>
<div class="panel-group" id="home-accordion" role="tablist" aria-multiselectable="true">
    <div id="member-info" class="panel panel-primary">
        <div class="panel-heading">
            <span class="glyphicon glyphicon-list"></span> 
            &nbsp;DETTAGLIO CORSO
            &nbsp;&nbsp;&nbsp;
            <a class="collapse-control" role="button" data-toggle="collapse" 
               data-parent="#home-accordion" href="#collapse-profile" 
               aria-expanded="<?= empty($section) ? 'true' : 'false' ?>" aria-controls="collapse-profile">
            </a>
            <a href="javascript: $('#play-course').submit();" class="pull-right white" style="margin-right:10px;">
                <span class="glyphicon glyphicon-play"></span>
                AVVIA CORSO
            </a>
            <a href="javascript: void(0);" class="pull-right white" style="margin-right:10px;">
                <span class="glyphicon glyphicon-pencil"></span>
                MODIFICA
            </a>
            <a href="javascript: void(0);" class="pull-right white" style="margin-right:10px;">
                <span class="glyphicon glyphicon-print"></span>
                STAMPA
            </a>

        </div>
        <div id="collapse-profile" class="panel-collapse collapse <?= empty($section) ? 'in' : '' ?>" role="tabpanel">
            <div class="panel-body">


                <div id="course_container">


                    <!-- <h1 class="title_detail">
                        <?php $file_path = "user_store/" . $course['owner_user_id'] . "/courses/ecommerce_images/thumb/" . $course['ecommerce_image_filename'];
                        if (file_exists(BASE_MEDIA_PATH . $file_path) && (trim($course['ecommerce_image_filename']) != "")) {
                            ?>

                            <img border="0" src="media/<?= $file_path ?>"/>

<?php } ?>

                        (ID:<?= $course['id'] ?>) <?= $course['title'] ?>
                    </h1> -->



                    <div id="course-detail" >
                        <dl class="dl-horizontal">

                            <?php
                            if ($course['subcategory_id']) {
                                $category_detail = $course_obj->getDetailSubcategory($course['subcategory_id']);
                                $category_name = $category_detail['cat_name'];
                                $subcategory_name = $category_detail['name'];
                            } else {
                                $category_name = "";
                                $subcategory_name = "";
                            }
                            ?>

                            <dt>Categoria</dt>
                            <dd><?= $category_name ?></dd>

                            <dt>Data di creazione</dt>
                            <dd><?= $course['creation_date'] ?>
                                <form class="form-inline">
                                    <div class="form-group">
                                    <label>ritirare il corso in data </label>
                                    <input type="text" class="form-control datepicker retirement-date" placeholder="inserisci una data">
                                </form>
                            </dd>

                            <dt>Destinatario</dt>
                            <dd><?= $course['customers'] ?></dd>

                            <dt>Titolo Corso</dt>
                            <dd><?= $course['title'] ?></dd>

                        <?php if ($category_detail['category_id']) { ?>   
                        
                            <dt>Sottocategoria</dt>
                            <dd><?= $subcategory_name ?></dd>

                            <?php foreach ($course_custom_categories as $custom_category) { ?>

                                <dt><?= $custom_category['fl_definition'] ?></dt>
                                <dd><?= $custom_category['definition'] ?></dd>

                            <?php } ?>

                            <?php
                            if ($course['type_id']) {
                                $type_detail = $course_obj->getDetailType($course['type_id']);
                                $type_name = $type_detail['description'];
                            } else {
                                $type_name = "";
                            }
                            ?>

                            <dt>Destinazione</dt>
                            <dd><?= $type_name ?></dd>

                            <dt>Validità</dt>
                            <dd><?= $course['course_validity'] ?></dd>

                            <dt>Integrazione in aula</dt>
                            <dd><?= $course['external_integration'] ?></dd>

                            <dt>Personalizzato</dt>
                            <dd><?= $course['custom'] ? 'SI' : 'NO' ?></dd>

                            <dt>Riferimento normativo</dt>
                            <dd><?= $course['law_reference'] ?></dd>

                        <?php } ?>
                            
                            <dt>Durata e-learning</dt>
                            <dd><?= (integer) $course['total_elearning'] ?> ore</dd>

                            <dt>Tempo massimo per la conclusione</dt>
                            <dd><?= $course['max_execution_time'] ?> giorni</dd>

                            <dt>Prodotto da</dt>
                            <dd><?= $course['producers'] ?></dd>

                            <dt>Percentuale risposte esatte</dt>
                            <dd><?= $course['percentage_correct_answer_to_pass'] ?> %</dd>

                            <dt>Docenti</dt>
                            <dd><?= $course['course_professors'] ?></dd>

                            <dt>Didattica</dt>
                            <dd><?= $course['didactics'] ?></dd>

                            <dt>Descrizione</dt>
                            <dd><?= $course['description'] ?></dd>

                            <dt>Autori del corso</dt>
                            <dd>
                                <form class="form-horizontal">
                                    <div class="form-group col-lg-6">
                                        <select class="form-control">
                                            <option value="0">Seleziona</option>
                                        </select>
                                    </div>
                                </form>
                            </dd>

                        </dl>
                    </div>

                    <div id="list_obj">
                        <h3>Moduli inseriti
                            
                            <?php if ($course_modules) { ?>
                            
                                <button id="toggle-object" class="btn btn-xs btn-default hidden-print" onclick="toggleObject()">mostra oggetti</button>
                            
                            <?php } ?>
                                
                        </h3>
                        <div id="sortable_module">
                            
                        <?php
                        $num_mod = 1;
                        foreach ($course_modules as $module) {
                            $course_lessons = $course_obj->getCourseLessonsByModuleID($module['id']);
                            ?>

                                <div <?= 'id="' . $module['id'] . '"' ?> class="panel panel-info">
                                    <div class="panel-heading">
                                        MODULO <?= $num_mod++ ?>: <?= $module['title'] ?>
                                        
                                    <?php if ($_SESSION['user']['role'] == 1000) { ?>

                                        <button onclick="editModuleOjbect(<?= $module['id'] ?>)" class="btn btn-xs btn-default hidden-print pull-right">Modifica</button>

                                    <?php } ?>
                                    
                                    </div>
                                    <div class="panel-body">
                                        <dl class="dl-horizontal">
                                            <dt>Durata:</dt>
                                            <dd><?= $module['duration'] ?> ore</dd>
                                            <!-- <dt>Tempo massimo di esecuzione:</dt>
                                            <dd><?php //=$module['max_execution_time'] ?> min</dd> -->
                                            <dt style="vertical-align: top">Descrizione:</dt>
                                            <dd><?= $module['description'] ?></dd>
                                        </dl>
                                        <?php if (!$is_published) { ?>

                                            <button onclick="newLesson(<?= $module['id'] ?>)" class="btn">Aggiungi lezione</button>

                                        <?php } ?>
                                        <div class="lesson_box"<?= ' id="lesson_module_' . $module['id'] . '"' ?>>
                                            <h4>Lezioni inserite</h4>
                                            <div>
                                                <ul<?= ' id="sortable_' . $module['id'] .'"'?> class="list-unstyled">

                                                    <?php foreach ($course_lessons as $lesson) { ?>

                                                        <li<?= ' id="lesson_' . $lesson['id'] .'"' ?> class="lesson">
                                                            <table>
                                                                <tr>
                                                                    <td><span class="glyphicon glyphicon-menu-down"></span></td>
                                                                    <td>Lezione <?= $lesson['position'] ?>: <?= $lesson['title'] ?></td>
                                                                    <!-- <td style="width: 80px;"><?= $lesson['duration'] ?> min</td> -->
                                                                    <!-- <td style="width: 80px;"><?= $lesson['percentage_correct_answer_to_pass'] ?> %</td> -->
                                                                    <td>

                                                                        <?php if ($_SESSION['user']['role'] == 1000) { 
                                                                            if (!$is_published) { ?>

                                                                                <button onclick="editLesson(<?= $lesson['id'] ?>,<?= $module['id'] ?>)" class="btn btn-xs btn-default hidden-print">Modifica</button>
                                                                                <button onclick="removeLesson(<?= $lesson['course_module_lesson_id'] ?>)" class="btn btn-xs btn-danger hidden-print">Elimina</button>

                                                                            <?php }
                                                                        } ?>

                                                                    </td>
                                                                </tr>
                                                            </table>
                                                            <table class="lesson-content" style="display: none; text-align: center;">
                                                                <thead>
                                                                    <tr>
                                                                        <th style="width: 40px; text-align: center;">media</th>
                                                                        <th style="width: 70px; text-align: center;">data</th>
                                                                        <th style="width: 40px; text-align: center;">id</th>
                                                                        <th style="width: 300px; text-align: center;">titolo</th>
                                                                        <th style="width: 40px; text-align: center;">test</th>
                                                                        <th style="width: 40px; text-align: center;">durata</th>
                                                                        <th style="width: 40px; text-align: center;">livello</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>

                                                                <?php
                                                                $lesson_id = $lesson['id'];
                                                                $res = $learn_obj->getLearningObjByLesson($lesson_id);
                                                                foreach ($res as $single) {
                                                                    if ($single['learning_object_type_id'] == 1) {
                                                                        $icon = "img/video48.png";
                                                                    } elseif ($single['learning_object_type_id'] == 2) {
                                                                        $icon = "img/slide48.png";
                                                                    } elseif ($single['learning_object_type_id'] == 3) {
                                                                        $icon = "img/doc48.png";
                                                                    } elseif ($single['learning_object_type_id'] == 4) {
                                                                        $icon = "img/web48.png";
                                                                    }
                                                                    ?>

                                                                    <tr class="lesson-om">
                                                                        <td><img src="<?= $icon ?>"></td>
                                                                        <td><?= $single['creation_date'] ?></td>
                                                                        <td><?= $single['id'] ?></td>
                                                                        <td style="text-align: left;"><a href="javascript: void(0)" target="_blank" data-object_id="<?= $single['id'] ?>"><?= $single['title'] ?></a></td>
                                                                        <td></td>
                                                                        <td><?= $single['duration'] ?> min</td>
                                                                        <td></td>
                                                                    </tr>

                                                                <?php } ?>

                                                                </tbody>
                                                            </table>
                                                        </li>

                                                    <?php } ?>

                                                </ul>
                                            </div>

                                            <br style="clear: both"/>

                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>

    $('.retirement-date').datepicker({
        format: "dd/mm/yyyy",
        todayBtn: "linked",
        language: "it",
        autoclose: true,
        todayHighlight: true
    });

    $('.lesson > table td:first-child').click(function (e) {
        $(this).parents('.lesson').find('.lesson-content').toggle();
        var i = $(this).children('span');
        if ($(i).hasClass('glyphicon glyphicon-menu-down')) {
            $(i).removeClass('glyphicon glyphicon-menu-down').addClass('glyphicon glyphicon-menu-up');
        } else {
            $(i).removeClass('glyphicon glyphicon-menu-up').addClass('glyphicon glyphicon-menu-down');
        }
    });

</script>