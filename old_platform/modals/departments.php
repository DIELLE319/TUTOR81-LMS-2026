<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 30-ott-2015
 * File: modals/departments.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3>Unità produttive e reparti</h3>
</div>
<div class="modal-body">
    
    <?php require BASE_ROOT_PATH . 'pages/departments.php'?>
    
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
</div>