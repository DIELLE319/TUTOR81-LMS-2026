<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 23-oct-2021
 * File: api/v1/models/Purchase.class.php
 * Project: Piattaforma Tutor81
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.api.php';
require_once BASE_LIBRARY_PATH . 'class_purchase.php';

class Purchase extends iWDPurchase {
    
    public function __construct() {
        parent::__construct();
    }
}