<?php

require_once 'class_db.php';
require_once 'sanitize.php';
require_once 'function.php';

class iWDCourse {

    var $db_conn;
    protected $date_start_table_purchase = '2023-01-01 00:00:00';

    public function __construct() {
        $this->db_conn = new MySQLConn();
    }

    public function is_published($course_id) {
        $course_id = sanitize($course_id, INT);
        $query = "SELECT COUNT(id) as qta FROM unities WHERE course_id = " . $course_id;
        $res = $this->db_conn->query($query);
        return (boolean) $res[0]['qta'];
    }

    public function countLearningProjectUsersNotAssigned($company_id){
        $query = "SELECT 
                        COUNT(id) as id
                    FROM 
                        learning_project_users as LPU
                    WHERE
                        LPU.finish_within >= now()
                        AND LPU.assigned = 0
                        AND LPU.id_company in (SELECT companies.id FROM companies JOIN users ON users.id = owner_user_id WHERE owner_user_id IN (SELECT id FROM users WHERE company_id = $company_id))
                    ORDER BY LPU.id DESC";

        $res = $this->db_conn->query($query);
        return isset($res) ? $res[0]['id'] : false;
    }

    public function getCourseObjectByID($id) {
        $id = sanitize($id, INT);
        $query = "SELECT * FROM courses WHERE courses.id = " . $id;
        $res = $this->db_conn->query($query);
        if ($res) {
            return $res[0];
        } else {
            return false;
        }
    }

    public function getLearningProjectFromCourse($course_id) {
        $course_id = sanitize($course_id, INT);
        $query = "SELECT unities.course_id as course_id, learning_project.*
		FROM unities JOIN learning_project ON unities.learning_project_id = learning_project.id WHERE course_id = $course_id
		ORDER BY learning_project.is_published_in_ecommerce DESC";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    /**
     * Restituisce la lista dei corsi che può acquistare l'azienda <code>$company_id</code>
     * con i dettagli delle quantità acquistate.
     * Se viene passata la subcategory recupera solo i corsi relativi alla subcategory
     *
     * @param type $company_id
     * @return type
     */
    public function getCourseDetailedListOfAvailableLearningProject($company_id, $subcategory_id = NULL) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $subcategory_id = filter_var($subcategory_id, FILTER_SANITIZE_NUMBER_INT);
        $and_subcategory = $subcategory_id > 0 ? " AND subcategory_id = $subcategory_id" : "";
        $query = "SELECT
                    learning_project.id as learning_project_id,
                    learning_project.ecommerce_image_filename as ecommerce_image_filename,
                    courses.id as course_id,
                    learning_project.title as title,
                    categories.name as category,
                    subcategories.name as subcategory,
                    types.description as type,
                    courses.total_elearning as duration,
                    courses.owner_user_id as owner_user_id
                  FROM learning_project
                    JOIN unities ON unities.learning_project_id = learning_project.id
                    JOIN courses ON course_id = courses.id
                    JOIN subcategories ON courses.subcategory_id = subcategories.id
                    JOIN categories ON subcategories.category_id = categories.id
                    JOIN types ON courses.type_id = types.id
                  WHERE unit_type_id = 3
                    AND is_published_in_ecommerce = 1
                    AND ( reserved_to = '' OR FIND_IN_SET('$company_id',reserved_to) )
                    $and_subcategory
                GROUP BY learning_project_id
                ORDER BY title";
        $res = $this->db_conn->query($query);

        // add detail
        for ($i = 0; $i < count($res); $i++) {
            $custom_categories = $this->getCourseCustomCategories($res[$i]['course_id']);
            foreach ($custom_categories as $single_category) {
                $res[$i][$single_category['fl_definition']] = $single_category['definition'];
            }

            $query = "SELECT SUM(qta) as qta FROM tutors_purchases WHERE customer_company_id = $company_id AND learning_project_id = {$res[$i]['learning_project_id']}";
            $qta = $this->db_conn->query($query);
            $res[$i]['qty_purchased'] = isset($qta[0]['qta']) ? $qta[0]['qta'] : 0;

            // Per evitare problemi legati al passaggio di un corsista da un'azienda a
            // un'altra MODIFICARE la query facendo riferimento non al corsista, ma 
            // all'acquisto (riferirsi all'acquisto e poi alla ditta oppure aggiungere
            // una colonna per registrare la ditta al momento dell'assegnazione)   <<----- FATTO
            /* old: $query = "SELECT COUNT(learning_project_users.id) as qta FROM learning_project_users JOIN users ON user_id = users.id 
              WHERE users.company_id = $company_id AND learning_project_id = {$res[$i]['learning_project_id']}"; */
            // vengono contate come assegnate
            $query = "SELECT 
                                    COUNT(learning_project_users.id) as qty_licensed,
                                    SUM(learning_project_users.assigned) as qty_assigned
                                  FROM learning_project_users
                                    LEFT JOIN users ON user_id = users.id
				  WHERE learning_project_id = {$res[$i]['learning_project_id']}
                                    AND (
                                        id_company = $company_id 
                                        OR users.company_id = $company_id
                                        )";

            $licenses = $this->db_conn->query($query);
            $res[$i]['qty_licensed'] = $licenses[0]['qty_licensed'];
            $res[$i]['qty_assigned'] = $licenses[0]['qty_assigned'];
        }

        return isset($res[0]) ? $res : false;
    }

