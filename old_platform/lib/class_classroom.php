<?php
require_once 'class_db.php';
require_once 'function.php';

class T81Classroom {

    protected $db_conn;
    
    protected $classroom_scheduled_filter_keys;
    
    public function __construct() {
        $this->db_conn = new MySQLConn();
        $this->classroom_scheduled_filter_keys = array(
            'course_type_id' => FILTER_SANITIZE_NUMBER_INT,
            'created_by' => FILTER_SANITIZE_NUMBER_INT,
            'tutor_id' => FILTER_SANITIZE_NUMBER_INT,
            'month' => FILTER_SANITIZE_NUMBER_INT,
            'start_date' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => 'FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH'),
            'province_id' => FILTER_SANITIZE_NUMBER_INT,
            'location' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => 'FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH'),
            'places' => FILTER_SANITIZE_NUMBER_INT,
            'published' => FILTER_SANITIZE_NUMBER_INT,
            'published_in_ecommerce' => FILTER_SANITIZE_NUMBER_INT,
            'note' => array('filter' => FILTER_CALLBACK, 'options' => function($note){return htmlentities($note, ENT_QUOTES);}),
            'contact_name' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => 'FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH'),
            'contact_email' => FILTER_SANITIZE_EMAIL,
            'price' => array('filter' => FILTER_SANITIZE_NUMBER_INT)
        );
    }
    
    public function sanitizeClassroomScheduledEdition($args){
        return sanitizeArray($args, $this->classroom_scheduled_filter_keys);
    }
    
    
    /**
     * 
     * @param array $args: ('course_type_id' => integer,
     *                        'created_by' => integer,
     *                        'tutor_id' => integer,
     *                        'month' => integer (1-12),
     *                        'start_date' => 'Y-m-d,
     *                        'province_id' => integer,
     *                        'published' => 0/1,
     é                        'published_in_ecommerce' => 0/1,
     *                        'note' => text,
     *                        'contact_name' => string,
     *                        'contact_email' => string email,
     *                        'price' => float);
     * @return array or false
     */
    public function getClassroomsScheduled($args = NULL){
        $and = "";
        if (!empty($args) && is_array($args)){
            $args = $this->sanitizeClassroomScheduledEdition($args);
            foreach ($args as $key => $value){
                if ($key == 'location' || $key == 'start_date' || $key == 'note' || $key == 'email') {
                    $value = $args[$key] = $this->db_conn->escapestr($value);
                }
                $and .= " AND classrooms_scheduled.$key = '$value'";
            }
        }
        $query = "SELECT classrooms_scheduled.*, 
                    classrooms_scheduled.places - IFNULL(SUM(classroom_booking.booked_places),0) as places_available, 
                    users.name as user_name,
                    users.surname as user_surname,
                    users.email as user_email,
                    companies.business_name as business_name,
                    companies.telephone as telephone,
                    provinces.name as province,
                    course_types.course_code as course_code,
                    course_types.course_description as course_description,
                    subcategories.name as subcategory, 
                    custom_categories.definition as type 
                  FROM classrooms_scheduled 
                    LEFT JOIN (SELECT * FROM classroom_booking WHERE confirmed = 1 AND deleted = 0) as classroom_booking ON classrooms_scheduled.id_classroom_scheduled = classroom_booking.classroom_scheduled_id
                    JOIN users ON classrooms_scheduled.created_by = users.id
                    JOIN companies ON classrooms_scheduled.tutor_id = companies.id
                    JOIN provinces ON classrooms_scheduled.province_id = provinces.id
                    JOIN course_types ON classrooms_scheduled.course_type_id = course_types.id_course_type
                    JOIN subcategories ON course_types.subcategory_id = subcategories.id 
                    JOIN custom_categories ON course_types.custom_category_id = custom_categories.id
                  WHERE 1 $and
                  GROUP BY classrooms_scheduled.id_classroom_scheduled";
        return $this->db_conn->query($query) ? : false;
    }
    
    /**
     * Restituisce le aule programmate a partire dal giorno dopo $start_date.
     * Calcola i posti disponibili in base aquelli programmati meno i prenotati
     * 
     * @param string $start_date (valid date/time format)
     * @return array/boolean
     */
    public function getClassroomsAvailable($start_date = "now"){
        try {
            $start_date = new DateTime($start_date);
        } catch (Exception $e) {
            //return $e->getMessage();
            return false;
        }
        $query = "SELECT classrooms_scheduled.*, 
                    classrooms_scheduled.places - IFNULL(SUM(classroom_booking.booked_places),0) as places_available, 
                    users.name as user_name, 
                    users.surname as user_surname, 
                    users.email as user_email, 
                    companies.business_name as business_name, 
                    companies.telephone as telephone, 
                    provinces.name as province, 
                    course_types.course_code as course_code,
                    course_types.course_description as course_description,
                    subcategories.name as subcategory, 
                    custom_categories.definition as type 
                  FROM classrooms_scheduled 
                    LEFT JOIN (SELECT * FROM classroom_booking WHERE confirmed = 1 AND deleted = 0) as classroom_booking ON classrooms_scheduled.id_classroom_scheduled = classroom_booking.classroom_scheduled_id 
                    JOIN users ON classrooms_scheduled.created_by = users.id 
                    JOIN companies ON classrooms_scheduled.tutor_id = companies.id 
                    JOIN provinces ON classrooms_scheduled.province_id = provinces.id 
                    JOIN course_types ON classrooms_scheduled.course_type_id = course_types.id_course_type 
                    JOIN subcategories ON course_types.subcategory_id = subcategories.id 
                    JOIN custom_categories ON course_types.custom_category_id = custom_categories.id 
                  WHERE (classrooms_scheduled.start_date = '0000-00-00' OR classrooms_scheduled.start_date > {$start_date->format('Y-m-d')})
                      AND published = 1
                  GROUP BY classrooms_scheduled.id_classroom_scheduled";
        return $this->db_conn->query($query) ? : false;
    }
    
    /**
     * 
     * @param array $args: ('course_type_id' => integer,
     *                      'created_by' => integer,
     *                      'tutor_id' => integer,
     *                      'month' => integer (1-12),
     *                      'start_date' => 'Y-m-d,
     *                      'province_id' => integer,
     *                      'location' => string,
     *                      'places' => smallint unsigned
     *                      'published' => 0/1,
     é                      'published_in_ecommerce' => 0/1),
     *                      'note' => text,
     *                      'contact_name' => string,
     *                      'contact_email => string email,
     *                      'price' => float);
     * @return integer or false
     */
    public function addClassroomScheduled($args){
        if (is_array($args)) {
            $args = $this->sanitizeClassroomScheduledEdition($args);
            if (!empty($args)){
                $args['start_date'] = $this->db_conn->escapestr($args['start_date']);
                $args['location'] = $this->db_conn->escapestr($args['location']);
                $args['note'] = $this->db_conn->escapestr($args['note']);
                $args['contact_name'] = $this->db_conn->escapestr($args['contact_name']);
                $args['contact_email'] = $this->db_conn->escapestr($args['contact_email']);
                $query = "INSERT INTO classrooms_scheduled (course_type_id, 
                            created_by, tutor_id, month, start_date,
                            province_id, location, places, published,
                            published_in_ecommerce, note, contact_name, contact_email, price) 
                          VALUES ({$args['course_type_id']}, {$args['created_by']},
                            {$args['tutor_id']}, {$args['month']}, '{$args['start_date']}',
                            {$args['province_id']}, '{$args['location']}', {$args['places']},
                            {$args['published']}, {$args['published_in_ecommerce']},
                            '{$args['note']}', '{$args['contact_name']}', '{$args['contact_email']}', '{$args['price']}')";
                return $this->db_conn->insert($query) ? : false; 
            }
        }
        return false;
    }
    
    public function setClassroomScheduledPublishedState($id_classroom_scheduled, $published){
        $id_classroom_scheduled = filter_var($id_classroom_scheduled, FILTER_SANITIZE_NUMBER_INT);
        $published = filter_var($published, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        return $this->db_conn->update("UPDATE classrooms_scheduled "
                . "SET published = $published "
                . "WHERE id_classroom_scheduled = $id_classroom_scheduled") ? true : false;
    }
    
    public function setClassroomScheduledPublishedInEcommerceState($id_classroom_scheduled, $published_in_ecommerce){
        $id_classroom_scheduled = filter_var($id_classroom_scheduled, FILTER_SANITIZE_NUMBER_INT);
        $published_in_ecommerce = filter_var($published_in_ecommerce, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        return $this->db_conn->update("UPDATE classrooms_scheduled "
                . "SET published_in_ecommerce = $published_in_ecommerce "
                . "WHERE id_classroom_scheduled = $id_classroom_scheduled") ? true : false;
    }
    
    public function deleteClassroomScheduled($id_classroom_scheduled){
        $id_classroom_scheduled = filter_var($id_classroom_scheduled, FILTER_SANITIZE_NUMBER_INT);
        if ($this->checkClassroomIsBooked($id_classroom_scheduled)) return false;
        $query = "DELETE FROM classrooms_scheduled WHERE id_classroom_scheduled = $id_classroom_scheduled";
        return $this->db_conn->delete($query) ? true : false;
    }
    
    /**
     * prenotazione di un  aula
     * 
     * @param type $classroom_scheduled_id
     * @param type $reserved_by
     * @param type $tutor_id
     * @param type $company_id
     * @param type $from_ecommerce
     * @param type $booked_places
     * @return type
     */
    public function bookingClassroom($classroom_scheduled_id,$reserved_by_user_id,$reserved_by_tutor_id,
                                        $booked_places,$from_ecommerce = 0, $customer_name = '', 
                                            $customer_email = '', $customer_phone = ''){
        $classroom_scheduled_id = filter_var($classroom_scheduled_id, FILTER_SANITIZE_NUMBER_INT);
        $reserved_by_user_id = filter_var($reserved_by_user_id, FILTER_SANITIZE_NUMBER_INT);
        $reserved_by_tutor_id = filter_var($reserved_by_tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $booked_places = filter_var($booked_places, FILTER_SANITIZE_NUMBER_INT);
        $from_ecommerce = filter_var($from_ecommerce, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $customer_name = $this->db_conn->escapestr(trim($customer_name));
        $customer_email = filter_var(trim($customer_email), FILTER_VALIDATE_EMAIL) ? $this->db_conn->escapestr(trim($customer_email)) : '';
        $customer_phone = $this->db_conn->escapestr(trim($customer_phone));
        $places = $this->db_conn->query("SELECT places FROM classrooms_scheduled 
                                         WHERE id_classroom_scheduled = $classroom_scheduled_id");
        $this->db_conn->query("LOCK TABLES classroom_booking WRITE");
        $booked = $this->db_conn->query("SELECT SUM(booked_places) as sum_booked_places FROM classroom_booking WHERE classroom_scheduled_id = $classroom_scheduled_id");
        $places_available = $places[0]['places'] - (isset($booked_places[0]['sum_booked_places']) ? $booked_places[0]['sum_booked_places'] : 0);
        if ($places_available >= $booked_places){
            $query = "INSERT INTO classroom_booking (classroom_scheduled_id, 
                                    reserved_by_user_id, reserved_by_tutor_id, booked_places, from_ecommerce, 
                                    customer_name, customer_email, customer_phone)
                    VALUES ('$classroom_scheduled_id', '$reserved_by_user_id', '$reserved_by_tutor_id', 
                            '$booked_places', '$from_ecommerce', '$customer_name',
                            '$customer_email', '$customer_phone')";
            $res = $this->db_conn->insert($query);
        }
        $this->db_conn->query("UNLOCK TABLES");
        return !empty($res) ? $res : false;
    }
    
    /**
     * Restituisce i dettagli di un aula prenotata
     * 
     * @param type $id_classroom_booking
     * @return type
     */
    public function getClassroomBooked($id_classroom_booking){
        $id_classroom_booking = filter_var($id_classroom_booking, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT classrooms_scheduled.*,
                    classroom_booking.*,
                    course_types.*, 
                    users.name as user_name, 
                    users.surname as user_surname, 
                    users.email as user_email, 
                    companies.business_name as business_name, 
                    companies.telephone as telephone, 
                    provinces.name as province
                  FROM classroom_booking 
                    JOIN classrooms_scheduled ON classroom_booking.classroom_scheduled_id = classrooms_scheduled.id_classroom_scheduled
                    JOIN users ON classrooms_scheduled.created_by = users.id 
                    JOIN companies ON classrooms_scheduled.tutor_id = companies.id
                    JOIN provinces ON classrooms_scheduled.province_id = provinces.id
                    JOIN course_types ON classrooms_scheduled.course_type_id = course_types.id_course_type
                  WHERE id_classroom_booking = '$id_classroom_booking'";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }
    
    protected function checkClassroomIsBooked($classroom_scheduled_id){
        $classroom_scheduled_id = filter_var($classroom_scheduled_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT COUNT(id_classroom_booking) as bookings 
                  FROM classroom_booking 
                  WHERE classroom_scheduled_id = '$classroom_scheduled_id'";
        $res = $this->db_conn->query($query);
        return isset($res['booking']) && ($res['booking'] > 0) ? true : false;
    }
    
    /**
     * Restituisce i dettagli delle prenotazioni confermate (default) o non confermate
     * e non cancellate (default) o cancellate
     * @param integer $tutor_id
     * @param boolean $confirmed
     * @return mixed
     */
    public function getClassroomBooking($tutor_id, $confirmed = FALSE, $deleted = FALSE){
        $tutor_id = filter_var($tutor_id, FILTER_SANITIZE_NUMBER_INT);
        $confirmed = filter_var($confirmed, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $deleted = filter_var($deleted, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $query = "SELECT classrooms_scheduled.*,
                    classroom_booking.*,
                    course_types.*,
                    companies.business_name as business_name,
                    provinces.name as province,
                    provinces.regione as regione,
                    (classrooms_scheduled.places - IFNULL(booking.booked_places, 0)) as places_available
                  FROM classroom_booking 
                    JOIN classrooms_scheduled ON classroom_booking.classroom_scheduled_id = classrooms_scheduled.id_classroom_scheduled
                    LEFT JOIN companies ON classroom_booking.reserved_by_tutor_id = companies.id
                    JOIN provinces ON classrooms_scheduled.province_id = provinces.id
                    JOIN course_types ON classrooms_scheduled.course_type_id = course_types.id_course_type
                    LEFT JOIN (
                        SELECT classroom_scheduled_id, SUM(classroom_booking.booked_places) as booked_places 
                        FROM classroom_booking WHERE confirmed = 1 AND deleted = 0
                        GROUP BY classroom_scheduled_id
                        ) as booking ON classroom_booking.classroom_scheduled_id = booking.classroom_scheduled_id
                  WHERE classrooms_scheduled.tutor_id = '$tutor_id' 
                    AND classroom_booking.confirmed = '$confirmed'
                    AND classroom_booking.deleted = '$deleted'";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : false;
    }
    
    public function confirmClassroomBooking($id_classroom_booking, $booked_places){
        $id_classroom_booking = filter_var($id_classroom_booking, FILTER_SANITIZE_NUMBER_INT);
        $booked_places = filter_var($booked_places, FILTER_SANITIZE_NUMBER_INT);
        $query = "UPDATE classroom_booking SET confirmed = 1, booked_places = '$booked_places' 
                    WHERE id_classroom_booking = '$id_classroom_booking'";
        return $this->db_conn->update($query) ? : false;
    }
    
    public function deleteClassroomBooking($id_classroom_booking, $deleted = TRUE){
        $id_classroom_booking = filter_var($id_classroom_booking, FILTER_SANITIZE_NUMBER_INT);
        $deleted = filter_var($deleted, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        $query = "UPDATE classroom_booking SET deleted = '$deleted' 
                    WHERE id_classroom_booking = '$id_classroom_booking'";
        return $this->db_conn->update($query) ? : false;
        
    }
}