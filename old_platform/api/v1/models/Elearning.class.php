<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 18-ago-2015
 * File: api/v1/models/Elearning.class.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.api.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'class_notification.php';

class Elearning extends T81LearningProject {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getElearningPublishedInEcommerceByCourseType($course_type_id){
        $course_type_id = filter_var($course_type_id, FILTER_SANITIZE_NUMBER_INT);
        $query = "SELECT courses.*,
                    learning_project.id as learn_id,
                    learning_project.title as learn_title,
                    learning_project.description as learn_description 
                  FROM learning_project 
                    JOIN unities ON unities.learning_project_id = learning_project.id 
                    JOIN courses ON course_id = courses.id 
                  WHERE learning_project.course_type_id = '$course_type_id'
                  AND reserved_to = ''
                  AND unit_type_id = 3";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res : FALSE;
    }
    
    /**
     * Inserisce in piattaforma acquisti fatti in ecommerce creando l'azienda se
     * questa non esiste. In questo caso la crea sotto l'ente formativo Tutor81.
     * @param array $company_data (
     *                  vat_code        => partita iva
     *                  busines_name    => ragione sociale
     *                  address         => indirizzo
     *                  postal_code     => CAP
     *                  city            => città
     *                  province        => provincia
     *                  telephone       => telefono
     *                  email           => email      
     *              )
     * @param array $order array (
     *                      "id"        => integer id dell'ordine (nell'ambiente di origine)
     *                      "completed" => boolean true se pagamento eseguito
     *                      "items" =>  array(
     *                                      learning_project_id => id del learning project
     *                                      qta                 => quantità acquistata
     *                                      price               => prezzo unitario senza iva
     *                                  ),
     *                                  array(...)
     *                      )
     * @param string url $origin url di origine della richiesta
     * @throws Exception
     */
    public function purchaseFromEcommerce($company_data, $order, $origin){
        require_once 'Company.class.php';
        require_once 'Purchase.class.php';
        $company_obj = new Company();
        $purchase_obj = new Purchase();
        $vat_code = filter_var($company_data['vat_code'], FILTER_SANITIZE_STRING);// check if vat is correct
        if (!$vat_code) {
            throw new Exception('Incorrect Vat Code');
        }

        $origin = filter_var($origin, FILTER_SANITIZE_URL); // url di origine dell'ordine
        $order_id = filter_var($order['id'], FILTER_SANITIZE_NUMBER_INT);   // ID dell'ordine nell'ambiente di origine
        $completed = filter_var($order['completed'], FILTER_VALIDATE_BOOLEAN); // true se il pagamento è completo
        $code = $origin . ': #' . $order_id; // codice per far riferimento all'URL di origine e all'ID dell'ordine di origine
        $company = $company_obj->getCompanyByVatCode($vat_code);// cerca azienda con la stessa P.IVA
        if ($company === FALSE) {
            $business_name = filter_var($company_data['business_name'], FILTER_SANITIZE_STRING);
            $address = filter_var($company_data['address'], FILTER_SANITIZE_STRING);
            $postal_code = filter_var($company_data['postal_code'], FILTER_SANITIZE_STRING);
            $city = filter_var($company_data['city'], FILTER_SANITIZE_STRING);
            $province = ($company_obj->getProvinceFromSigla($company_data['province']));
            $telephone = filter_var($company_data['telephone'], FILTER_SANITIZE_STRING);
            $email = filter_var($company_data['email'], FILTER_SANITIZE_EMAIL);
            // crea nuova azienda
            $company_id = $company_obj->createCompany($business_name, 
                $vat_code, $address, $postal_code, 
                    $city, $province['id'], 0, 0, 6, 0, 1, $telephone, 
                        $email, 23, 1, 0, 0);
            if ($company_id > 0){
                $validity_start = new DateTime('now');
                $validity_end = clone $validity_start;
                $validity_end->add(new DateInterval('P10Y'));
                // Assegna un piano all'azienda
                if (!$company_obj->assignCompanyPlan(6, 2, $company_id, $validity_start->format('Y-m-d'), $validity_end->format('Y-m-d'), 0, 0, 0, 0, 0, 0)) {
                    throw new Exception('Error in assigning a plan to the company');
                }
                $company = $company_obj->getCompanyByVatCode($vat_code);
            } else {
                throw new Exception('Error creating company - ' . $company_id);
            }
        }
        $licenses = array();
        foreach ($order['items'] as $single_order) {
            $learning_project_id = filter_var($single_order['learning_project_id'], FILTER_SANITIZE_NUMBER_INT);
            $qta = filter_var($single_order['qta'], FILTER_SANITIZE_NUMBER_INT);
            $price = filter_var($single_order['price'], FILTER_SANITIZE_NUMBER_FLOAT);
            // per ogni ID di corso ordinato crea un acquisto in piattaforma
            $purchase_id = $purchase_obj->purchaseCourse($company['id'], 
                $learning_project_id, $qta, 
                    $company['owner_user_id'], 0, '', 0, 0, $price, $code);
            if ($purchase_id) {
                $learning_project = $this->getCourseDetailFromLearningProject($learning_project_id);
                $start_date = date("Y-m-d");
                $end_date = date("Y-m-d", strtotime($start_date . $learning_project['max_execution_time'] . 'days'));
                $alert = 15;
                for ($i = $qta; $i > 0; $i--) {
                    // Per ogni corso ordinato crea una licenza
                    $license_id = $purchase_obj->createNewEcommerceLicense(0, $learning_project_id, 
                        $company['owner_user_id'], $start_date, $end_date, $alert, $company['id'],
                            '', $purchase_id, $company['email'], $completed);
                    if (!$license_id) {
                        throw new Exception('Error creating license');
                    }
                    array_push($licenses, $license_id); // raggruppa gli id delle licenze corsi
                    
                }
            } else {
                throw new Exception('Error creating purchase');
            }
        }
        if ($completed) {
            // notifica le licenze da assegnare
            $not_obj = new Tutor81Notification();
            $not_obj->notifyEcommerceLicenses($licenses, $company['email']);
        }
        return "SUCCESS";
    }
}