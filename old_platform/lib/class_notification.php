<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once dirname(__FILE__).'/../config.php';

require_once BASE_LIBRARY_PATH . 'PHPMailer/src/Exception.php';
require_once BASE_LIBRARY_PATH . 'PHPMailer/src/PHPMailer.php';
require_once BASE_LIBRARY_PATH . 'PHPMailer/src/SMTP.php';
require_once BASE_LIBRARY_PATH . 'class_db.php';
require_once BASE_LIBRARY_PATH . 'sanitize.php';
require_once BASE_LIBRARY_PATH . 'function.php';
require_once BASE_LIBRARY_PATH . 'class_user.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
//require_once BASE_LIBRARY_PATH . 'class.phpmailer.php';
require_once BASE_LIBRARY_PATH . 'class_ticket.php';
require_once BASE_LIBRARY_PATH . 'class_permissions.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_purchase.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';

class Tutor81Notification {

    const FOOTER_TUTOR81 = <<<EOF
		<hr>
		<p>Technical Department TUTOR81</p>
		<p>Questo è un messaggio automatico non rispondere a questa mail</p>
		<p>Per informazioni <a href="mailto: assistenza@tutor81.it">assistenza@tutor81.it</a></p>
                <hr>
EOF;

    const FOOTER_ITALIA = <<<EOF
		<hr>
		<p>Grazie</p>
		<p><i>LO STAFF di TUTORITALIA</i></p>
		<p style="border-top: 1px solid #000; font-size: 12px;">Non replicare a questa mail.
		Per informazioni scrivere a <a href="mailto: info@tutoritalia.it">info@tutoritalia.it</a>.
		Per assistenza contattare <a href="mailto: assistenza@tutoritalia.it">assistenza@tutoritalia.it</a></p>
EOF;

    const FOOTER_ERACLITEA = <<<EOF
		<hr>
		<p>ACCADEMIA ERACLITEA</p>
		<p>Questo è un messaggio automatico non rispondere a questa mail</p>
                <hr>
EOF;    
    var $db_conn;
    var $ticket_obj;
    var $user_obj;
    var $learn_obj;
    var $permission_obj;
    var $company_obj;
    var $purchase_obj;
    var $course_obj;

    /**
     * Costruttore
     */
    public function __construct() {
        $this->db_conn = new MySQLConn ();
        $this->ticket_obj = new Tutor81Ticket ();
        $this->user_obj = new T81User ();
        $this->learn_obj = new T81LearningProject ();
        $this->permission_obj = new Permissions();
        $this->company_obj = new T81Company();
        $this->purchase_obj = new iWDPurchase();
        $this->course_obj = new iWDCourse();
    }

    /**
     * Invio email
     * 
     * @param array $mail        	
     */
    private function sendMail($args) {
        $mail = array(
            'from' => array(
                'mail' => 'assistenza@tutor81.it',
                'name' => 'Tutor81'
            ),
            'a' => array(
                array(
                    'mail' => '',
                    'name' => ''
                )
            ),
            'cc' => array(
                array(
                    'mail' => '',
                    'name' => ''
                )
            ),
            'ccn' => array(
                array(
                    'mail' => '',
                    'name' => ''
                )
            ),
            'reply' => array(
                array(
                    'mail' => 'assistenza@tutor81.it',
                    'name' => 'Tutor81'
                )
            ),
            'confirmReadingTo' => '',
            'object' => '',
            'body' => '',
            'sender' => ''
        );

        $mail = array_merge($mail, $args);

        $mail_obj = new PHPMailer(true);
        try {
            $mail_obj->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail_obj->Debugoutput = 'error_log';
            $mail_obj->IsSMTP();
            if ($mail['sender'] === 'Eraclitea'){
                $mail_obj->Host = 'smtp.gmail.com';//smtps.aruba.it';
                $mail_obj->SMTPAuth = true;
                $mail_obj->Username = 'corsieraclitea@gmail.com';//'avviocorsi@eraclitea.it';
                $mail_obj->Password = 'jemh tkur xmvk qivj';//'9XppAb@e9@fmA67Pi@';//'Eraclitea2024@';
                $mail_obj->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
                $mail_obj->Port = '465';
                $mail['from']['mail'] = 'corsieraclitea@gmail.com';
                $mail['from']['name'] = 'Accademia Eraclitea';
                $mail['reply'][0]['mail'] = 'corsieraclitea@gmail.com';
                $mail['reply'][0]['name'] = 'Accademia Eraclitea';
            } else {
                $mail_obj->Host = 'ssl0.ovh.net';//'pro1.mail.ovh.net';//'smtp-relay.sendinblue.com';//'smtp.tutor81.com';
                $mail_obj->SMTPAuth = true;
                $mail_obj->Username = 'assistenza@tutor81.it';//'assistenza@tutor81.it';//'info@tutor81.com';//'noreplay@tutor81.com';
                $mail_obj->Password = 'ZXcv1712--';//'tczmXDB7n56';//'ZXcv17121969';//'ZXcv1712';//'sGIgxRc1CA0MSthq';
                $mail_obj->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
                $mail_obj->Port = '465';//'587';            
            }
            $mail_obj->CharSet = 'UTF-8';
            $mail_obj->SetFrom($mail ['from'] ['mail'], $mail ['from'] ['name']);
            // $mail_obj->Sender = $mail['reply_to'];
            $mail_obj->ConfirmReadingTo = $mail_obj->ValidateAddress($mail['confirmReadingTo']) ? $mail['confirmReadingTo'] : '';
            foreach ($mail ['a'] as $a_mail) {
                $mail_obj->AddAddress($a_mail ['mail'], $a_mail ['name']);
            }
            foreach ($mail ['cc'] as $cc_mail) {
                if ($cc_mail ['mail'] != '') $mail_obj->AddCC($cc_mail ['mail'], $cc_mail ['name']);
            }
            foreach ($mail ['ccn'] as $ccn_mail) {
                if ($ccn_mail ['mail'] != '') $mail_obj->AddBCC($ccn_mail ['mail'], $ccn_mail ['name']);
            }
            // log della mail
            $mail_obj->AddBCC('log@tutor81.it', 'log tutor81');
            foreach ($mail ['reply'] as $reply_mail) {
                if ($reply_mail ['mail'] != '') $mail_obj->AddReplyTo($reply_mail ['mail'], $ccn_mail ['name']);
            }
            $mail_obj->Subject = $mail ['object'];
            $mail_obj->AltBody = "To view the message, please use an HTML compatible email viewer!";
            $mail_obj->MsgHTML($mail ['body']);

            $r = $mail_obj->Send();
            return true;
        } catch (phpmailerException $e) {
            error_log("PHPMAILER ERROR: " . $e->errorMessage());
            return false;
        } catch (Exception $ex) {
            error_log("MAIL ERROR: " . $ex->getMessage());
            return false;
        }
    }