    /**
     * Restituisce la lista dei corsi che può acquistare l'azienda <code>$company_id</code>
     * con i dettagli delle quantità acquistate.
     * Se viene passata la subcategory recupera solo i corsi relativi alla subcategory
     *
     * @param type $company_id
     * @return type
     */
    public function getCourseDetailedListOfAvailableLearningProjectEcommerce($company_id, $subcategory_id = NULL, $id_course= NULL) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $and_categories = "";
        if ($subcategory_id == "A") {
            $and_categories = " AND categories.id = 5";
            $and_subcategory = "";
        } elseif ($subcategory_id == "B") {
            $and_categories = " AND categories.id = 8";
            $and_subcategory = "";
        }
        elseif ($subcategory_id == "C") {
            $and_categories = " AND categories.id = 4";
            $and_subcategory = "";
        }
        else {
            $subcategory_id = filter_var($subcategory_id, FILTER_SANITIZE_NUMBER_INT);
            $and_subcategory = $subcategory_id > 0 ? " AND subcategory_id = $subcategory_id" : "";
        }
        $id_course = filter_var($id_course, FILTER_SANITIZE_NUMBER_INT);
        $and_course = $id_course > 0 ? " AND courses.id = $id_course" : "";


        $query = "SELECT
        learning_project.id as learning_project_id,
        learning_project.description as lp_description,
                    learning_project.ecommerce_image_filename as ecommerce_image_filename,
                    courses.id as course_id,
                    learning_project.title as title,
                    categories.name as category,
                    subcategories.name as subcategory,
                    types.description as type,
                    courses.total_elearning as duration,
                    courses.owner_user_id as owner_user_id,
                    courses.video_link as video,
                    courses.max_execution_time as execution_time,
                    courses.description as single_description,
                    courses.law_reference as reference_law,
                    courses.video_link as video_courses,
                    courses.didactics as didactics_course,
                    courses.percentage_correct_answer_to_pass as percentage_answer_to_pass,
                    courses.customers as destinatari,
                    courses.course_validity as course_validita,
                    custom_categories.definition,
                    courses.external_integration,
                    courses.targets,
                    courses.requirements
                  FROM learning_project
                    JOIN unities ON unities.learning_project_id = learning_project.id
                    JOIN courses ON course_id = courses.id
                    JOIN subcategories ON courses.subcategory_id = subcategories.id
                    JOIN categories ON subcategories.category_id = categories.id
                    JOIN types ON courses.type_id = types.id
                    
                    INNER JOIN course_custom_categories on course_custom_categories.course_id = courses.id 
                    INNER JOIN custom_categories on custom_categories.id = course_custom_categories.custom_category_id 

                  WHERE unit_type_id = 3
                        AND is_published_in_ecommerce >= 1
                        AND courses.custom != 1
                        AND (courses.type_id = 3 OR courses.type_id = 4 OR courses.type_id = 2)
                        AND (types.id = 3)
                        AND ( learning_project.reserved_to = '' OR FIND_IN_SET('$company_id',learning_project.reserved_to) )
         
                        $and_subcategory
                        $and_course
                        $and_categories
                
              GROUP BY learning_project_id
                 ORDER BY  custom_categories.definition DESC, learning_project.title
        ";


        $res = $this->db_conn->query($query);
        $counter = sizeof($res);

        // add detail
        for ($i = 0; $i < count($res); $i++) {
            $custom_categories = $this->getCourseCustomCategories($res[$i]['course_id']);
            foreach ($custom_categories as $single_category) {
                $res[$i][$single_category['fl_definition']] = $single_category['definition'];
            }
        }

        // add detail
//        for ($i = 0; $i < count($res); $i++) {
//            $custom_categories = $this->getCourseCustomCategories($res[$i]['course_id']);
//            foreach ($custom_categories as $single_category) {
//                $res[$i][$single_category['fl_definition']] = $single_category['definition'];
//            }
//
//            $query = "SELECT SUM(qta) as qta FROM tutors_purchases WHERE customer_company_id = $company_id AND learning_project_id = {$res[$i]['learning_project_id']}";
//            $qta = $this->db_conn->query($query);
//            $res[$i]['qty_purchased'] = isset($qta[0]['qta']) ? $qta[0]['qta'] : 0;
        // Per evitare problemi legati al passaggio di un corsista da un'azienda a
        // un'altra MODIFICARE la query facendo riferimento non al corsista, ma
        // all'acquisto (riferirsi all'acquisto e poi alla ditta oppure aggiungere
        // una colonna per registrare la ditta al momento dell'assegnazione)   <<----- FATTO
        /* old: $query = "SELECT COUNT(learning_project_users.id) as qta FROM learning_project_users JOIN users ON user_id = users.id
          WHERE users.company_id = $company_id AND learning_project_id = {$res[$i]['learning_project_id']}"; */
        // vengono contate come assegnate
//            $query = "SELECT
//                                    COUNT(learning_project_users.id) as qty_licensed,
//                                    SUM(learning_project_users.assigned) as qty_assigned
//                                  FROM learning_project_users
//                                    LEFT JOIN users ON user_id = users.id
//				  WHERE learning_project_id = {$res[$i]['learning_project_id']}
//                                    AND (
//                                        id_company = $company_id
//                                        OR users.company_id = $company_id
//                                        )";
//
//            $licenses = $this->db_conn->query($query);
//            $res[$i]['qty_licensed'] = $licenses[0]['qty_licensed'];
//            $res[$i]['qty_assigned'] = $licenses[0]['qty_assigned'];
//        }

