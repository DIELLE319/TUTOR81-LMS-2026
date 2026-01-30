<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 24-lug-2015
 * File: manage/render_document.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'function.php';
$doc_type = filter_input(INPUT_GET, 'doc_type', FILTER_SANITIZE_STRING);


// MOSTRA ATTESTATO
if ($doc_type === "attestato_elearning") {
    $license_id = filter_input(INPUT_GET, 'license_id', FILTER_SANITIZE_STRING);
    $file = BASE_MEDIA_PATH."attestati/attestato_licenza_$license_id.pdf";
    if (file_exists($file)){
        // verifica permessi utente
        require_once BASE_LIBRARY_PATH . 'class_user.php';
        $user_obj = new T81User();
        $license = $user_obj->getUserLicenseById($license_id);
        if (authorizeByRole($license['user_id'])){
            header("Content-Description: File Transfer");
            header("Content-type: application/pdf");
            header("Content-Disposition: inline; filename='attestato_licenza_$license_id.pdf'");
            header("Content-Transfer-Encoding: binary");
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header("Content-Length: ".filesize($file));
            @readfile($file);
            exit();
        } else {
            require_once BASE_ROOT_PATH . '403.php';
        }
    } else {
        require_once BASE_LIBRARY_PATH . 'class_attestato.php';
	$attestato = new Tutor81Attestato();
	$attestato->generatePDF($license_id);
        exit();
    };

    
    
// MOSTRA TEST IN PRESENZA    
} elseif ($doc_type === "test_in_presenza"){
    $license_id = filter_input(INPUT_GET, 'license_id', FILTER_SANITIZE_STRING);
    $file = BASE_MEDIA_PATH."test_in_presenza/test_licenza_$license_id.pdf";
    if (file_exists($file)){
        // verifica permessi utente
        require_once BASE_LIBRARY_PATH . 'class_user.php';
        $user_obj = new T81User();
        $license = $user_obj->getUserLicenseById($license_id);
        if (authorizeByRole($license['user_id'])){
            header("Content-Description: File Transfer");
            header("Content-type: application/pdf");
            header("Content-Disposition: inline; filename='test_licenza_$license_id.pdf'");
            header("Content-Transfer-Encoding: binary");
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header("Content-Length: ".filesize($file));
            @readfile($file);
            exit();
        } else {
            require_once BASE_ROOT_PATH . '403.php';
        }
    } else require_once BASE_ROOT_PATH . '404.php';
    
    
} else {
    require_once BASE_ROOT_PATH . '403.php';
}
echo $res;