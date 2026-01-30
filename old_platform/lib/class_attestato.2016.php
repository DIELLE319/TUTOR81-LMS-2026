<?php
require_once 'class_db.php';
require_once 'sanitize.php';
require_once('tcpdf/tcpdf.php');
require_once('tcpdf/config/lang/ita.php');

require_once 'class_learning_project.php';
require_once 'class_user.php';
require_once 'class_learning_event.php';
require_once 'class_learning_question.php';
require_once 'class_company.php';

class MYPDF extends TCPDF
{

    //Page header
    public function Header()
    {

        if ($this->header_xobjid < 0) {
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
            if (($headerdata['logo']) AND ($headerdata['logo'] != K_BLANK_IMAGE)) {
                $imgtype = $this->getImageFileType(K_PATH_IMAGES . $headerdata['logo']);
                if (($imgtype == 'eps') OR ($imgtype == 'ai')) {
                    $this->ImageEps(K_PATH_IMAGES . $headerdata['logo'], '', 5, '', 12.3);
                } elseif ($imgtype == 'svg') {
                    $this->ImageSVG(K_PATH_IMAGES . $headerdata['logo'], '', 5, '', 12.3);
                } else {
                    $this->Image(K_PATH_IMAGES . $headerdata['logo'], '', 5, '', 12.3);
                }
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
        if ($this->booklet AND (($this->page % 2) == 0)) {
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

        $image_file = '../img/logo_Tutor81.png';
        $this->Image($image_file, 152.5, 5, 42.5, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }

    // Page footer
    public function Footer()
    {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Pagina ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }

}

class Tutor81Attestato
{

    // In OOP classes are usually named starting with a cap letter.
    //private $r_conn;
    //private $conn;
    //var $conn; var $rem_conn;

    var $db_conn;

    public function __construct()
    {
        $this->db_conn = new MySQLConn();
    }

    public function generatePDF($license_id)
    {

        $evt = new Tutor81LearningEvt();
        $user = new T81User();
        $tutor_model = new T81LearningProject();
        $comp_obj = new T81Company();

        $license_id = sanitize($license_id, INT);
        $purchase_detail = $evt->getPurchaseDetailById($license_id);
        $learning_project_id = $purchase_detail['learning_project_id'];
        $user_id = $purchase_detail['user_id'];
        $event_detail = $evt->alreadyStarted($license_id);
        $learning_prj_events = $evt->getLearningEventFromPurchase($license_id);
        $learning_prj_questions = $evt->getQuestionFromPurchase($license_id);
        $user_dett = $user->getDetail($user_id);
        $course_detail = $tutor_model->getCourseDetailFromLearningProject($learning_project_id);
        $modules = $tutor_model->getModulesByCourseID($course_detail['id']);
        $module_txt = $tutor_model->getListLessons($learning_project_id);
        $company = $comp_obj->getBusinessDetail($purchase_detail['id_company']);
        $tutor_company = $user->getUserCompany($company['owner_user_id']);
        $ateco = $comp_obj->getCompanyByID($purchase_detail['id_company']);
        $didactic_tutor = $comp_obj->getDidacticTutor($user_dett['company_id']);
        if (file_exists(__DIR__ . "/../media/img/company/" . $tutor_company['id'] . ".png")) {
            $tutor_logo = "../../../media/img/company/" . $tutor_company['id'] . ".png";
        } else {
            $tutor_logo = "../../../img/logo_Tutor81.png";
        }

        $header_string = "TRACCIATO N° {$tutor_company['abrv_company']} {$event_detail['certificate_number']} T81";


        $l = Array();
        $l['a_meta_charset'] = 'UTF-8';
        $l['a_meta_dir'] = 'ltr';
        $l['a_meta_language'] = 'it';
        $l['w_page'] = 'pagina';
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
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

        $boldtext = '<table border="0"><tr><td colspan="2" align="center"><h1 style="margin-bottom: 0;"><b>ATTESTATO</b><br/><small style="font-size: xx-small">di avvenuta formazione in e-learning</small></h1></td></tr></table>';
        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'center', true);

        $sum_duration = 0;
        $x = 1;
        $pdf->Ln(2);
        $pdf->SetFont('dejavusans', '', 9);
        $boldtext = "<div><b>" . $tutor_company['business_name'] . "</b> dichiara  che  presso  la  propria  piattaforma tecnologica  denominata TUTOR81  LMS,  dotata  del  monitoraggio  continuo  del  processo (LMS Learning Management System) il Sig.</div>";
        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'center', true);

        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 10);
        $tablealign = '<table border="0"><tr><td colspan="2" align="center"><h1><b>' .$user_dett["name"] .' ' . $user_dett["surname"] . '</b><br/><small style="font-size: xx-small">codice fiscale: ' . $user_dett["tax_code"] . '</small></h1></td></tr>
                                         <tr><td colspan="2" align="center">in data <b>' . $purchase_detail["starting_from"] . '</b> e <b>' . $purchase_detail["finish_within"] . '</b></td></tr></table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);
        $pdf->Ln(0);
        $pdf->SetFont('dejavusans', '', 8);

        $pdf->Ln(2);
        $pdf->SetFont('dejavusans', '', 9);
        $boldtext = "<div>ha svolto tutto il programma formativo di seguito descritto, rispondendo correttamente ai test in itinere del corso:</div><br/>";
        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'center', true);


        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 10);
        $tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:center; padding: 20px 0;">
                <tr><td colspan="2"><b style="color:#FF0000;">' . $course_detail['title'] . '</b><br/><br/></td></tr>
            </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);
        $pdf->Ln(0);
        $pdf->SetFont('dejavusans', '', 8);

        foreach ($modules as $single) {
            $sum_duration += $single['duration'];
            $x++;
        }

        $tablealign = '<table width="635px" cellpadding="5" cellspacing="0" border="1" style="text-align:left;">
                    <tr>
                        <td width="210px" style="background-color:#cccccc;">Datore di Lavoro</td>
                        <td width="425px">' . $company['business_name'] . '</td>
                    </tr>
                    <tr>
                        <td width="210px" style="background-color:#cccccc;">Codice Ateco</td>
                        <td width="425px">' . $ateco['ateco_sector'] . '</td>
                    </tr>
                    <tr>
                        <td width="210px" style="background-color:#cccccc;">Durata totale corso in e-learning</td>
                        <td width="425px">' . $course_detail['total_duration'] . '</td>
                    </tr>
                     <tr>
                        <td width="210px" style="background-color:#cccccc;">Descrizione del corso</td>
                        <td width="425px">' . strip_tags($course_detail['description']) . '</td>
                    </tr>      
                    <tr>
                        <td width="210px" style="background-color:#cccccc;">Riferimento normativo:</td>
                        <td width="425px">' . $course_detail['law_reference'] . '</td>
                    </tr>
                     <tr>
                        <td width="210px" style="background-color:#cccccc;">Rivolto a:</td>
                        <td width="425px">' . $course_detail['customers'] . '</td>
                    </tr> 
                    <tr>
                        <td width="210px" style="background-color:#cccccc;">Docenti:</td>
                        <td width="425px">' . $course_detail['course_professors'] . '</td>
                    </tr>  
                     <tr>
                        <td width="210px" style="background-color:#cccccc;">Corso Personalizzato:</td>
                        <td width="425px"></td>
                    </tr>     
                    <tr>
                        <td width="210px" style="background-color:#cccccc;">Percentuale test necessaria per validazione corso:</td>
                        <td width="425px">' . $course_detail['percentage_correct_answer_to_pass'] . ' %</td>
                    </tr>
                    </table><br/>';

        $pdf->writeHTML($tablealign, true, 0, true, 0);

        $pdf->SetFont('Courier', '', 9);
        $boldtext = "<div>Il presente attestato rappresenta la sintesi del documento completo emesso dalla piattaforma contenente l’articolazione del programma formativo, 
