<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 30-lug-2015
 * File: content-user.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';
switch ($page){
    case 'home':
        require_once 'home-user.php';
        break;
    case 'help':
        require_once 'pages/help.php';
        break;
    case 'messaging':
        require_once 'pages/messaging.php';
        break;
    default:
        require_once "404.php";
}