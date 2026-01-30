<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 06-lug-2015
 * File: content-admin.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';
if ($_SESSION['user']['role'] != 1000) {
    require_once '403.php';
    return false;
}
switch ($page){
    case 'home':
        require_once 'home-admin.php';
        break;
        if ($section === 'new') require_once 'pages/new-company.php';
        break;
    case 'elearning-projects':
        require_once 'pages/elearning-projects.php';
        break;
    case 'course-types':
        if (!$section) require_once 'pages/course-types.php';
        elseif ($section === 'new') require_once 'pages/sections/course-types-new.php';
        break;
    case 'ticket':
        require_once 'pages/ticket.php';
        break;
    case 'purchases':
        require_once 'pages/purchases-admin.php';
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