i singoli accessi del discente agli oggetti multimediali proposti, i test in itinere e le risposte rilasciate. Il tracciato di formazione in e-learning viene conservato in formato elettronico per dieci anni.</div><br/><br/><br/>";
        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'center', true);



        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 10);
        $tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" >
                <tr><td colspan="2" style="background-color: #1bbae1;border:1px solid #FFF; color:#000;text-align:center;">Allegato Attestato - <b>TRACCIATO DI AVVENUTA FORMAZIONE IN ELEARNING<br/> Learning Management System</b></td></tr>
            </table><br/><br/>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);


        $pdf->SetFont('dejavusans', '', 9);
        $boldtext = "<div style='text-align: center;'>Tracciamento continuo e costante del materiale didattico presso il server:______________</div><br/>";
        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'center', true);


        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 9);
        $tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
                    <tr>
                        <td width="635px"><b>DISCENTE</b>
                        <br/>Nome: ' . strtoupper($user_dett["name"] . '<br/>Cognome: ' . $user_dett["surname"]) . '
                        <br/>Codice fiscale: ' . strtoupper($user_dett["tax_code"]) . '
                        <br/>Azienda di appartenenza: ' . $company['business_name'] . '
                        <br/>Organizzatore del corso: '  . $tutor_company['business_name'] .  '
                        </td>
                    </tr>
                </table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);


        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 10);
        $tablealign = '<table border="0"><tr><td colspan="2" align="center" style="color:#0090CF;">' . $course_detail['title'] . '</td></tr></table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);


