<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 06-lug-2015
 * File: menu-admin.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';
if ($_SESSION['user']['role'] != 1000) return false;
?>
<ul class="nav nav-sidebar">
    <li<?= $page === 'home' ? ' class="active"' : ""?>><a href="tutor/home">Dashboard</a></li>
    <li<?= $page === 'company' && $section === 'new' ? ' class="active"' : ""?>><a href="admin/company/new?is_tutor=true">Crea Ente Formativo</a></li>
    <li<?= $page === 'ticket' ? ' class="active"' : ""?>><a href="admin/ticket/open">Ticket assistenza</a></li>
</ul>
<ul class="nav nav-sidebar">
    <li<?= $page === 'report' && $section === 'purchases' ? ' class="active"' : ""?>><a href="admin/report/purchases">Acquisti</a></li>
    <li<?= $page === 'report' && $section === 'monitor' ? ' class="active"' : ""?>><a href="admin/report/monitor">Utenti Online</a></li>
    <li<?= $page === 'report' && $section === 'feedback' ? ' class="active"' : ""?>><a href="admin/report/feedback">Feedback</a></li>
</ul>
<ul class="nav nav-sidebar">
    <li<?= $page === 'elearning-projects' ? ' class="active"' : ""?>><a href="admin/elearning-projects">Corsi in e-learning</a>
    <li<?= $page === 'course-types' && !$section ? ' class="active"' : ""?>><a href="admin/course-types">Corsi in aula</a>
    <li<?= $page === 'course-types' && $section === 'new' ? ' class="active"' : ""?>><a href="admin/course-types/new">Crea corso in aula</a></li>
</ul>