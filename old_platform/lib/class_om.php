<?php

/* iWebDev di Thomas Orlandi
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging
 * to iWebDev di Thomas Orlandi. No part of this information may be used, reproduced,
 * or stored without prior written consent of iWebDev di Thomas Orlandi.
 * -----------------------------------------------------------------------------------------/
 * 3-lug-2012
 * File: class_learning_object.php
 * Project: tutor81
 *
 * Author: Thomas Orlandi :: info@iwebdev.it
 *
 */

require_once 'class_db.php';
require_once 'sanitize.php';

class T81DOM {

    // In OOP classes are usually named starting with a cap letter.
    //private $r_conn;
    //private $conn;
    //var $conn; var $rem_conn;
    var $db_conn;

    public function __construct() {
        $this->db_conn = new MySQLConn();
    }

    public function getPriceFromLicense($course_id,$license_id){
        $course_id = sanitize( $course_id, INT);
        $license_id = sanitize( $license_id, INT);
        $query = "SELECT * FROM course_price_range_sequences 
                          JOIN ranges ON ranges.id = course_price_range_sequences.range_id
			  WHERE course_id = $course_id 
                            AND $license_id BETWEEN lower_limit AND upper_limit 
                            ORDER BY ranges.price_range_sequence_id, ranges.index";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getLearningObjectByID($id) {
        $id = sanitize($id, INT);
        $query = "SELECT * FROM learning_objects WHERE id = " . $id;
        $res = $this->db_conn->query($query);
        return $res[0];
    }

    public function getActiveLearningProjectsByOM($id) {
        $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT learning_project.id as learning_project_id, unities.course_id
                  FROM learning_project
                    JOIN unities ON learning_project.id = unities.learning_project_id
                    JOIN course_course_modules ON unities.course_id = course_course_modules.course_id
                    JOIN course_module_lessons ON course_course_modules.course_module_id = course_module_lessons.course_module_id
                    JOIN lesson_learning_objects ON course_module_lessons.lesson_id = lesson_learning_objects.lesson_id
                  WHERE lesson_learning_objects.learning_object_id = $id AND learning_project.is_published_in_ecommerce = 1";
        $res = $this->db_conn->query($query);
        return isset($res) ? $res : false;
    }

    public function getOmLearningProjects($lerning_object_id = NULL) {
        $where = 1;
        if (!empty($lerning_object_id)){
            $lerning_object_id = filter_var($lerning_object_id, FILTER_SANITIZE_NUMBER_INT);
            $where .= " AND learning_objects.id = $lerning_object_id";
        }
        $query = "SELECT learning_objects.id as learning_object_id, 
                    learning_project.id as learning_project_id,
                    learning_project.title as learning_project_title,
                    learning_project.is_published_in_ecommerce as learning_project_published
                  FROM learning_objects
                    JOIN lesson_learning_objects ON learning_objects.id = lesson_learning_objects.learning_object_id
                    JOIN course_module_lessons ON lesson_learning_objects.lesson_id = course_module_lessons.lesson_id
                    JOIN course_course_modules ON course_course_modules.course_module_id = course_module_lessons.course_module_id
                    JOIN unities ON unities.course_id = course_course_modules.course_id
                    JOIN learning_project ON learning_project.id = unities.learning_project_id
                  WHERE $where";
        $res = $this->db_conn->query($query);
        return isset($res) ? $res : false;
    }

    public function getSelectLanguage() {
        $query = "SELECT * FROM languages";
        $res = $this->db_conn->query($query);
        $select_txt = '<select id="language">';
        foreach ($res as $single) {
            $select_txt .= "<option value='" . $single['id'] . "'>" . $single['name'] . "</option>";
        }
        $select_txt .= "</select>";
        return $select_txt;
    }

