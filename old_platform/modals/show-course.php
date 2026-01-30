<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 07-lug-2015
 * File: modals/show-course.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_om.php';
$course_obj = new iWDCourse();
$learn_obj = new T81DOM();

$learn_id = filter_input(INPUT_POST, 'learn_id', FILTER_SANITIZE_NUMBER_INT);
$course_id = filter_input(INPUT_POST, 'course_id', FILTER_SANITIZE_NUMBER_INT);

if ($learn_id) {
    require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
    $learning_project_obj = new T81LearningProject;
    $course = $learning_project_obj->getCourseDetailFromLearningProject($learn_id);
    $course_id = $course['id'];
} else {
    $course = $course_obj->getCourseObjectByID($course_id);
}
$course_custom_categories = $course_obj->getCourseCustomCategories($course_id);
$course_modules = $course_obj->getCourseModules($course_id);
$is_published = $course_obj->is_published($course_id);
?>
<script>

    function toggleObject() {
        if ($('#list_obj > h2 > button').text() == 'mostra oggetti') {
            $('.lesson ul').show();
            $('#list_obj > h2 > button').text('nascondi oggetti');
            $('.lesson span').each(function () {
                if ($(this).hasClass('glyphicon glyphicon-chevron-down')) {
                    $(this).removeClass('glyphicon glyphicon-chevron-down').addClass('glyphicon glyphicon-chevron-up');
                }
            });
        } else {
            $('.lesson ul').hide();
            $('#list_obj > h2 > button').text('mostra oggetti');
            $('.lesson span').each(function () {
                if ($(this).hasClass('glyphicon glyphicon-chevron-up')) {
                    $(this).removeClass('glyphicon glyphicon-chevron-up').addClass('glyphicon glyphicon-chevron-down');
                }
            });
        }
    }

</script>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal"
            aria-hidden="true">×</button>
    <h3 id="myModalLabel">Dettaglio corso</h3>
