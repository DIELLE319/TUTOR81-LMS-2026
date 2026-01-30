<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 29-giu-2015
 * File: menu-tutor.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';
if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) return false;
$is_partner = filter_input(INPUT_GET, 'is_partner', FILTER_VALIDATE_BOOLEAN);
?>
<ul class="nav nav-sidebar">
    <li<?= $page === 'home' ? ' class="active"' : ""?>><a href="tutor/home">Clienti</a></li>
    <li<?= $page === 'company' && $section === 'new' && !$is_partner ? ' class="active"' : ""?>><a href="tutor/company/new?is_partner=false">Crea azienda cliente</a></li>
    <li<?= $page === 'employee' && $section === 'new' ? ' class="active"' : ""?>><a href="tutor/employee/new?role=1">Crea amministratore</a></li>
</ul>
<ul class="nav nav-sidebar">
    <li<?= $page === 'report' && $section === 'purchases' ? ' class="active"' : ""?>><a href="tutor/report/purchases">Acquisti</a></li>
    <li<?= $page === 'monitor' ? ' class="active"' : ""?>><a href="tutor/monitor">Utenti Online</a></li>
    <li<?= $page === 'report' && $section === 'progress' ? ' class="active"' : ""?>><a href="tutor/report/progress">Stato avanzamento corsi</a></li>
    <li<?= $page === 'report' && $section === 'feedback' ? ' class="active"' : ""?>><a href="tutor/report/feedback">Feedback</a></li>
</ul>
<ul class="nav nav-sidebar">
    <li<?= $page === 'classroom' && $section === 'planner' ? ' class="active"' : ""?>><a href="tutor/classroom/planner">Crea Aula</a></li>
    <li<?= $page === 'classroom' && $section === 'project' ? ' class="active"' : ""?>><a href="tutor/classroom/project">Crea Progetto Aula</a></li>
    <li<?= $page === 'classroom' && $section === 'calendar' ? ' class="active"' : ""?>><a href="tutor/classroom/calendar">Calendario Aule</a></li>
    <li<?= $page === 'classroom' && $section === 'manager' ? ' class="active"' : ""?>><a href="tutor/classroom/manager">Gestione Aule</a></li>
    <li><a href="#">Crea corso personalizzato</a></li>
</ul>