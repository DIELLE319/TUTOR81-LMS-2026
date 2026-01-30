<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 30-lug-2015
 * File: menu-user.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';
?>
<ul class="nav nav-sidebar">
    <li<?= $page === 'home' ? ' class="active"' : ""?>><a href="user/home">Corsi</a></li>
    <li<?= $page === 'help' ? ' class="active"' : ""?>><a href="user/help">Assistenza</a></li>
    <!-- <li<?= $page === 'messaging' ? ' class="active"' : ""?>><a href="user/messaging">Messaggi</a></li> -->
</ul>