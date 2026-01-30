<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 30-lug-2015
 * File: modals/session.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3>Crea nuovo dipendente</h3>
</div>
<div class="modal-body">
    
    <?php require BASE_ROOT_PATH . 'report/sessions.php'?>
    
</div>
<div class="modal-footer">
    <button class="btn btn-info" onclick="$('#course_container').printArea()">Stampa</button>
    <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Chiudi</button>
</div>