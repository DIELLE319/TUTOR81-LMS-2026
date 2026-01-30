<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 28-ott-2015
 * File: modals/new-company.php
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
    <h3>Crea nuovo Ente Formativo</h3>
</div>
<div class="modal-body">
    
    <?php require BASE_ROOT_PATH . 'pages/new-tutor.php'?>
    
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
    <button type="button" class="btn btn-primary save-modal"><span class="glyphicon glyphicon-ok"></span> Salva</button>
</div>
<script>
    $('#new-company').hide();
    $('.save-modal').click(function(){$('#new-company').click();});
</script>