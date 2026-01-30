<?php

/* iWebDev di Thomas Orlandi
 * -----------------------------------------------------------------------------------------
* This software contains confidential proprietary information belonging
* to iWebDev di Thomas Orlandi. No part of this information may be used, reproduced,
* or stored without prior written consent of iWebDev di Thomas Orlandi.
* -----------------------------------------------------------------------------------------/
* 29-ott-2012
* File: download_xls.php
* Project: tutor81
*
* Author: Thomas Orlandi :: info@iwebdev.it
*
*/
?>
<?php
$filename = base64_decode($_GET['filename']);
$filename .= ".xlsx";
header('Content-disposition: attachment; filename='.$filename);
header('Content-type: application/vnd.ms-excel');
readfile('caricamento.xlsx');
?>