</div>
<div class="modal-body">
    <div id="course_container">
        <h1 class="title_detail">

            <?php
            $file_path = "user_store/" . $course['owner_user_id'] . "/courses/ecommerce_images/thumb/" . $course['ecommerce_image_filename'];
            if (file_exists(BASE_MEDIA_PATH . $file_path) && (trim($course['ecommerce_image_filename']) != "")) {
                ?>

                <img border="0" src="media/<?= $file_path ?>"/>

<?php } ?>

            (ID:<?= $course['id'] ?>) <?= $course['title'] ?>            
        </h1>

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

                <dt>Personalizzato</dt>
                <dd><?= $course['custom'] ? 'SI' : 'NO' ?></dd>

                <dt>Codice</dt>
                <dd><?= $course['course_code'] ?></dd>

                <dt>Versione</dt>
                <dd><?= $course['version'] ?></dd>

                <dt>Riferimento normativo</dt>
                <dd><?= $course['law_reference'] ?></dd>

                <dt>Validità</dt>
                <dd><?= $course['course_validity'] ?></dd>

                <dt>Integrazione in aula</dt>
                <dd><?= $course['external_integration'] ?></dd>

                <dt>Durata Totale</dt>
                <dd><?= (integer) $course['total_duration'] ?> ore</dd>

                <dt>Durata e-learning</dt>
                <dd><?= (integer) $course['total_elearning'] ?> ore</dd>

                <dt>Tempo massimo per la conclusione</dt>
                <dd><?= $course['max_execution_time'] ?> giorni</dd>

                <dt>Prodotto da</dt>
                <dd><?= $course['producers'] ?></dd>

                <dt>Percentuale risposte esatte</dt>
                <dd><?= $course['percentage_correct_answer_to_pass'] ?> %</dd>

                <dt>Rivolto a</dt>
                <dd><?= $course['customers'] ?></dd>

                <dt>Docenti</dt>
                <dd><?= $course['course_professors'] ?></dd>

                <dt>Didattica</dt>
                <dd><?= $course['didactics'] ?></dd>

                <dt>Data di creazione</dt>
                <dd><?= $course['creation_date'] ?></dd>

                <dt>Descrizione</dt>
                <dd><?= $course['description'] ?></dd>

            </dl>
        </div>

        <div id="list_obj">
            <h2 class="subtitle_detail">Moduli inseriti
                <?php if ($course_modules) { ?>
                    <button class="btn btn-default btn-xs hidden-print" onclick="toggleObject()">mostra oggetti</button>
<?php } ?>
            </h2>
            <ul id="sortable_module" class="list-unstyled">
                <?php
                $num_mod = 1;
                foreach ($course_modules as $module) {
                    $course_lessons = $course_obj->getCourseLessonsByModuleID($module['id']);
                    ?>

                    <li <?= 'id="' . $module['id'] . '"' ?> class="module_box">
                        <h5>MODULO <?= $num_mod++ ?>: <?= $module['title'] ?></h5>
                        <table style="margin-left: -2px; width: 100%">
                            <tr>
                                <td style="width: 100px;">Durata:</td>
                                <td><?= $module['duration'] ?> ore</td>

                                <?php /*
                                  <td>Tempo massimo di esecuzione:</td>
                                  <td><?=$module['max_execution_time'] ?> min</td>
                                 */ ?>

                            </tr>
                            <tr>
                                <td style="vertical-align: top">Descrizione:</td>
                                <td><?= $module['description'] ?></td>
                            </tr>
                        </table>

                        <br>
                        <div class="lesson_box"<?= ' id="lesson_module_' . $module['id'] . '"' ?>>
                            <h2 class="subtitle_detail">Lezioni inserite</h2>
                            <div>
                                <ul<?= ' id="sortable_' . $module['id'] . '"' ?>  class="list-unstyled">
    <?php foreach ($course_lessons as $lesson) { ?>
                                        <li<?= ' id="lesson_' . $lesson['id'] . '"' ?> class="lesson">
                                            <table>
                                                <tr>
                                                    <td><span class="glyphicon glyphicon-chevron-down"></span></td>
                                                    <td>Lezione <?= $lesson['position'] ?>: <?= $lesson['title'] ?></td>

                                                    <?php /*
                                                      <td style="width: 80px;"><?= $lesson['duration'] ?> min</td>
                                                      <td style="width: 80px;"><?= $lesson['percentage_correct_answer_to_pass'] ?> %</td>
                                                     */ ?>

                                                </tr>
                                            </table>
                                            <ul class="list-unstyled" style="display: none">

                                                <?php
                                                $lesson_id = $lesson['id'];
                                                $res = $learn_obj->getLearningObjectByLessonID($lesson_id);
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

                                                    <li class="lesson-om">
                                                        <table>
                                                            <tr>
                                                                <td style="width:30px;"><img src="<?= $icon ?>"></td>
                                                                <td style="width:30px; text-align: center"><?= "({$single['id']})" ?></td>
                                                                <td style="width:300px;"><?= $single['title'] ?></td>
                                                                <td style="width:80px; text-align: right"><?= "({$single['duration']} min)" ?></td>
                                                                <td style="width:80px; text-align: right"><?= "({$single['battute']} battute)" ?></td>
                                                            </tr>
                                                        </table>

                                                    </li>

        <?php } ?>

                                            </ul>
                                        </li>

    <?php } ?>

                                </ul>
                            </div>

                            <br style="clear: both"/>

                        </div>
                    </li>

<?php } ?>

            </ul>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button class="btn btn-info" onclick="$('#course_container').printArea()">Stampa</button>
    <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Chiudi</button>
</div>
<script>
    $('.lesson > table td:first-child').click(function (e) {
        $(this).parents('li.lesson').find('ul').toggle();
        var i = $(this).children('span');
        if ($(i).hasClass('glyphicon glyphicon-chevron-down')) {
            $(i).removeClass('glyphicon glyphicon-chevron-down').addClass('glyphicon glyphicon-chevron-up');
        } else {
            $(i).removeClass('glyphicon glyphicon-chevron-up').addClass('glyphicon glyphicon-chevron-down');
        }
    });

</script>