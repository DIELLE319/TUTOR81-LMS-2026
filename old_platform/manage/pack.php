<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 28-set-2015
 * File: manage/pack.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_pack.php';

$pack_obj = new T81Pack();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);


/*
 * PURCHASE_PACK
 */
if ($op_type === "purchase_pack"){
    $res = $pack_obj->purchasePack($_POST['pack_type_id'], $_POST['tutor_id'], $_POST['qty_purchased']) ? : 0;

    
/*
 * CAK ELEARNING PACKED
 * calcola quanti corsi in elearning sono assegnabili (acquistabili) in base ai pacchetti acquistati
 */
} elseif ($op_type === "calc_elearning_packed"){
    $res = $pack_obj->calcElearningPacked($_POST['company_id'], $_POST['learning_project_id']) ? : 0;
}

echo $res;
