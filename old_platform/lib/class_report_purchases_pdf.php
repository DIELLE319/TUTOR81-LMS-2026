<?php
require_once 'class_db.php';
require_once 'sanitize.php';
require_once('tcpdf/tcpdf.php');
require_once('tcpdf/config/lang/ita.php');
require_once 'check_session.php';
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


class iWDReportPurchasesPdf{


	// In OOP classes are usually named starting with a cap letter.
	//private $r_conn;
	//private $conn;
	//var $conn; var $rem_conn;

	var $db_conn;

	public function iWDReportPurchasesPdf(){
		$this->db_conn = new MySQLConn();
	}

	public function generatePDF($id){
		$id = sanitize($id, INT);
		$l = Array();
		$l['a_meta_charset'] = 'UTF-8';
		$l['a_meta_dir'] = 'ltr';
		$l['a_meta_language'] = 'it';
		$l['w_page'] = 'pagina';
		$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('Tutor81');
		$pdf->SetTitle('Report Acquisti');
		$pdf->SetSubject('Acquisto delle licenze per i corsi');
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

		require_once 'check_session.php';
		$column_fatturati = 0;
		$id = sanitize($_GET['id'], INT);
		if ($_SESSION['user']['role'] == 1000){
			$column_fatturati = 95;
		}

		$comp = new T81Company();
		$report_obj = new Report();

		$company = $comp->getCompanyByID($id);
		$company_list;
		$tutor_purchases = array();

		if($company['is_tutor']){
			$tutor = $company;
			$company_list = $comp->getCompanyByTutorCompany($id);

			foreach ($company_list as $single_company){
				$company_purchases['company_detail'] = $single_company;
				$company_purchases['company_purchases'] = $report_obj->getPurchasesByCompany($single_company['id']);
				array_push($tutor_purchases, $company_purchases);
			}

		}else{
			$owner_user = $comp->getDetail($company['owner_user_id']);
			$tutor = $comp->getCompanyByID($owner_user['company_id']);
			 
			$course_purchased = $comp->getPurchaseByCompany($id);

			$company_purchases['company_detail'] = $company;
			$company_purchases['company_purchases'] = $report_obj->getPurchasesByCompany($id);
			array_push($tutor_purchases, $company_purchases);

		}

		$comp->closeiWDCompany();

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
				<td colspan="2" align="center"><b>ACQUISTO LICENZE</b></td>
				</tr>
				</table>';
		$pdf->writeHTMLCell(0,0,'',20, $boldtext,0,1,0,true,'center',true);

		$pdf->Ln(5);
		/*$pdf->SetFont('dejavusans', '', 13);
		 $tablealign = '<table width="635px"  cellpadding="10" cellspacing="0" style="background-color:#0090CF; color:#FFFFFF;">
		<tr>
		<td width="215px"><b>Tutor</b></td>
		<td width="420px" style="text-align:center;">'.$tutor['business_name'].'</td>
		</tr>
		</table>';
		$pdf->writeHTML($tablealign, true, 0, true, 0);*/
		$prima_pagina = TRUE;
		foreach ($tutor_purchases as $single_company_purchases){
			if ($prima_pagina) {
				$prima_pagina = FALSE;
			} else {
				$pdf->AddPage();
			}
			$pdf->SetFont('dejavusans', '', 13);
			$tablealign = '<table width="635px"  cellpadding="10" cellspacing="0" style="background-color:#0090CF; color:#FFFFFF;">
					<tr>
					<td width="215px"><b>Azienda</b></td>
					<td width="420px" style="text-align:center;">'.$single_company_purchases['company_detail']["business_name"].'</td>
							</tr>
							</table>';
			$pdf->writeHTML($tablealign, true, 0, true, 0);
			 
			$pdf->Ln(5);
			$pdf->SetFont('dejavusans', '', 10);
			$tablealign = '<table width="635px" cellpadding="10" cellspacing="0" border="1" style="text-align:left;">
					<tr style="background-color:#cccccc;">
					<th width="'.(220-$column_fatturati).'px">Nome Corso</th>
							<th width="130px" style="text-align:center">Data di acquisto</th>
							<th width="190px">Acquirente</th>
							<th width="95px" style="text-align:center">Quantità</th>';
			 
			if ($column_fatturati){
				$tablealign .= '<th width="95px" style="text-align:center">Fatturati</th>';
			}
			$tablealign .= '</tr>';
			 
			foreach ($single_company_purchases['company_purchases'] as $row) {

				$tablealign .= '<tr>
						<td colspan = "2" width="'.(220-$column_fatturati+130+190).'px">'.$row['title'].'</td>
								<td colspan = "2"  width="'.(95+$column_fatturati).'px" style="text-align:center">totale: '.$row['somma'].'</td>
										</tr>';

				$project_purchases = $report_obj->getPurchasesByCompanyByLearningProject($single_company_purchases['company_detail']['id'], $row['id']);

				foreach ($project_purchases as $single_purchase){
					$creation_date=$report_obj->formatDate($single_purchase['creation_date']);
					 
					$tablealign .= '<tr>
							<td colspan="2" width="'.(220-$column_fatturati+130).'" style="text-align:right">'.$creation_date.'</td>
									<td width="190px">'.$single_purchase['name']." ".$single_purchase['surname'].'</td>
											<td width="95px" style="text-align:center">'.$single_purchase['qta'].'</td>';
					 
					if ($column_fatturati){
						$tablealign .= '<td width="'.$column_fatturati.'px" style="text-align:center">'.(($single_purchase['invoiced']) ? 'SI' : 'NO').'</td>';
					}
					$tablealign .= '</tr>';

				}
			}
			 
			$tablealign .= '</table>';

			$pdf->writeHTML($tablealign, true, 0, true, 0);

			$pdf->SetFont('dejavusans', '', 9);
			$boldtext = "<div>Data di stampa: <b>".date("d-m-Y")."</b></div>";
			$pdf->writeHTMLCell(0,0,'','', $boldtext,0,1,0,true,'center',true);
			 
		}
		$pdf->Output('acquisti.pdf', 'D');
	}

	public function closeTutor81Attestato(){
		//PHP B id=30525
		//@mysql_close($this->conn);
	}
}


?>
