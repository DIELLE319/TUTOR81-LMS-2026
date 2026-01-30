<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 01-lug-2015
 * File: content-company.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';

if ($_SESSION['user']['role'] == 0 || $_SESSION['user']['role'] == 8 || $_SESSION['user']['role'] == 16) {
    require_once "403.php";
    return false;
}

switch ($page){
    case 'home':
    case 'employees':
    case 'report':
    case 'subscribe':
    case 'progress':
        require_once 'home-company.php';
        break;
    case 'purchases':
        require_once 'pages/purchases.php';
        break;
    case 'edit':
        require_once 'pages/edit-company.php';
        break;
    case 'unit':
        require_once 'pages/departments.php';
        break;
    case 'elearning-projects':
        require_once 'pages/elearning-projects.php';
        break;
    case 'employee':
        if ($section === 'new') require_once 'new_employee.php';
        break;
    case 'objects':
        require_once 'pages/objects-manager.php';
        break;
    case 'download':
        require_once 'pages/download.php';
        break;
    default:
        require_once "404.php";
}