<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 03-lug-2015
 * File: content-member.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';
if ($_SESSION['user']['role'] != 32) {
    require_once '403.php';
    return false;
}
switch ($page){
    case 'home':
        require_once 'home-member.php';
        break;
    case 'company':
        if ($section === 'new') require_once 'pages/new-company.php';
        break;
    case 'classroom':
        if ($section === 'planner' || $section === 'project') require_once 'pages/sections/classroom-planner.php';
        elseif($section === 'calendar') require_once 'pages/sections/classroom-calendar.php';
        elseif($section === 'manager' || $section === 'new') require_once 'pages/classroom-manager.php';
        break;
    case 'elearning-projects':
        require_once 'pages/elearning-projects.php';
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