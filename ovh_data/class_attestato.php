<?php
    // 5 minutes execution time
    @set_time_limit(5 * 60);
require_once dirname(__FILE__) . '/../config.php';
require_once 'class_db.php';
require_once 'sanitize.php';
require_once 'tcpdf/tcpdf.php';
require_once 'tcpdf/lang/ita.php';

require_once 'class_learning_project.php';
require_once 'class_user.php';
require_once 'class_learning_event.php';
require_once 'class_learning_question.php';
require_once 'class_company.php';
require_once 'class_course.php';

class MYPDF extends TCPDF {

    protected $tutor81_logo;
    
     function setTutor81Logo($logo) {
         $this->tutor81_logo = $logo;
     }

    //Page header
    public function Header() {

        if (!$this->header_xobjid) { //$this->header_xobjid < 0
            // start a new XObject Template
            $this->header_xobjid = $this->startTemplate($this->w, $this->tMargin);
            $headerfont = $this->getHeaderFont();
            $headerdata = $this->getHeaderData();
            $this->y = $this->header_margin;
            if ($this->rtl) {
                $this->x = $this->w - $this->original_rMargin;
            } else {
                $this->x = $this->original_lMargin;
            }
            if (($headerdata['logo']) AND ( $headerdata['logo'] != K_BLANK_IMAGE)) {
                $this->Image($headerdata['logo'], '', '', '', 10, '', '', 'T', false, 300, '');
                $imgy = $this->getImageRBY();
            } else {
                $imgy = $this->y;
            }
            $cell_height = round(($this->cell_height_ratio * $headerfont[2]) / $this->k, 2);
            // set starting margin for text data cell
            if ($this->getRTL()) {
                $header_x = $this->original_rMargin;
            } else {
                $header_x = $this->original_lMargin;
            }
            //$cw = $this->w - $this->original_lMargin - $this->original_rMargin - ($headerdata['logo_width'] * 1.1);
            $this->SetTextColor(0, 0, 0);
            // header title
            $this->SetFont($headerfont[0], 'B', $headerfont[2] + 1);
            $this->SetX($header_x);
            $this->Cell('', $cell_height, $headerdata['title'], 0, 1, 'C', 0, '', 0);
            // header string
            $this->SetFont($headerfont[0], $headerfont[1], $headerfont[2]);
            $this->SetX($header_x);
            $this->MultiCell(($this->w - $this->original_lMargin - $this->original_rMargin), $cell_height, $headerdata['string'], 0, 'C', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
            // print an ending header line
            $this->SetLineStyle(array('width' => 0.85 / $this->k, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
            $this->SetY((2.835 / $this->k) + max($imgy, $this->y));
            if ($this->rtl) {
                $this->SetX($this->original_rMargin);
            } else {
                $this->SetX($this->original_lMargin);
            }
            $this->Cell(($this->w - $this->original_lMargin - $this->original_rMargin), 0, '', 'T', 0, 'C');
            $this->endTemplate();
        }
        // print header template
        $x = 0;
        $dx = 0;
        if ($this->booklet AND ( ($this->page % 2) == 0)) {
            // adjust margins for booklet mode
            $dx = ($this->original_lMargin - $this->original_rMargin);
        }
        if ($this->rtl) {
            $x = $this->w + $dx;
        } else {
            $x = 0 + $dx;
        }
        $this->printTemplate($this->header_xobjid, $x, 0, 0, 0, '', '', false);
        if ($this->header_xobj_autoreset) {
            // reset header xobject template at each page
            $this->header_xobjid = -1;
        }

        //$image_file = BASE_MEDIA_PATH . "img/company/2.jpg";
        $this->Image($this->tutor81_logo, 140.5, 5, '', 10, '', '', 'T', false, 300, 'R', false, false, 0, false, false, false);
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Pagina ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

}

class Tutor81Attestato {

    var $db_conn;

    public function __construct() {
        $this->db_conn = new MySQLConn();
    }

    public function generatePDF($license_id, $rewrite = FALSE) {

        $evt = new Tutor81LearningEvt();
        $user = new T81User();
        $tutor_model = new T81LearningProject();
        $comp_obj = new T81Company();
        $course_obj = new iWDCourse();

        $license_id = sanitize($license_id, INT);
        $rewrite = filter_var($rewrite, FILTER_VALIDATE_BOOLEAN);
        $purchase_detail = $evt->getPurchaseDetailById($license_id);
        $learning_project_id = $purchase_detail['learning_project_id'];
        $user_id = $purchase_detail['user_id'];
        $event_detail = $evt->alreadyStarted($license_id);
        $learning_prj_events = $evt->getLearningEventFromPurchase($license_id);
        $learning_prj_questions = $evt->getQuestionFromPurchase($license_id);
        $user_dett = $user->getDetail($user_id);
        $born_data = T81User::getBornDataFromTaxCode($user_dett['tax_code']);
        $course_detail = $tutor_model->getCourseDetailFromLearningProject($learning_project_id);
        $modules = $tutor_model->getModulesByCourseID($course_detail['id']);
        $module_txt = $tutor_model->getListLessons($learning_project_id);
        $subcategory = $course_obj->getDetailSubcategory($course_detail['subcategory_id']);
        $is_DL81 = in_array($subcategory['category_id'], array(5,9,10));
        $company = $comp_obj->getBusinessDetail($purchase_detail['id_company']);
        $tutor_company = $user->getUserCompany($company['owner_user_id']);
        if (count($comp_obj->getCompanyByTutorCompany($tutor_company['id'])) == 1) 
            $tutor_company = $comp_obj->getBusinessDetail (1333); // se si tratta di un'azienda "company" il tutor è Prometeo
        //if ($tutor_company['id'] == 583) $tutor_company = $comp_obj->getBusinessDetail (1333); //prende i dati di Prometeo come tutor
        //if (empty($tutor_company['regional_autorization'])) $tutor_company = $comp_obj->getBusinessDetail (1333); //prende i dati di Prometeo come tutor
//        $tutor81_id = $tutor_company['id'] != 2600 ? '2' : '2600';
        $didactic_tutor = $comp_obj->getDidacticTutor($user_dett['company_id']);
        
        
        $learner_name =strtoupper($user_dett['name'] . ' ' . $user_dett['surname']);
        $learner_taxcode = strtoupper($user_dett['tax_code']);
        $course_title = strtoupper($course_detail['title']);
        $learner_business_function = strtoupper($user_dett['business_function']);
        
        $total_elearning = $course_detail['total_elearning'];
        $customers = htmlentities($course_detail['customers']);
        $percentage_correct_answer_to_pass = $course_detail['percentage_correct_answer_to_pass'];
        $law_reference = $course_detail['law_reference'];
        $professors = $course_detail['course_professors'];
        
        $company_name = strtoupper($company['business_name']);
        $tutor_name = strtoupper($tutor_company['business_name']);
        $tutor_address = $tutor_company['address'];
        $tutor_city = $tutor_company['city'];
                
        $tutor_vendor = $comp_obj->getCompanyByID($tutor_company['id']);
        if (!empty($tutor_company['regional_authorization'])) {
            $ente = $tutor_vendor;
            foreach ($tutor_vendor as $key => $value) $tutor_vendor[$key] = '';
        } else {
            $ente = $comp_obj->getCompanyByID(1333); //Prometeo
        }
        
        if ($company['trainer'] != '') {
            $trainer = nl2br($company['trainer']);
        } elseif ($tutor_company['trainer'] != '') {
            $trainer = nl2br($tutor_company['trainer']);
        } else {
            $trainer = strtoupper($ente['business_name']);
        }
        
        //$tutor_vendor = $user->getDetail($purchase_detail['company_id']);
        $company_logo = BASE_MEDIA_PATH . "img/company/" . $company['id'] . ".png";
        $tutor_logo = BASE_MEDIA_PATH . "img/company/2.png";
        $ente_logo = BASE_MEDIA_PATH . "img/company/" . $ente['id'] . ".png";
        if ($tutor_company['id'] == 2600) {
            $tutor81_logo = BASE_MEDIA_PATH . "img/company/logo_sicilia.png";
        } elseif ($tutor_company['id'] == 2978) {
            $tutor81_logo = BASE_MEDIA_PATH . "img/company/2978.png";
        }
        $header_string = "TRACCIATO N° {$tutor_company['abrv_company']} {$event_detail['certificate_number']} T81";

        $l = Array();
        $l['a_meta_charset'] = 'UTF-8';
        $l['a_meta_dir'] = 'ltr';
        $l['a_meta_language'] = 'it';
        $l['w_page'] = 'pagina';
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setTutor81Logo($tutor81_logo);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Tutor81');
        $pdf->SetTitle('Attestato di avvenuta formazione in e-learning');
        $pdf->SetSubject('conforme Allegato 1 Accordi Stato-Regioni 21 dicembre 2011');
        $pdf->SetKeywords('');
        $pdf->SetHeaderData($tutor_logo, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, $header_string);
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 8));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->setLanguageArray($l);
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('dejavusans', '', 11, '', true);

        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 10);

        $boldtext = "<table border=\"0\">
                <tr>
                    <td colspan=\"2\" align=\"center\"><b>TRACCIATO N° {$tutor_company['abrv_company']} {$event_detail['certificate_number']} T81
                        <br/>DI AVVENUTA FORMAZIONE IN ELEARNING</b></td>
                </tr>
            </table>";
        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'center', true);

        $sum_duration = 0;
        $x = 1;
        $pdf->Ln(2);
        $pdf->SetFont('dejavusans', '', 9);
        $boldtext = "<div>L’infrastruttura tecnologica TUTOR81 LMS";
        $boldtext .= $is_DL81 ? " in conformità all’Accordo tra Stato e Regioni del 7 luglio 2016" : ""; // se è un corso sicurezza aggiunge la dicitura sull'accordo Stato Regione
        $boldtext .= " certifica il completamento del corso in e-learning da parte di:</div>";
        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'center', true);


        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 10);
        $tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
                    <tr>
                        <td width="210px">Nominativo</td>
                        <td width="425px">' . $learner_name . '</td>
                    </tr>
                    <tr>
                        <td width="210px">Codice fiscale</td>
                        <td width="425px">' . $learner_taxcode . '</td>
                    </tr>        
                    <tr>
                        <td>In qualità di</td>
                        <td>' . $learner_business_function . '</td>
                    </tr>
                    <tr>
                        <td>Organizzatore del corso</td>
                        <td>' . $company_name . '</td>
                    </tr>
                    <tr>
                        <td>Soggetto formatore autorizzato</td>
                        <td>' . $trainer . '</td>
                    </tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);

        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 13);
        $tablealign = '<table width="635px" cellpadding="" cellspacing="0" border="0" style="text-align:center;">
                <tr>
                    <td colspan="2" style="border:0px solid #FFF;">
                        <b>Scheda progettuale del corso in e-learning</b>
                    </td>
                </tr>
            </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);
        $pdf->Ln(0);
        $pdf->SetFont('dejavusans', '', 10);


        foreach ($modules as $single) {
            $sum_duration += $single['duration'];
            $x++;
        }

        $tablealign = '<table width="635px" cellpadding="5" cellspacing="0" border="0">
                    <tr>
                        <td width="210px" style="text-align: right;"><b>Titolo del corso:</b></td>
                        <td width="425px">' . $course_detail['title'] . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Rivolto a:</b></td>
                        <td>' . $course_detail['customers'] . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Riferimento normativo:</b></td>
                        <td>' . $course_detail['law_reference'] . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Validità corso:</b></td>
                        <td>' . $course_detail['course_validity'] . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Descrizione del corso:</b></td>
                        <td width="425px">' . strip_tags($course_detail['description']) . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Durata del corso in e-learning:</b></td>
                        <td>' . $course_detail['total_elearning'] . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Tempo massimo per la conclusione:</b></td>
                        <td>' . $course_detail['max_execution_time'] . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Relatori e Docenti:</b></td>
                        <td>' . $course_detail['course_professors'] . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Requisiti minimi per accedere al corso:</b></td>
                        <td>' . $course_detail['requirements'] . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Verifica di apprendimento:</b></td>
                        <td>' . $course_detail['checking'] . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Soglia minima per il superamento del corso:</b></td>
                        <td>' . $course_detail['percentage_correct_answer_to_pass'] . ' %</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Caratteristiche tecniche della piattaforma:</b></td>
                        <td>' . $course_detail['didactics'] . '</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><b>Programma del corso:</b></td>
                        <td>' . $course_detail['description'] . '</td>
                    </tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);
