<?php
/**
 * PHPExcel
 *
 * Copyright (C) 2006 - 2013 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2013 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.9, 2013-06-02
 */

/** Error reporting  */
 error_reporting(E_ALL);
 ini_set('display_errors', FALSE);
 ini_set('display_startup_errors', FALSE);

if (PHP_SAPI == 'cli')
	die('This example should only be run from a Web Browser');

/** Include PHPExcel */
require_once 'PHPExcel.php';

/** Include Tutor81 */

require_once 'check_session.php';
require_once 'sanitize.php';
require_once 'class_report.php';
require_once 'class_company.php';

//require_once 'class_learning_project.php';

$id = sanitize($_GET['id'], INT);

$comp = new T81Company();
$report_obj = new Report();

$company = $comp->getCompanyByID($id);
$company_list;
$tutor_purchases = array();

if($company['is_tutor']){
	$company_list = $comp->getCompanyByTutorCompany($id);

	foreach ($company_list as $single_company){
		$company_purchases['company_detail'] = $single_company;
		$company_purchases['company_purchases'] = $report_obj->getPurchasesByCompany($single_company['id']);
		array_push($tutor_purchases, $company_purchases);
	}

}else{
	$course_purchased = $comp->getPurchaseByCompany($id);

	$company_purchases['company_detail'] = $company;
	$company_purchases['company_purchases'] = $report_obj->getPurchasesByCompany($id);
	array_push($tutor_purchases, $company_purchases);
	 
}

$comp->closeiWDCompany();





// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Tutor81")
							 ->setLastModifiedBy("Tutor81")
							 ->setTitle("Report Acquisti")
							 ->setSubject($company['business_name'])
							 ->setDescription("Report degli acquisti eseguiti per la ditta ".$company['business_name'])
							 ->setKeywords("office 2007 openxml php tutor81 acquisti")
							 ->setCategory("Test result file");

$row_num = 1;
$objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A'.$row_num,"Nome Ditta")
			->setCellValue('B'.$row_num,"Nome Corso")
			->setCellValue('C'.$row_num,"Num. Ordine")
			->setCellValue('D'.$row_num,"Data di acquisto")
			->setCellValue('E'.$row_num,"Acquirente")
			->setCellValue('F'.$row_num,"QTA");
if ($_SESSION['user']['role'] == 1000){
	$last_col = 'H';
	$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('G'.$row_num,"Nota")
				->setCellValue('H'.$row_num,"Fatturati");
} else {
	$last_col = 'F';
}
$objPHPExcel->getActiveSheet(0)->getStyle('A1:'.$last_col.'1')->getFont()->setBold(true);
$objPHPExcel->getActiveSheet(0)->getStyle('A1:'.$last_col.'1')->getFont()->getColor()->setRGB('FFFFFF');
$objPHPExcel->getActiveSheet(0)->getStyle('A1:'.$last_col.'1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('23abdd');
					
foreach ($tutor_purchases as $single_company_purchases){	
	foreach ($single_company_purchases['company_purchases'] as $row) {
		$project_purchases = $report_obj->getPurchasesByCompanyByLearningProject($single_company_purchases['company_detail']['id'], $row['id']);
				
		foreach ($project_purchases as $single_purchase){
			$creation_date=$report_obj->formatDate($single_purchase['creation_date']);
			
			$objPHPExcel->setActiveSheetIndex(0)
						->setCellValue('A'.++$row_num,$single_company_purchases['company_detail']["business_name"])
						->setCellValue('B'.$row_num,$row['title'])
						->setCellValue('C'.$row_num,$single_purchase['id'])
						->setCellValue('D'.$row_num,$creation_date)
						->setCellValue('E'.$row_num,$single_purchase['name']." ".$single_purchase['surname'])
						->setCellValue('F'.$row_num,$single_purchase['qta']);
			
			if ($_SESSION['user']['role'] == 1000){
				$objPHPExcel->setActiveSheetIndex(0)
							->setCellValue('G'.$row_num,$single_purchase['nota'])
							->setCellValue('H'.$row_num,(($single_purchase['invoiced']) ? 'si' : 'no'));
			}
		}
	}
}

$objPHPExcel->getActiveSheet(0)->getStyle('C1:C'.$row_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet(0)->getStyle('D2:D'.$row_num)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
$objPHPExcel->getActiveSheet(0)->getStyle('D1:D'.$row_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet(0)->getStyle('F1:F'.$row_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('A')->setAutoSize(true);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('B')->setAutoSize(true);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('C')->setAutoSize(true);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('D')->setAutoSize(true);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('E')->setAutoSize(true);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('F')->setAutoSize(true);
if ($_SESSION['user']['role'] == 1000){
	$objPHPExcel->getActiveSheet(0)->getStyle('G1:G'.$row_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
	$objPHPExcel->getActiveSheet(0)->getColumnDimension('G')->setAutoSize(true);
	$objPHPExcel->getActiveSheet(0)->getStyle('H1:H'.$row_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$objPHPExcel->getActiveSheet(0)->getColumnDimension('H')->setAutoSize(true);
}	
// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('Acquisti');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);


// Redirect output to a client’s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="acquisti.xls"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');
exit;
