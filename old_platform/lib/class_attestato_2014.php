<?php
require_once 'check_session.php';
require_once 'class_db.php';
require_once 'sanitize.php';
require_once('tcpdf/tcpdf.php');
require_once('tcpdf/config/lang/ita.php');

require_once 'class_learning_project.php';
require_once 'class_user.php';
require_once 'class_learning_event.php';
require_once 'class_learning_question.php';


class MYPDFATTESTATO extends TCPDF {

	//Page header
	public function Header() {
		// Logo
		$tutor_obj = new T81User();
		$tutor_info = $tutor_obj->getDetail($_SESSION['user']['id']);
		$com_tutor = $tutor_obj->getUserCompany($_SESSION['user']['id']);
		/*
		 $image_file = '../img/logo_bicolore.png';
		$this->Image($image_file, 165, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
		*/
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


class Tutor81Attestato2014{

	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}

	public function generatePDF($license_id){
		$license_id = sanitize($license_id, INT);
		$l = Array();
		$l['a_meta_charset'] = 'UTF-8';
		$l['a_meta_dir'] = 'ltr';
		$l['a_meta_language'] = 'it';
		$l['w_page'] = 'pagina';
		$pdf = new MYPDFATTESTATO(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Tutor81');
		$pdf->SetTitle('Allegato 1 - Tracciato del percorso formativo');
		$pdf->SetSubject('conforme Allegato 1 Accordi Stato-Regioni 21 dicembre 2011 e successivi aggiornamenti');
		$pdf->SetKeywords('');
		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		$pdf->setLanguageArray($l);
		$pdf->setFontSubsetting(true);

		$evt = new Tutor81LearningEvt();
		$purchase_detail = $evt->getPurchaseDetailById($license_id);
		$learning_project_id = $purchase_detail['learning_project_id'];
		$user_id = $purchase_detail['user_id'];
		$event_detail = $evt->alreadyStarted($license_id);
		$learning_prj_events = $evt->getLearningEventFromPurchase($license_id);
		$learning_prj_questions = $evt->getQuestionFromPurchase($license_id);
		$user = new T81User();
		$learning_project_tutor = $user->getDetail($user_id);
		$tutor_model = new T81LearningProject();
		$course_detail = $tutor_model->getCourseDetailFromLearningProject($learning_project_id);
		$modules =  $tutor_model->getModulesByCourseID($course_detail['id']);
		$module_txt = $tutor_model->getListLessons($learning_project_id);

		$company_name = $user->getUserCompany($purchase_detail['company_id']);

		$pdf->SetFont('dejavusans', '', 11, '', true);
		$pdf->AddPage();
		$pdf->SetFont('dejavusans', '', 10);

		//LOGO
		/*if(file_exists("../../img/company/".$company_name['id'].".jpg")){
		$image_file = "../../img/company/".$company_name['id'].".jpg";
		$tag_logo_tutor = '<img src="'.$image_file.'" height="36px" />';
		}else{*/
		$tag_logo_tutor = ' ';
		/*$image_file = "../img/logo_bicolore.png";
		 }
		$path_parts = pathinfo($image_file);
		$pdf->Image($image_file, 10, 10, 20, '', $path_parts['extension'], '', 'T', false, 300, '', false, false, 0, false, false, false);
		$pdf->Image('../img/logo_bicolore.png', 165, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
		*/

		/* ---- INTESTAZIONE ALLEGATO 1---- */
		$boldtext = "<table border=\"0\">
				<tr>
				<td colspan=\"2\" align=\"center\"><b>ALLEGATO 1<br/>Tracciato del percorso formativo</b></td>
				</tr>
				</table>";
		$pdf->writeHTMLCell(0,0,'',20, $boldtext,0,1,0,true,'center',true);

			
		/* ---- TUTOR ---- */
		$pdf->Ln(5);
		$pdf->SetFont('dejavusans', '', 10);
		$tablealign = '<table width="635px" cellpadding="10" border="1" style="text-align:center;">
				<tr>
				<td style="background-color:#23abdd; border:1px solid #23abdd; color:#FFFFFF;"><b>PIATTAFORMA TECNOLOGICA</b></td>
				<td style="background-color:#23abdd; border:1px solid #23abdd; color:#FFFFFF;"><b>ENTE FORMATORE</b></td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$tablealign = '<table width="635px" cellpadding="10" border="1" style="text-align:center;">
				<tr>
				<td style="border:1px solid #000; border-bottom: none; color:#000;"><b>TUTOR81 vers. 5.0</b></td>
				<td style="border:1px solid #000; border-bottom: none; color:#000;"><b>'.$company_name['business_name'].'</b></td>
						</tr>
						<tr>
						<td style="border:1px solid #000; border-top: none; color:#000;"><img src="../img/logo_normale.png" height="36px" /></td>
						<td style="border:1px solid #000; border-top: none; color:#000;">'.$tag_logo_tutor.'</td>
								</tr>
								</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);
			

		/* ---- DATI UTENTE ---- */
		$pdf->Ln(5);
		$pdf->SetFont('dejavusans', '', 13);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:left;">
				<tr><td colspan="2" style="background-color:#23abdd; border:1px solid #FFF; color:#FFFFFF;"><b>Nominativo discente</b></td></tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);
		$pdf->SetFont('dejavusans', '', 8);
		$user_dett = $user->getDetail($user_id);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
				<tr>
				<td width="210px" style="background-color:#cccccc;">Nome Cognome</td>
				<td width="425px">'.strtoupper($user_dett['name'].' '.$user_dett['surname']).'</td>
						</tr>
						<tr>
						<td style="background-color:#cccccc;">Codice fiscale</td>
						<td>'.strtoupper($user_dett['tax_code']).'</td>
								</tr>
								</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);


