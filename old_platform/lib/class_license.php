<?php

/*
 * To change this template, choose Tools | Templates
* and open the template in the editor.
*/
require_once 'class_db.php';
require_once 'sanitize.php';
require_once('tcpdf/tcpdf.php');
require_once('tcpdf/config/lang/ita.php');

require_once 'class_learning_project.php';
require_once 'class_user.php';
require_once 'class_learning_event.php';

class MYLICENSEPDF extends TCPDF {

	//Page header
	public function Header() {
		// Logo
		require_once 'check_session.php';
		$tutor_obj = new T81User();
		$tutor_info = $tutor_obj->getDetail($_SESSION['user']['id']);
		$com_tutor = $tutor_obj->getUserCompany($_SESSION['user']['id']);
		//$image_file = "../img/manpower_att.jpg";
		//$path_parts = pathinfo($image_file);
		//$this->Image($image_file, 10, 10, 20, '', $path_parts['extension'], '', 'T', false, 300, '', false, false, 0, false, false, false);
		$image_file = '../img/logo_normale.png';
		$this->Image($image_file, 165, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
	}

	// Page footer
	public function Footer() {
		// Position at 15 mm from bottom
		$this->SetY(-15);
		// Set font
		$this->SetFont('helvetica', 'I', 8);
		// Page number
		$this->Cell(0, 10, 'Pagina '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
	}
}

class Tutor81License{

	var $db_conn;

	public function Tutor81License(){
		$this->db_conn = new MySQLConn();
	}

	public function generateLicense($license_id){
		$license_id = sanitize($license_id, INT);
		$l = Array();

		// PAGE META DESCRIPTORS --------------------------------------

		$l['a_meta_charset'] = 'UTF-8';
		$l['a_meta_dir'] = 'ltr';
		$l['a_meta_language'] = 'it';

		// TRANSLATIONS --------------------------------------
		$l['w_page'] = 'pagina';



		$pdf = new MYLICENSEPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Tutor81');
		$pdf->SetTitle('Licenza generata');
		$pdf->SetKeywords('');

		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		//set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		//set some language-dependent strings
		$pdf->setLanguageArray($l);

		// ---------------------------------------------------------

		// set default font subsetting mode
		$pdf->setFontSubsetting(true);

		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		$pdf->SetFont('dejavusans', '', 11, '', true);

		// Add a page
		// This method has several options, check the source code documentation for more information.
		$pdf->AddPage();

		$evt = new Tutor81LearningEvt();
		$purchase_detail = $evt->getPurchaseDetailById($license_id);
		$learning_project_id = $purchase_detail['learning_project_id'];
		$user_id = $purchase_detail['user_id'];
		$user = new T81User();
		$user_detail = $user->getDetail($user_id);
		$learn = new T81LearningProject();
		$learn_detail = $learn->getDetail($learning_project_id);
		 
		$course_detail = $learn->getCourseDetailFromLearningProject($learning_project_id);
		 
		$company_name = $user->getUserCompany($purchase_detail['company_id']);
		$tutor_detail = $user->getDetail($_SESSION['user']['id']);

		if($company_name['id'] == 63) {
			$site_link = 'http://manpower.tutor81.com';
		} else {
			$site_link = 'http://player.tutor81.com';
		}

		$pdf->SetFont('dejavusans', '', 10);
		$pdf->SetFont('dejavusans', '', 9);
		 
		//LOGO
		if(file_exists("../../img/company/".$company_name['id'].".jpg")){
			$image_file = "../../img/company/".$company_name['id'].".jpg";
			$path_parts = pathinfo($image_file);
			$pdf->Image($image_file, 10, 10, 20, '', $path_parts['extension'], '', 'T', false, 300, '', false, false, 0, false, false, false);
		}else{
			$image_file = "../img/logo_normale.png";
			$path_parts = pathinfo($image_file);
			$pdf->Image($image_file, 10, 10, 20, '', $path_parts['extension'], '', 'T', false, 300, '', false, false, 0, false, false, false);
		}
		 
		 
		$boldtext = "<table border=\"0\">
				<tr>
				<td colspan=\"2\" align=\"center\"><b>LICENZA CORSO<br/>FORMAZIONE IN ELEARNING</b></td>
				</tr>
				</table>";
		$pdf->writeHTMLCell(0,0,'',20, $boldtext,0,1,0,true,'center',true);
		 
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:left;">
				<tr><td>Trasmettiamo la licenza per accedere al corso di formazione in elearing:
				<br/>
				<br/>
				Datore di lavoro: '.strtoupper($user_detail['business_name']).'<br/><br/>
						Nome: '.strtoupper($user_detail['surname']).' '.ucfirst(strtolower($user_detail['name'])).'<br/>
								Codice fiscale: '.strtoupper($user_detail['tax_code']).'<br/>
										Corso: <b>'.strtoupper($learn_detail['title']).'</b><br/>
												Corso da terminare entro: '.$course_detail['max_execution_time'].' gg<br/>
														Tutor del corso: '.ucfirst(strtolower($tutor_detail['name'])).' '.strtoupper($tutor_detail['surname']).' ('.strtoupper($tutor_detail['business_name']).')<br/><br/><br/>
																Per iniziare il corso collegati a <b>'.$site_link.'</b><br/><br/>
																		<b>CLICCA SU QUESTO PULSANTE</b><br/><a href="'.$site_link.'"><img src="../img/avvia_corso.png" height="100"/></a><br/><br/><br/>
																				Nella pagina Inserisci le tue credenziali
																				</td></tr>
																				</table>';

		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
				<tr><td style="background-color:#cccccc; text-align:center">Nome utente</td><td style="background-color:#cccccc; text-align:center">Licenza corso</td></tr>
				<tr><td style="text-align:center"><b>'.$user_detail['username'].'</b></td><td style="text-align:center"><b>'.$purchase_detail['learning_project_pwd'].'</b></td></tr></table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:left;">
				<tr><td><b>Modalità e avvertenze nell’effettuare il corso:</b><br/>
				Una volta avviato il corso potrai interrompere la lezione in corso in ogni momento; al rientro riprenderai dal punto in cui eri arrivato.
				Tutte risposte che hai dato vengono registrate e non ti saranno riproposte; per uscire dal corso clicca sul pulsante <b>ESCI/STOP</b><br/><br/>
				Nel caso vi siano problemi c’è un assistenza tecnica sempre online, in chat o per email. Se il tuo pc consente l’accesso alla chat clicca sul
				pulsante verde in alto CHAT oppure clicca su ASSISTENZA ONLINE  per visualizzare alcuni consigli e segnalare il tuo problema per email.<br/><br/>
				TEST - ogni test o questionario ha una durata temporizzata di 30 secondi; se lasci trascorrere questo tempo dovrai rientrare nella piattaforma.<br/>
				L’attestato ti sarà spedito dal Tutor (responsabile del corso) che valuterà se sarà necessario ripetere alcune lezioni<br/><br/>
				Ricorda a volte gli oggetti didattici video hanno bisogno di maggior tempo per essere caricati, non fare nulla attendi qualche secondo; se il
				problema persiste segnala all’assistenza tecnica.
				</td></tr>
				</table>';

		$pdf->writeHTML($tablealign, true, 0, true, 0);
		//$pdf->AddPage();
		//}
		// Set some content to print
		/*$html = $_GET['learning_project_user_id']."
		<h1>Welcome to=
		<i>This is the first example of TCPDF library.</i>";
		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $html, $border=0, $ln=1, $fill=0, $reseth=true, $align='', $autopadding=true);*/
		$pdf->Output('licenza_corso_'.$purchase_detail['learning_project_id'].'_'.strtoupper($user_detail['surname'].'_'.$user_detail['name']).'_'.$license_id.'.pdf', 'I');

	}
}
?>
