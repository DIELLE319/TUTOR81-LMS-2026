<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 01-lug-2015
 * File: menu-company.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';
if ($_SESSION['user']['role'] == 0 || $_SESSION['user']['role'] == 8 || $_SESSION['user']['role'] == 16) return false;
?>
<ul class="nav nav-sidebar">
    <li<?= $page === 'home' ? ' class="active"' : ""?>><a href="company/home">Profilo</a></li>
    <li<?= $page === 'edit' ? ' class="active"' : ""?>><a href="company/edit">Modifica</a></li>
    <li<?= $page === 'unit' ? ' class="active"' : ""?>><a href="company/unit">Unità e Reparti</a></li>
    <li<?= $page === 'employee' ? ' class="active"' : ""?>><a href="company/employee">Elenco Utenti</a></li>
    <li<?= $page === 'elearning-projects' ? ' class="active"' : ""?>><a href="company/elearning-projects">Elenco corsi in e-elarning</a>
    <li<?= $page === 'subscribe' ? ' class="active"' : ""?>><a href="company/subscribe">Iscrivi ai corsi</a></li>
</ul>
<ul class="nav nav-sidebar">
    <li<?= $page === 'report' && $section === 'progress' ? ' class="active"' : ""?>><a href="company/report/progress">Stato di avanzamento</a></li>
    <li<?= $page === 'report' && $section === 'sessions' ? ' class="active"' : ""?>><a href="company/report/sessions">Sessioni</a></li>
    <li<?= $page === 'report' && $section === 'feedback' ? ' class="active"' : ""?>><a href="company/report/feedback">Feedback</a></li>
    <li<?= $page === 'report' && $section === 'tracks' ? ' class="active"' : ""?>><a href="company/report/tracks">Archivio tracciati</a></li>
</ul>
<ul class="nav nav-sidebar">
    <li<?= $page === 'alert' ? ' class="active"' : ""?>><a href="company/alert">Invia Alert</a></li>
    <li><a href="#">Crea corso personalizzato</a></li>
    <li<?= $page === 'messaging' ? ' class="active"' : ""?>><a href="company/messaging">Gestione messaggi utenti</a></li>
</ul>