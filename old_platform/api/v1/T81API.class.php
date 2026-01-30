<?php
require_once 'API.class.php';
require_once 'models/APIKey.class.php';
require_once 'models/User.class.php';

class T81API extends API {

    protected $User;
    protected $Origin;

    public function __construct($request, $origin) {
        parent::__construct($request);

        // Abstracted out for example
        $APIKey = new APIKey();
        $User = new User();

        if (!array_key_exists('apiKey', $this->request)) {
            throw new Exception('No API Key provided');
        } else if (!$APIKey->verifyKey($this->request['apiKey'], $origin)) {
            throw new Exception('Invalid API Key');
        } else if (array_key_exists('token', $this->request) &&
                !$User->get('token', $this->request['token'])) {

            throw new Exception('Invalid User Token');
        }

        $this->User = $User;
        $this->Origin = $origin;
    }

    /**
     * Test Enpoint
     */
    protected function test() {
        $msg = '';
        switch ($this->method){
            case 'GET':
                $msg .= "SUCCESS GET REQUEST";
                break;
            case 'POST':
                $msg .= "SUCCESS POST REQUEST";
                break;
            default :
                $msg .= "Only accepts GET and POST requests";
        }
        return $msg;
    }
    
    protected function demo(){
        require_once 'models/Demo.class.php';
        $Demo = new Demo();
        if ($this->method == 'POST'){
            if (isset($this->verb) && $this->verb == 'new'){
                // create new demo
                return $Demo->create($this->request['surname'],$this->request['email']);
            }
        } else {
            return "Only accepts POST requests";            
        }
    }
    
    /**
     * endpoint / verb/args
     * coursetype                                                       (GET)
     *           /<code>id_course_type</code>(numerico)                 (GET)
     *           /bySubcategory/<code>subcategory_id</code>(numerico)   (GET)
     * 
     * @return string|\coursetype
     */
    protected function coursetype(){
        require_once 'models/CourseType.class.php';
        $CourseType = new CourseType();
        if ($this->method == 'GET'){
            switch ($this->verb){
                case '':
                    if (isset($this->args[0]) && is_numeric($this->args[0])) {
                        return $CourseType->getCourseTypeDetail($this->args[0]);
                    } else {
                        return $CourseType->getCourseTypesList();
                    }
                    break;
                case 'bySubcategory':
                    if (isset($this->args[0])) {
                        return $CourseType->getAllCourseTypesListBySubcategory($this->args[0]) ? : "FALSE";
                        
                    }
                    break;
                default:
                    return "Error: invalid Verb or Arguments";
            }
        } else {
            return "Only accepts GET requests";            
        }
    }
    
    /**
     * endpoint / verb/args
     * classroom/           									(GET)
     *          /<code>id_classroom</code>(numerico)            (GET)
     *          /byCourseType/<code>id_course_type</code>(numerico) (GET)
     *          /byProvince/<code>id_province</code>(numerico)  (GET)
     *          /byTutor/<code>id_tutor</code>(numerico)        (GET)
     *          /reserve                                        (POST + data)
     * 
     * @return string|\Classroom
     */
    protected function classroom(){
        require_once 'models/Classroom.class.php';
        $classroom = new Classroom();
        if ($this->method == 'GET'){
            switch ($this->verb){
                case '':
                    if (isset($this->args[0]) && is_numeric($this->args[0])) {
                        // return classroom with id_classroom=$this->args[0]
                        return "ON COMING";
                    } else {
                        // return all classroom
                        return "ON COMING";
                    }
                    break;
                case 'byCourseType':
                    if (isset($this->args[0])) {
                        return $classroom->getClassroomPublishedInEcommerceByCourseType($this->args[0]) ? : "FALSE";
                    }
                    break;
                case 'byProvince':
                    if (isset($this->args[0])) {
                        // call classroom method to return classroom by id_province=$this->args[0] 
                        return "ON COMING";
                    }
                    break;
                case 'byTutor':
                    if (isset($this->args[0])) {
                        // call classroom method to return classroom by id_tutor=$this->args[0]
                        return "ON COMING";
                    }
                    break;
                default:
                    return "Error: invalid Verb or Arguments";
            }
        } elseif ($this->method == 'POST') {
            if (!is_numeric($this->verb && $this->verb != '')){
                switch ($this->verb){
                    case 'reserve':
                        $classroom->bookingClassroom($_POST['classroom_scheduled_id'], 0, 0, $_POST['booked_places'],
                                				1, $_POST['customer_name'], $_POST['customer_email'], $_POST['customer_phone']);
						// NOTIFICA LA PRENOTAZIONE
			return "SUCCESS";
                    default:
                        return "Error: invalid Verb or Arguments";
                }    
            } else {
                return "Error: not isset Verb, required on POST requests";
            }
        } else {
            return "Only accepts GET or POST requests";            
        }
    }
    
    /**
     * endpoint / verb/args
     * elearning/           						(GET)
     *          /<code>id_elearning</code>(numerico)            	(GET)
     *          /byCourseType/<code>id_course_type</code>(numerico)     (GET)
     *          /purchase                                        	(POST + data)
     *              data is ( company = {
     *                                   }
     *                        
     *                        order = [
     *                                  id :        id dell'ordine,
     *                                  completed:  boolean vero se pagamento completato,
     *                                  items = {
     *                                          learning_project_id:    id del corso (lp), 
     *                                          qta:                    quantità acquistata, 
     *                                          price:                  prezzo singolo di acquisto senza iva
     *                                          },
     *                                          { ... }
     *                                ]
     * 
     * @return string|\Elearning
     */
    protected function elearning(){
        require_once 'models/Elearning.class.php';
        $elearning_obj = new Elearning();
        if ($this->method == 'GET'){
            switch ($this->verb){
                case 'id_elearning':
                    if (isset($this->args[0]) && is_numeric($this->args[0])) {
                        $elearning_obj->getCourseDetailFromLearningProject($this->args[0]);
                    } else {
                        $elearning_obj->getAvailableList();
                    }
                    break;
                case 'byCourseType':
                    if (isset($this->args[0])) {
                        return $elearning_obj->getElearningPublishedInEcommerceByCourseType($this->args[0]) ? : "FALSE";    
                    }
                    break;
                default:
                    return "Error: invalid Verb or Arguments";
            }
        } elseif ($this->method == 'POST') {
            if (!is_numeric($this->verb && $this->verb != '')){
                switch ($this->verb){
                    case 'purchase':
                       try {
                            $company = json_decode($_POST['company'], TRUE);
                            $order = json_decode($_POST['order'], TRUE);
                            return $elearning_obj->purchaseFromEcommerce($company, $order, $this->Origin);
                        } catch (Exception $exc) {
                            echo $exc->getTraceAsString();
                            break;
                        }
                        break;
                    default:
                        return "Error: invalid Verb or Arguments";
                }    
            } else {
                return "Error: not isset Verb, required on POST requests";
            }
        } else {
            return "Only accepts GET or POST requests";            
        }
    }

}