    public function getCategories() {
        $query = "SELECT * FROM categories ORDER BY name";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getSubCategories($id) {
        $id = sanitize($id, INT);
        $query = "SELECT * FROM subcategories WHERE category_id = " . $id . " ORDER BY position, name";
        $res = $this->db_conn->query($query);
        return $res;
    }

    private function addSubCategoryToObject($id, $object_subcategories) {
        $id = sanitize($id, INT);
        $object_subcategories = sanitize($object_subcategories, INT);
        $query = "INSERT INTO learning_object_subcategories(learning_object_id,subcategory_id) VALUES(" . $id . "," . $object_subcategories . ")";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function getList($filter = 1) {
        $filter = sanitize($filter, INT);
        switch ($filter) {
            case 0;
                $filter = "";
                break;
            case 1;
                $filter = "WHERE deleted = 0 ";
                break;
            case 2:
                $filter = "WHERE deleted = 1 ";
                break;
        }
        $query = "SELECT * FROM learning_objects " . $filter . "ORDER BY title ASC";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getGroupArguments() {
        $query = "SELECT * FROM argument_group ORDER BY id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getGroupArgumentByID($id) {
        $id = sanitize($id, INT);
        $query = "SELECT * FROM argument_group WHERE id = " . $id;
        $res = $this->db_conn->query($query);
        return $res[0];
    }

    public function getArguments() {
        $query = "SELECT * FROM arguments ORDER BY argument_group_id, id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getArgumentByID($id) {
        $id = sanitize($id, INT);
        $query = "SELECT * FROM arguments JOIN argument_group ON arguments.argument_group_id = argument_group.id WHERE arguments.id =" . $id;
        $res = $this->db_conn->query($query);
        if (count($res)) {
            return $res[0];
        } else {
            return false;
        }
    }

    public function getTypes() {
        $query = "SELECT * FROM types ORDER BY id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getTypeByID($id) {
        $id = sanitize($id, INT);
        $query = "SELECT * FROM types WHERE id = " . $id;
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getLevels() {
        $query = "SELECT * FROM level ORDER BY id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getLevelByID($id) {
        $id = sanitize($id, INT);
        $query = "SELECT * FROM level WHERE id = " . $id;
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    // ---- Fornisce le domande dell'oggetto video --------------------------------------------
    public function getVideoQuestions($id) {
        $id = sanitize($id, INT);
        $query = "SELECT * FROM video_test_interruption_points
				JOIN video_test_interruption_point_questions ON video_test_interruption_points.id = video_test_interruption_point_questions.video_test_interruption_point_id
				WHERE learning_object_id =  " . $id . " ORDER BY time";
        $res = $this->db_conn->query($query);
        return $res;
    }

    // ---- Fornisce le domande dell'oggetto slide --------------------------------------------
    public function getSlideQuestions($id) {
        $id = sanitize($id, INT);
        $query = "SELECT slides.*,slide_test_questions.id as slide_test_question_id, slide_test_questions.question_sentence_id as question_sentence_id  FROM slides JOIN slide_test_questions ON slides.id = slide_test_questions.slide_id WHERE is_question = 1 AND learning_object_id = " . $id . " ORDER BY position";
        $res = $this->db_conn->query($query);
        return $res;
    }

    // ---- Fornisce le domande dell'oggetto document -----------------------------------------
    public function getDocQuestions($id) {
        $id = sanitize($id, INT);
        $query = "SELECT doc_questions.*, question_sentences.text
		FROM doc_questions
		JOIN question_sentences ON question_sentence_id = question_sentences.id
		WHERE doc_id = $id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    // ---- Fornisce le domande dell'oggetto web -----------------------------------------
    public function getWebQuestions($id) {
        $id = sanitize($id, INT);
        $query = "SELECT web_questions.*, question_sentences.text
        						FROM web_questions
        						JOIN question_sentences ON question_sentence_id = question_sentences.id
        						WHERE web_id = $id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function createNewLearningObject($owner_user_id, $learning_object_type_id, $language_id, $percentage_correct_answer_to_pass, $description, $duration, $published_in_ecommerce, $obj_cat, $arg, $obj_lesson, $obj_module, $level_id, $title, $custom, $type_id) {
        $owner_user_id = sanitize($owner_user_id, INT);
        $code = "TEMP";
        $deleted = 0;
        $learning_object_type_id = sanitize($learning_object_type_id, INT);
        $language_id = sanitize($language_id, INT);
        $percentage_correct_answer_to_pass = sanitize($percentage_correct_answer_to_pass, INT);
        $description = $this->db_conn->escapestr($description);
        $duration = sanitize($duration, INT);
        $published_in_ecommerce = sanitize($published_in_ecommerce, INT);
        $title = $this->db_conn->escapestr($title);
        $obj_lesson = sanitize($obj_lesson, INT);
        $obj_module = sanitize($obj_module, INT);
        $arg = sanitize($arg, INT);
        $level_id = sanitize($level_id, INT);
        $custom = sanitize($custom, INT);
        $type_id = sanitize($type_id, INT);

        $query = "INSERT INTO learning_objects(owner_user_id,code,deleted,learning_object_type_id,filename_slide_pdf,
				language_id,video_filename,percentage_correct_answer_to_pass,document_filename,description,duration,
				published_in_ecommerce,title,server_filename_slide_pdf,server_video_filename,server_document_filename,argument_id,level_id, custom, type_id)
				VALUES(
				'" . $owner_user_id . "',
						'" . $code . "',
								'" . $deleted . "',
										'" . $learning_object_type_id . "',
												'',
												'" . $language_id . "',
														'',
														'" . $percentage_correct_answer_to_pass . "',
																'',
																'" . $description . "',
																		'" . $duration . "',
																				'" . $published_in_ecommerce . "',
																						'" . $title . "',
																								'','','','" . $arg . "'," . $level_id . "," . $custom . "," . $type_id . ")";
        $id = $this->db_conn->insert($query);

        foreach ($obj_cat as $single) {
            $this->addSubCategoryToObject($id, $single);
        }


        // creazione automatica di una lezione e di un modulo
        // ????? vecchio codice
        /*
          if($obj_lesson == 1){
          require_once 'class_lesson_object.php';
          $obj_lesson = new LessonObject();
          $less_id = $obj_lesson->createNewLesson($title,$description,$duration,$percentage_correct_answer_to_pass,$owner_user_id);
          $obj_lesson->addNewLearningObjectToLesson($id, $less_id);
          if($obj_module == 1){
          require_once 'class_module_object.php';
          $obj_module = new ModuleObject();
          $modu_id = $obj_module->createNewModule($title, $description, $duration, $duration);
          $obj_module->addNewLessonObjectToModule($less_id, $modu_id);
          }
          }
         */

        return $id;
    }

    public function editLearningObject($id, $language_id, $percentage_correct_answer_to_pass, $description, $duration, $title, $argument_id, $level_id, $obj_cat, $type_id, $custom) {
        $id = sanitize($id, INT);
        $language_id = sanitize($language_id, INT);
        $percentage_correct_answer_to_pass = sanitize($percentage_correct_answer_to_pass, INT);
        $description = $this->db_conn->escapestr($description);
        $duration = sanitize($duration, INT);
        $title = $this->db_conn->escapestr($title);
        $argumet_id = sanitize($argument_id, INT);
        $level_id = sanitize($level_id, INT);
        $type_id = sanitize($type_id, INT);
        $custom = sanitize($custom, INT);

        $query = "UPDATE learning_objects
				SET language_id = " . $language_id . ",
						percentage_correct_answer_to_pass = " . $percentage_correct_answer_to_pass . ",
								description = '" . $description . "',
										duration = " . $duration . ",
												title = '" . $title . "',
														argument_id = " . $argument_id . ",
																level_id = " . $level_id . ",
																		type_id = " . $type_id . ",
																				custom = " . $custom . "
																						WHERE id = " . $id;
        $res = $this->db_conn->update($query);

        $query = "DELETE FROM learning_object_subcategories WHERE learning_object_id = " . $id;
        $this->db_conn->query($query);
        foreach ($obj_cat as $single) {
            $single = sanitize($single, INT);
            $res += $this->addSubCategoryToObject($id, $single);
        }

        return $res;
    }

    public function editLearnTags($id, $argument_id, $level_id, $obj_cat, $type_id, $custom) {
        $id = sanitize($id, INT);
        $argument_id = sanitize($argument_id, INT);
        $level_id = sanitize($level_id, INT);
        $type_id = sanitize($type_id, INT);
        $custom = sanitize($custom, INT);

        $query = "UPDATE learning_objects SET argument_id = " . $argument_id . ", level_id = " . $level_id . ", type_id = " . $type_id . ", custom = " . $custom . " WHERE id = " . $id;
        $res = $this->db_conn->update($query);

        $query = "DELETE FROM learning_object_subcategories WHERE learning_object_id = " . $id;
        $this->db_conn->query($query);
        foreach ($obj_cat as $single) {
            $single = sanitize($single, INT);
            $res += $this->addSubCategoryToObject($id, $single);
        }
        return $res;
    }

    public function updateTitle($object_id, $title) {
        $object_id = sanitize($object_id, INT);
        $title = $this->db_conn->escapestr($title);
        return $this->db_conn->update("UPDATE learning_objects SET title = '$title' WHERE id = $object_id");
    }

    public function updateCode($object_id, $code) {
        $object_id = sanitize($object_id, INT);
        $code = $this->db_conn->escapestr($code);
        return $this->db_conn->update("UPDATE learning_objects SET code = '$code' WHERE id = $object_id");
    }

    public function updateVideoPath($object_id, $video_filename, $file_server) {
        $object_id = sanitize($object_id, INT);
        $video_filename = $this->db_conn->escapestr($video_filename);
        $file_server = $this->db_conn->escapestr($file_server);
        $query = "UPDATE learning_objects SET video_filename = '$video_filename', server_video_filename = '$file_server' WHERE id = $object_id";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function updateSlidePath($object_id, $filename_slide_pdf, $file_server) {
        $object_id = sanitize($object_id, INT);
        $filename_slide_pdf = $this->db_conn->escapestr($filename_slide_pdf);
        $file_server = $this->db_conn->escapestr($file_server);
        $query = "UPDATE learning_objects SET filename_slide_pdf = '$filename_slide_pdf', server_filename_slide_pdf = '$file_server' WHERE id = $object_id";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function updateDocumentPath($object_id, $document_filename, $file_server) {
        $object_id = sanitize($object_id, INT);
        $document_filename = $this->db_conn->escapestr($document_filename);
        $file_server = $this->db_conn->escapestr($file_server);
        $query = "UPDATE learning_objects SET document_filename = '$document_filename', server_document_filename = '$file_server' WHERE id = $object_id";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function updateWebPath($object_id, $web_filename, $server_name) {
        $object_id = sanitize($object_id, INT);
        $web_filename = $this->db_conn->escapestr($web_filename);
        $server_name = $this->db_conn->escapestr($server_name);
        $query = "UPDATE learning_objects SET web_filename = '$web_filename', server_web_name = '$server_name' WHERE id = $object_id";
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function addImageSlide($pos, $learn_id, $filename) {
        $pos = sanitize($pos, INT);
        $learn_id = sanitize($learn_id, INT);
        $query = "INSERT INTO slides(learning_object_id,image_filename,image_position,position)VALUES(" . $learn_id . ",'" . $filename . "'," . $pos . "," . $pos . ")";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function getLearningObjectByLessonID($lesson_id) {
        $lesson_id = sanitize($lesson_id, INT);
        $query = "SELECT learning_objects.* FROM learning_objects JOIN lesson_learning_objects ON learning_objects.id = lesson_learning_objects.learning_object_id WHERE lesson_id = " . $lesson_id . " ORDER BY position";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getCategoryByObjectID($id) {
        $id = sanitize($id, INT);
        $query = "SELECT categories.name as category_name, subcategories.name as sub_category_name, subcategories.id as sub_id,categories.id as cat_id  FROM learning_object_subcategories JOIN subcategories ON learning_object_subcategories.subcategory_id = subcategories.id JOIN categories ON subcategories.category_id = categories.id WHERE learning_object_id = " . $id;
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getQuestionsByObjectID($id) {
        $id = sanitize($id, INT);
        $query = "SELECT * FROM video_test_interruption_points
				JOIN video_test_interruption_point_questions ON video_test_interruption_points.id = video_test_interruption_point_questions.video_test_interruption_point_id
				JOIN question_sentences ON question_sentence_id = question_sentences.id WHERE learning_object_id = " . $id . " ORDER BY time";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getSlideImageByObjectID($id) {
        $id = sanitize($id, INT);
        $query = "SELECT id,image_filename FROM slides WHERE learning_object_id =  " . $id . " ORDER BY position";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getQuestionsBySlideID($id) {
        $id = sanitize($id, INT);
        $query = "SELECT * FROM slides JOIN slide_test_questions ON slides.id = slide_test_questions.slide_id JOIN question_sentences ON slide_test_questions.question_sentence_id = question_sentences.id WHERE learning_object_id =  " . $id . " ORDER BY position";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function disableByID($id) {
        $id = sanitize($id, INT);
        $query = "UPDATE learning_objects SET deleted = 1 WHERE id = " . $id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function enableByID($id) {
        $id = sanitize($id, INT);
        $query = "UPDATE learning_objects SET deleted = 0 WHERE id = " . $id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function newInterruptPoint($learning_object_id, $time) {
        $learning_object_id = sanitize($learning_object_id, INT);
        $time = sanitize($time, INT);
        $time = $time;
        $query = "INSERT INTO video_test_interruption_points(learning_object_id,time) VALUES(" . $learning_object_id . "," . $time . ")";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function newInterruptPointQuestion($interrupt_point_id, $question_id) {
        $interrupt_point_id = sanitize($interrupt_point_id, INT);
        $question_id = sanitize($question_id, INT);
        $query = "INSERT INTO video_test_interruption_point_questions(video_test_interruption_point_id,question_sentence_id) VALUES(" . $interrupt_point_id . "," . $question_id . ")";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function newSlide($learning_object_id, $position) {
        $learning_object_id = sanitize($learning_object_id, INT);
        $position = sanitize($position, INT);
        $query = "INSERT INTO slides(learning_object_id,is_question,position,image_position) VALUES(" . $learning_object_id . ",1," . $position . "," . $position . ")";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function newSlideTestQuestion($slide_id, $question_id) {
        $slide_id = sanitize($slide_id, INT);
        $question_id = sanitize($question_id, INT);
        $query = "INSERT INTO slide_test_questions(slide_id,question_sentence_id) VALUES(" . $slide_id . "," . $question_id . ")";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function updateSlidePosition($learning_object_id) {
        $learning_object_id = sanitize($learning_object_id, INT);
        $res = $this->db_conn->query("SELECT * FROM `slides` WHERE `learning_object_id` = " . $learning_object_id . " ORDER BY position");
        $conta = 1;
        foreach ($res as $single) {
            $query = "UPDATE slides SET position = " . $conta . " WHERE id = " . $single['id'];
            $conta++;
            $this->db_conn->update($query);
        }
    }

    public function getQuestionSlideDetail($slide_id) {
        $slide_id = sanitize($slide_id, INT);
        $query = "SELECT * FROM slide_test_questions WHERE slide_id = " . $slide_id;
        $res = $this->db_conn->query($query);
        return $res[0];
    }

    public function editQuestionTime($id, $time_question) {
        $id = sanitize($id, INT);
        $time_question = sanitize($time_question, INT);
        $query = "UPDATE video_test_interruption_points SET time = " . $time_question . " WHERE id = " . $id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function editSlideQuestionPosition($slide_id, $position) {
        $slide_id = sanitize($slide_id, INT);
        $position = sanitize($position, INT);
        $query = "SELECT position FROM slides WHERE id = " . $slide_id;
        $res = $this->db_conn->query($query);
        $old_position = $res[0]['position'];
        $query = "SELECT * FROM slides WHERE learning_object_id IN (SELECT learning_object_id FROM slides WHERE id = " . $slide_id . ") ORDER BY position";
        $res = $this->db_conn->query($query);
        $conta = 1;
        foreach ($res as $single) {
            if ($single['id'] != $slide_id) {
                if ($single['position'] <= $position && $old_position < $position && $single['position'] > $old_position) {
                    $query = "UPDATE slides SET position = " . $conta . " WHERE id = " . $single['id'];
                    $this->db_conn->update($query);
                    $conta++;
                } else if ($single['position'] >= $position && $old_position > $position && $single['position'] < $old_position) {
                    $conta++;
                    $query = "UPDATE slides SET position = " . $conta . " WHERE id = " . $single['id'];
                    $this->db_conn->update($query);
                } else {
                    $conta++;
                }
            } else {
                $query = "UPDATE slides SET position = " . $position . " WHERE id = " . $single['id'];
                $this->db_conn->update($query);
            }
        }
    }

    public function addDocQuestion($question_id, $doc_id) {
        $question_id = sanitize($question_id, INT);
        $doc_id = sanitize($doc_id, INT);
        $query = "INSERT INTO doc_questions (doc_id, question_sentence_id) VALUES ($doc_id, $question_id)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function addWebQuestion($question_id, $web_id) {
        $question_id = sanitize($question_id, INT);
        $web_id = sanitize($web_id, INT);
        $query = "INSERT INTO web_questions (web_id, question_sentence_id) VALUES ($web_id, $question_id)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function checkObjectInLesson($id) {
        $id = sanitize($id, INT);
        $query = "SELECT COUNT(id) as qta FROM lesson_learning_objects WHERE learning_object_id = " . $id;
        $res = $this->db_conn->query($query);
        return $res[0]['qta'];
    }

    public function checkInUse($id) {
        $id = sanitize($id, INT);
        $query = "SELECT COUNT(id) as qta FROM learning_event_learning_objects WHERE learning_object_id = " . $id;
        $res = $this->db_conn->query($query);
        return (bool) $res[0]['qta'];
    }

    public function closeiWDOM() {
        //PHP B id=30525
        //@mysql_close($this->conn);
    }

}

?>