        return isset($res[0]) ? $res : false;
    }

    /**
     * Restituisce la lista dei corsi che può acquistare l'azienda <code>$company_id</code>
     * con i dettagli delle quantità acquistate.
     * Se viene passata la subcategory recupera solo i corsi relativi alla subcategory
     *
     * @param type $company_id
     * @return type
     */
    public function getCourseDetailedListOfAvailableLearningProjectByCompany($company_id, $category_id = NULL, $subcategory_id = NULL, $id_course= NULL) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        
        $category_id = filter_var($category_id, FILTER_SANITIZE_NUMBER_INT);
        $and_category = $category_id > 0 ? " AND categories.id = $category_id" : "";
        
        $subcategory_id = filter_var($subcategory_id, FILTER_SANITIZE_NUMBER_INT);
        $and_subcategory = $subcategory_id > 0 ? " AND subcategory_id = $subcategory_id" : "";
        
        $id_course = filter_var($id_course, FILTER_SANITIZE_NUMBER_INT);
        $and_course = $id_course > 0 ? " AND courses.id = $id_course" : "";


        $query = "SELECT
                    learning_project.id as learning_project_id,
                    learning_project.ecommerce_image_filename as ecommerce_image_filename,
                    courses.id as course_id,
                    learning_project.title as title,
                    categories.name as category,
                    subcategories.name as subcategory,
                    types.description as type,
                    courses.total_elearning as duration,
                    courses.owner_user_id as owner_user_id,
                    courses.video_link as video,
                    courses.max_execution_time as execution_time,
                    courses.description as single_description,
                    courses.law_reference as reference_law,
                    courses.video_link as video_courses,
                    courses.didactics as didactics_course,
                    courses.percentage_correct_answer_to_pass as percentage_answer_to_pass,
                    courses.customers as destinatari,
                    courses.course_validity as course_validita,
                    custom_categories.definition,
                    learning_project.reserved_to
                  FROM learning_project
                    JOIN unities ON unities.learning_project_id = learning_project.id
                    JOIN courses ON course_id = courses.id
                    JOIN subcategories ON courses.subcategory_id = subcategories.id
                    JOIN categories ON subcategories.category_id = categories.id
                    JOIN types ON courses.type_id = types.id
                    
                    INNER JOIN course_custom_categories on course_custom_categories.course_id = courses.id 
                    LEFT JOIN custom_categories on custom_categories.id = course_custom_categories.custom_category_id AND custom_categories.lev_1 = 1 

                  WHERE unit_type_id = 3
                        AND is_published_in_ecommerce = 1
                        AND (courses.type_id != 1)
                        AND ( learning_project.reserved_to = '' OR FIND_IN_SET('$company_id',learning_project.reserved_to) )
         
                        $and_subcategory
                        $and_course
                        $and_category
                
              GROUP BY learning_project_id
                 ORDER BY custom_categories.lev_3 ASC, courses.total_elearning ASC, learning_project.title ASC
        ";


        $res = $this->db_conn->query($query);
        $counter = sizeof($res);

        // add detail
        for ($i = 0; $i < count($res); $i++) {
            $custom_categories = $this->getCourseCustomCategories($res[$i]['course_id']);
            foreach ($custom_categories as $single_category) {
                $res[$i][$single_category['fl_definition']] = $single_category['definition'];
            }
        }
        return isset($res[0]) ? $res : false;
    }

    /**
     * 
     * @param type $company_id
     * @param type $subcategory_id
     * @param type $id_course
     * @return type
     */
    public function getCourseCourseEcommerce($company_id, $subcategory_id = NULL, $id_course= NULL) {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $and_categories = "";
        if ($subcategory_id == "A") {
            $and_categories = " AND categories.id = 5";
            $and_subcategory = "";
        } elseif ($subcategory_id == "B") {
            $and_categories = " AND categories.id = 1";
            $and_subcategory = "";
        }
        elseif ($subcategory_id == "C") {
            $and_categories = " AND categories.id = 4";
            $and_subcategory = "";
        }
        else {
            $subcategory_id = filter_var($subcategory_id, FILTER_SANITIZE_NUMBER_INT);
            $and_subcategory = $subcategory_id > 0 ? " AND subcategory_id = $subcategory_id" : "";
        }
        $id_course = filter_var($id_course, FILTER_SANITIZE_NUMBER_INT);
        $and_course = $id_course > 0 ? " AND courses.id = $id_course" : "";

        $query = "SELECT
                    learning_project.id,
                    courses.subcategory_id,
	            count(learning_project.id) as counter,
	            categories.name as category
                  FROM learning_project
                    JOIN unities ON unities.learning_project_id = learning_project.id
                    JOIN courses ON unities.course_id = courses.id
                    JOIN subcategories ON courses.subcategory_id = subcategories.id
                    JOIN categories ON subcategories.category_id = categories.id
                    JOIN types ON courses.type_id = types.id
                  WHERE unit_type_id = 3
                    AND is_published_in_ecommerce >= 1
                    AND courses.custom != 1
                    AND (courses.type_id != 1)
                    AND ( reserved_to = '' OR FIND_IN_SET('$company_id',reserved_to) )
                    $and_subcategory
                    $and_course
                    $and_categories
                GROUP BY courses.subcategory_id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getCourseType($course_id){
        $course_id = sanitize($course_id, INT);
        $query = "SELECT course_custom_categories.*, owner_user_id, category_id, lev_1, lev_2, lev_3, definition, abrv, reserved_company_id
                          FROM course_custom_categories JOIN custom_categories ON course_custom_categories.custom_category_id = custom_categories.id
                          WHERE course_id = $course_id AND lev_1 = 1";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getCourseRisk($course_id){
        $course_id = sanitize($course_id, INT);
        $query = "SELECT course_custom_categories.*, owner_user_id, category_id, lev_1, lev_2, lev_3, definition, abrv, reserved_company_id
                          FROM course_custom_categories JOIN custom_categories ON course_custom_categories.custom_category_id = custom_categories.id
                          WHERE course_id = $course_id AND lev_1 = 2";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }

    public function getListActiveCourse($subcategory_id = 0, $reserved_to = ""){
        $subcategory_id = sanitize($subcategory_id, INT);
        $reserved_to = sanitize($reserved_to, PARANOID);
        $and_subcategory= "";
        if ($subcategory_id){
            $and_subcategory = " AND courses.subcategory_id = $subcategory_id";
        }
        $query = "SELECT unities.learning_project_id, unities.course_id, learning_project.title as learn_title,
                            learning_project.description as learn_description,
                            learning_project.owner_user_id as learn_owner_user_id,
                            learning_project.reserved_to as learn_reserved_to, courses.*
                          FROM learning_project
                            JOIN unities ON learning_project.id = unities.learning_project_id
                            JOIN courses ON unities.course_id = courses.id
                          WHERE learning_project.is_published_in_ecommerce = 1 AND learning_project.reserved_to = '$reserved_to'
                            $and_subcategory
                          ORDER BY learning_project.title, courses.subcategory_id";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    /**
     * Restituisce la lista di tutti i corsi in base a se sono pubblicati o meno
     * ecommerce, in accordo all'argomento (boolean) passato.
     * Se non viene passato alcun argomento restituisce tutti i corsi.
     *
     * @param type $is_published_in_ecommerce (qualsiasi valore che verrà poi validato come boolean)
     * @return array di corsi / false;
     */
    public function getCourseDetailedList($is_published_in_ecommerce = NULL) {
        if (!isset($is_published_in_ecommerce)) $and_is_published_in_ecommerce = '';
        else {
            $is_published_in_ecommerce = filter_var($is_published_in_ecommerce, FILTER_VALIDATE_BOOLEAN);
            $and_is_published_in_ecommerce = ' AND is_published_in_ecommerce = ' . ($is_published_in_ecommerce ? '1' : '0');
        }
        $query = "SELECT
                    learning_project.id as learning_project_id,
                    courses.id as course_id,
                    learning_project.title as title,
                    categories.name as category,
                    subcategories.name as subcategory,
                    types.description as type,
                    courses.total_elearning as duration,
                    courses.owner_user_id as owner_user_id
                  FROM learning_project
                    JOIN unities ON unities.learning_project_id = learning_project.id
                    JOIN courses ON course_id = courses.id
                    JOIN subcategories ON courses.subcategory_id = subcategories.id
                    JOIN categories ON subcategories.category_id = categories.id
                    JOIN types ON courses.type_id = types.id
                  WHERE unit_type_id = 3
                    $and_is_published_in_ecommerce
                GROUP BY learning_project_id
                ORDER BY title";
        $res = $this->db_conn->query($query);

        // add detail
        for ($i = 0; $i < count($res); $i++) {
            $custom_categories = $this->getCourseCustomCategories($res[$i]['course_id']);
            foreach ($custom_categories as $single_category) {
                $res[$i][$single_category['fl_definition']] = $single_category['definition'];
            }
        }

        return isset($res[0]) ? $res : false;
    }

    public function getCourseCustomCategories($course_id) {
        $course_id = sanitize($course_id, INT);
        $query = "SELECT course_custom_categories.*, owner_user_id, category_id, lev_1, lev_2, lev_3, definition, abrv, reserved_company_id
		FROM course_custom_categories JOIN custom_categories ON course_custom_categories.custom_category_id = custom_categories.id
		WHERE course_id = $course_id";
        $res = $this->db_conn->query($query);
        foreach ($res as $key => $custom_category) {
            $firstLevel = $this->getFirstLevelCustomCategory($custom_category['lev_1']);
            $res[$key]['fl_definition'] = $firstLevel['definition'];
            $res[$key]['fl_abrv'] = $firstLevel['abrv'];
        }
        return $res;
    }

    public function getFirstLevelCustomCategory($lev_1) {
        $lev_1 = sanitize($lev_1, INT);
        $query = "SELECT * FROM custom_categories WHERE lev_1 = $lev_1 AND lev_2 = 0 AND lev_3 = 0";
        $res = $this->db_conn->query($query);
        return $res[0];
    }

    public function getCourseModules($id) {
        $id = sanitize($id, INT);
        $query = "SELECT course_modules.* FROM course_course_modules JOIN course_modules ON course_module_id = course_modules.id WHERE course_id = " . $id . " ORDER BY position,id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getCourseLessonsByModuleID($id) {
        $id = sanitize($id, INT);
        $query = "SELECT lessons.*,course_module_lessons.id as course_module_lesson_id,course_module_lessons.position as position FROM course_module_lessons JOIN lessons ON lesson_id = lessons.id WHERE course_module_id = " . $id . " ORDER BY position,id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getCategories() {
        $query = "SELECT * FROM categories ORDER BY position";
        $res = $this->db_conn->query($query);
        return $res;
    }
    
    public function getAllSubcategories() {
        $query = "SELECT * FROM subcategories";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getSubCategories($id) {
        $id = sanitize($id, INT);
        $query = "SELECT * FROM subcategories WHERE category_id = " . $id . " ORDER BY position, name";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getDetailSubcategory($subcategory_id) {
        $subcategory_id = sanitize($subcategory_id, INT);
        $query = "SELECT subcategories.*, categories.name as cat_name, categories.abrv
				FROM subcategories JOIN categories ON category_id = categories.id
				WHERE subcategories.id =" . $subcategory_id;
        $res = $this->db_conn->query($query);
        if ($res) {
            return $res[0];
        } else {
            return false;
        }
    }

    public function getCategoryAbrvFromSubcategory($subcategory_id) {
        $subcategory_id = sanitize($subcategory_id, INT);
        $query = "SELECT abrv	FROM subcategories JOIN categories ON category_id = categories.id
				WHERE subcategories.id =" . $subcategory_id;
        $res = $this->db_conn->query($query);
        return $res[0]['abrv'];
    }

    public function getTypes() {
        $query = "SELECT * FROM types ORDER BY description";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getDetailType($type_id) {
        $type_id = sanitize($type_id, INT);
        $query = "SELECT * FROM types WHERE id = " . $type_id;
        $res = $this->db_conn->query($query);
        return $res[0];
    }

    public function getTypeAbrv($type_id) {
        $type_id = sanitize($type_id, INT);
        $query = "SELECT abrv FROM types WHERE id = " . $type_id;
        $res = $this->db_conn->query($query);
        return $res[0]['abrv'];
    }

    public function getCustomCategoriesByOwner($owner_user_id) {
        $owner_user_id = sanitize($owner_user_id, INT);
        $query = "SELECT custom_categories.*, categories.name, categories.abrv as cat_abrv
		FROM custom_categories JOIN categories ON custom_categories.category_id = categories.id
		WHERE reserved_company_id = 0 OR owner_user_id = $owner_user_id ORDER BY category_id, lev_1, lev_2, lev_3";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getGeneralCustomCategoriesByOwner($owner_user_id) {
        $owner_user_id = sanitize($owner_user_id, INT);
        $query = "SELECT * FROM custom_categories
		WHERE category_id = 0 AND (reserved_company_id = 0 OR owner_user_id = $owner_user_id)
		ORDER BY lev_1, lev_2, lev_3";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getSpecificCustomCategoriesByOwner($owner_user_id, $category_id) {
        $owner_user_id = sanitize($owner_user_id, INT);
        $category_id = sanitize($category_id, INT);
        $query = "SELECT custom_categories.*
		FROM custom_categories 
		WHERE FIND_IN_SET('$category_id', category_id)>0 AND (reserved_company_id = 0 OR owner_user_id = $owner_user_id) ORDER BY lev_1, lev_2, lev_3";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getModulesByCourseID($course_id) {
        $course_id = sanitize($course_id, INT);
        $query = "SELECT course_modules.* FROM course_course_modules JOIN course_modules ON course_course_modules.course_module_id = course_modules.id WHERE course_course_modules.course_id = " . $course_id . " ORDER BY position";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getLessonsByModule($module_id) {
        $module_id = sanitize($module_id, INT);
        $query = "SELECT lessons.* FROM course_module_lessons JOIN lessons ON course_module_lessons.lesson_id = lessons.id WHERE course_module_id = " . $module_id . " ORDER BY position";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getLearningObjByLesson($lesson_id) {
        $lesson_id = sanitize($lesson_id, INT);
        $query = "SELECT learning_objects.* FROM lesson_learning_objects JOIN learning_objects ON learning_objects.id = lesson_learning_objects.learning_object_id WHERE lesson_id = " . $lesson_id . " ORDER BY position";
        $res = $this->db_conn->query($query);
        return $res;
    }

    function create($created_by, $title, $subcategory_id, $type_id, $custom, $version, $law, $validity, $integration, $total_duration, $total_elearning, $max_duration, $producer, $professor, $customer, $didactics, $perc, $video, $description) {
        $title = $this->db_conn->escapestr($title);
        $subcategory_id = sanitize($subcategory_id, INT);
        $type_id = sanitize($type_id, INT);
        $custom = sanitize($custom, INT);
        $version = $this->db_conn->escapestr($version);
        $law = $this->db_conn->escapestr($law);
        $validity = $this->db_conn->escapestr($validity);
        $integration = $this->db_conn->escapestr($integration);
        $total_duration = $this->db_conn->escapestr($total_duration);
        $total_elearning = $this->db_conn->escapestr($total_elearning);
        $max_duration = $this->db_conn->escapestr($max_duration);
        $producer = $this->db_conn->escapestr($producer);
        $professor = $this->db_conn->escapestr($professor);
        $customer = $this->db_conn->escapestr($customer);
        $didactics = $this->db_conn->escapestr($didactics);
        $perc = $this->db_conn->escapestr($perc);
        $video = $this->db_conn->escapestr($video);
        $description = $this->db_conn->escapestr($description);
        $user_id = sanitize($created_by, INT);
        $course_code = sha1($title);
        $code = "";
        $query = "INSERT INTO courses(
				title,
				max_execution_time,
				description,
				law_reference,
				course_contents,
				video_link,
				brochure_pdf_filename,
				server_brochure_pdf_filename,
				didactics,
				course_professors,
				percentage_correct_answer_to_pass,
				creation_date,
				owner_user_id,
				code,
				producers,
				ecommerce_image_filename,
				course_code,
				version,
				customers,
				max_duration,
				total_duration,
				total_elearning,
				external_integration,
				course_validity,
				subcategory_id,
				type_id,
				custom) VALUES(
				'" . $title . "',
						'" . $max_duration . "',
								'" . $description . "',
										'" . $law . "',
												'',
												'" . $video . "',
														'',
														'',
														'" . $didactics . "',
																'" . $professor . "',
																		'" . $perc . "',
																				'" . date("Y-m-d H:i:s") . "',
																						'" . $user_id . "',
																								'" . $course_code . "',
																										'" . $producer . "',
																												'" . $course_code . "',
																														'" . $code . "',
																																'" . $version . "',
																																		'" . $customer . "',
																																				'" . $max_duration . "',
																																						'" . $total_duration . "',
																																								'" . $total_elearning . "',
																																										'" . $integration . "',
																																												'" . $validity . "',
																																														" . $subcategory_id . ",
																																																" . $type_id . ",
																																																		" . $custom . ")";

        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function edit($course_id, $title, $custom, $version, $law_reference, $validity, $integration, $total_duration, $total_elearning, $max_duration, $producer, $professor, $customer, $didactics, $perc, $video, $description, $subcategory_id, $type_id) {
        $course_id = sanitize($course_id, INT);
        $title = $this->db_conn->escapestr($title);
        $custom = sanitize($custom, INT);
        $version = $this->db_conn->escapestr($version);
        $law_reference = $this->db_conn->escapestr($law_reference);
        $validity = $this->db_conn->escapestr($validity);
        $integration = $this->db_conn->escapestr($integration);
        $total_duration = $this->db_conn->escapestr($total_duration);
        $total_elearning = $this->db_conn->escapestr($total_elearning);
        $max_duration = $this->db_conn->escapestr($max_duration);
        $producer = $this->db_conn->escapestr($producer);
        $professor = $this->db_conn->escapestr($professor);
        $customer = $this->db_conn->escapestr($customer);
        $didactics = $this->db_conn->escapestr($didactics);
        $perc = $this->db_conn->escapestr($perc);
        $video = $this->db_conn->escapestr($video);
        $description = $this->db_conn->escapestr($description);
        $subcategory_id = sanitize($subcategory_id, INT);
        $type_id = sanitize($type_id, INT);
        $code_course = "";
        $query = "UPDATE courses SET
				title = '" . $title . "',
						max_execution_time = '" . $max_duration . "',
								description = '" . $description . "',
										law_reference = '" . $law_reference . "',
												video_link = '" . $video . "',
														didactics = '" . $didactics . "',
																course_professors = '" . $professor . "',
																		percentage_correct_answer_to_pass = '" . $perc . "',
																				producers = '" . $producer . "',
																						course_code = '" . $code_course . "',
																								version = '" . $version . "',
																										customers = '" . $customer . "',
																												max_duration = '" . $max_duration . "',
																														total_duration = '" . $total_duration . "',
																																total_elearning = '" . $total_elearning . "',
																																		external_integration = '" . $integration . "',
																																				course_validity = '" . $validity . "',
																																				subcategory_id = $subcategory_id,
																																				type_id = $type_id,
																																				custom = $custom
																																				WHERE id = " . $course_id;

        $res = $this->db_conn->update($query);
        return $res;
    }

    public function closeCourse($course_id) {
        $course_id = sanitize($course_id, INT);
        $query = "UPDATE courses SET closed = 1 WHERE id = " . $course_id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function openCourse($course_id) {
        $course_id = sanitize($course_id, INT);
        $query = "UPDATE courses SET closed = 0 WHERE id = " . $course_id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function removeCourse($course_id) {
        $course_id = sanitize($course_id, INT);
        $query = "UPDATE courses SET deleted = 1 WHERE id = " . $course_id;
        $res = $this->db_conn->update($query);
        return $res;
    }

    public function addCourseCustomCategory($course_id, $custom_category_id) {
        $course_id = sanitize($course_id, INT);
        $custom_category_id = sanitize($custom_category_id, INT);
        $query = "INSERT INTO course_custom_categories (course_id, custom_category_id) VALUES ($course_id, $custom_category_id)";
        $res = $this->db_conn->insert($query);
        return $res;
    }

    public function deleteCourseCustomCategory($course_id, $custom_category_id) {
        $course_id = sanitize($course_id, INT);
        $custom_category_id = sanitize($custom_category_id, INT);
        $query = "DELETE FROM course_custom_categories WHERE course_id = $course_id AND custom_category_id = $custom_category_id";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getList() {
        $query = "SELECT * FROM courses WHERE deleted = 0 ORDER BY title ASC";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getObjectList() {
        $query = "SELECT * FROM learning_objects WHERE deleted = 0 ORDER BY title ASC";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getObjectIdInCourse($course_id) {
        $query = "SELECT lesson_learning_objects.learning_object_id
		FROM lesson_learning_objects
		JOIN course_module_lessons ON lesson_learning_objects.lesson_id = course_module_lessons.lesson_id
		JOIN course_course_modules ON course_module_lessons.course_module_id = course_course_modules.course_module_id
		WHERE course_course_modules.course_id = $course_id";
        $res = $this->db_conn->query($query);
        $learning_objects = array();
        foreach ($res as $id) {
            array_push($learning_objects, $id['learning_object_id']);
        }
        return $learning_objects;
    }

    public function getObjectListDistinct($course_id) {
        $course_id = sanitize($course_id, INT);
        $query = "SELECT learning_objects.* FROM learning_objects WHERE deleted = 0 AND id NOT IN
		(SELECT lesson_learning_objects.learning_object_id
		FROM lesson_learning_objects
		JOIN course_module_lessons ON lesson_learning_objects.lesson_id = course_module_lessons.lesson_id
		JOIN course_course_modules ON course_module_lessons.course_module_id = course_course_modules.course_module_id
		WHERE course_course_modules.course_id = $course_id) ORDER BY title ASC";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getPriceList($course_id) {
        $course_id = sanitize($course_id, INT);
        $query = "SELECT *,course_price_range_sequences.id as ref_id FROM course_price_range_sequences JOIN ranges ON range_id = ranges.id JOIN price_range_sequences ON price_range_sequences.id = price_range_sequence_id WHERE course_id = " . $course_id . " ORDER BY lower_limit";
        $res = $this->db_conn->query($query);
        return $res;
    }

    public function getStandardRange() {
        $query = "SELECT * FROM ranges WHERE id = 86 OR id = 87 OR id = 88 ORDER BY lower_limit";
        return $this->db_conn->query($query);
    }

    public function addPrice($course_id, $price, $price_range_sequence_id = 2, $upper_limit = 999999, $lower_limit = 1) {
        $course_id = sanitize($course_id, INT);
        $price = sanitize($price, INT);
        $price_range_sequence_id = sanitize($price_range_sequence_id, INT);
        $upper_limit = sanitize($upper_limit, INT);
        $lower_limit = sanitize($lower_limit, INT);
        $query = "INSERT INTO ranges (upper_limit, lower_limit, price_range_sequence_id)
		VALUES ($upper_limit, $lower_limit, $price_range_sequence_id)";
        $res = $this->db_conn->insert($query);
        $query = "INSERT INTO course_price_range_sequences (range_id, price, course_id) VALUES ($res, $price, $course_id)";
        return $this->db_conn->insert($query);
    }

    public function addStandardPrice($course_id, $range_id, $price) {
        $course_id = sanitize($course_id, INT);
        $range_id = sanitize($range_id, INT);
        $price = sanitize($price, INT);
        $query = "INSERT INTO course_price_range_sequences (range_id, price, course_id) VALUES ($range_id, $price, $course_id)";
        return $this->db_conn->insert($query);
    }

    public function editPrice($ref_id, $price) {
        $ref_id = sanitize($ref_id, INT);
        $price = sanitize($price, INT);
        $query = "UPDATE course_price_range_sequences SET price = $price WHERE id = $ref_id";
        return $this->db_conn->update($query);
    }

    public function removePrice($ref_id, $range_id) {
        $ref_id = sanitize($ref_id, INT);
        $range_id = sanitize($range_id, INT);
        $query = "DELETE FROM course_price_range_sequences WHERE id = $ref_id";
        $this->db_conn->query($query);
        $query = "DELETE FROM ranges WHERE id = $range_id";
        return $this->db_conn->query($query);
    }

    public function editRange($range_id, $upper_limit, $lower_limit) {
        $range_id = sanitize($range_id, INT);
        $upper_limit = sanitize($upper_limit, INT);
        $lower_limit = sanitize($lower_limit, INT);
        $query = "UPDATE ranges SET upper_limit = $upper_limit, lower_limit = $lower_limit WHERE id = $range_id";
        return $this->db_conn->update($query);
    }

    public function courseHasCustomCategory($course_id, $custom_category_id){
        $course_id = filter_var($course_id, FILTER_SANITIZE_NUMBER_INT);
        $custom_category_id = filter_var($custom_category_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT COUNT(*) as qty FROM course_custom_categories
                  WHERE course_id = $course_id AND custom_category_id = $custom_category_id";
        $res = $this->db_conn->query($query);
        return $res[0]['qty'] > 0 ? true : false;
    }

    public function closeiWDCourse() {
        //PHP B id=30525
        //@mysql_close($this->conn);
    }

    public function getLearningProjectUsersCompany($company_id){
        $query = "SELECT 
                        LPU.*,
                        learning_project.title as title,
                        companies.business_name as business_name,
                        users.name, users.surname, users.email as email_accountholder
                    FROM 
                        learning_project_users as LPU
                        JOIN tutors_purchases ON tutors_purchases.id = LPU.tutor_purchase_id
                        JOIN learning_project ON learning_project.id = LPU.learning_project_id
                        JOIN companies ON companies.id = LPU.id_company
                        LEFT JOIN users ON LPU.user_id = users.id
                    WHERE
                        LPU.finish_within >= now()
                        AND LPU.user_id != 0
                        AND LPU.assigned = 1
                        AND LPU.id_company = $company_id
                    ORDER BY LPU.id DESC";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getLearningProjectUsersByTutor($company_id, $finish_within = FALSE){
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $LPU_finish_within = '';
        if ($finish_within){
            $finish_within = filter_var($finish_within, FILTER_SANITIZE_STRING);
            $LPU_finish_within = 'LPU.finish_within >= "' . $finish_within . '" AND ';
        }
        $query = "SELECT 
                        LPU.*,
                        learning_project.title as title,
                        companies.business_name as business_name,
                        users.name, users.surname, users.email as email_accountholder
                    FROM 
                        learning_project_users as LPU
                        JOIN tutors_purchases ON tutors_purchases.id = LPU.tutor_purchase_id
                        JOIN learning_project ON learning_project.id = LPU.learning_project_id
                        JOIN companies ON companies.id = LPU.id_company
                        LEFT JOIN users ON LPU.user_id = users.id
                    WHERE
                        $LPU_finish_within
                        LPU.id_company in (SELECT companies.id FROM companies JOIN users ON users.id = owner_user_id WHERE owner_user_id IN (SELECT id FROM users WHERE company_id = $company_id))
                    ORDER BY LPU.id DESC";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getLearningProjectUsers($limit = 1000){
        $limit = filter_var($limit, FILTER_SANITIZE_NUMBER_INT);
        $limit = !empty($limit) ? " LIMIT 1000" : "";
        $query = "SELECT 
                        LPU.*,
                        learning_project.title as title,
                        companies.business_name as business_name,
                        users.name, users.surname, users.email as email_accountholder
                    FROM 
                        learning_project_users as LPU
                        JOIN learning_project ON learning_project.id = LPU.learning_project_id
                        JOIN companies ON companies.id = LPU.id_company
                        LEFT JOIN users ON LPU.user_id = users.id
                    ORDER BY LPU.id DESC $limit";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getPurchasesCompany($company_id){
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT 
                CP.course_price,
                unities.course_id as U_course_id,
                unities.learning_project_id as U_learn_id,
                TP.*,
                learning_project.title as title,
                companies.business_name as business_name,
                U1.surname as surname,
                U1.name as name
            FROM 
                tutors_purchases AS TP
                JOIN learning_project ON learning_project.id = TP.learning_project_id
                JOIN companies ON companies.id = TP.customer_company_id
                LEFT JOIN users as U1 ON TP.user_company_ref = U1.id
		JOIN unities ON unities.learning_project_id = learning_project.id
                LEFT JOIN (SELECT course_price_range_sequences.price as course_price, course_price_range_sequences.course_id as course_id FROM course_price_range_sequences JOIN ranges ON range_id = ranges.id JOIN price_range_sequences ON price_range_sequences.id = price_range_sequence_id GROUP BY course_price_range_sequences.course_id ORDER BY lower_limit) as CP
                ON CP.course_id = unities.course_id
            WHERE
                TP.creation_date > '$this->date_start_table_purchase' 
                AND
                TP.customer_company_id = $company_id
            ORDER BY TP.id DESC";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getPurchasesTutor($company_id, $from_date = NULL, $to_date = NULL){
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $from_date = !is_null($from_date) && ($from_date instanceof DateTime)? $from_date : new DateTime($this->date_start_table_purchase);
        //$d = DateTime::createFromFormat('Y-m-d H:i:s', $from_date);
        //if (!$d || $d->format('Y-m-d H:i:s') != $from_date) { $from_date = '2020-01-01 00:00:00';}
        //if (!validate_datetime($from_date)) { $from_date = '2020-01-01 00:00:00'; }
        $to_date = !is_null($to_date) && ($to_date instanceof DateTime)? $to_date : new DateTime();
//        if (is_null($to_date)) {
//            $to_date = date("Y-m-d H:i:s");            
//        } else {
//            $to_date = filter_var($to_date, FILTER_SANITIZE_STRING);
//            
//            $d = DateTime::createFromFormat('Y-m-d H:i:s', $to_date);
//            if (!$d || $d->format('Y-m-d H:i:s') != $to_date) { $to_date = date("Y-m-d H:i:s");}
//            if (!validate_datetime($datetime)) { $to_date = date("Y-m-d H:i:s"); }
//        }
        $from_date = $from_date->format('Y-m-d H:i:s');
        $to_date = $to_date->format('Y-m-d H:i:s');
        $query = "SELECT 
                CP.course_price,
                unities.course_id as U_course_id,
                unities.learning_project_id as U_learn_id,
                TP.*,
                learning_project.title as title,
                companies.business_name as business_name,
		U2.surname as surname_tutor,
                U2.name as name_tutor,
                U1.surname as surname,
                U1.name as name
            FROM 
                tutors_purchases AS TP
                JOIN learning_project ON learning_project.id = TP.learning_project_id
                JOIN companies ON companies.id = TP.customer_company_id
                LEFT JOIN users as U1 ON TP.user_company_ref = U1.id
                LEFT JOIN users as U2 ON TP.tutor_id = U2.id
		JOIN unities ON unities.learning_project_id = learning_project.id
                LEFT JOIN (SELECT course_price_range_sequences.price as course_price, course_price_range_sequences.course_id as course_id FROM course_price_range_sequences JOIN ranges ON range_id = ranges.id JOIN price_range_sequences ON price_range_sequences.id = price_range_sequence_id GROUP BY course_price_range_sequences.course_id ORDER BY lower_limit) as CP
                ON CP.course_id = unities.course_id
            WHERE
                TP.creation_date > '$from_date' 
                AND
                TP.creation_date < '$to_date'
                AND
                TP.customer_company_id in (SELECT companies.id FROM companies JOIN users ON users.id = owner_user_id WHERE owner_user_id IN (SELECT id FROM users WHERE company_id = $company_id))
            ORDER BY TP.id DESC";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    /**
     * Restituisce tutti gli acquisti
     * 
     * @return type
     */
    public function getAllPurchases(){
        $query = "SELECT 
CP.course_price,
                unities.course_id as U_course_id,
                unities.learning_project_id as U_learn_id,
                    TP.*,
                    learning_project.title as title,
                    companies.business_name as business_name,
                    tutor_companies.business_name as tutor_company,
                    U2.surname as surname_tutor,
                    U2.name as name_tutor,
                    U1.surname as surname,
                    U1.name as name
                FROM 
                    tutors_purchases AS TP
                    JOIN learning_project ON learning_project.id = TP.learning_project_id
                    JOIN companies ON companies.id = TP.customer_company_id
                    LEFT JOIN users as U1 ON TP.user_company_ref = U1.id
                    LEFT JOIN users as U2 ON TP.tutor_id = U2.id
                    JOIN users as U3 ON companies.owner_user_id = U3.id
                    JOIN companies as tutor_companies ON U3.company_id = tutor_companies.id
		JOIN unities ON unities.learning_project_id = learning_project.id
               LEFT JOIN (SELECT course_price_range_sequences.price as course_price, course_price_range_sequences.course_id as course_id FROM course_price_range_sequences JOIN ranges ON range_id = ranges.id JOIN price_range_sequences ON price_range_sequences.id = price_range_sequence_id GROUP BY course_price_range_sequences.course_id ORDER BY lower_limit) as CP
                ON CP.course_id = unities.course_id
                WHERE 
                TP.creation_date > '$this->date_start_table_purchase'
                ORDER BY TP.id DESC";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }

    public function getLearningProjectUserByLicence($licence_code){
        $query = "SELECT 
                    LPU.*
                FROM 
                    learning_project_users as LPU
                    JOIN tutors_purchases ON tutors_purchases.id = LPU.tutor_purchase_id
                WHERE
                    LPU.learning_project_pwd = '$licence_code'";

        $res = $this->db_conn->query($query);
        return isset($res) ? $res[0] : false;
    }


    public function getLearningProjectUsersEcommerce(){
        $query = "SELECT 
                    LPU.*
                FROM 
                    learning_project_users as LPU
                    JOIN tutors_purchases ON tutors_purchases.id = LPU.tutor_purchase_id
                WHERE
                    LPU.finish_within >= now() AND LPU.user_id = 0
                ORDER BY LPU.id ASC";

        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }


}