<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 18-ago-2015
 * File: api/v1/models/Classroom.class.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.api.php';
require_once BASE_LIBRARY_PATH . 'class_classroom.php';

class Classroom extends T81Classroom {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getClassroomPublishedInEcommerceByCourseType($course_type_id){
        $course_type_id = filter_var($course_type_id, FILTER_SANITIZE_NUMBER_INT);
        $brochure_url = "http://" . $_SERVER['HTTP_HOST'] . BASE_WEBSITE_PATH . "media/public/classroom/scheduled/brochure_";
        $query = "SELECT * FROM (SELECT classrooms_scheduled.*,
                    CONCAT('$brochure_url', classrooms_scheduled.id_classroom_scheduled, '.pdf') as brochure_url,
                    classrooms_scheduled.places - IFNULL(SUM(classroom_booking.booked_places),0) as places_available, 
                    users.name as user_name, 
                    users.surname as user_surname, 
                    users.email as user_email, 
                    companies.business_name as business_name, 
                    companies.telephone as telephone, 
                    provinces.name as province, 
                    provinces.regione as regione,
                    course_types.course_code as course_code,
                    course_types.course_description as course_description,
                    subcategories.name as subcategory, 
                    custom_categories.definition as type 
                  FROM classrooms_scheduled 
                    LEFT JOIN classroom_booking ON classrooms_scheduled.id_classroom_scheduled = classroom_booking.classroom_scheduled_id 
                    JOIN users ON classrooms_scheduled.created_by = users.id 
                    JOIN companies ON classrooms_scheduled.tutor_id = companies.id 
                    JOIN provinces ON classrooms_scheduled.province_id = provinces.id 
                    JOIN course_types ON classrooms_scheduled.course_type_id = course_types.id_course_type 
                    JOIN subcategories ON course_types.subcategory_id = subcategories.id 
                    JOIN custom_categories ON course_types.custom_category_id = custom_categories.id 
                  WHERE classrooms_scheduled.course_type_id = $course_type_id
                    AND classrooms_scheduled.start_date > CURDATE()
                    AND published_in_ecommerce = 1
                    AND (classroom_booking.deleted = 0 OR classroom_booking.deleted IS NULL)
                  GROUP BY classrooms_scheduled.id_classroom_scheduled
                  ORDER BY classrooms_scheduled.start_date) as classroom WHERE classroom.places_available > 0";
        return $this->db_conn->query($query) ? : false;
    }
}