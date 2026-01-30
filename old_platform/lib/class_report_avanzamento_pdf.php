<?php
require_once 'class_db.php';
require_once 'sanitize.php';
require_once('tcpdf/tcpdf.php');
require_once('tcpdf/config/lang/ita.php');
require_once 'class_learning_project.php';
require_once 'class_report.php';
require_once 'class_company.php';


class MYPDF extends TCPDF {

	//Page header
	public function Header() {
		// Logo
		$image_file = '../img/logo_bicolore.png';
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


class iWDReportAvanzamentoPdf{


	// In OOP classes are usually named starting with a cap letter.
	//private $r_conn;
	//private $conn;
	//var $conn; var $rem_conn;

	var $db_conn;

	public function __construct(){
		$this->db_conn = new MySQLConn();
	}

	public function generatePDF($id, $course_id){
		$id = sanitize($id, INT);
		$course_id = sanitize($course_id, INT);
		$l = Array();
		$l['a_meta_charset'] = 'UTF-8';
		$l['a_meta_dir'] = 'ltr';
		$l['a_meta_language'] = 'it';
		$l['w_page'] = 'pagina';
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Tutor81');
		$pdf->SetTitle('Report Avanzamento Corsi');
		$pdf->SetSubject('Stato di avanzamento dei corsi assegnati agli utenti');
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


		$comp = new T81Company();
		$learn = new T81LearningProject();
		$report_obj = new Report();

		$company = $comp->getCompanyByID($id);
		$learn_detail = $learn->getDetail($course_id);
		$user_assigned = $comp->getAssignmentPurchase($course_id, $id);

		$comp->closeiWDCompany();

		$owner_user = $comp->getDetail($company['owner_user_id']);
		$tutor = $comp->getCompanyByID($owner_user['company_id']);

		$pdf->SetFont('dejavusans', '', 11, '', true);
		$pdf->AddPage();
		$pdf->SetFont('dejavusans', '', 10);

		//LOGO
		if(file_exists("../../img/company/".$tutor['id'].".jpg")){
			$image_file = "../../img/company/".$tutor['id'].".jpg";
			$path_parts = pathinfo($image_file);
			$pdf->Image($image_file, 20, 5, 30, '', $path_parts['extension'], '', 'T', false, 300, '', false, false, 0, false, false, false);
		}else{
			$image_file = "../img/logo_bicolore.png";
			$path_parts = pathinfo($image_file);
			$pdf->Image($image_file, 20, 5, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
		}

		$boldtext = '<table border="0">
				<tr>
				<td colspan="2" align="center"><b>STATO DI AVANZAMENTO CORSI</b></td>
				</tr>
				</table>';
		$pdf->writeHTMLCell(0,0,'',20, $boldtext,0,1,0,true,'center',true);

		$pdf->Ln(5);
		$pdf->SetFont('dejavusans', '', 13);
		$tablealign = '<table width="635px"  cellpadding="10" cellspacing="0" style="background-color:#0090CF; color:#FFFFFF;">
				<tr>
				<td width="215px"><b>Tutor</b></td>
				<td width="420px" style="text-align:center;">'.$tutor['business_name'].'</td>
						</tr>
						</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);
		 
		$tablealign = '<table width="635px"  cellpadding="10" cellspacing="0" style="background-color:#0090CF; color:#FFFFFF;">
				<tr>
				<td width="215px"><b>Azienda</b></td>
				<td width="420px" style="text-align:center;">'.$company['business_name'].'</td>
						</tr>
						</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$pdf->Ln(5);

		$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
				<tr>
				<td width="215px" style="background-color:#cccccc;">Corso</td>
				<td width="420px" style="text-align:center;">'.$learn_detail['title'].'</td>
						</tr>
						</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$pdf->SetFont('dejavusans', '', 9);
		$tablealign = '<table width="635px" cellpadding="2" cellspacing="0" border="1" style="text-align:left;">
				<tr>
				<td width="215" style="background-color:#cccccc;">Cognome e Nome</td>
				<td width="150" style="background-color:#cccccc; text-align:center;">Data Inizio</td>
				<td width="150" style="background-color:#cccccc; text-align:center;">Data Fine</td>
				<td width="120" style="background-color:#cccccc; text-align:center;">Progresso</td>
				</tr>';

		foreach ($user_assigned as $single){

			$learning_project_user_id = $single['id'];

            $count_end = $report_obj->getLastLearningEvent($learning_project_user_id);

            if ($count_end["count"] == 0) {
                continue;
            }


            $learning_event = $report_obj->getLearningEvent($learning_project_user_id);

			$num_lo = $learn->get_num_learning_objects($course_id);
			$num_exe_lo = $learn->get_num_lo_executed($learning_project_user_id);

			if($num_exe_lo != 0){
				$execution_percentage = round($num_exe_lo / $num_lo * 100);
			}else{
				$execution_percentage = 0;
			}

			if(($single['id'] == 4330 || $single['id'] == 4341 || $single['id'] == 3470 || $single['id'] == 2558
					|| $single['id'] == 4414
					|| $single['id'] == 4428
					|| $single['id'] == 3680
					|| $single['id'] == 3684)){
				$execution_percentage = 100;
			}


			if (!isset($learning_event['start_date_time']) || $learning_event['start_date_time'] == "0000-00-00 00:00:00"){
				$start_date_time = "-";
			} elseif (strtotime($learning_event['start_date_time'])>strtotime('2013-06-24 14:30:00')) {
				$start_date_time = date("d-m-Y H:i:s", strtotime($learning_event['start_date_time']));
			} else {
				$start_date_time = date("d-m-Y H:i:s", strtotime($learning_event['start_date_time'])+28900);
			}
			if (!isset($learning_event['end_date_time']) || $learning_event['end_date_time'] == "0000-00-00 00:00:00"){
				$end_date_time = "-";
			} elseif (strtotime($learning_event['end_date_time'])>strtotime('2013-06-24 14:30:00')) {
				$end_date_time = date("d-m-Y H:i:s", strtotime($learning_event['end_date_time']));
			} else {
				$end_date_time = date("d-m-Y H:i:s", strtotime($learning_event['end_date_time'])+28900);
			}
			 
			$tablealign .= '<tr>
					<td><b>'.strtoupper($single['surname']).'</b> '.ucfirst(strtolower($single['name'])).'</td>
							<td style="text-align:center;">'.$start_date_time.'</td>
									<td style="text-align:center;">'.$end_date_time.'</td>
											<td class="progress" style="text-align:center;">';
			if($execution_percentage > 100){
				$tablealign .= '<h5 style="margin: 0;">COMPLETATO</h5>';
			}else{
				$tablealign .= '<h5>'.$execution_percentage.'%</h5>';
			}
			$tablealign .= '</td></tr>';

		}
		$tablealign .= '</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);

		$pdf->SetFont('dejavusans', '', 9);
		$boldtext = "<div>Data di stampa: <b>".date("d-m-Y")."</b></div>";
		$pdf->writeHTMLCell(0,0,'','', $boldtext,0,1,0,true,'center',true);

		$pdf->Output('avanzamento.pdf', 'D');
	}

	public function closeTutor81Attestato(){
		//PHP B id=30525
		//@mysql_close($this->conn);
	}
}


?>
