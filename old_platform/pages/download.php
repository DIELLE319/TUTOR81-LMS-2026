<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 24-nov-2015
 * File: pages/download.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32 && $_SESSION['user']['role'] != 2) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

?>

<div id="workspace" class="container-fluid">

    <div class="title-page clearfix hidden">
        <h2>Area download<br/>
            <small>Scarica il test di fine corso, le risposte e altro ancora:</small>
        </h2>
    </div>
    
    <hr>
    <h4>Test in presenza</h4>
    <ul>
        <li>
            <a href="download/tutor/MOD8_test_in_presenza_dirigente_nuovo.pdf" target="_blank">Dirigente Nuovo</a>
        </li>
        <li>
            <a href="download/tutor/MOD8_test_in_presenza_dirigente_nuovo_risposte.pdf" target="_blank">Dirigente Nuovo (con risposte)</a>
        </li>
        <li>
            <a href="download/tutor/test_preposto_nuovo.pdf" target="_blank">Preposto Nuovo</a>
        </li>
        <li>
            <a href="download/tutor/test_preposto_nuovo_risposte.pdf" target="_blank">Preposto Nuovo (con risposte)</a>
        </li>
        <li>
            <a href="download/tutor/test_in_presenza_corso_lavoratore.doc" target="_blank">Lavoratore</a>
        </li>
        
        <!-- disabilitati in data 2015-06-12
        <li>
            <a href="download/tutor/MOD10_test_lavoratore_nuovo.pdf" target="_blank">Lavoratore Nuovo</a>
        </li>
        <li>
            <a href="download/tutor/MOD9_test_lavoratore_nuovo_risposte.pdf" target="_blank">Lavoratore Nuovo (con risposte)</a>
        </li> -->
    </ul>
    <hr>
    <h4>Altro</h4>
    <ul>
        <li>
            <a href="download/tutor/MOD7_invio_modulo_formazione_specifica.doc">Invio modulo formazione specifica</a>
        </li>
        <li>
            <a href="download/tutor/Tabella_riepilogativa_criteri_della_formazione.xls">Tabella riepilogativa criteri della formazione</a>
        </li>

    </ul>

</div><!--/#workspace-->