/*
        $pdf->AddPage();
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 13);
        $tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:center;">
                    <tr><td colspan="2" style=" border:0px solid #FFF;"><b>ENTE FORMATIVO</b></td></tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);
        $pdf->SetFont('dejavusans', '', 9);

        $tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
                    <tr>
                        <td width="210px">Ragione sociale</td>
                        <td width="425px">' . $tutor_company['business_name'] . '</td>
                    </tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);

        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 13);
        $tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:center;">
                    <tr><td colspan="2" style="border:1px solid #FFF;"><b>TUTOR DIDATTICO</b></td></tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);
        $pdf->SetFont('dejavusans', '', 9);

        if (isset($didactic_tutor['user_id']) && $didactic_tutor['user_id'] != 6) {
            $didactic_tutor_name = ucwords("{$didactic_tutor['name']} {$didactic_tutor['surname']}");
        } else {
            $didactic_tutor_name = "Luca Pedretti";
        }
        $tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
                    <tr>
                        <td width="210px">Nome Cognome</td>
                        <td width="425px">' . $didactic_tutor_name . '</td>
                    </tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);


        $pdf->SetFont('dejavusans', '', 9);
        $boldtext = "<div>Il tutor didattico è il soggetto a disposizione per la gestione del percorso formativo. Il Tutor dichiara di essere in possesso di esperienza almeno triennale di docenza o insegnamento o professionale in materia di tutela della salute e sicurezza sul lavoro maturata nei settori pubblici o privati.</div>";
        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'center', true);

        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 13);
        $tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:center;">
                    <tr><td colspan="2" style="border:1px solid #FFF;"><b>Nominativo discente</b></td></tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);
        $pdf->SetFont('dejavusans', '', 8);
        $tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
                    <tr>
                        <td width="210px">Nome Cognome</td>
                        <td width="425px">' . strtoupper($user_dett['name'] . ' ' . $user_dett['surname']) . '</td>
                    </tr>
                    <tr>
                        <td>Codice fiscale</td>
                        <td>' . strtoupper($user_dett['tax_code']) . '</td>
                    </tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);

*/
        $pdf->AddPage();
        //$pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 13);
        $tablealign = '<table width="635px" cellpadding="0" cellspacing="0" border="0" style="text-align:center;">
                    <tr><td colspan="2" style="border:0px solid #FFF;"><b>Tracciamento del percorso formativo</b></td></tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);
        $pdf->SetFont('dejavusans', '', 6);
        $tablealign = '<table width="635px" cellpadding="2" cellspacing="0" border="1" style="text-align:left;">
                    <tr>
                        <td width="150" style="background-color:#cccccc;">Collegamento</td>
                        <td width="335" style="background-color:#cccccc;">Materiale didattico svolto</td>
                        <td width="150" style="background-color:#cccccc;">Termine</td>
                    </tr>';

        $lp_num = 1;
        $start_course_date = '';
        $end_course_date = '';
        foreach ($learning_prj_events as $single) {
            $fuso = 28900;
            $fuso_end = 28900;
            if (strtotime($single['start_date_time']) > strtotime('2013-06-24 14:30:00')) {
                if (strtotime($single['start_date_time']) > strtotime('2025-01-15 18:00:00')) { // data inserimento default timezone
                    $fuso = 0;
                } else {
                    $fuso = 3600;
                }
            }
            if (strtotime($single['end_date_time']) > strtotime('2013-06-24 14:30:00')) {
                $fuso_end = 0;
            }
            $tablealign .= '<tr>';
            $tablealign .= '<td width="150">' . date("d-m-Y H:i:s", strtotime($single['start_date_time']) + $fuso) . '</td>';
            $tablealign .= '<td width="335">' . $single['title'] . '</td>';
            $tablealign .= '<td width="150">' . date("d-m-Y H:i:s", strtotime($single['end_date_time']) + $fuso_end) . '</td>';
            $tablealign .= '</tr>';
            
            if ($lp_num == 1) $start_course_date = date("d/m/Y", strtotime($single['start_date_time']) + $fuso);
            if ($lp_num == count($learning_prj_events)) $end_course_date = date("d/m/Y", strtotime($single['end_date_time']) + $fuso_end);
            $lp_num++;
        }
        $tablealign .= '</table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);


        $pdf->AddPage();
        //$pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 13);
        $tablealign = '<table width="635px" cellpadding="0" cellspacing="0" border="0" style="text-align:center;">
                    <tr><td colspan="2" style="border:0px solid #FFF;"><b>Valutazione dell\'apprendimento</b></td></tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);
        $pdf->SetFont('dejavusans', '', 6);
        $tablealign = '<table width="635px" cellpadding="2" cellspacing="0" border="1" style="text-align:left;">
                    <tr>
                        <td width="150" style="background-color:#cccccc;">Data/ora</td>
                        <td width="235" style="background-color:#cccccc;">Quesito</td>
                        <td width="150" style="background-color:#cccccc;">Risposta corsista</td>
                        <td width="100" style="background-color:#cccccc;">Valutazione</td>
                    </tr>';

        $question = new Tutor81QuestionObj();
        foreach ($learning_prj_questions as $single) {

            $answer = $question->getAnswerFromQuestion($single['id']);
            $fuso = 28900;
            if (strtotime($single['generation_time']) > strtotime('2013-06-24 14:30:00')) {
                if (strtotime($single['generation_time']) > strtotime('2025-01-15 18:00:00')) { // data inserimento default timezone
                    $fuso = 0;
                } else {
                    $fuso = 3600;
                }
            }
            if ($answer != NULL) {
                $tablealign .= '<tr>';
                $tablealign .= '<td width="150">' . date("d-m-Y H:i:s", strtotime($single['generation_time']) + $fuso) . '</td>';
                $tablealign .= '<td width="235">' . $single['text'] . '</td>';
                $tablealign .= '<td width="150">' . $answer[0]['text'] . '</td>';
                if ($answer[0]['is_correct'] == "1") {
                    $tablealign .= '<td width="100"><b style="color:green">CORRETTA</b></td>';
                } else {
                    $tablealign .= '<td width="100"><b style="color:red">ERRATA</b></td>';
                }
            } else {
                $tablealign .= '<tr>';
                $tablealign .= '<td width="150">' . date("d-m-Y H:i:s", strtotime($single['generation_time']) + $fuso) . '</td>';
                $tablealign .= '<td width="235">' . $single['text'] . '</td>';
                $tablealign .= '<td width="150">&nbsp;</td>';
                $tablealign .= '<td width="100"><b style="color:red">SENZA RISPOSTA</b></td>';
            }



            $tablealign .= '</tr>';
        }
        $tablealign .= '</table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);

        if (isset($event_detail['end_date_time']) && $event_detail['end_date_time'] != '0000-00-00 00:00:00')
            $print_date = new DateTime($event_detail['end_date_time'], new DateTimeZone('Europe/Rome'));
        else
            $print_date = new DateTime('now', new DateTimeZone('Europe/Rome'));
        
        $start_date = new DateTime($event_detail['start_date_time'], new DateTimeZone('Europe/Rome'));

        $pdf->SetFont('dejavusans', '', 9);
        $boldtext = "<div>Data: <b>" . $print_date->format("d-m-Y") . "</b></div>";
        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'center', true);
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 9);
        $tablealign = '<table width="635px" cellpadding="0" cellspacing="0" border="1" style="text-align:center;">
                    <tr>
                        <!-- <td width="317px" style="background-color:#cccccc;">Firma del tutor</td> -->
                        <td width="317px" style="background-color:#cccccc;">Firma del corsista</td>
                    </tr>
                    <tr>
                        <!-- <td width="317px"><br/><br/><br/><br/></td> -->
                        <td width="317px"><br/><br/><br/><br/></td>
                    </tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);
        
        
        /** add white page **/
        
        $pdf->AddPage();
        
        $pdf->SetFont('dejavusans', '', 11);
        $tablealign = '<table width="635px" cellpadding="0" cellspacing="0" border="0" style="text-align:center;">
                    <tr><td  colspan="2" style="border:0px solid #FFF;" height="300px"></td></tr>
                    <tr><td colspan="2" style="border:0px solid #FFF;"><b>Questa pagina &egrave; stata lasciata intenzionalmente vuota</b></td></tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);
        
        /** add pergamena **/
        
        $end_date = $print_date->format("d-m-Y");
        
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetMargins(8, 8);
        $pdf->SetAutoPageBreak(TRUE, 0);
        $fontname = TCPDF_FONTS::addTTFfont(BASE_LIBRARY_PATH . 'Avenir-Roman-webfont.ttf', '','', 14);
        $pdf->SetFont($fontname, '', 11);
        //$pdf->SetFont('dejavusans', '', 10);
        $pdf->AddPage('L', 'A4');

        