		/* ---- DESCRIZIONE CORSO ---- */
		$pdf->Ln(5);
		$pdf->SetFont('dejavusans', '', 10);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:left;">
				<tr>
				<td colspan="2" style="background-color:#23abdd; border:1px solid #FFF; color:#FFFFFF;">
				<b>DETTAGLI CORSO</b>
				</td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);
		$pdf->Ln(0);
		$pdf->SetFont('dejavusans', '', 8);

		$sum_duration = 0;
		$x = 1;
		foreach($modules as $single){
			$sum_duration += $single['duration'];
			$x++;
		}

		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
				<tr>
				<td width="210px" style="background-color:#cccccc;">Nome</td>
				<td width="425px">'.$course_detail['title'].'</td>
						</tr>
						<tr>
						<td width="210px" style="background-color:#cccccc;">Descrizione del corso</td>
						<td width="425px">'.strip_tags($course_detail['description']).'</td>
								</tr>
								<tr>
								<td style="background-color:#cccccc;">Riferimento normativo:</td>
								<td>'.$course_detail['law_reference'].'</td>
										</tr>
										<tr>
										<td style="background-color:#cccccc;">Didattica:</td>
										<td>'.$course_detail['didactics'].'</td>
												</tr>
												<tr>
												<td style="background-color:#cccccc;">Integrazioni in aula:</td>
												<td>'.$course_detail['external_integration'].'</td>
														</tr>
														<tr>
														<td style="background-color:#cccccc;">Durata totale:</td>
														<td>'.$course_detail['total_duration'].'</td>
																</tr>
																<tr>
																<td style="background-color:#cccccc;">Durata totale e-learning:</td>
																<td>'.$course_detail['total_elearning'].'</td>
																		</tr>
																		<tr>
																		<td style="background-color:#cccccc;">Percentuale test necessaria per validazione corso:</td>
																		<td>'.$course_detail['percentage_correct_answer_to_pass'].' %</td>
																				</tr>
																				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		 
		/* ---- SESSIONE FORMATIVA ---- */
		$pdf->AddPage();
		$pdf->SetFont('dejavusans', '', 13);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:left;">
				<tr><td colspan="2" style="background-color:#23abdd; border:1px solid #FFF; color:#FFFFFF;"><b>RIEPILOGO DELLA SESSIONE FORMATIVA</b></td></tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);
		$pdf->SetFont('dejavusans', '', 6);
		$tablealign = '<table width="635px" cellpadding="2" cellspacing="0" border="1" style="text-align:left;">
				<tr>
				<td width="150" style="background-color:#cccccc;">Collegamento</td>
				<td width="335" style="background-color:#cccccc;">Materiale didattico svolto</td>
				<td width="150" style="background-color:#cccccc;">Termine</td>
				</tr>';

		foreach($learning_prj_events as $single){
			$fuso = 28900;
			$fuso_end = 28900;
			if (strtotime($single['start_date_time'])>strtotime('2013-06-24 14:30:00')) {
				$fuso = 0;
			}
			if (strtotime($single['end_date_time'])>strtotime('2013-06-24 14:30:00')) {
				$fuso_end = 0;
			}
			$tablealign .= '<tr>';
			$tablealign .= '<td width="150">'.date("d-m-Y H:i:s",  strtotime($single['start_date_time'])+ $fuso).'</td>';
			$tablealign .= '<td width="335">'.$single['title'].'</td>';
			$tablealign .= '<td width="150">'.date("d-m-Y H:i:s",  strtotime($single['end_date_time'])+ $fuso_end).'</td>';
			$tablealign .= '</tr>';
		}
		$tablealign .= '</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);



		$pdf->Ln(5);
		$pdf->SetFont('dejavusans', '', 13);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:left;">
				<tr><td colspan="2" style="background-color:#23abdd; border:1px solid #FFF; color:#FFFFFF;"><b>Valutazione dell\'apprendimento</b></td></tr>
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
		foreach($learning_prj_questions as $single){

			$answer = $question->getAnswerFromQuestion($single['id']);
			$fuso = 28900;
			if (strtotime($single['generation_time'])>strtotime('2013-06-24 14:30:00')) {
				$fuso = 0;
			}
			if($answer != NULL){
				$tablealign .= '<tr>';
				$tablealign .= '<td width="150">'.date("d-m-Y H:i:s",  strtotime($single['generation_time'])+$fuso).'</td>';
				$tablealign .= '<td width="235">'.$single['text'].'</td>';
				$tablealign .= '<td width="150">'.$answer[0]['text'].'</td>';
				if ($answer[0]['is_correct'] == "1"){
					$tablealign .= '<td width="100"><b style="color:green">CORRETTA</b></td>';
				}else{
					$tablealign .= '<td width="100"><b style="color:red">ERRATA</b></td>';
				}
			}else{
				$tablealign .= '<tr>';
				$tablealign .= '<td width="150">'.date("d-m-Y H:i:s",  strtotime($single['generation_time'])+$fuso).'</td>';
				$tablealign .= '<td width="235">'.$single['text'].'</td>';
				$tablealign .= '<td width="150">&nbsp;</td>';
				$tablealign .= '<td width="100"><b style="color:red">SENZA RISPOSTA</b></td>';
			}



			$tablealign .= '</tr>';
		}
		$tablealign .= '</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);


		/* ---- FOOTER ALLEGATO 1 ---- */
		$pdf->Ln(5);
		$pdf->SetFont('dejavusans', '', 9);
		$boldtext = "<div><p><b>TUTOR81</b><br>
				La piattaforma Tutor81 dichiara che tutte le lezioni sono state visionate in modo sequenziale
				e senza problemi tecnici; il progetto formativo previsto si è concluso correttamente.</p></div>";
		$pdf->writeHTMLCell(0,0,'','', $boldtext,0,1,0,true,'center',true);

		$pdf->Ln(5);
		$boldtext = "<div>Data di stampa: <b>".date("d-m-Y")."</b></div>";
		$pdf->writeHTMLCell(0,0,'','', $boldtext,0,1,0,true,'center',true);

		$pdf->SetFont('dejavusans', '', 9);
		$tablealign = '<table width="300px" cellpadding="10" cellspacing="0" border="1" style="text-align:center;">
				<tr>
				<td style="background-color:#cccccc;">Firma del tutor</td>
				</tr>
				<tr>
				<td><br/><br/><br/><br/></td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		/* ---- INTESTAZIONE ALLEGATO 2---- */
		$pdf->AddPage();
		$pdf->SetFont('dejavusans', '', 13);
		$boldtext = "<table border=\"0\">
				<tr>
				<td colspan=\"2\" align=\"center\"><b>ALLEGATO 2<br/>ART.36 DLVO 81/08</b></td>
				</tr>
				</table>";
		$pdf->writeHTMLCell(0,0,'',20, $boldtext,0,1,0,true,'center',true);


		/* ---- DICHIARAZIONI ALLEGATO 2---- */
		$pdf->Ln(5);
		$boldtext = "<div><p><b>In base all’Art. 36 DLVO 81/08 Il datore di lavoro provvede affinche' ciascun
				lavoratore riceva una adeguata informazione:<b><p></div>";
		$pdf->writeHTMLCell(0,0,'','', $boldtext,0,1,0,true,'center',true);


		$pdf->SetFont('dejavusans', '', 9);
		$boldtext = "<div> - sui rischi per la salute e sicurezza sul lavoro connessi alla attivita' della
				impresa in generale</div>";
		$pdf->writeHTMLCell(0,0,'','', $boldtext,0,1,0,true,'center',true);

		$pdf->SetFont('dejavusans', '', 13);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:left;">
				<tr>
				<td colspan="2" style="background-color:#23abdd; border:1px solid #FFF; color:#FFFFFF;">
				<b>INFORMATIVA SUI RISCHI DERIVANTI DALL’ATTIVITA’ LAVORATIVA</b>
				</td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$pdf->SetFont('dejavusans', '', 9);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
				<tr>
				<td width="317px"></td>
				<td width="317px"></td>
				</tr>
				<tr>
				<td width="317px"></td>
				<td width="317px"></td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$pdf->Ln(5);
		$pdf->SetFont('dejavusans', '', 9);
		$boldtext = "<div> - sui nominativi del responsabile e degli addetti del servizio di prevenzione e
				protezione, e del medico competente.</div>";
		$pdf->writeHTMLCell(0,0,'','', $boldtext,0,1,0,true,'center',true);

		$pdf->SetFont('dejavusans', '', 13);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:left;">
				<tr>
				<td colspan="2" style="background-color:#23abdd; border:1px solid #FFF; color:#FFFFFF;">
				<b>NOMINATIVI DELL’UFFICIO DI PREVENZIONE E PROTEZIONE</b>
				</td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$pdf->SetFont('dejavusans', '', 9);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
				<tr>
				<td width="210px" style="background-color:#cccccc;">DATORE DI LAVORO</td>
				<td width="425px"></td>
				</tr>
				<tr>
				<td width="210px" style="background-color:#cccccc;">DELEGATO alla sicurezza per il DATORE DI LAVORO</td>
				<td width="425px"></td>
				</tr>
				<tr>
				<td width="210px" style="background-color:#cccccc;">Rspp</td>
				<td width="425px"></td>
				</tr>
				<tr>
				<td width="210px" style="background-color:#cccccc;">Medico competente</td>
				<td width="425px"></td>
				</tr>
				<tr>
				<td width="210px" style="background-color:#cccccc;">Responsabile progetto formativo</td>
				<td width="425px"></td>
				</tr>
				<tr>
				<td width="210px" style="background-color:#cccccc;">Rappresentante lavoratori per la sicurezza</td>
				<td width="425px"></td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);


		$pdf->AddPage();
		$pdf->SetFont('dejavusans', '', 9);
		$boldtext = "<div> - sui nominativi dei lavoratori incaricati di applicare le misure di cui agli articoli 45 e 46</div>";
		$pdf->writeHTMLCell(0,0,'','', $boldtext,0,1,0,true,'center',true);

		$pdf->SetFont('dejavusans', '', 13);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:left;">
				<tr>
				<td colspan="2" style="background-color:#23abdd; border:1px solid #FFF; color:#FFFFFF;">
				<b>NOMINATIVI DELLA SQUADRA ANTINCENDIO</b>
				</td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$pdf->SetFont('dejavusans', '', 9);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
				<tr>
				<td width="317px"></td>
				<td width="317px"></td>
				</tr>
				<tr>
				<td width="317px"></td>
				<td width="317px"></td>
				</tr>
				<tr>
				<td width="317px"></td>
				<td width="317px"></td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$pdf->Ln(5);
		$pdf->SetFont('dejavusans', '', 13);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="0" style="text-align:left;">
				<tr>
				<td colspan="2" style="background-color:#23abdd; border:1px solid #FFF; color:#FFFFFF;">
				<b>NOMINATIVI DELLA SQUADRA PRIMO SOCCORSO</b>
				</td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$pdf->SetFont('dejavusans', '', 9);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
				<tr>
				<td width="317px"></td>
				<td width="317px"></td>
				</tr>
				<tr>
				<td width="317px"></td>
				<td width="317px"></td>
				</tr>
				<tr>
				<td width="317px"></td>
				<td width="317px"></td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		/* ---- FIRME ALLEGATO 2---- */
		$pdf->Ln(15);
		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:center;">
				<tr>
				<td width="317px" style="background-color:#cccccc;">Firma del tutor</td>
				<td width="317px" style="background-color:#cccccc;">Firma del corsista</td>
				</tr>
				<tr>
				<td width="317px"><br/><br/><br/><br/></td>
				<td width="317px"><br/><br/><br/><br/></td>
				</tr>
				</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);


		//$pdf->Output('../attestati/attestato_licenza_'.$license_id.'.pdf', 'F');
		$pdf->Output('attestato_licenza_'.$license_id.'.pdf', 'D');
	}
	 
}


?>
