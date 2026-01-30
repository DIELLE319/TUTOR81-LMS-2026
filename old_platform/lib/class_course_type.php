<?php
require_once 'class_db.php';
require_once 'function.php';

class T81CourseType {

    var $db_conn;
    
    public static $course_type_filter_keys = array(
        'id_course_type' => FILTER_SANITIZE_NUMBER_INT,
        'course_code' => array('filter' => FILTER_SANITIZE_STRING),
        'course_description' => array('filter' => FILTER_SANITIZE_STRING),
        'duration' => FILTER_SANITIZE_NUMBER_INT,
        'subcategory_id' => FILTER_SANITIZE_NUMBER_INT,
        'custom_category_id' => FILTER_SANITIZE_NUMBER_INT
    );

    public function __construct() {
        $this->db_conn = new MySQLConn();
    }
    
    public static function sanitizeCourseType($args){
        return sanitizeArray($args, self::$course_type_filter_keys);
    }
    
    /**
     * Restituisce la lista dei corsi tipo, con le categorie e sottocategorie
     * 
     * @return array of Course Types / false
     */
    public function getCourseTypesList(){
        $query = "SELECT course_types.*, 
                    subcategories.name as subcategory,
                    categories.name as category,
                    custom_categories.definition as type 
                  FROM course_types 
                    JOIN subcategories ON course_types.subcategory_id = subcategories.id 
                    JOIN categories ON subcategories.category_id = categories.id
                    JOIN custom_categories ON course_types.custom_category_id = custom_categories.id";
        return $this->db_conn->query($query) ? : false;
    }
    
    /**
     * Restituisce la lista dei corsi tipo, con le categorie e sottocategorie
     * sia elearning che in aula
     * 
     * @return array of Course Types / false
     */
    public function getAllCourseTypesListBySubcategory($subcategory_id){
        $subcategory_id = filter_var($subcategory_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT course_types.*, 
                    subcategories.name as subcategory,
                    categories.name as category,
                    custom_categories.definition as type 
                  FROM course_types 
                    JOIN subcategories ON course_types.subcategory_id = subcategories.id 
                    JOIN categories ON subcategories.category_id = categories.id
                    JOIN custom_categories ON course_types.custom_category_id = custom_categories.id
                  WHERE subcategory_id = $subcategory_id";
        return $this->db_conn->query($query) ? : false;
    }
    
    /**
     * Restituisce la lista dei corsi tipo in aula, con le categorie e sottocategorie
     * 
     * @return array of Course Types / false
     */
    public function getClassroomCourseTypesListBySubcategory($subcategory_id){
        $subcategory_id = filter_var($subcategory_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT course_types.*, 
                    subcategories.name as subcategory,
                    categories.name as category,
                    custom_categories.definition as type 
                  FROM course_types 
                    JOIN subcategories ON course_types.subcategory_id = subcategories.id 
                    JOIN categories ON subcategories.category_id = categories.id
                    JOIN custom_categories ON course_types.custom_category_id = custom_categories.id
                  WHERE classroom = 1 AND subcategory_id = $subcategory_id";
        return $this->db_conn->query($query) ? : false;
    }
    
    /**
     * Restituisce il dettaglio del corso tipo con id = <code>$id_course_type</code>
     * 
     * @param integer $id_course_type
     * @return array corso / false
     */
    public function getCourseTypeDetail($id_course_type){
        $id_course_type = filter_var($id_course_type, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT course_types.*, subcategories.name as subcategory, custom_categories.definition as type 
                  FROM course_types 
                    JOIN subcategories ON course_types.subcategory_id = subcategories.id 
                    JOIN custom_categories ON course_types.custom_category_id = custom_categories.id
                  WHERE id_course_type = '$id_course_type'";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }
    
    /**
     * Aggiunge un corso tipo per le aule
     * 
     * @param $args = array(
     *     'id_course_type' => integer,
     *     'course_code' => string,
     *     'course_description' => string,
     *     'duration' => integer,
     *     'subcategory_id' => integer,
     *     'custom_category_id' => integer
     * )
     * 
     * @return integer id del corso / false
     */
    public function addCourseType($args){
        if (is_array($args)) {
            $args = self::sanitizeCourseType($args);
            if (!empty($args)){
                $args['course_code'] = $this->db_conn->escapestr($args['course_code']);
                $args['course_description'] = $this->db_conn->escapestr($args['course_description']);
                $query = "INSERT INTO course_types (
                                course_code,
                                course_description,
                                duration,
                                subcategory_id,
                                custom_category_id
                            ) 
                          VALUES (
                                '{$args['course_code']}', 
                                '{$args['course_description']}',
                                {$args['duration']}, 
                                {$args['subcategory_id']}, 
                                {$args['custom_category_id']}
                            )";
                $res = $this->db_conn->insert($query) ? : false;
                return $res;
            }
        }
        return false;
    }
    
}