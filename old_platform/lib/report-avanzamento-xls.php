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
require_once 'sanitize.php';
require_once 'class_report.php';
require_once 'class_company.php';
require_once 'class_learning_project.php';
require_once 'class_departments.php';


$id = sanitize($_GET['id'], INT);
$course_id = sanitize($_GET['course_id'], INT);

$comp = new T81Company();
$learn = new T81LearningProject();
$report_obj = new Report();
$dep_obj = new Departments();


$company = $comp->getCompanyByID($id);
$learn_detail = $learn->getDetail($course_id);
$learn_title = strtoupper($learn_detail['title']);
//$user_assigned = $comp->getAssignmentPurchase($course_id, $id);
$user_assigned = $comp->getReportAssignmentPurchase($course_id, $id);

$comp->closeiWDCompany();

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Tutor81")
->setLastModifiedBy("Tutor81")
->setTitle("Report Avanzamento Corso $learn_title")
->setSubject($company['business_name'])
->setDescription("Report sullo stato di avanzamento dei corsi per l'azienda ".$company['business_name'])
->setKeywords("office 2007 openxml php tutor81 avanzamento corsi")
->setCategory("Test result file");

$row_num = 1;
$objPHPExcel->setActiveSheetIndex(0)
->setCellValue('A1',"Unità produttiva")
->setCellValue('B1',"Reparto")
->setCellValue('C1',"Congome Nome")
->setCellValue('D1',"Data assegnazione")
->setCellValue('E1',"Data Inizio")
->setCellValue('F1',"Data Fine")
->setCellValue('G1',"Progresso");


foreach ($user_assigned as $single){

    //$user_dep_detail = $dep_obj->getEmployeeDetail($single['user_id']);
	$learning_project_user_id = $single['id'];
    $count_end = $report_obj->getLastLearningEvent($learning_project_user_id);

    if ($count_end["count"] == 0) {
        continue;
    }


    $creation_date = new DateTime($single['creation_date']);


	//$num_lo = $learn->get_num_learning_objects($course_id);
	//$num_exe_lo = $learn->get_num_lo_executed($learning_project_user_id);
    $num_lo = 0;
	$num_exe_lo = 0;



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



    if($execution_percentage < 100) {

        if (($single['id'] == 4330 || $single['id'] == 4341 || $single['id'] == 3470 || $single['id'] == 2558
            || $single['id'] == 4414
            || $single['id'] == 4428
            || $single['id'] == 3680
            || $single['id'] == 3684)
        ) {
            $execution_percentage = 100;
        }

        $learning_event = $report_obj->getLearningEvent($learning_project_user_id);
        if (!isset($learning_event['start_date_time']) || $learning_event['start_date_time'] == "0000-00-00 00:00:00") {
            $start_date_time = "-";
        } elseif (strtotime($learning_event['start_date_time']) > strtotime('2013-06-24 14:30:00')) {
            $start_date_time = date("d-m-Y H:i:s", strtotime($learning_event['start_date_time']));
        } else {
            $start_date_time = date("d-m-Y H:i:s", strtotime($learning_event['start_date_time']) + 28900);
        }
        if (!isset($learning_event['end_date_time']) || $learning_event['end_date_time'] == "0000-00-00 00:00:00") {
            $end_date_time = "-";
        } elseif (strtotime($learning_event['end_date_time']) > strtotime('2013-06-24 14:30:00')) {
            $end_date_time = date("d-m-Y H:i:s", strtotime($learning_event['end_date_time']));
        } else {
            $end_date_time = date("d-m-Y H:i:s", strtotime($learning_event['end_date_time']) + 28900);
        }

        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A' . (++$row_num), $single['short_desc_pu'])
            ->setCellValue('B' . $row_num, $single['short_desc_dep_type'])
            ->setCellValue('C' . $row_num, strtoupper($single['surname']) . ' ' . ucfirst(strtolower($single['name'])))
            ->setCellValue('D' . $row_num, $creation_date->format('d/m/Y'))
            ->setCellValue('E' . $row_num, $start_date_time)
            ->setCellValue('F' . $row_num, $end_date_time);

        if ($execution_percentage > 100) {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('G' . $row_num, 'COMPLETATO');
        } else {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('G' . $row_num, $execution_percentage / 100);
        }

    }


}

$objPHPExcel->getActiveSheet(0)->getStyle('A1:G1')->getFont()->setBold(true);
$objPHPExcel->getActiveSheet(0)->getStyle('A1:G1')->getFont()->getColor()->setRGB('FFFFFF');
$objPHPExcel->getActiveSheet(0)->getStyle('A1:G1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('23abdd');
$objPHPExcel->getActiveSheet(0)->getStyle('D2:F'.$row_num)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);
$objPHPExcel->getActiveSheet(0)->getStyle('G2:G'.$row_num)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE);
$objPHPExcel->getActiveSheet(0)->getStyle('D1:G'.$row_num)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('A')->setAutoSize(true);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('B')->setAutoSize(true);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('C')->setAutoSize(true);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('D')->setAutoSize(true);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('E')->setAutoSize(true);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('F')->setAutoSize(true);
$objPHPExcel->getActiveSheet(0)->getColumnDimension('G')->setAutoSize(true);

// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('Avanzamento Corsi');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

// Redirect output to a client’s web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="avanzamento ' . $learn_title . ' - ' . date('Ymdhis') . '.xls"');
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