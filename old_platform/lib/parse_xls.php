<?php

require_once 'PHPExcel.php';
require_once 'PHPExcel/Writer/Excel5.php';

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!empty($_FILES)) {
    // Get parameters
    $chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
    $chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
    $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
    $output = "";
    // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
    if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        $inputFileName = $_FILES['file']['tmp_name'];
        /** Identify the type of $inputFileName * */
        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        /** Create a new Reader of the type that has been identified * */
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        /** Load $inputFileName to a PHPExcel Object * */
        if ($inputFileType == "CSV") {
            //PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());
            $objReader->setDelimiter(";");
        }
        $objPHPExcel = $objReader->load($inputFileName);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow();
        $sheetData = $objPHPExcel->getActiveSheet()->toArray();
        for ($row = 2; $row <= $highestRow; $row++) {
            $nome = trim($objWorksheet->getCellByColumnAndRow(0, $row)->getValue());
            $cognome = trim($objWorksheet->getCellByColumnAndRow(1, $row)->getValue());
            $cod_fisc = trim($objWorksheet->getCellByColumnAndRow(2, $row)->getValue());
            $email = trim($objWorksheet->getCellByColumnAndRow(3, $row)->getValue());
            $unita = trim($objWorksheet->getCellByColumnAndRow(4, $row)->getValue());
            $reparto = trim($objWorksheet->getCellByColumnAndRow(5, $row)->getValue());
            if ($inputFileType == "CSV" || $inputFileType == "OOCalc" ) {
                $data_assunzione = DateTime::createFromFormat("d/m/Y", trim($objWorksheet->getCellByColumnAndRow(6, $row)->getValue()), new DateTimeZone('Europe/Rome'));
            } else {
                $data_assunzione = PHPExcel_Shared_Date::ExcelToPHPObject($objWorksheet->getCellByColumnAndRow(6, $row)->getValue());
            }
            if (!empty($data_assunzione)){
                $data_assunzione = $data_assunzione->format("Y-m-d");
            }
            
            /*
            $nome = trim($nome);
            $cognome = trim($cognome);
            $cod_fisc = trim($cod_fisc);
            $email = trim($email);
            */
            
            if (($nome != "") && ($cognome != "") && ($cod_fisc != "") && ($email != "")) {
                $output .= "$nome;$cognome;$cod_fisc;$email".
                        (!empty($unita) && !empty($reparto) && !empty($data_assunzione) ? ";$unita;$reparto;$data_assunzione|" : "|");
            }
        }
    }
}
echo $output;
?>