//        $pdf->SetFont('dejavusans', '', 9);
//        $boldtext = "<div>Materiale didattico: <b>" . $single['text'] . "</b> </div>";
//        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'left', true);
//
//        $pdf->SetFont('dejavusans', '', 9);
//        $boldtext = "<div>Collegamenti: <br/><b>12.2.2016 ore 22:40 interroto ore 22:42 - 13.2.2016 ore 20:11</b> </div>";
//        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'left', true);
//
//        $pdf->SetFont('dejavusans', '', 9);
//        $boldtext = "<div>Test in itiniere: <br/><p><b>Questa e una domanda</b></p><p>Risposta <b>Vero</b> <span>Esito: <b style='color: #0ead87;'>CORRETO</b></span></p> </div>";
//        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'left', true);
//
//        $pdf->SetFont('dejavusans', '', 9);
//        $boldtext = "<div>Materiale didattico: <b>NOME</b> </div>";
//        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'left', true);
//
//        $pdf->SetFont('dejavusans', '', 9);
//        $boldtext = "<div>Collegamenti: <br/><b>12.2.2016 ore 22:40 interroto ore 22:42 - 13.2.2016 ore 20:11</b> </div>";
//        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'left', true);
//
//        $pdf->SetFont('dejavusans', '', 9);
//        $boldtext = "<div>Test in itiniere: <br/><p><b>Questa e una domanda</b></p><p>Risposta <b>Vero</b> <span>Esito: <b style='color: #0ead87;'>CORRETO</b></span></p> </div>";
//        $pdf->writeHTMLCell(0, 0, '', '', $boldtext, 0, 1, 0, true, 'left', true);


        $pdf->SetFont('dejavusans', '', 6);
        $tablealign = '<table width="635px" cellpadding="2" cellspacing="0" border="1" style="text-align:left;">
                    <tr>
                        <td width="150" style="background-color:#cccccc;">Collegamento</td>
                        <td width="335" style="background-color:#cccccc;">Materiale didattico svolto</td>
                        <td width="150" style="background-color:#cccccc;">Termine</td>
                    </tr>';

        foreach ($learning_prj_events as $single) {
            $fuso = 28900;
            $fuso_end = 28900;
            if (strtotime($single['start_date_time']) > strtotime('2013-06-24 14:30:00')) {
                $fuso = 0;
            }
            if (strtotime($single['end_date_time']) > strtotime('2013-06-24 14:30:00')) {
                $fuso_end = 0;
            }
            $tablealign .= '<tr>';
            $tablealign .= '<td width="150">' . date("d-m-Y H:i:s", strtotime($single['start_date_time']) + $fuso) . '</td>';
            $tablealign .= '<td width="335">' . $single['title'] . '</td>';
            $tablealign .= '<td width="150">' . date("d-m-Y H:i:s", strtotime($single['end_date_time']) + $fuso_end) . '</td>';
            $tablealign .= '</tr>';
        }
        $tablealign .= '</table>';
        $pdf->writeHTML($tablealign, true, 0, true, 0);

        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 13);
        $tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:center;">
                    <tr><td colspan="2" style="background-color:#1bbae1;color:#000;"><b>Valutazione dell\'apprendimento</b></td></tr>
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
                $fuso = 0;
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

        $pdf->Output('attestato_licenza_' . $license_id . '.pdf', 'I');
//
//       if (file_exists('../media/attestati/attestato_licenza_' . $license_id . '.pdf'))
//            $pdf->Output('attestato_licenza_' . $license_id . '.pdf', 'I');
//        else
//            $pdf->Output('../media/attestati/attestato_licenza_' . $license_id . '.pdf', 'FI');
//
//        $pdf->Output('attestato_licenza_'.$license_id.'.pdf', 'I');
    }
//
    public function closeTutor81Attestato()
    {
//        //PHP B id=30525
//        //@mysql_close($this->conn);
    }
//
}

?>
