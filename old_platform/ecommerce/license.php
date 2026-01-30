<?php
/**
 * Created by PhpStorm.
 * User: Davide
 * Date: 18/01/2017
 * Time: 23.26
 */

require_once dirname(__FILE__).'/../config.php';
require_once BASE_LIBRARY_PATH . 'class_purchase.php';
require_once BASE_LIBRARY_PATH . 'sanitize.php';

$purchase_obj = new iWDPurchase();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

$res = 0;

/**
 * NUOVA ASSEGNAZIONE
 */
if ($op_type === 'assign_new') {

    // se la data di inizio del corso non è impostata definisco data di inizio, di fine e giorni di alert
    if (!isset($_POST['start'])) {
        require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
        $learning_project_obj = new T81LearningProject();
        $learning_project = $learning_project_obj->getCourseDetailFromLearningProject($_POST['learn_prj']);
        $start_date = date("Y-m-d");
        $end_date = date("Y-m-d", strtotime($start_date . $learning_project['max_execution_time'] . 'days'));
        $alert = 15;
    } else {
        $start_date = $_POST['start'];
        $end_date = $_POST['end'];
        $alert = $_POST['alert'];
    }

    $res = $purchase_obj->createNewLicense($_POST['user_id'], $_POST['learn_prj'], $_POST['tutor_id'], $start_date, $end_date, $alert, $_POST['id_company'], $_POST['accreditation_code']);

    // invia notifica assegnazione
    require_once BASE_LIBRARY_PATH . 'class_notification.php';
    $not_obj = new Tutor81Notification();
    $not_obj->notifyCourseAssignment($res);

    /**
     * NUOVO ACQUISTO
     */
}
elseif ($op_type === 'new_ecommerce_purchase') {
    require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
    require_once BASE_LIBRARY_PATH . 'class_company.php';
    
    $learning_project_obj = new T81LearningProject();
    $company_obj = new T81Company();
    
    $destination_email = $_POST['email'];
    $amount = $_POST['amount'];
    $learningproject_id = $_POST['learningproject_id'];
    $arr_ext_po_number = array("");
    $arr_cost_centre_id = array(0);
    $user_company_ref = $_POST['user_company_ref'];
    $tutor_id = filter_input(INPUT_POST, 'tutorid',FILTER_SANITIZE_NUMBER_INT);
    $tutor_company_id = filter_input(INPUT_POST, 'tutor_company_id',FILTER_SANITIZE_NUMBER_INT);
    $company_id = $_POST['customercompany_id'];
    $accreditation_code = "";
    $payment_type = $_POST['payment'];
    $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING);

    // TODO: take max_execution time from database
    $learning_project = $learning_project_obj->getCourseDetailFromLearningProject($learningproject_id);
    $start_date = date("Y-m-d");
    $max_execution_time = "300";
    $end_date = date("Y-m-d", strtotime($start_date . $max_execution_time . 'days'));
    $alert = 15;
    $price = $_POST['price'];
    $total = $_POST['total'];
    $tutor_company = $company_obj->getBusinessDetail($tutor_company_id);

    $purchase_id = $purchase_obj->purchaseCourse(
        $company_id,
        $learningproject_id,
        $amount,
        $user_company_ref,
        0,
        $arr_ext_po_number,
        $arr_cost_centre_id,
        0,
        $price*(1-$tutor_company['discount']/100)
    );

    if ($purchase_id) {
        require_once BASE_LIBRARY_PATH . 'class_notification.php';
        require_once BASE_LIBRARY_PATH . 'class_learning_event.php';
        require_once BASE_LIBRARY_PATH . 'class_user.php';
        $not_obj = new Tutor81Notification();
        $ler_obj = new Tutor81LearningEvt();
        $user_obj = new T81User();
        
        $not_obj->notifyPurchaseEcommerce($destination_email, $learning_project, $total, $purchase_id);

        //require_once BASE_LIBRARY_PATH . 'class_company.php';
        //$usr_obj = new T81User();
        //$tutor_user = $usr_obj->getDetail($tutor_id);
        //$not_obj->notifyPurchaseEcommerce($tutor_user["email"], $learning_project, $price, $purchase_id);
        // Generare un entry per ogni licenza con la quantita arrivata
        for($i = 0; $i < $amount; $i++) {
            $res = $purchase_obj->createNewEcommerceLicense(
                0,
                $learningproject_id,
                $tutor_id,
                $start_date,
                $end_date,
                $alert,
                $company_id,
                $accreditation_code,
                $purchase_id,
                $destination_email
            );
//            $purchase_detail = $ler_obj->getPurchaseDetailById($res);
//            $not_obj->notifyUnlockLicenceEcommerce($purchase_detail, $destination_email, $learning_project, $i, $start_date, $end_date);
        }
    }
    $res = 1;


    /**
     * CALCOLA CORSI ELEARNING ACQUISTATI MA NON ASSEGNATI
     */
}
elseif ($op_type === 'new_backend_purchase') {
    require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
    require_once BASE_LIBRARY_PATH . 'class_notification.php';
    require_once BASE_LIBRARY_PATH . 'class_learning_event.php';
    $learning_project_obj = new T81LearningProject();
    $not_obj = new Tutor81Notification();
    $ler_obj = new Tutor81LearningEvt();

    $for_existing_users = filter_input(INPUT_POST, 'for_existing_users', FILTER_VALIDATE_BOOLEAN);
    $company_id         = filter_input(INPUT_POST, 'customercompany_id', FILTER_SANITIZE_NUMBER_INT);
    $tutor_id           = filter_input(INPUT_POST, 'tutor_id', FILTER_SANITIZE_NUMBER_INT);
    $amount             = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_INT);
    $course_id          = filter_input(INPUT_POST, 'course_id', FILTER_SANITIZE_NUMBER_INT);
    $learningproject_id = filter_input(INPUT_POST, 'learningproject_id', FILTER_SANITIZE_NUMBER_INT);
    $usercompany_ref    = filter_input(INPUT_POST, 'usercompany_ref', FILTER_SANITIZE_NUMBER_INT);
    $payment_type       = filter_input(INPUT_POST, 'payment', FILTER_SANITIZE_STRING);
    $price              = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $destination_email  = filter_input(INPUT_POST, 'destination_email', FILTER_SANITIZE_EMAIL);
    $employees          = json_decode($_POST['employees'], TRUE);
    $start_date         = filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING);
    $end_date           = filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING);
   
    $arr_ext_po_number = array("");
    $arr_cost_centre_id = array(0);
    $accreditation_code = "";
    $isAssigned = TRUE;

    if ($usercompany_ref == "0") {
        require_once BASE_LIBRARY_PATH . 'class_user.php';
        $usr_obj = new T81User();
        // get the user from $company_id
        // getUserCompany
        $usercompany_ref = $usr_obj->getUserCompanyOwner($company_id);
    }

    $learning_project = $learning_project_obj->getCourseDetailFromLearningProject($learningproject_id);
    
    $start_date = date("Y-m-d");
    $max_execution_time = $learning_project['max_execution_time'] ? : "90";
    $end_date = date("Y-m-d", strtotime($start_date . $max_execution_time . 'days'));
    $alert = 15;
    
    if (!$for_existing_users) {  // se si tratta di utenti nuovi li creo
            
        foreach($employees as &$new_employee){

            // Genero un nuovo utente con i dati postati
            $new_employee['email']      = strtolower(filter_var($new_employee['email'], FILTER_SANITIZE_EMAIL));
            $new_employee['tax_code']   = strtoupper(trim(filter_var($new_employee['tax_code'], FILTER_SANITIZE_STRING)));

            if ($new_employee['tax_code'] == '') {
                $new_employee['name']       = '';
                $new_employee['surname']    = '';
                $new_employee['func_id']    = '';
                $new_employee['id'] = 0;
            } else {
                $new_employee['name']       = ucwords(strtolower(trim(filter_var($new_employee['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES))));
                $new_employee['surname']    = ucwords(strtolower(trim(filter_var($new_employee['surname'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES))));
                $new_employee['func_id']    = filter_var($new_employee['func_id'], FILTER_SANITIZE_NUMBER_INT);
                
                $password = $new_employee['tax_code'];
                $username = strtolower($new_employee['name'].'.'.$new_employee['surname']);

                try {

                    $new_employee['id'] = $usr_obj->createUser($tutor_id, 0, $new_employee['name'], $new_employee['surname'], $username,
                        $password, $new_employee['email'], $company_id, $new_employee['tax_code'], $new_employee['func_id']);

                    if(!is_numeric($new_employee['id'])) { // errore nella creazione dell'utente

                        throw new Exception($new_employee['id']);

                    }

                }

                catch (Exception $e) {

                    $amount -= 1; // riduco di 1 la quantità da acquistare

                    if ($e->getMessage() == 'UTENTE') {
                        $new_employee['error'] = 'EXISTING_TAX_CODE';
                    } else {
                        $new_employee['error'] = $e->getMessage();
                        //return $e->getMessage();
                    }
                }
            }
        }
    }
    
    $res = $amount;
    
    if ($amount > 0) {
        
        require_once BASE_LIBRARY_PATH . 'class_course.php';
        require_once BASE_LIBRARY_PATH . 'class_company.php';
        $course_obj = new iWDCourse();
        $comp_obj = new T81Company();

        $tutor_detail = $comp_obj->getDetail($tutor_id);
        $plan = $comp_obj->getCompanyPlan($tutor_detail['company_id']);
        $price_list = $course_obj->getPriceList($course_id);
        $price = isset($price_list[0]["price"]) ? number_format($price_list[0]["price"] * (1-$plan["discount"]/100), 2) : 0;

        $purchase_id = $purchase_obj->purchaseCourse(
            $company_id,
            $learningproject_id,
            $amount,
            $usercompany_ref,
            $tutor_id,
            $arr_ext_po_number,
            $arr_cost_centre_id,
            0,
            $price
        );

        if ($purchase_id) {

            // creo e assegno la licenza
            $licenses_email = array();
            
            foreach ($employees as &$single_employee) {
                if (key_exists('error', $single_employee)) continue;

                if ($for_existing_users) {
                    $single_employee['start_date'] = $start_date;
                    $single_employee['end_date']   = $end_date;
                    $single_employee['alert_days'] = $alert;
                }
                
                // sanitizzazione dati effettuata nel modello

                try {
                    $single_employee['licence_id'] = $purchase_obj->createNewEcommerceLicense(
                        $single_employee['id'],
                        $learningproject_id,
                        $tutor_id,
                        $single_employee['start_date'],
                        $single_employee['end_date'],
                        $single_employee['alert_days'],
                        $company_id,
                        $single_employee['accreditation_code'],
                        $purchase_id,
                        $single_employee['email'],
                        $isAssigned
                    );

                    if (!is_numeric($single_employee['licence_id'])) {
                        throw new Exception($single_employee['licence_id']);
                    }

                    $lpu_detail = $ler_obj->getPurchaseDetailById($single_employee['licence_id']);
                    $single_employee['licence_pwd'] = $lpu_detail['learning_project_pwd'];
                    // raggruppare in un array con chiave l'indirizzo email i
                    // dettagli delle lecenze comuni per inviarle in un'email comune
                    $licenses_email[$single_employee['email']][$single_employee['licence_id']] = array ('lpu_detail'        => $lpu_detail,
                                                                                                        'learning_project'  => $learning_project, 
                                                                                                        'isAssigned'        => $isAssigned
                                                                                                        );
                    //$not_obj->notifyUnlockLicenceEcommerce($lpu_detail, $single_employee['email'], $learning_project, $isAssigned);

                }

                 catch (Exception $e) {
                     $single_employee['error'] = $e->getMessage();
                 }
            }
            
            // inviare qui le email delle licenze utilizzando l'array degli indirizzi email
            if (!empty($licenses_email)) {
                foreach ($licenses_email as $single_email => $multiple_licenses) {
                    $not_obj->notifyUnlockMultipleLicences ($multiple_licenses, $single_email);
                }
            }
            $emailsended = $not_obj->notifySellTutorFromBackend($destination_email, $learning_project, $price, $purchase_id, $employees);
            $res = $amount;
        }
        else {
            $res = 0;
        }
    }
    if ($amount == 0) {
        $emailsended = $not_obj->notifyErrorSellTutorFromBackend($destination_email, $learning_project, $employees);
        $res = 'error';
    }
    

    /**
     * CALCOLA CORSI ELEARNING ACQUISTATI MA NON ASSEGNATI
     */
}
elseif ($op_type === 'new_ecommercetutorbackend_assignment') {

    require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

    $tutor_id = $_POST['tutorid'];
    $purchase_id = $_POST['purchase_id'];
    $turor_userid = $_POST['turor_userid'];
    $company_id = $_POST['user_companyid'];
    $learningproject_id = $_POST['learningproject_id'];
    $learningproject_data_inizio = $_POST['learningproject_data_inizio'];
    $learningproject_data_fine = $_POST['learningproject_data_fine'];
    $user_alert_days = $_POST['user_alert_days'];
    $user_nome = $_POST['user_nome'];
    $user_cognome = $_POST['user_cognome'];
    $user_cod_fisc = $_POST['user_cod_fisc'];
    $user_data_assunzione = $_POST['user_data_assunzione'];
    $user_unita = $_POST['user_unita'];
    $user_reparto = $_POST['user_reparto'];
    $user_tipo_utente = $_POST['user_tipo_utente'];
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT) ? : false;
    $destination_email= $_POST['user_email'];
    $learningproject_id = $_POST['learningproject_id'];
    $accreditation_code = $_POST['accreditation_code'];
    $start_date =  $learningproject_data_inizio =="" ? date("Y-m-d") : $learningproject_data_inizio;
    $max_execution_time = "300";
    $new_end_date = date("Y-m-d", strtotime($start_date . $max_execution_time . 'days'));
    $end_date = $learningproject_data_fine == "" ? $new_end_date : $learningproject_data_fine;

    $learning_project_obj = new T81LearningProject();
    $learning_project = $learning_project_obj->getCourseDetailFromLearningProject($learningproject_id);

    if ($purchase_id) {
        require_once BASE_LIBRARY_PATH . 'class_notification.php';
        require_once BASE_LIBRARY_PATH . 'class_learning_event.php';
        require_once BASE_LIBRARY_PATH . 'class_user.php';
        $not_obj = new Tutor81Notification();
        $ler_obj = new Tutor81LearningEvt();
        $usr_obj = new T81User();

        // Creazione utente non esistente, ma non anonimo
        if ($user_nome != "" || $user_cognome != "" || $user_cod_fisc != "") {  // non anonimo
            if (!$user_id) {                                                    // utente non esistente
                // Genero un nuovo utente con i dati postati
                require_once BASE_LIBRARY_PATH . 'function.php';
                $password = strtoupper($user_cod_fisc);
                $username = trim($user_nome).'.'.trim($user_cognome);

                try {
                    $user_id = $usr_obj->createUser($turor_userid, 0, $user_nome, $user_cognome, $username,
                        $password, $destination_email, $company_id, $user_cod_fisc, $user_tipo_utente);
                    if(!is_numeric($user_id)) {
                        echo $user_id;
                    } // else {
                      //  $not_obj->notifyUserName($user_id);
                    //}
                }
                catch (Exception $e) {
                    return $e->getMessage();
                }
            }
            $isAssigned = true;
        }
        else {                                                                  // anonimo
            if ($user_id > 0) {                                                 // utente esistente ??
                $isAssigned = true;
            }
            else
            {
                $isAssigned = false;                                            // utente non esistente e anonimo
            }
        }

        // Genero la licenza e la invio al destinatario
        //error_log("ecommerce/license.php --> data inizio: " + var_dump($start_date));
        //error_log("ecommerce/license.php --> data fine: " + var_dump($end_date));
        $res = $purchase_obj->createNewEcommerceLicense(
            $user_id,
            $learningproject_id,
            $tutor_id,
            $start_date,
            $end_date,
            $user_alert_days,
            $company_id,
            $accreditation_code,
            $purchase_id,
            $destination_email,
            $isAssigned
        );
        $purchase_detail = $ler_obj->getPurchaseDetailById($res);
        $not_obj->notifyUnlockLicenceEcommerce($purchase_detail, $destination_email, $learning_project, $isAssigned);

        $is_notified = $ler_obj->setLPUUnlocked($res);

    }
}
elseif ($op_type === 'get_elearning_purchase_unassigned'){
    $res = $purchase_obj->countElearningProjectUnassigned($_POST['company_id'], $_POST['learning_project_id']);


    /**
     * RIMUOVI ACQUISTO
     */
}
elseif ($op_type === 'remove_license') {

    $res = $purchase_obj->removePurchaseCourse($_POST['purchase_id'], $_POST['license_qta']);


    /**
     * INVIA NOTIFCA ASSEGNAZIONE CORSO
     */
}
elseif ($op_type === 'notify_course_assignment') {

    require_once BASE_LIBRARY_PATH . 'class_notification.php';
    $not_obj = new Tutor81Notification();
    $sending = $not_obj->notifyCourseAssignment($_POST['license_id']);
    $res = $sending['result'] ? 1 : 0;


    /**
     * INVIA ALERT
     */
}
elseif ($op_type === 'send_alert') {

    require_once BASE_LIBRARY_PATH . 'class_notification.php';
    $not_obj = new Tutor81Notification();
    if ($not_obj->notifyAlert($_POST['license_id'], $_POST['custom_message']) == true)
        $res = 1;
    else
        $res = 0;


    /**
     * INVIO AUTOMATICO ALERT
     */
}
elseif ($op_type === 'auto_alert') {
    $licenses_expiring = $purchase_obj->getLicenseExpiring();

    if ($licenses_expiring) {
        require_once BASE_LIBRARY_PATH . 'class_notification.php';
        $not_obj = new Tutor81Notification();
        $res = 0;
        foreach ($licenses_expiring as $license) {
            $res += (int) $not_obj->notifyAlert($license['id']);
        }
    }

    /**
     * ISCRIZIONE MULTIPLA
     */
}
elseif ($op_type === 'subscribe') {
    $total_purchased = 0;
    if ($_POST['to_buy'] !== "false") {
        if (filter_input(INPUT_POST,'packed', FILTER_SANITIZE_NUMBER_INT) > 0) {
            $to_buy = filter_input(INPUT_POST, 'to_buy', FILTER_SANITIZE_NUMBER_INT);
            require_once BASE_LIBRARY_PATH . 'class_pack.php';
            $pack_obj = new T81Pack();
            $pack_purchased = $pack_obj->getCurrentPackPurchased($_POST['tutor_company_id']);
            $learning_project_price_duration = $pack_obj->getElearningPriceDuration($_POST['learning_project_id']);
            foreach ($pack_purchased as $pack) {
                if ($to_buy <= 0) break;
                if ($pack['content_type'] === 'COURSES') {
                    $available = $pack['content_available'];
                    $to_purchase = $available >= $to_buy ? $to_buy : $available;
                    $to_reduce = $to_purchase;
                } elseif ($pack['content_type'] === 'HOURS' && (integer) $learning_project_price_duration['duration'] > 0) {
                    $available = $pack['content_available']/((integer) $learning_project_price_duration['duration']);
                    $to_purchase = $available >= $to_buy ? $to_buy : $available;
                    $to_reduce = $to_purchase*((integer) $learning_project_price_duration['duration']);
                } elseif ($pack['content_type'] === 'MONEY' && $learning_project_price_duration['price'] > 0) {
                    $available = floor($pack['content_available']/$learning_project_price_duration['price']);
                    $to_purchase = $available >= $to_buy ? $to_buy : $available;
                    $to_reduce = $to_purchase*$learning_project_price_duration['price'];
                }
                $purchased = $purchase_obj->purchaseCourse($_POST['company_id'], $_POST['learning_project_id'],
                    $to_purchase, $_POST['user_company_ref'], $_POST['tutor_id'],
                    $_POST['ext_po_number'], $_POST['cost_centre'], $pack['id_pack_purchase']);
                if ($purchased) {
                    $to_buy -= $to_purchase;
                    // riduco il content available del pack
                    $pack_obj->setContentAvialablePackPurchased($pack['id_pack_purchase'], $pack['content_available'] - $to_purchase);

                }
            }
        } else {
            $purchased = $purchase_obj->purchaseCourse($_POST['company_id'], $_POST['learning_project_id'],
                $_POST['to_buy'], $_POST['user_company_ref'], $_POST['tutor_id'],
                $_POST['ext_po_number'],$_POST['cost_centre']);
        }
    }

    $res = array();
    foreach ($_POST['users'] as $user_id) {
        $license_id = $purchase_obj->createNewLicense($user_id, $_POST['learning_project_id'], $_POST['tutor_id'], $_POST['start'], $_POST['end'], $_POST['alert'], $_POST['company_id']);
        array_push($res, $license_id);
    }

    $res = !empty($res) ? json_encode($res) : 0;


    /**
     * MODIFICA SCADENZA CORSO
     */
}
elseif ($op_type === 'schedule_license') {
    $res = $purchase_obj->scheduleLicense($_POST['license_id'], $_POST['starting_from'], $_POST['finish_within'], $_POST['days_to_alert']);
    if ($res && $_POST['send_mail'] === "true") {
        require_once BASE_LIBRARY_PATH . 'class_notification.php';
        $not_obj = new Tutor81Notification();
        $not_obj->notifyLicenseExpirationDate($_POST['license_id']);
    }
}
elseif ($op_type == "check_tax_code_validation"){
    require_once '../lib/class_user.php';
    $user_obj = new T81User();

    $question = filter_input(INPUT_POST, 'question',FILTER_SANITIZE_NUMBER_INT);
    $code = filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $username_or_course = filter_input(INPUT_POST, 'usernane_or_course', FILTER_SANITIZE_STRING);

    // TODO: check anche del course code nel caso si verificasse il tax code lo username apro direttamente la pagina di avviacorso loggato
    if ($code != '') {

        $tax_code = $user_obj->getTaxCode($username_or_course );
        if ($question == 3) {
            $last_name_code = strtolower(substr($tax_code, 0, 3));
            $res = strlen($last_name_code) == 3 && strlen($code) == 3 && $last_name_code === strtolower($code) ? $tax_code : 0;
        } elseif ($question == 1) {
            $birth = (int)substr($tax_code, 9, 2);
            $birth = $birth - 40 > 0 ? $birth - 40 : $birth;
            $res = !empty($birth) && $birth === (int)$code ? $tax_code : 0;
        } elseif ($question == 2) {
            $month = substr($tax_code, 8, 1);
            $month_list = array('A','B','C','D','E','H','L','M','P','R','S','T');
            $month_key = array_search($month, $month_list);
            $res = $month_key !== FALSE && ($month_key +1) === (int) $code ? $tax_code : 0;
        }
//
//        if ($res === $tax_code) {
//            header('Location: '.AVVIACORSO_URL."/prelogin.php?tax_code=".$tax_code."&username=".$username_or_course); exit();
//        }
    } else {
        $res = 0;
    }
}
elseif ($op_type === "get_user_by_username") {
    require_once '../lib/class_user.php';
    $u_obj = new T81User();
    $loginstring = filter_input(INPUT_POST, 'loginstring', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $user = $u_obj->getUserByUsername($loginstring);
    $res = $user ? json_encode($user) : 0;
}
elseif ($op_type === "get_user_by_course_code_or_username") {
    require_once '../lib/class_user.php';
    $u_obj = new T81User();
    $loginstring = filter_input(INPUT_POST, 'loginstring', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $user = $u_obj->getUserByCourseCodeOrUsername($loginstring);
    $res = $user ? json_encode($user) : 0;
}
elseif ($op_type === 'send_help_request') {
    require_once BASE_LIBRARY_PATH . 'class_notification.php';
    $not_obj = new Tutor81Notification();
    $problem_id = filter_input(INPUT_POST, 'problem_id', FILTER_SANITIZE_NUMBER_INT) ? : 0;
    /*
    if ($problem_id == 3) {
        // send help request to tutor admin
        $res = $not_obj->notifyTaxcodeProblem($_POST['name'],
            $_POST['surname'],
            $_POST['company_name'],
            $_POST['taxcode'],
            $_POST['email'],
            $_POST['problem'],
            $_POST['username']  ) ? 1 : 0;
    } else {*/
        // send help request to superadmin
        $res = $not_obj->notifyHelpRequest($_POST['name'],
            $_POST['surname'],
            $_POST['company_name'],
            $_POST['taxcode'],
            $_POST['email'],
            $_POST['problem'],
            $_POST['username']  ) ? 1 : 0;
    /*}*/



}
elseif ($op_type === 'edit_accreditation_code') {
    $res = $purchase_obj->setAccreditationCode($_POST['license_id'], $_POST['accreditation_code']) ? : 0;
}

echo $res;