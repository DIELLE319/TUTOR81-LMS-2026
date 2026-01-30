<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 13-nov-2015
 * File: manage/purchase.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_purchase.php';

$purchase_obj = new iWDPurchase();

$op_type = filter_input(INPUT_POST, 'op_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

$res = 0;

/**
 * SALVA DATA FATTURA per acquisti multipli (passati come json)
 */
if ($op_type === 'save_invoice_date') {
    foreach ($_POST['invoices'] as $invoice){
        $updated = $purchase_obj->setInvoiceDate($invoice, $_POST['invoice_date']);
        if (((int)$updated) > 0 ) {
            $res += $updated;
        }
        else {
            $res = $updated;
            break;
        }
    }
    
    
/**
 * AGGIORNA DATA FATTURA per acquisto singolo
 */
} elseif ($op_type === 'update_invoice_date') {
    $res =  $purchase_obj->setInvoiceDate($_POST['tutor_purchase_id'], $_POST['invoice_date']) ? : 0;
    
    
/**
 * IMPOSTA ACQUISTO COME NON FATTURATO
 */
} elseif ($op_type === 'uninvoice_purchase') {
    $res =  $purchase_obj->setPurchaseUninvoiced($_POST['tutor_purchase_id']) ? : 0;
    
/**
 * AGGIORNA NOTA
 */
} elseif ($op_type === 'update_nota') {
    $res = json_encode($purchase_obj->updateNota($_POST['id'],$_POST['nota']));

}

echo $res;