    public function testNotify($email,$message = "") {
        $email = $this->db_conn->escapestr(trim($email));
        $message = $this->db_conn->escapestr(trim($message));

        $msg = <<<EOF
		<h3><a href="http://www.tutor81.it">TUTOR81</a> - TEST NOTIFICA</h3>
		<p>Questo messaggio è un test della piattaforma Tutor81</p>
                <p>$message</p>
EOF;
        $msg .= self::FOOTER_TUTOR81;

        $obj = "Test Notifica Piattaforma TUTOR81";
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $email,
                            'name' => "Test"
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg
                ));
    }

    public function notifyError($message = "") {
        $message = is_string($message) ? html_entity_decode($message) : '';

        $msg = <<<EOF
		<h3><a href="http://www.tutor81.it">TUTOR81</a> - NOTIFICA ERRORE</h3>
		<p>Questo messaggio è stato generato da un errore della piattaforma Tutor81</p>
EOF;
        $msg .= is_string($message) ? html_entity_decode($message) : '';
        $msg .= self::FOOTER_TUTOR81;

        $obj = "Notifica Errore Piattaforma TUTOR81";
        return $this->sendMail(array(
            'a' => array(
                array(
                    'mail' => 'assistenza@tutor81.it',
                    'name' => ''
                )
            ),
            'ccn' => array(
                array(
                    'mail' => 'info@rzweb.it',
                    'name' => ''
                )
            ),
            'object' => $obj,
            'body' => $msg
        ));
    }

    public function notifyCompanyCreation($company_id, $user_id="0") {
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $company = $this->company_obj->getBusinessDetail($company_id);
        //$tutor_name = strtoupper("{$company_ref['name']} {$company_ref['surname']}");
        $company_name = strtoupper($company['business_name']);
        $tutor_user = $user_id == 0 ? $this->company_obj->getDetail($company['owner_user_id']) : $this->user_obj->getDetail($user_id);
        $admin_fullname = ucwords(strtoupper($tutor_user["surname"]." ".$tutor_user["name"]));
        $admin_username = $tutor_user["username"];
        $admin_password = $tutor_user["tax_code"];
        $admin_email = $tutor_user["email"];
        $msg = <<<EOF
            <p>&Egrave; stata creata la nuova azienda $company_name</p>
            <p>Amministratore registrato $admin_fullname</p>
            <p>Username: $admin_username</p>
            <p>Password: $admin_password</p>
            <p>Email: $admin_email</p>
EOF;
        $msg .= self::FOOTER_TUTOR81;

        $obj = "TUTOR81 - CREAZIONE NUOVA AZIENDA";
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $admin_email,
                            'name' => ucwords($admin_fullname)
                        )
                    ),
                    'ccn' => array(
                        array(
                            'mail' => 'amministrazione@tutor81.it',
                            'name' => 'Amministrazione'
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg
                ));
    }

    /**
     * Notifica l'avvenuta vendita di corsi in e-learning dalla piattaforma
     * inviando l'elenco delle licenze e dei destinatari e indicando se ci sono 
     * stati degli errori
     * 
     * @param email $tutor_logged_email
     * @param array $learning_project
     * @param float $price
     * @param integer $purchase_id
     * @param array $employees
     * @return boolean
     */
    public function notifySellTutorFromBackend($tutor_logged_email, $learning_project, $price, $purchase_id, $employees) {

        $purchase_id = filter_var($purchase_id, FILTER_SANITIZE_NUMBER_INT);
        $purchase = $this->purchase_obj->getPurchase($purchase_id);
        $company = $this->company_obj->getBusinessDetail($purchase['customer_company_id']);
        $tutor_user = $this->user_obj->getDetail($company['owner_user_id']);
        $tutor_company = $this->company_obj->getBusinessDetail($tutor_user['company_id']);
        $learning_project = $this->learn_obj->getDetail($purchase['learning_project_id']);
        $tutor_companies = $this->company_obj->getCompanyByTutorCompany($tutor_company['id']);
        $is_tutor_with_single_company = count($tutor_companies) == 1;
        
        $tutor_business_name = strtoupper(($tutor_company['business_name']));
        $creation_date = new DateTime($purchase['creation_date']);
        $purchase_date = $creation_date->format('d/m/Y');
        $purchase_time = $creation_date->format('H:i');
        $learning_project_title = strtoupper(T81LearningProject::formatTitle($learning_project['title']));
        $importo = $purchase['qta']*$purchase['price'];
        $business_name = strtoupper($company['business_name']);
        $acquistato_venduto = $is_tutor_with_single_company ? 'acquistato' : 'venduto';
        
        $msg = <<< EOF
            <p>Spett.le <b>{$tutor_business_name}</b></p>
            
            <p>In data: <b>{$purchase_date}</b><br>
            Alle ore: <b>{$purchase_time}</b><br>
            Con codice vendita: <b>{$purchase['id']}</b></p>
            
            <p>L'utente <b>{$tutor_user['name']} {$tutor_user['surname']}</b></p>
            
            <p>Ha {$acquistato_venduto} e generato la licenza di accesso al corso<br>
            <b>{$learning_project_title}</b></p>
                    
            <p>Quantit&agrave;: <b>{$purchase['qta']}</b></p>
            
            <p>Importo Euro <b>{$importo}</b></p>
            
            <p>DESTINATARI:</p>
            <p>
EOF;
            $errors = array();
            foreach($employees as $single_employee){
                if (key_exists('error', $single_employee)) {
                    array_push($errors, $single_employee);
                    continue;
                }
                $msg .= 'Licenza <b>' . $single_employee['licence_pwd'] . '</b> ';
                $msg .= 'intestata a: <b>' . $single_employee['name'] . ' ' . $single_employee['surname'] . '</b><br>';
            }
            
            $msg .= '</p>';
            
            if (!$is_tutor_with_single_company){
                
            $msg .= <<< EOF
                <p><b>Cliente:</b><br>
                Ragione sociale: {$business_name}<br>
                Indirizzo: {$company['address']}<br>
                Località: {$company['city']}<br>
                P.IVA: {$company['vat']}<br>
                email: {$company['email']}</p>                
EOF;
            }
            
            if (count($errors) > 0) {
                $msg .= "<p>Durante l'acquisto o l'intestazione della licenza si sono verificati i seguenti errori:</p><p>";
                
                foreach($errors as $single_employee){
                    $msg .= 'Nominativo: <b>' . $single_employee['name'] . ' ' . $single_employee['surname'] . '</b> ';
                    $msg .= ' - Errore: ' . localize_error($single_employee['error']) . '<br>';
                }

                $msg .= "</p><p>In caso di problemi Vi inviatiamo a contattarci inoltrando la presente email ad assistenza@tutor81.it.</p>";
                
            }
            
        $msg .= '<p>Come accedere al corso <a href="https://www.tutor81.it/come-si-avvia-un-corso/">clicca qui</a>.</p>';
        $msg .= '<p>Requisiti tecnici per avviare il corso <a href="https://www.tutor81.it/requisiti-tecnici-per-avviare-il-corso/">clicca qui</a>.</p>';

        $obj = "TUTOR81 - hai $acquistato_venduto un nuovo corso";
        
        return $this->sendMail(array(
            'a' => array(
                array(
                    'mail' => $tutor_company['email'],
                    'name' => ''
                )
            ),
            'ccn' => array(
                array(
                    'mail' => 'assistenza@tutor81.it',
                    'name' => ''
                )
            ),
            'object' => $obj,
            'body' => $msg
        ));
    }

    /**
     * Notifica gli acquisti effettuati fra un intervallo di date con la possibilità
     * di inserire in calce un messaggio opzionale
     * 
     * @param email $tutor_company_id
     * @param timestamp $from_date
     * @param timestamp $to_date
     * @param string $message
     * @return boolean
     */
    public function notifyTutorPurchasesDateInterval($tutor_company_id, $from_date, $to_date, $message = "") {

        $tutor_company_id = filter_var($tutor_company_id, FILTER_SANITIZE_NUMBER_INT);
        
        $tutor_company = $this->company_obj->getBusinessDetail($tutor_company_id);
        if ($tutor_company) {
            //$from_date = date('Y-m-d H:i:s', strtotime($this->db_conn->escapestr($from_date)));
            //$to_date = date('Y-m-d H:i:s', strtotime($this->db_conn->escapestr($to_date)));
            
            $purchases = (isset($from_date) && $from_date instanceof DateTime 
                    && isset($to_date) && $to_date instanceof DateTime) 
                    ? $this->course_obj->getPurchasesTutor($tutor_company_id, $from_date, $to_date) : FALSE;
            if ($purchases) {
                
                $message = is_string($message) ? html_entity_decode($message) : '';

                $tutor_business_name = strtoupper(($tutor_company['business_name']));
                $tutor_companies = $this->company_obj->getCompanyByTutorCompany($tutor_company['id']);
                $is_tutor_with_single_company = count($tutor_companies) == 1;
                //$from_date = new DateTime($from_date);
                $from_date = $from_date->format('d/m/Y');
                //$to_date = new DateTime($to_date);
                $to_date = $to_date->format('d/m/Y');

                $msg = <<< EOF
                <p>Spett.le <b>{$tutor_business_name}</b> - {$tutor_company['email']}</p>

                <p>Gli acquisti effettuati dal <b>{$from_date}</b> al <b>{$to_date}</b> sono i seguenti:<br><p>
EOF;

                $amount = 0;
                foreach ($purchases as $purchase){
                    $company = $this->company_obj->getBusinessDetail($purchase['customer_company_id']);
                    //$tutor_user = $this->user_obj->getDetail($company['owner_user_id']);
                    //$tutor_company = $this->company_obj->getBusinessDetail($tutor_user['company_id']);
                    $learning_project = $this->learn_obj->getDetail($purchase['learning_project_id']);
                    //$tutor_companies = $this->company_obj->getCompanyByTutorCompany($tutor_company['id']);
                    //$is_tutor_with_single_company = count($tutor_companies) == 1;
                    $creation_date = new DateTime($purchase['creation_date']);
                    $purchase_date = $creation_date->format('d/m/Y');
                    $purchase_time = $creation_date->format('H:i');
                    $learning_project_title = strtoupper(T81LearningProject::formatTitle($learning_project['title']));
                    $importo = $purchase['qta']*$purchase['price'];
                    $amount += $importo;
                    $costo = number_format($importo, 2, ',', '.');
                    $business_name = strtoupper($company['business_name']);
                    //$acquistato_venduto = $is_tutor_with_single_company ? 'acquistato' : 'venduto';

                    $msg .= <<< EOF
                        <p>ORDINE N° <b>{$purchase['id']}</b><br>
                        In data: <b>{$purchase_date}</b> alle ore: <b>{$purchase_time}</b><br>
EOF;

                    if (!$is_tutor_with_single_company){
                        $msg .= "CLIENTE: <b>$business_name</b><br>";
                    }

                    $msg .= <<< EOF
                        CORSO: <b>{$learning_project_title}</b><br>
                        Quantit&agrave;: <b>{$purchase['qta']}</b><br>
                        Importo Euro <b>{$costo}</b> (IVA ESENTE)<br>
                        <hr>
                        </p>
EOF;
                }
                $totale = number_format($amount, 2, ',', '.');
                $msg .= '<p>Per un importo totale di <b style="color:red">EURO ' . $totale . '</b></p>';
                $msg .= $message;
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
            
        $msg .= self::FOOTER_TUTOR81;
        
        $obj = "TUTOR81 - Acquisti effettuati dal $from_date al $to_date";
        
        return $this->sendMail(array(
            'a' => array(
                array(
                    'mail' => $tutor_company['email'],
                    'name' => ''
                )
            ),
            'ccn' => array(
                array(
                    'mail' => 'assistenza@tutor81.it',
                    'name' => ''
                ),
                array(
                    'mail' => 'zaniol.roberto@gmail.com',
                    'name' => ''
                )
            ),
            'object' => $obj,
            'body' => $msg
        ));
    }
    
    /**
     * Notifica un fallito acquisto con i dati dei corsisti e gli errori associati
     * 
     * @param email $tutor_logged_email
     * @param array $learning_project
     * @param array $employees
     * @return boolean
     */
    public function notifyErrorSellTutorFromBackend($tutor_logged_email, $learning_project, $employees) {

        $learning_project_title = strtoupper(T81LearningProject::formatTitle($learning_project['title']));
        $creation_date = new DateTime();
        $purchase_date = $creation_date->format('d/m/Y');
        $purchase_time = $creation_date->format('H:i');
        
        $msg = <<< EOF
            <p>Buongiorno,</p>
            
            <p>In data: <b>{$purchase_date}</b><br>
            Alle ore: <b>{$purchase_time}</b><br>
            Hai cercato di effettuare l'acquisto per il corso<br>
            <b>{$learning_project_title}</b></p>
                    
            <p>Non è stato possibile procedere. Ricontrolla i dati dei corsisti prima di riprovare.</p>
            
            <p>DESTINATARI CORSO:</p>
            <p>
EOF;

        $msg .= "<p>Durante l'acquisto o l'intestazione della licenza si sono verificati i seguenti errori:</p><p>";

        foreach($employees as $single_employee){
            $msg .= 'Nominativo: <b>' . $single_employee['name'] . ' ' . $single_employee['surname'] . '</b> ';
            $msg .= ' - Errore: ' . localize_error($single_employee['error']) . '<br>';
        }

        $obj = "TUTOR81 - errore acqusto corso";
        
        return $this->sendMail(array(
            'a' => array(
                array(
                    'mail' => $tutor_logged_email,
                    'name' => ''
                )
            ),
            'cc' => array(
                array(
                    'mail' => 'assistenza@tutor81.it',
                    'name' => ''
                )
            ),
            'object' => $obj,
            'body' => $msg
        ));
    }

    public function notifyPurchaseEcommerce($destination_email, $learning_project, $total, $purchase_id) {
        if (!$learning_project)
            return false;

        $purchase = $this->purchase_obj->getPurchase($purchase_id);
        $company = $this->company_obj->getBusinessDetail($purchase['customer_company_id']);
        $tutor_user = $this->user_obj->getDetail($company['owner_user_id']);
        $tutor_company = $this->company_obj->getBusinessDetail($tutor_user['company_id']);
        
        // define logo - if is_tutor_with_single_company logo tutor81 (id 2)
        $companies = $this->company_obj->getCompanyByTutorCompany($tutor_company['id']);
        $tutor_company["logo"] = BASE_MEDIA_PATH . 'img/company/' . (count($companies) == 1 ? '2' : $tutor_company["id"]) . '.png';
        
        ob_start();
        include BASE_LIBRARY_PATH .'../ecommerce/mail_conferma_pagamento.php';
        $msg = ob_get_contents();
        ob_end_clean();

        $title = "TITOLO DEL CORSO";

        $obj = "TUTOR81 - CONFERMA ACQUISTO";


        return $this->sendMail(array(
            'a' => array(
                array(
                    'mail' => $destination_email,
                    'name' => ''
                )
            ),
            'cc' => array(
                array(
                    'mail' => $tutor_user['email'],
                    'name' => ucwords(strtolower("{$tutor_user['surname']} {$tutor_user['name']}"))
                )
            ),
            'ccn' => array(
                array(
                    'mail' => 'assistenza@tutor81.it',
                    'name' => ''
                )
            ),
            'object' => $obj,
            'body' => $msg
        ));
    }

    /**
     * Notifica lo sblocco di una licenza acqustata in ecommerce
     * 
     * @param type $lpu_detail
     * @param type $destination_email
     * @param type $learning_project
     * @param type $isAssigned
     * @return boolean
     */
    public function notifyUnlockLicenceEcommerce($lpu_detail, $destination_email, $learning_project, $isAssigned=false) {
        if (!$lpu_detail)
            return false;


        // Get tutor user email
        $tutor_user = $this->user_obj->getDetail($lpu_detail["company_id"]);
        $tutor_company = $this->company_obj->getBusinessDetail($tutor_user['company_id']);
        // Get learning project detail
        $learning_project = $this->learn_obj->getCourseDetailFromLearningProject($lpu_detail['learning_project_id']);
        
        $tutor_company["logo"] = HUB_URL."/media/img/company/".$tutor_company["id"].".png";
        
        $assigned_user = $lpu_detail['user_id'] > 0 ? $this->user_obj->getDetail($lpu_detail["user_id"]) : false;
        
        $destination_email = $assigned_user ? $assigned_user['email'] : $destination_email;
        $destination_name = $assigned_user ? ucwords(strtolower($assigned_user['name'] . ' ' . $assigned_user['surname'])) : '';
        
        if ($tutor_company['id'] == 2600) {
            $sender = 'Eraclitea';
            $platform_name = 'ACCADEMIA ERACLITEA';
            $url_avviacorso = $assigned_user ? "https://eraclitea.tutor81.com" : "https://eraclitea.tutor81.com?course=" . $lpu_detail["learning_project_pwd"];
            $mail_file = $assigned_user ? 'mail_avvia_corso_eraclitea.php' : 'mail_licenza_corso_eraclitea.php';
        } elseif ($tutor_company['id'] == 2978) {
            $sender = 'Libellula';
            $platform_name = 'FONDAZIONE LIBELLULA';
            $url_avviacorso = $assigned_user ? "https://libellula.tutor81.com" : "https://libellula.tutor81.com?course=" . $lpu_detail["learning_project_pwd"];
            $mail_file = $assigned_user ? 'mail_avvia_corso_libellula.php' : 'mail_licenza_corso_libellula.php';
        } else {
            $sender = '';
            $platform_name = 'TUTOR81';
            $url_avviacorso = $assigned_user ? COMMON_AVVIACORSO_URL : AVVIACORSO_URL."?course=" . $lpu_detail["learning_project_pwd"];
            $mail_file = $assigned_user ? 'mail_avvia_corso.php' : 'mail_licenza_corso.php';  
        }  
        
        ob_start();
        include BASE_LIBRARY_PATH .'../ecommerce/' . $mail_file;
        $msg = ob_get_contents();
        ob_end_clean();
        

        $title = T81LearningProject::formatTitle($learning_project["learning_project_title"]);

        $obj = $platform_name . " - Avvia il tuo corso $title";

        //$tutor_company = $this->user_obj->getUserCompany($tutor_user['id']);



        return $this->sendMail(array(
            'a' => array(
                array(
                    'mail' => $destination_email,
                    'name' => $destination_name
                )
            ),
            'cc' => array(
                array(
                    'mail' => $isAssigned ? $tutor_user["email"] : "",
                    'name' => $isAssigned ? $tutor_user["surname"]." ".$tutor_user["name"]  : ""
                ),
                array(
                    'mail' => $tutor_company['email'],
                    'name' => $tutor_company['business_name']
                )
            ),
            'ccn' => array(
                array(
                    'mail' => 'assistenza@tutor81.it',
                    'name' => ''
                )
            ),
            'object' => $obj,
            'body' => $msg,
            'sender' => $sender
        ));
    }

    /**
     * Notifica lo sblocco di più licenze acqusitate in piattaforma
     * 
     * @param type $multiple_licenses
     * @param type $destination_email
     * @return boolean
     */
    public function notifyUnlockMultipleLicences($multiple_licenses, $destination_email) {
        if (!$multiple_licenses)
            return false;
        $destination_email = filter_var($destination_email, FILTER_SANITIZE_EMAIL);
        if (!$destination_email)
            return false;
        $msg = '';
        $tutor_user = array();
        $tutor_company = array();
        
        foreach ($multiple_licenses as $license) {
            $lpu_detail = $license['lpu_detail'];
            $learning_project = $license['learning_project'];
            //$isAssigned = $license_id['is_assigned'];
        
            // Get tutor user email
            $tutor_user = $this->user_obj->getDetail($lpu_detail["company_id"]);
            $tutor_company = $this->company_obj->getBusinessDetail($tutor_user['company_id']);
        
            $tutor_company["logo"] = HUB_URL."/media/img/company/".$tutor_company["id"].".png";
        
            $assigned_user = $lpu_detail['user_id'] > 0 ? $this->user_obj->getDetail($lpu_detail["user_id"]) : false;
        
            $destination_name = $assigned_user ? ucwords(strtolower($assigned_user['name'] . ' ' . $assigned_user['surname'])) : '';

            if ($tutor_company['id'] == 2600) {
                $sender = 'Eraclitea';
                $platform_name = 'ACCADEMIA ERACLITEA';
                $url_avviacorso = $assigned_user ? "https://eraclitea.tutor81.com" : "https://eraclitea.tutor81.com?course=" . $lpu_detail["learning_project_pwd"];
                $mail_file = $assigned_user ? 'mail_avvia_corso_eraclitea.php' : 'mail_licenza_corso_eraclitea.php';
            } elseif ($tutor_company['id'] == 2978) {
                $sender = 'Libellula';
                $platform_name = 'FONDAZIONE LIBELLULA';
                $url_avviacorso = $assigned_user ? "https://libellula.tutor81.com" : "https://libellula.tutor81.com?course=" . $lpu_detail["learning_project_pwd"];
                $mail_file = $assigned_user ? 'mail_avvia_corso_libellula.php' : 'mail_licenza_corso_libellula.php';
            } else {
                $sender = '';
                $platform_name = 'TUTOR81';
                $url_avviacorso = $assigned_user ? COMMON_AVVIACORSO_URL : AVVIACORSO_URL."?course=" . $lpu_detail["learning_project_pwd"];
                $mail_file = $assigned_user ? 'mail_avvia_corso.php' : 'mail_licenza_corso.php';  
            }
            
            ob_start();
            include BASE_LIBRARY_PATH .'../ecommerce/' . $mail_file;
            $msg .= ob_get_contents();
            ob_end_clean();
        
        }
        
        //$title = T81LearningProject::formatTitle($learning_project["learning_project_title"]);

        $obj = $platform_name . " - Avvia il tuo corso";

        //$tutor_company = $this->user_obj->getUserCompany($tutor_user['id']);

        return $this->sendMail(array(
            'a' => array(
                array(
                    'mail' => $destination_email,
                    'name' => $destination_name
                )
            ),
            'cc' => array(
                array(
                    'mail' => $tutor_user["email"],
                    'name' => $tutor_user["surname"]." ".$tutor_user["name"]
                ),
                array(
                    'mail' => $tutor_company['email'],
                    'name' => $tutor_company['business_name']
                )
            ),
            'ccn' => array(
                array(
                    'mail' => 'assistenza@tutor81.it',
                    'name' => ''
                )
            ),
            'confirmReadingTo' => $tutor_user["email"],
            'object' => $obj,
            'body' => $msg,
            'sender' => $sender
        ));
    }

    /**
     * Notifica le licenze acquistate in ecommerce (se pagamento completato)
     * utente non assegnato!
     * 
     * @param array di Integer $licenses
     * @param string email $destination_email
     * @return boolean
     */
    public function notifyEcommerceLicenses($licenses, $email = '') {
        if (!is_array($licenses) || empty($licenses)) {
            return false;
        }
        $destination_email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $isAssigned = false; // necessario nel file mail_licenza_corso.php
        
        $msg = '';
        $tutor_user = array();
        $tutor_company = array();
        $assigned_user = array(); // necessario nel file mail_licenza_corso.php
        foreach ($licenses as $licence_id) {
            $lpu_detail = $this->purchase_obj->getLicenceDetail($licence_id);
            $learning_project = $this->learn_obj->getCourseDetailFromLearningProject($lpu_detail['learning_project_id']); //necessario nel file mail_licenza_corso.php
            

            // Get tutor user email
            if (empty($tutor_user)) {
                $tutor_user = $this->user_obj->getDetail($lpu_detail["company_id"]);
                $tutor_company = $this->company_obj->getBusinessDetail($tutor_user['company_id']);
                $tutor_company["logo"] = HUB_URL."/media/img/company/".$tutor_company["id"].".png";
            }
        
            $url_avviacorso = AVVIACORSO_URL."?course=" . $lpu_detail["learning_project_pwd"]; // necessario nel file mail_licenza_corso.php
        
            ob_start();
            include BASE_LIBRARY_PATH .'../ecommerce/mail_licenza_corso.php';
            $msg .= ob_get_contents();
            ob_end_clean();
        
        }
        
        $obj = "TUTOR81 - Avvia il tuo corso";

        return $this->sendMail(array(
            'a' => array(
                array(
                    'mail' => $destination_email ? : $tutor_user["email"],
                    'name' => $destination_email ? '' : $tutor_user["surname"]." ".$tutor_user["name"]
                )
            ),
            'cc' => array(
                array(
                    'mail' => $tutor_user["email"],
                    'name' => $tutor_user["surname"]." ".$tutor_user["name"]
                ),
                array(
                    'mail' => $tutor_company['email'],
                    'name' => $tutor_company['business_name']
                )
            ),
            'ccn' => array(
                array(
                    'mail' => 'assistenza@tutor81.it',
                    'name' => ''
                )
            ),
            'confirmReadingTo' => $tutor_user["email"],
            'object' => $obj,
            'body' => $msg
        ));
    }

    /**
     * Notifica l'avvenuta registrazione di un utente e contestuale associazione 
     * della licenza
     * 
     * @param array $lpu
     * @return boolean
     */
    public function notifyLearningUserRegistration($lpu) {
        $user = $this->user_obj->getDetail($lpu['user_id']);
        $learning_project = $this->learn_obj->getCourseDetailFromLearningProject($lpu['learning_project_id']);

        $destination_email = $user['email'];
        $destination_name = ucwords(strtolower($user['name'] . ' ' . $user['surname']));
        
        $title = T81LearningProject::formatTitle($learning_project["learning_project_title"]);

        $obj = "TUTOR81 - Assegnazione corso $title";

        $msg = <<< EOF
                <p>Buongiorno Sig. {$destination_name}<br>
                   Ti confermiamo l’avvio del corso: {$title}<br>
                   Per ogni esigenza non esitare a contattare <a href="mailto:assistenza@tutor81.it">assistenza@tutor81.it</a>
                </p>
                
EOF;

        return $this->sendMail(array(
            'a' => array(
                array(
                    'mail' => $destination_email,
                    'name' => $destination_name
                )
            ),
            'cc' => array(
                array(
                    'mail' => $isAssigned ? $tutor_user["email"] : "",
                    'name' => $isAssigned ? $tutor_user["surname"]." ".$tutor_user["name"]  : ""
                ),
                array(
                    'mail' => $tutor_company ['email'],
                    'name' => $tutor_company['business_name']
                )
            ),
            'ccn' => array(
                array(
                    'mail' => 'assistenza@tutor81.it',
                    'name' => ''
                )
            ),
            'object' => $obj,
            'body' => $msg
        ));
    }

    public function notifyPurchase($tutor_purchase_id) {
        $tutor_purchase_id = sanitize($tutor_purchase_id, INT);
        $purchase = $this->purchase_obj->getPurchase($tutor_purchase_id);
        if (!$purchase)
            return false;

        $tutor = $this->user_obj->getDetail($purchase ['tutor_id']);
        
        $learning = $this->learn_obj->getDetail($purchase ['learning_project_id']);
        $course = $this->learn_obj->getCourseDetailFromLearningProject($purchase ['learning_project_id']);
        $tutor_company_name = strtoupper($tutor ['business_name']);
        $tutor_name = strtoupper("{$tutor['name']} {$tutor['surname']}");
        $company_name = strtoupper($tutor['business_name']);
        $course_name = strtoupper($learning ['title']);
        $msg = <<<EOF
            <p>Il tuo ordine è stato eseguito correttamente e ti verrà fatturato secondo gli accordi commerciali previsti:</p>   
            <p>Ente formativo: <b>$tutor_company_name</b>
                <br>
                Acquirente: <b>$tutor_name</b>
                <br>
                Committente: <b>$company_name</b>
                <br>
                Nome del corso: <b>$course_name</b>
                <br>
                Licenze numero: <b>{$purchase ['qta']}</b>
            </p>
            <p>NOTA BENE: le licenze scadono dopo 6 mesi dalla loro attivazione</p>
EOF;
        $msg .= self::FOOTER_TUTOR81;

        $obj = "TUTOR81 - RIEPILOGO ACQUISTO CORSO numero $tutor_purchase_id";
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $tutor ['email'],
                            'name' => ucwords(strtolower("{$tutor['surname']} {$tutor['name']}"))
                        )
                    ),
                    'ccn' => array(
                        array(
                            'mail' => 'amministrazione@tutor81.it',
                            'name' => 'Amministrazione'
                        ),
                        array(
                            'mail' => 'luca.pedretti@tutor81.com',
                            'name' => 'Luca Pedretti'
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg
                ));
    }

    /**
     * Notifica della registrazione di un utente (invio username)
     *
     * @param Integer $user_id        	
     */
    public function notifyUserRegistration($user_id) {
        $user_id = sanitize($user_id, INT);
        $user = $this->user_obj->getDetail($user_id);
        $user_company = $this->user_obj->getUserCompany($user_id);
        $tutor_company = $this->user_obj->getUserCompany($user_company['owner_user_id']);
        $hub_url = HUB_URL;
        $user_name = ucwords("{$user['name']} {$user['surname']}");
        $role = $this->permission_obj->getRoleById($user ['role']);
        $role_title = strtoupper($role['short_desc_role']);

        $msg = <<<EOF
		<h3><a href="$hub_url">TUTOR81</a> - Registrazione piattaforma</h3>
		<p>Buongiorno $user_name,</p>
                <p>sei stato registrato nella piattaforma Tutor81 in qualità di $role_title.</p>
                <p>per accedere alla piattaforma clicca <a href="$hub_url">$hub_url</a></p>
		<p>NOME UTENTE: <b>{$user['username']}</b></p>
		<p>PASSWORD: La password per il primo accesso sarà il tuo codice fiscale.
                    Puoi modificarla seguendo queste <a href="https://www.tutor81.it/istruzioni-uso-tutor81/" target="_blank">istruzioni</a>.</p>
                <br>
                <p>L’utilizzo della piattaforma è estremamente semplice e non richiede 
                    la lettura di nessun manuale, puoi fare riferimento ai tutorial 
                    interni oppure a questa pagina <a href="https://www.tutor81.it/istruzioni-uso-tutor81/" target="_blank">
                        https://www.tutor81.it/istruzioni-uso-tutor81/</a>
                            </p>
                <br>
EOF;
        $msg .= self::FOOTER_TUTOR81;

        $obj = "Registrazione Piattaforma e-learning TUTOR81";
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $user ['email'],
                            'name' => $user_name
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg
                ));
    }

    /** ATTENZIONE SERVE SOLO SE LA PASSWORD e' DEFINITA DALLA PIATTAFORMA
     * Notifica della password di un utente (invio password)
     *
     * @param Integer $user_id        	
     */
    /*
      public function notifyUserPassword($user_id, $password) {
      $user_id = sanitize ( $user_id, INT );
      // $password = $this->db_conn->escapestr($password);
      $user = $this->user_obj->getDetail ( $user_id );
      $user_name = ucwords ( "{$user['name' ]} {$user['surname']}" );
      $role = $user ['role'] == 1000 ? 'SUPER UTENTE' : ($user ['role'] == 1 ? 'AMMINISTRATORE ENTE FORMATIVO' : ($user ['role'] == 2 ? 'AMMINISTRATORE' : 'CORSISTA'));

      $msg = <<<EOF
      <h3><a href="http://www.tutor81.com">TUTOR81</a> - Accesso piattaforma</h3>
      <p>Buongiorno $user_name, ti comunichiamo la generazione della password per l'accesso alla piattaforma di e-learning
      <a href="http://amministrazione.tutor81.com">TUTOR81</a>	in qualità di $role.</p>
      <p>Per accedere al sito utilizza la seguente password: <b>$password</b></p>
      <p>Utilizzando le credenziali fornite potrai accedere al sito <a href="http://amministrazione.tutor81.com">amministrazione.tutor81.com</a>
      presso il quale potrai seguire i corsi in e-learning a te riservati e svolgere le attività di gestione.</p>
      EOF;
      $msg .= self::FOOTER_TUTOR81;

      $obj = "Credenziali Piattaforma TUTOR81";
      return $this->sendMail ( array (
      'a' => array (
      array (
      'mail' => $user ['email'],
      'name' => $user_name
      )
      ),
      'object' => $obj,
      'body' => $msg
      ) );
      }
     */

    /**
     * Notifica del nome utente di un utente (invio username)
     *
     * @param Integer $user_id
     */
    public function notifyUserName($user_id) {
        $user_id = sanitize($user_id, INT);
        $user = $this->user_obj->getDetail($user_id);
        //$user_company = $this->user_obj->getUserCompany($user_id);
        //$tutor_company = $this->user_obj->getUserCompany($user_company['owner_user_id']);
        //$hub_url = !empty($tutor_company['hub_url']) ? $tutor_company['hub_url'] : 'amministrazione.tutor81.com';
        $user_name = ucwords("{$user['name']} {$user['surname']}");

        $msg = <<<EOF
            <h3><a href="http://avviacorso.tutor81.com">TUTOR81</a> - Invio nome utente</h3>
            <p>Buongiorno $user_name, ti comunichiamo che per accedere al sito 
                <a href="http://avviacorso.tutor81.com">avviacorso.tutor81.com</a> devi utilizzare 
                il seguente nome utente: <b>{$user['username']}</b></p>
            <p>La password, nel caso del primo accesso alla piattaforma, corrisponde 
                al tuo codice fiscale.</p>
EOF;
        $msg .= self::FOOTER_TUTOR81;

        $obj = "TUTOR81 - Credenziali Piattaforma";
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $user ['email'],
                            'name' => $user_name
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg
                ));
    }

    /**
     * Notifica del reset della password (puàò entrare con il codice fiscale)
     * 
     * @param Integer $user_id
     */
    public function notifyPasswordReset($user_id) {
        $user_id = sanitize($user_id, INT);
        $user = $this->user_obj->getDetail($user_id);
        $user_company = $this->user_obj->getUserCompany($user_id);
        $tutor_company = $this->user_obj->getUserCompany($user_company['owner_user_id']);
        $hub_url = !empty($tutor_company['hub_url']) ? $tutor_company['hub_url'] : 'amministrazione.tutor81.com';
        $user_name = ucwords("{$user['name']} {$user['surname']}");

        $msg = <<<EOF
		<h3><a href="http://$hub_url">TUTOR81</a> - Reimpostazione della password</h3>
		<p>Buongiorno $user_name, in seguito alla tua richiesta ti comunichiamo che
                    la password per l'accesso alla nostra piattaforma è stata reimpostata.</p>
                <p>Ora per accedere al sito <a href="http://$hub_url">$hub_url</a>
                    devi utilizzare il tuo nome utente: <b>{$user['username']}</b> e come password 
                    il tuo codice fiscale. Effettuato l'accesso ti verrà chiesto di modificare la password.</p>
		
EOF;
        $msg .= self::FOOTER_TUTOR81;

        $obj = "TUTOR81 - Reimpostazione password";
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $user ['email'],
                            'name' => $user_name
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg
                ));
    }

    /**
     * Notifica username e codice di rigenerazione della password
     *
     * @param Integer $user_id
     * @param String $code
     */
    public function notifyPasswordRecoverCode($user_id, $code) {
        $user_id = sanitize($user_id, INT);
        $code = $this->db_conn->escapestr($code);
        $user = $this->user_obj->getDetail($user_id);
        $user_company = $this->user_obj->getUserCompany($user_id);
        $tutor_company = $this->user_obj->getUserCompany($user_company['owner_user_id']);
        $hub_url = !empty($tutor_company['hub_url']) ? $tutor_company['hub_url'] : 'amministrazione.tutor81.com';
        $user_name = ucwords("{$user['name']} {$user['surname']}");

        $msg = <<<EOF
		<h3><a href="http://$hub_url">TUTOR81</a> - Invio nome utente e codice di reimpostazione password</h3>
		<p>Buongiorno $user_name, in seguito alla tua richiesta ti comunichiamo che
                    per accedere al sito <a href="http://$hub_url">$hub_url</a>
                    devi utilizzare il seguente nome utente: <b>{$user['username']}</b></p>
		<p>Se non ricordi la password puoi reimpostarla tramite il seguente link:<br>
                    <a href="http://$hub_url/login.php?recover=$code" title="recover password">http://$hub_url/login.php?recover=$code</a><br>
                    che sarà valido solo per le prossime 24 ore.</p>
		
EOF;
        $msg .= self::FOOTER_TUTOR81;

        $obj = "TUTOR81 - Recupero credenziali";
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $user ['email'],
                            'name' => $user_name
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg
                ));
    }

    /**
     * Assegnazione corso
     *
     * @param Integer $license_id        	
     */
    public function notifyCourseAssignment($license_id) {
        $license_id = sanitize($license_id, INT);
        $license = $this->user_obj->getUserLicenseById($license_id);

        $user = $this->user_obj->getDetail($license ['user_id']);
        $user_name = ucwords("{$user['name']} {$user['surname']}");
        $tutor = $this->user_obj->getDetail($license ['company_id']); // utente amminitratore ente formativo
        $tutor_name = ucwords("{$tutor['name']} {$tutor['surname']}");
        $course = $this->learn_obj->getCourseDetailFromLearningProject($license ['learning_project_id']);
        $start_date = DateTime::createFromFormat('Y-m-d', $license ['starting_from'], new DateTimeZone('Europe/Rome'));
        $end_date = DateTime::createFromFormat('Y-m-d', $license ['finish_within'], new DateTimeZone('Europe/Rome'));
        $course_title = strtoupper($course['title']);
        $course_duration = (int) $course['total_elearning'];
        $username = strtoupper($user['username']);
        
        $tutor_company = $this->company_obj->getBusinessDetail($tutor['company_id']);
        $tutor_company["logo"] = HUB_URL."/media/img/company/".$tutor_company["id"].".png";

        $assigned_user = $license['user_id'] > 0 ? $this->user_obj->getDetail($license["user_id"]) : false;

        $destination_name = $assigned_user ? ucwords(strtolower($assigned_user['name'] . ' ' . $assigned_user['surname'])) : '';

        if ($tutor_company['id'] == 2600) {
            $sender = 'Eraclitea';
            $platform_name = 'ACCADEMIA ERACLITEA';
            $url_avviacorso = $assigned_user ? "https://eraclitea.tutor81.com" : "https://eraclitea.tutor81.com?course=" . $license["learning_project_pwd"];
            $mail_file = $assigned_user ? 'mail_avvia_corso_eraclitea.php' : 'mail_licenza_corso_eraclitea.php';
        } elseif ($tutor_company['id'] == 2978) {
            $sender = 'Libellula';
            $platform_name = 'FONDAZIONE LIBELLULA';
            $url_avviacorso = $assigned_user ? "https://libellula.tutor81.com" : "https://libellula.tutor81.com?course=" . $lpu_detail["learning_project_pwd"];
            $mail_file = $assigned_user ? 'mail_avvia_corso_libellula.php' : 'mail_licenza_corso_libellula.php';
        }  else {
            $sender = '';
            $platform_name = 'TUTOR81';
            $url_avviacorso = $assigned_user ? COMMON_AVVIACORSO_URL : AVVIACORSO_URL."?course=" . $icense["learning_project_pwd"];
            $mail_file = $assigned_user ? 'mail_avvia_corso.php' : 'mail_licenza_corso.php';  
        }
        
        
        $msg = <<<EOF
		<p>Buongiorno: $user_name</p>
                <p>sei stato iscritto a CORSO $course_title
                    <br>
                    durata $course_duration ore
                    <br>
                    potrai iniziare a partire dal giorno: {$start_date->format('d/m/Y')}
                    <br>
                    e terminare entro il giorno: {$end_date->format('d/m/Y')}
                    <br>
                    il tuo referente per questo corso è: <a href="mailto:{$tutor['email']}">$tutor_name</a>
                    <br>
                    al termine del corso i tuoi referenti riceveranno l'attestato di avvenuta formazione
                </p>
                <br>
                <br>
                <p>Alcune avvertenze:</p>
                <ol>
                    <li><strong>IL CORSO PUO' ESSERE INTERROTTO</strong> con il
                        pulsante <strong>ESCI</strong> in alto a sinistra. Riaccedendo
                        al corso questo ripartirà dall'ultimo punto utile.</li>
                    <li><strong>PAUSA:</strong> puoi fermare temporaneamente il corso
                        con il pulsante Ferma, ma solo per 30 secondi, terminati i quali
                        il corso viene interrotto.</li>
                </ol>
                <br>
                <hr>
                <h2 style="color: red; text-align: center;">COME AVVIARE IL CORSO</h2>
                <p>Il corso si avvia per mezzo del nome utente, ovvero il nome.cognome (ad esempio MARIO.ROSSI).
                    <br>
                    Per verificare la tua identità vengono fatte delle domande 
                    estratte dal codice fiscale (ad esempio mese di nascita, giorno…eccc..)</p>
                <h2>Il tuo nome utente è: <span style="color: #23abdd;">{$username}</span> puoi scriverlo come preferisci in maiuscolo o minuscolo.
                    <br>
                    Ora puoi iniziare cliccando o memorizzando il sito: 
                        <a href="$url_avviacorso" style="color: #23abdd">$url_avviacorso</a></h2>
EOF;

        $msg .= self::FOOTER_TUTOR81;

        $obj = $platform_name . " - Avviacorso";
        $result = $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $user ['email'],
                            'name' => $user_name
                        )
                    ),
                    'cc' => array(
                        array(
                            'mail' => $tutor["email"],
                            'name' => $tutor_name
                        ),
                        array(
                            'mail' => $tutor_company['email'],
                            'name' => $tutor_company['business_name']
                        )
                    ),
                    'ccn' => array(
                        array(
                            'mail' => 'assistenza@tutor81.it',
                            'name' => ''
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg,
                    'sender' => $sender
                ));
        return array('result' => $result, 'name' => $user_name, 'email' => $user['email']);
    }
    
    /**
     * Invia all'amministratore acquirente e ai manager dell'azienda per cui 
     * vengono acquistati i corsi, una mail riepilogativa dell'invio delle mail
     * 
     * @param int $buyer_id
     * @param int $company_id
     * @param int $learning_project_id
     * @param array $results_success (array('name' => 'user name', 'email' => 'user email'))
     * @param array $result_failed (array('name' => 'user name', 'email' => 'user email'))
     * @param DateTime $date_assignment
     * @return boolean
     */
    public function notifyCourseAssignmentResult($buyer_id, $company_id, $learning_project_id, $results_success, $results_failed, $date_assignment = null) {
        $buyer_id = filter_var($buyer_id, FILTER_SANITIZE_NUMBER_INT);
        $company_id = filter_var($company_id, FILTER_SANITIZE_NUMBER_INT);
        $learning_project_id = filter_var($learning_project_id, FILTER_SANITIZE_NUMBER_INT);
        $date_assignment = $date_assignment instanceof DateTime ? $date_assignment : new DateTime('now');
        $learning = $this->learn_obj->getDetail($learning_project_id);
        $learn_title = strtoupper(substr($learning['title'], strpos($learning['title'], ' - ') + 3));
        $company = $this->company_obj->getBusinessDetail($company_id);
        $buyer = $this->user_obj->getDetail($buyer_id);
        $company_managers = $this->company_obj->getUsersCompanyByID($company_id, 2);
        $tutor_user = $this->user_obj->getDetail($company['owner_user_id']);
        $tutor_company = $this->user_obj->getUserCompany($company['owner_user_id']);
        $hub_url = !empty($tutor_company['hub_url']) ? $tutor_company['hub_url'] : 'amministrazione.tutor81.com';
        $url_player = URL_PLAYER;

        $msg = <<<EOF
                <p>Di seguito l’esito delle email inviate per il corso $learn_title
                <br>
                da te assegnato in data {$date_assignment->format('d/m/Y')}
                <br>
                Azienda {$company['business_name']}</p>
                <p>Ricorda che per avviare il corso gli utenti si devono collegare
                a <a href="$url_player">avviacorso.tutor81.com</a>, 
                in qualità di amministratore devi accedere all'indirizzo <a href="http://$hub_url">$hub_url</a></p>
                <br>
                <p>ESITO POSITIVO
                <br>
EOF;
        if (!empty($results_success)){
            foreach ($results_success as $result){
                $name = filter_var($result['name'], FILTER_SANITIZE_STRING);
                $email = filter_var($result['email'], FILTER_SANITIZE_STRING);
                $msg .= $name . ' - ' . $email . '<br>';
            }
        } else {
            $msg .= 'nessuno<br>';
        }
        
        $msg .= '</p><br><p>ESITO NEGATIVO<br>';
        
        if (!empty($results_failed)){
            foreach ($results_failed as $result){
                $name = filter_var($result['name'], FILTER_SANITIZE_STRING);
                $email = filter_var($result['email'], FILTER_SANITIZE_STRING);
                $msg .= $name . ' - ' . $email . '<br>';
            }
        
            $msg .= <<<EOF
                </p>
                <hr>
                <p>Cosa puoi fare:
                    <ul>
                        <li>controlla l'indirizzo email</li>
                        <li>entra nel pannello di amministrazione e spedisci nuovamente 
                            l'email di avviacorso
                        <li>contatta l'amministratore di rete</li>
                        <li>contattaci <a href="mailto:assistenza@tutor81.it">assistenza@tutor81.it</a></li>
                    </ul>
                </p>
                <br>
EOF;

        } else {
            $msg .= 'nessuno<br></p>';
        }
        
        $msg .= self::FOOTER_TUTOR81;
        
        $aa = array(
                array('mail' => $buyer['email'],
                      'name' => ucwords("{$buyer['name']} {$buyer['surname']}")
                      )
                    );
        if ($buyer['id'] != $tutor_user['id']) {
            array_push($aa, array('mail' => $tutor_user['email'], 
                                  'name' => ucwords("{$tutor_user['name']} {$tutor_user['surname']}")
                                  ));
        }

        $cc = array();
        if ($company_managers) {
            foreach ($company_managers as $manager){
                array_push($cc, array('mail' => $manager['email'], 
                                      'name' => ucwords("{$manager['name']} {$manager['surname']}")
                                      ));
            }
        }
        
        $obj = "TUTOR81 - Esito invio email avviacorso";
        return $this->sendMail(array(
                    'a' => $aa,
                    'cc' => $cc,
                    'object' => $obj,
                    'body' => $msg
                ));
    }
    
    /**
     * Invia la licenza del corso
     * 
     * @param type $license_id
     * @return type
     */
    public function notifyLicense($license_id) {
        $license_id = sanitize($license_id, INT);
        $license = $this->user_obj->getUserLicenseById($license_id);

        $user = $this->user_obj->getDetail($license ['user_id']);
        $user_name = ucwords("{$user['name']} {$user['surname']}");
        $tax_code = strtoupper($user['tax_code']);
        $course = $this->learn_obj->getCourseDetailFromLearningProject($license ['learning_project_id']);
        $start_date = DateTime::createFromFormat('Y-m-d', $license ['starting_from'], new DateTimeZone('Europe/Rome'));
        $end_date = DateTime::createFromFormat('Y-m-d', $license ['finish_within'], new DateTimeZone('Europe/Rome'));

        $msg = <<<EOF
		<h3><a href="http://avviacorso.tutor81.com">TUTOR81</a> - Assegnazione corso {$course['title']}</h3>
		<p>Buongiorno $user_name, ti è stato assegnato un nuovo corso che potrai iniziare a partire
		dal giorno {$start_date->format('d/m/Y')} e dovrai terminare entro il giorno {$end_date->format('d/m/Y')}.</p>
                <h4>Licenza corso</h4>
                <dl>
                    <dt>Nome</dt>
                    <dd>$user_name</dd>
                    <dt>Codice Fiscale</dt>
                    <dd>$tax_code</dd>
                    <dt>Corso</dt>
                    <dd><b>{$course['title']}</b></dd>
                </dl>
		<h1>PER INIZIARE IL CORSO VAI AL SITO <a href="http://avviacorso.tutor81.com">avviacorso.tutor81.com</a> 
                    E INSERISCI LE TUE CREDENZIALI:</h1>
                <p>Nome utente: <b>{$user['username']}</b><br>
                   Licenza: <b>{$license['learning_project_pwd']}</b></p>
                
                <div>
                    <h4>MODALITA' DI UTILIZZO</h4>
                    <ol>
                        <li><b>IL CORSO PUO' ESSERE INTERROTTO</b> con il
                            pulsante <b>ESCI/STOP</b> in alto a destra. Riaccedendo
                            al corso questo ripartirà dall'ultimo punto utile.</li>
                        <li><b>I TEST</b> hanno una durata temporizzata di 30
                            secondi, trascorsi i quali il corso si interrompe e dovrai
                            riprendere la lezione.</li>
			<li><b>PAUSA:</b> puoi fermare temporaneamente il corso
                            con il pulsante Ferma, ma solo per 30 secondi, terminati i quali
                            il corso viene interrotto.</li>
			<li><b>ASSISTENZA:</b> gli operatori in chat sono
                            disponibili dalle 9:00 alle 13:00 e dalle 14:00 alle 18:00, dal
                            lunedì al venerdì. In ogni momento è
                            possibile inviare una segnalazione anche tramite mail con il
                            pulsante <b>Richiedi Assistenza</b></li>
			<li><b>VIDEO A SCATTI</b> se la vostra banda adsl è
                            satura è possibile che i video si vedano e si sentano a scatti, in
                            questo caso contattare l' amministratore di rete o interrompere
                            qualsiasi download in corso.</li>
                    </ol>
                    <p>Ricordiamo che la velocità effettivamente raggiungibile può
                        essere inferiore a quella nominale, in quanto essa dipende da
			numerosi fattori quali ad esempio:</p>
                    <ul>
			<li>Velocità della propria connessione ad Internet</li>
			<li>Velocità dei nodi Internet attraversati per raggiungere la rete
                            tutor81.com</li>
			<li>Limitazioni di velocità dei sistemi Firewall/Antivirus/IDP
                            hardware o software che proteggono la propria rete</li>
                        <li>Numero di utenti collegati contemporaneamente e gestibili dal
                            proprio Sistema</li>
                    </ul>
		</div>
                <div>
                    <h3>REQUISITI TECNICI</h3>
                    <ul>
			<li><b>Browser: Firefox 21 (consigliato)</b>, Chrome 27,
                            Internet Explorer 8.x, o versioni superiori.</li>
			<li>Nel caso si utilizzi Internet Explorer 8 è necessario
                            installare il <b>plugin di Flash.</b></li>
			<li><b>Abilitazione degli scipt.</b></li>
			<li><b>Connessione a banda larga (ADSL).</b></li>
			<li><b>Firewall con libero accesso alla porta 8080.</b></li>
			<li>Per poter visualizzare i <b>TEST</b> e la <b>CHAT</b>
                            è necessario abilitare <b>JAVASCRIPT</b> nel browser.</li>
			<li><b>Nessun filtro</b> sui seguenti siti:
                            <ul>
                                <li>tutor81.com</li>
                                <li>avviacorso.tutor81.com</li>
                                <li>62.149.225.172</li>
                                <li>tutor81-vh.akamaihd.net</li>
                                <li>jwpcdn.com</li>
                                <li>snapengage.com</li>
                                <li>commandatastorage.googleapis.com</li>
                            </ul>
                        </li>
                    </ul>
                </div>
EOF;

        $msg .= self::FOOTER_TUTOR81;

        $obj = "TUTOR81 - Licenza corso";
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $user ['email'],
                            'name' => $user_name
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg
                ));
    }


    /**
     * Alert per scadenza corso
     *
     * @param Integer $user_id        	
     */
    public function notifyAlert($license_id, $custom_message = '') {
        $license_id = filter_var($license_id, FILTER_SANITIZE_NUMBER_INT);
        $custom_message = $this->db_conn->escapestr($custom_message);
        $license = $this->user_obj->getUserLicenseById($license_id);

        // se non è impostato un limite di tempo per il corso annullare tutto
        if (!isset($license ['finish_within']) || $license ['finish_within'] == "0000-00-00")
            return false;

        $user = $this->user_obj->getDetail($license ['user_id']);
        $user_name = ucwords(strtolower($user['name'] . ' ' . $user['surname']));
        $course = $this->learn_obj->getCourseDetailFromLearningProject($license ['learning_project_id']);
        $end_date = DateTime::createFromFormat('Y-m-d', $license ['finish_within'], new DateTimeZone('Europe/Rome'));
        
        // definizione dell'amministratore dell'ente fomrativo per invio mail in cc
        $company = $this->user_obj->getUserCompany($license ['user_id']);
        $tutor_user = $this->user_obj->getDetail($company['owner_user_id']);
        $tutor_user_name = ucwords(strtolower($tutor_user['name'] . ' ' . $tutor_user['surname']));
        
        $tutor_company = $this->company_obj->getBusinessDetail($tutor_user['company_id']);
        if ($tutor_company['id'] == 2600) {
            $sender = 'Eraclitea';
            $platform_name = 'ACCADEMIA ERACLITEA';
        } elseif ($tutor_company['id'] == 2978) {
            $sender = 'Libellula';
            $platform_name = 'FONDAZIONE LIBELLULA';
        }  else {
            $sender = '';
            $platform_name = 'TUTOR81'; 
        }

        $msg = <<<EOF
		<p>Buongiorno $user_name,
                <br>ti ricordiamo che la scadenza del corso {$course['title']} 
		è prevista per il giorno {$end_date->format('d/m/Y')}.</p>
		<p>Oltre tale data non potrai più accedere al corso.</p>
                <p>Affrettati a terminare!</p>
		<p>$custom_message</p>
EOF;

        $msg .= $sender == "Eraclitea" ? self::FOOTER_ERACLITEA : self::FOOTER_TUTOR81;

        $obj = $platform_name . " - Promemoria scadenza corso";
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $user ['email'],
                            'name' => $user_name
                        )
                    ),
                    'cc' => array(
                        array(
                            'mail' => $tutor_user['email'],
                            'name' => $tutor_user_name
                        )
                    ),
                    'ccn' => array(
                        array(
                            'mail' => '',
                            'name' => ''
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg,
                    'sender' => $sender
                ));
    }

    /**
     * Modifica scadenza corso
     *
     * @param Integer $license_id        	
     */
    public function notifyLicenseExpirationDate($license_id) {
        $license_id = sanitize($license_id, INT);
        $license = $this->user_obj->getUserLicenseById($license_id);

        $user = $this->user_obj->getDetail($license ['user_id']);
        $user_name = ucwords("{$user['name']} {$user['surname']}");
        $course = $this->learn_obj->getCourseDetailFromLearningProject($license ['learning_project_id']);
        $end_date = DateTime::createFromFormat('Y-m-d', $license ['finish_within'], new DateTimeZone('Europe/Rome'));
        
        // definizione dell'amministratore dell'ente fomrativo per invio mail in cc
        $company = $this->user_obj->getUserCompany($license ['user_id']);
        $tutor_user = $this->user_obj->getDetail($company['owner_user_id']);
        $tutor_user_name = ucwords(strtolower($tutor_user['name'] . ' ' . $tutor_user['surname']));

        $msg = <<<EOF
		<p>Buongiorno $user_name, ti comunichiamo che è stata modificata la data di scadenza del corso,
                che dovrà essere completato entro il giorno {$end_date->format('d/m/Y')}.</p>
EOF;

        $msg .= self::FOOTER_TUTOR81;

        $obj = "TUTOR81 - Nuova scadenza corso";
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $user ['email'],
                            'name' => $user_name
                        )
                    ),
                    'cc' => array(
                        array(
                            'mail' => $tutor_user['email'],
                            'name' => $tutor_user_name
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg
                ));
    }

    /**
     * Invio link per download attestato
     * 
     * @param type $license_id
     * @param type $destination_email
     * @return boolean
     */
    public function notifyAttestato($license_id, $destination_email = false) {
        $license_id = filter_var($license_id, FILTER_SANITIZE_NUMBER_INT);
        $destination_email = filter_var($destination_email, FILTER_VALIDATE_EMAIL);
        
        if (!$license_id)
            return false;
        
        $user_obj = new T81User();
        $learning_project_obj = new T81LearningProject();
        $company_obj = new T81Company();
        
        $learning_project_user = $user_obj->getUserLicenseById($license_id);
        $user = $user_obj->getDetail($learning_project_user['user_id']);
        $company = $company_obj->getBusinessDetail($user['company_id']);
        $tutor_user = $user_obj->getDetail($company['owner_user_id']);
        $tutor = $company_obj->getBusinessDetail($tutor_user['company_id']);
        $destination_email = $destination_email ? : ($company['send_certificate'] ? $user['email'] : $tutor_user['email']);
        $user_name = ucwords("{$user['name']} {$user['surname']}");
        $learning_project = $learning_project_obj->getCourseDetailFromLearningProject($learning_project_user['learning_project_id']);
        $learning_project_title = strtoupper(substr($learning_project['learning_project_title'], strpos($learning_project['learning_project_title'], ' - ') + 3));
        if ($tutor_company['id'] == 2600) {
            $sender = 'Eraclitea';
            $platform_name = 'ACCADEMIA ERACLITEA';
        } elseif ($tutor_company['id'] == 2978) {
            $sender = 'Libellula';
            $platform_name = 'FONDAZIONE LIBELLULA';
        }  else {
            $sender = '';
            $platform_name = 'TUTOR81'; 
        }
        $tutor["logo"] = HUB_URL. "/media/img/company/" . $tutor["id"] . ".png";
        $link_attestato = HUB_URL . "/media/attestati/attestato_licenza_" . $license_id . ".pdf";

        $msg = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{$platform_name} Piattaforma di E-Learning</title>
    <meta name="viewport" content="width=device-width" />
    <style type="text/css">
        @media only screen and (max-width: 550px), screen and (max-device-width: 550px) {
            body[yahoo] .buttonwrapper { background-color: transparent !important; }
            body[yahoo] .button { padding: 0 !important; }
            body[yahoo] .button a { background-color: #31313A; padding: 15px 25px !important; }
        }

        @media only screen and (min-device-width: 601px) {
            .content { width: 600px !important; }
            .col387 { width: 387px !important; }
        }
    </style>
</head>
<body bgcolor="#31313A" style="margin: 0; padding: 0;" yahoo="fix">
<!--[if (gte mso 9)|(IE)]>
<table width="600" align="center" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td>
<![endif]-->
<table align="center" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 600px;" class="content">
    <tr>
        <td style="padding: 15px 10px 15px 10px;">
        </td>
    </tr>
    <tr>
        <td align="center" bgcolor="#394263" style="padding: 15px 15px 0 15px ; color: #ffffff; font-family: 'Helvetica Neue', sans-serif; font-size: 36px; font-weight: bold;">
            <div style="background-color:  #1bbae1; padding-bottom: 50px;">
                <img src="{$tutor["logo"]}" alt="{$tutor["logo"]}" style="height: 44px; padding-top: 20px;"/><br />
                <h2 style="font-size: 16px; padding-top: 15px;">Buongiorno $user_name</h2>
            </div>
        </td>
    </tr>
    <tr>
        <td align="center" bgcolor="#394263" style="padding: 5px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 20px; line-height: 30px;">
            <p style="color: #fff;">Con la presente ti comunichiamo che hai correttamente completato il corso a te assegnato:</p>
            <h2 style="color: #00a7d0;">$learning_project_title</h2>
            <p>Puoi scaricare l'attestato <a href="$link_attestato">cliccando qui</a>.</p>
        </td>
    </tr>
    <tr>
        <td style="padding: 0 0 30px 0;" >
            <div style=" border: 15px;  border-style:  none solid none solid; border-color:#394263; ">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td align="center" width="100%" style="padding: 0 20px; color: #fff;  background-color: #394263; font-family: Arial, sans-serif; font-size: 12px;">
                            <h3>{$tutor["business_name"]} - {$tutor["address"]}<span>&nbsp;</span><span>&nbsp;</span><span>&nbsp;</span> {$tutor["city"]}</h3>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>
<!--[if (gte mso 9)|(IE)]>
</td>
</tr>
</table>
<![endif]-->
</body>
</html>
EOF;

        

        $title = "ATTESTATO FINE CORSO";

        $obj = $platform_name . " - ATTESTATO FINE CORSO";


        return $this->sendMail(array(
            'a' => array(
                array(
                    'mail' => $destination_email,
                    'name' => ''
                )
            ),
            'object' => $obj,
            'body' => $msg,
            'sender' => $sender
        ));
    }


    /**
     * Risposta automatica creazione ticket
     *
     * @param Integer $id_ticket        	
     */
    public function notifyNewTicket($id_ticket) {
        $id_ticket = sanitize($id_ticket, INT);
        $ticket = $this->ticket_obj->getTicketById($id_ticket);
        $user_name = "";
        if (isset($ticket ['user_id'])) {
            $user = $this->user_obj->getDetail($ticket ['user_id']);
            $user_name = ' ' . ucwords($user ['name']) . ' ' . ucwords($user ['surname']);
        }

        $msg = <<<EOF
		<p>Buongiorno $user_name,
		abbiamo ricevuto la tua richiesta di assistenza a cui è stato assegnato il codice: <b>{$ticket['code']}</b>. 
		Al più presto ti verrà data una risposta.</p>
		<p>NON RISPONDERE A QUESTA MAIL!</p>
		<p>Per visualizzare la nostra risposta e comunicare con il nostro staff in merito al ticket, accedi
		alla tua area utente o al corso che stai seguendo o all'area di assistenza di uno dei nostri siti
		facendo riferimento al codice sopra indicato.</p>
EOF;
        $msg .= self::FOOTER_TUTOR81;

        $obj = "TUTOR81 - Presa in carico nuovo ticket assistenza";
        $this->notifyTicketToAssistance($id_ticket);
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => $ticket ['user_email'],
                            'name' => $user_name
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg
                ));
    }

    public function notifyTicketToAssistance($id_ticket) {
        $ticket = $this->ticket_obj->getTicketById($id_ticket);

        $msg = <<<EOF
		<h3>Richiesta di assistenza</h3>
		<p>Ricevuta nuova richiesta di assistenza con codice: {$ticket['code']};</p>
EOF;
        $msg .= self::FOOTER_TUTOR81;

        $obj = "TUTOR81 - Nuovo ticket assistenza: {$ticket['code']}";
        return $this->sendMail(array(
                    'a' => array(
                        array(
                            'mail' => 'assistenza@tutor81.it',
                            'name' => 'Assistenza Tutor81'
                        )
                    ),
                    'object' => $obj,
                    'body' => $msg
                ));
    }

    public function notifyNewThread($id_ticket, $from_staff = 1) {
        $id_ticket = sanitize($id_ticket, INT);
        $ticket = $this->ticket_obj->getTicketById($id_ticket);

        if ($from_staff == 0) {
            $msg = <<<EOF
			<h3>Nuovo messaggio utente</h3>
			<p>Ricevuta nuova messaggio relativo alla richiesta di assistenza con codice: {$ticket['code']}</p>
EOF;
        $msg .= self::FOOTER_TUTOR81;

            $obj = "TUTOR81 - Nuovo messaggio da utente per {$ticket['code']}";
            return $this->sendMail(array(
                        'a' => array(
                            array(
                                'mail' => 'assistenza@tutor81.it',
                                'name' => 'Assistenza Tutor81'
                            )
                        ),
                        'object' => $obj,
                        'body' => $msg
                    ));
        } else {
            $user_name = "";
            if (isset($ticket ['user_id'])) {
                $user = $this->user_obj->getDetail($ticket ['user_id']);
                $user_name = ' ' . ucwords($user ['name']) . ' ' . ucwords($user ['surname']);
            }

            $msg = <<<EOF
			<h3><a href="http://www.tutor81.com">TUTOR81</a> - Risposta alla tua richiesta di assistenza</h3>
			<p>Buongiorno $user_name,<br>
			abbiamo risposto alla tua richiesta di assistenza con codice: <b>{$ticket['code']}</b>. 
			<p>NON RISPONDERE A QUESTA MAIL!</p>
			<p>Per visualizzare la nostra risposta e comunicare con il nostro staff, accedi
			alla tua area utente o al corso che stai seguendo o all'area di assistenza di uno dei nostri siti
			facendo riferimento al codice del ticket.</p>
EOF;
            $msg .= self::FOOTER_TUTOR81;

            $obj = "TUTOR81 - risposta alla richiesta di assistenza {$ticket['code']}";
            return $this->sendMail(array(
                        'a' => array(
                            array(
                                'mail' => $ticket ['user_email'],
                                'name' => $user_name
                            )
                        ),
                        'object' => $obj,
                        'body' => $msg
                    ));
        }
    }
    
    

    /**
     * Notifica prenotazione aula all'organizzatore e conferma a chi ha prenotato
     *
     * @param Integer $user_id
     */
    public function notifyClassroomBooking($id_classroom_booking) {
        require_once BASE_LIBRARY_PATH . 'class_classroom.php';
        require_once BASE_LIBRARY_PATH . 'class_company.php';
        
        $classroom_obj = new T81Classroom();
        $company_obj = new T81Company();
        
        $classroom_booked = $classroom_obj->getClassroomBooked($id_classroom_booking);
        $reserved_by = $this->user_obj->getDetail($classroom_booked['reserved_by_user_id']);
        $classroom_scheduled = $classroom_obj->getClassroomsScheduled(array('id_classroom_scheduled' => $classroom_booked['classroom_scheduled_id']));
        $classroom_scheduled = $classroom_scheduled[0];
        $created_by = $this->user_obj->getDetail($classroom_scheduled['created_by']);
        if ($classroom_booked['company_id']) 
            $company_booking = $company_obj->getBusinessDetail($classroom_booked['company_id']);
        else
            $company_booking = $company_obj->getBusinessDetail ($classroom_booked['tutor_id']);
        
        $course_type_code = strtoupper($classroom_scheduled['course_code']);
        $classroom_start_date = new DateTime($classroom_scheduled['start_date']);
        $classroom_start_date = $classroom_start_date->format('d/m/Y');
        $classroom_start_time = new DateTime($classroom_scheduled['start_time']);
        $classroom_start_time = $classroom_start_time->format('H:i');
        $classroom_end_time = new DateTime($classroom_scheduled['end_time']);
        $classroom_end_time = $classroom_end_time->format('H:i');
        $classroom_location = $classroom_scheduled['location'];
        $classroom_province = ucwords(strtolower($classroom_scheduled['province']));
        $classroom_booked_places = $classroom_booked['booked_places'];
        $classroom_company_creator = strtoupper($classroom_scheduled['business_name']);
        $classroom_company_booking = strtoupper($company_booking['business_name']);
        
        $reserved_by_name = ucwords(strtolower("{$reserved_by['name']} {$reserved_by['surname']}"));
        $created_by_name = ucwords(strtolower("{$created_by['name']} {$created_by['surname']}"));
        
        // messaggio per chi ha prenotato (reserved_by)
        $msg = <<<EOF
            <h3><a href="www.tutoritalia.it">TUTORITALIA</a> - Prenotazione corso in aula</h3>
            <p>Buongiorno $reserved_by_name, ti confermiamo l'avvenuta prenotazione
                del corso in aula:<p>
            <p><i>{$classroom_scheduled['course_description']}<i></p>
            <p>organizzato da <b>$classroom_company_creator</b></p>
                    
                </p>
            <table>
                <thead>
                    <tr>
                        <th>Nome corso</th>
                        <th>Data</th>
                        <th>Ora inizio</th>
                        <th>Ora fine</th>
                        <th>Sede</th>
                        <th>Provincia</th>
                        <th>Posti</th>
                    </tr>
                </thead>
                <tr>
                    <td>$course_type_code</td>
                    <td>$classroom_start_date</td>
                    <td>$classroom_start_time</td>
                    <td>$classroom_end_time</td>
                    <td>$classroom_location</td>
                    <td>$classroom_province</td>
                    <td>$classroom_booked_places</td>
                </tr>
            </table>
            <p>Per maggiori informazioni è possibile rivolgersi a 
                <a href="mailto:{$created_by['email']}" 
                    title="{$created_by['email']}" 
                        alt="$created_by_name:{$created_by['email']}">$created_by_name</a></p>
EOF;
        $msg .= self::FOOTER_ITALIA;

        $obj = "Prenotazione corso in aula";
        $sent_to_booking_agent = $this->sendMail(array(
            'from' => array(
                'mail' => 'noreply@tutoritalia.it',
                'name' => 'TutorItalia - no reply'
            ),
            'a' => array(
                array(
                    'mail' => $reserved_by ['email'],
                    'name' => $reserved_by_name
                )
            ),
            'reply' => array(
                array(
                    'mail' => 'noreply@tutoritalia.it',
                    'name' => 'TutorItalia - no reply'
                )
            ),
            'object' => $obj,
            'body' => $msg
        ));
        
        // messaggio per chi ha creato l'aula (created_by)
        $msg = <<<EOF
            <h3><a href="www.tutoritalia.it">TUTORITALIA</a> - Prenotazione corso in aula</h3>
            <p>Buongiorno $created_by_name, ti informiamo che <b>$reserved_by_name</b> 
                ha effettuato una prenotazione per l'azienda <b>$classroom_company_booking</b>
                    del corso in aula:<br>
                    <i>{$classroom_scheduled['course_description']}<i>
                </p>
            <table>
                <thead>
                    <tr>
                        <th>Nome corso</th>
                        <th>Data</th>
                        <th>Ora inizio</th>
                        <th>Ora fine</th>
                        <th>Sede</th>
                        <th>Provincia</th>
                        <th>Posti</th>
                    </tr>
                </thead>
                <tr>
                    <td>$course_type_code</td>
                    <td>$classroom_start_date</td>
                    <td>$classroom_start_time</td>
                    <td>$classroom_end_time</td>
                    <td>$classroom_location</td>
                    <td>$classroom_province</td>
                    <td>$classroom_booked_places</td>
                </tr>
            </table>
            <p>Per maggiori informazioni è possibile rivolgersi a 
                <a href="mailto:{$reserved_by['email']}" 
                    title="{$reserved_by['email']}" 
                        alt="$reserved_by_name:{$created_by['email']}">$reserved_by_name</a></p>
EOF;
        $msg .= self::FOOTER_ITALIA;

        $obj = "Prenotazione corso in aula";
        $sent_to_classroom_manager = $this->sendMail(array(
            'from' => array(
                'mail' => 'noreply@tutoritalia.it',
                'name' => 'TutorItalia - no reply'
            ),
            'a' => array(
                array(
                    'mail' => $created_by ['email'],
                    'name' => $created_by_name
                )
            ),
            'reply' => array(
                array(
                    'mail' => 'noreply@tutoritalia.it',
                    'name' => 'TutorItalia - no reply'
                )
            ),
            'object' => $obj,
            'body' => $msg
        ));
        
        return $sent_to_booking_agent && $sent_to_classroom_manager;
    }


    /**
     * Notifica una richiesta di assistenza per errato codice fiscale all'amministratore
     * dell'ente formativo
     *
     * @param type $name
     * @param type $surname
     * @param type $company_name
     * @param type $taxcode
     * @param type $email
     * @param type $username
     * @return boolean
     */
    public function notifyTaxcodeProblem($name, $surname, $company_name, $taxcode, $email, $problem, $username) {
        $username = filter_var(trim($username), FILTER_SANITIZE_STRING);
        $user = $this->user_obj->getUserByUsername($username);
        if (!$user) return $this->notifyHelpRequest($name, $surname, $company_name, $taxcode, $email, $problem, $username);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        $name = filter_var(trim($name), FILTER_SANITIZE_STRING);
        $surname = filter_var(trim($surname), FILTER_SANITIZE_STRING);
        $company_name = filter_var(trim($company_name), FILTER_SANITIZE_STRING);
        $taxcode = filter_var(trim($taxcode), FILTER_SANITIZE_STRING);
        $problem = filter_var(trim($problem), FILTER_SANITIZE_STRING);
        $company = $this->company_obj->getBusinessDetail($user['company_id']);
        $tutor = $this->user_obj->getDetail($company['owner_user_id']);
        $tutor_company = $this->company_obj->getBusinessDetail($tutor['company_id']);

        $company_business_name = strtoupper($company['business_name']);
        $tutor_company_business_name = strtoupper($tutor_company['business_name']);
        $user_name = strtoupper("{$user['name']} {$user['surname']}");

        $msg = <<<EOF
            <h3>Ente formativo: $tutor_company_business_name</h3>
            <h3>Azienda: $company_business_name</h3>
            <p>In qualità di acquirente del corso, ti informiamo che 
                <b>$user_name</b> ci ha notificato l’impossibilità di procedere 
                nel corso per problemi derivanti il codice fiscale. E’ importante 
                eseguire una verifica sulla correttezza dei dati inseriti perchè 
                impediscono all’utente di accedere al sistema e di avviare il 
                corso. Verificare la corretezza dei dati e avvisare l’utente 
                affinchè riacceda al sito <b>avviacorso.tutor81.com</b> 
                per riprendere il corso.</p>
            <p>Se il problema persiste contatta l’assitenza tecnica di tutor81, 
                COPIA INCOLLA IL PRESENTE MESSAGGIO e invialo a: 
                <a href="mailto:assistenza@tutor81.it">assistenza@tutor81.it</a> 
                indicando email dove risponderti oppure numero di telefono.</p>
            <h3>Dati inseriti dall'utente</h3>
            <p>
                Nome: $name
                <br>
                Cognome: $surname
                <br>
                Azienda: $company_name
                <br>
                <b>Codice Fiscale: $taxcode</b>
                <br>
                Email: $email
                <br>
                Nome utente: $username
            </p>
EOF;
        $msg .= self::FOOTER;

        $obj = "TUTOR81 - Attenzione $user_name è bloccato";
        return $this->sendMail(array(
            'a' => array(
                array('mail' => $tutor['email'], 'name' => $tutor['name'] . ' ' . $tutor['surname'])
            ),
            'object' => $obj,
            'body' => $msg));
    }

    public function notifyHelpRequest($name, $surname, $company_name, $taxcode, $email, $problem, $username) {
        require_once BASE_LIBRARY_PATH . 'class_company.php';
        $company_obj = new T81Company();
        //$tutor = $company_obj->getCompanyByServerAlias($_SERVER['SERVER_NAME']);

        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        $name = filter_var(trim($name), FILTER_SANITIZE_STRING);
        $surname = filter_var(trim($surname), FILTER_SANITIZE_STRING);
        $company_name = filter_var(trim($company_name), FILTER_SANITIZE_STRING);
        $taxcode = filter_var(trim($taxcode), FILTER_SANITIZE_STRING);
        $problem = filter_var(trim($problem), FILTER_SANITIZE_STRING);
        $username = filter_var(trim($username), FILTER_SANITIZE_STRING);

        $msg = <<<EOF
            <h3>Dati inseriti dall'utente</h3>
            <p>
                Nome: $name
                <br>
                Cognome: $surname
                <br>
                Azienda: $company_name
                <br>
                <b>Codice Fiscale: $taxcode</b>
                <br>
                Email: $email
                <br>
                Nome utente: $username
                <br>
                Problema: $problem
            </p>
EOF;

        $obj = "TUTOR81 - Richiesta di assistenza da Avviacorso";
        return $this->sendMail(array(
            'a' => array(
                array('mail' => 'assistenza@tutor81.it', 'name' => 'Assistenza Tutor81')
            ),
            'from' => array('mail' => $email, 'name' => $name . ' ' . $surname),
            'reply' => array(
                array('mail' => $email, 'name' => $name . ' ' . $surname)
            ),
            'object' => $obj,
            'body' => $msg));
    }
}