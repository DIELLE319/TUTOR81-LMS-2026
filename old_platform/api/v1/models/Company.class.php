<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 23-oct-2021
 * File: api/v1/models/Company.class.php
 * Project: Piattaforma Tutor81
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.api.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';

class Company extends T81Company {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * recupera l'ID della provincia in base alla sigla
     * 
     * @param string $sigla
     * @return string
     */
    public function getProvinceFromSigla($sigla) {
        $sigla = filter_var($sigla, FILTER_SANITIZE_STRING);
        $query = "SELECT * FROM provinces WHERE sigla = '$sigla'";
        $res = $this->db_conn->query($query);
        return isset($res[0]) ? $res[0] : false;
    }
    
}