$vers = file_exists(BASE_LIBRARY_PATH . 'attestato.' . $tutor_company['id'] . '.php') ? $tutor_company['id'] : 'base';
ob_start(); 
include BASE_LIBRARY_PATH . 'attestato.' . $vers . '.php';
$html = ob_get_contents();
ob_end_clean();

        $pdf->writeHTML($html, true, false, true, false, '');
        
        if ($vers === 'base') {
            if (file_exists($tutor81_logo)) {
                if ($tutor_company['id'] == 2600) {
                    $pdf->Image($tutor81_logo, 18, 15, '', 18, '', '', 'T', true, 300, '', false, false, 0, false, false, false);
                }else {
                    $pdf->Image($tutor81_logo, 18, 15, '', 10, '', '', 'T', true, 300, '', false, false, 0, false, false, false);
                }
            }
            if (file_exists($tutor_logo)) {
                $pdf->SetXY(227,13);
                $pdf->Image($tutor_logo, '', '', 50, '', '', '', '', true, 300, '', false, false, 0, false, false, false);
            }
            if (file_exists($ente_logo)) {
                $pdf->SetXY(227,150);
                $pdf->Image($ente_logo, '', '', 50, '', '', '', 'B', true, 300, '', false, false, 0, false, false, false);
        
            }
            $firma_ente_logo = BASE_MEDIA_PATH . "img/company/firma." . $ente['id'] . ".png";
            if(file_exists($firma_ente_logo)) {
                $ente_Y = 20;
                $pdf->SetXY(80,132);
                $pdf->Image($firma_ente_logo, '', '', 50, '', '', '', 'B', true, 300, '', false, false, 0, false, false, false);
            }
            if ($ente['id'] != $tutor_company['id']) {
                $firma_logo = BASE_MEDIA_PATH . "img/company/firma." . $tutor_company['id'] . ".png";
                if (file_exists($firma_logo)) {
                    $pdf->SetXY(180,132);
                    $pdf->Image($firma_logo, '', '', 50, '', '', '', 'B', true, 300, '', false, false, 0, false, false, false);
                }
            }
        }
        elseif ($vers === '1333') { //se Prometeo
            if (file_exists($tutor81_logo)) {
                $pdf->Image($tutor81_logo, 18, 15, '', 10, '', '', 'T', true, 300, '', false, false, 0, false, false, false);
            }
            if (in_array($company["id"], array(418,583,693,1468))){ // se aziende Miroglio
                $miroglio_logo = BASE_MEDIA_PATH . "img/company/attestato.miroglio.jpg";
                if (file_exists($miroglio_logo)) {
                    $pdf->SetXY(227,13);
                    $pdf->Image($miroglio_logo, '', '', 50, '', '', '', '', true, 300, '', false, false, 0, false, false, false);
                }
                if (file_exists($tutor_logo)) {
                    $pdf->SetXY(227,150);
                    $pdf->Image($tutor_logo, '', '', 50, '', '', '', '', true, 300, '', false, false, 0, false, false, false);
                }
            } elseif (file_exists($tutor_logo)) {
                $pdf->SetXY(227,13);
                $pdf->Image($tutor_logo, '', '', 50, '', '', '', '', true, 300, '', false, false, 0, false, false, false);
            }
            $firma_logo = BASE_MEDIA_PATH . "img/company/firma." . $vers . ".png";
            if (file_exists($firma_logo)) {
                $pdf->SetXY(180,132);
                $pdf->Image($firma_logo, '', '', 50, '', '', '', 'B', true, 300, '', false, false, 0, false, false, false);
            }
        } 
        elseif ($vers === '2978') { //se Fondazione Libellula
            if (file_exists($tutor81_logo)) {
                $pdf->Image($tutor81_logo, 18, 15, '', 15, '', '', 'T', true, 300, '', false, false, 0, false, false, false);
                $pdf->SetXY(227,13);
//                $pdf->Image($tutor_logo, '', '', 50, '', '', '', '', true, 300, '', false, false, 0, false, false, false);
            }
            $firma_logo = BASE_MEDIA_PATH . "img/company/firma." . $vers . ".png";
            if (file_exists($firma_logo)) {
                $pdf->SetXY(180,132);
                $pdf->Image($firma_logo, '', '', 50, '', '', '', 'B', true, 300, '', false, false, 0, false, false, false);
            }
        }
        /** Output **/
        //$pdf->Output('attestato_licenza_' . $license_id . '.pdf', $output);

        if (file_exists(BASE_MEDIA_PATH . 'attestati/attestato_licenza_' . $license_id . '.pdf') && !$rewrite) {
            $pdf->Output('attestato_licenza_' . $license_id . '.pdf', 'I');
            return true;
        } else {
            $pdf->Output(BASE_MEDIA_PATH . 'attestati/attestato_licenza_' . $license_id . '.pdf', $rewrite ? 'F' : 'FI');
            if (file_exists(BASE_MEDIA_PATH . 'attestati/attestato_licenza_' . $license_id . '.pdf')) return true;
            else return false;
        }
        //$pdf->Output('attestato_licenza_'.$license_id.'.pdf', 'I');

    }

    public function closeTutor81Attestato() {
        //PHP B id=30525
        //@mysql_close($this->conn);
    }

}