<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 19-nov-2015
 * File: pages/objects-manager.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';


if ($_SESSION['user']['role'] != 1000) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

if (key_exists('id', $_GET)){
    $learn_obj_id = sanitize($_GET['id'], INT);
} else {
    $learn_obj_id = 0;
    if (key_exists('filter', $_GET)){
            $filter_list = sanitize($_GET['filter'], INT);
    } else {
            $filter_list = 1;
    }
}

require_once 'lib/class_om.php';
$om = new T81DOM();

if ($filter_list >= 0 && $filter_list <= 2){
    $om_list = $om->getList($filter_list);
} else {
                    $om_list = $om->getList();
}
?>
<script>
	
    function showPreview(){
        if($("#preview_btn").hasClass('active')){
            $("#learning_obj_detail").fadeIn();
            $("#preview_btn").removeClass('active');
            $("#preview_btn").attr("value", "Anteprima e aggiungi domande");
            $("#div_player").slideUp();
        }else{
            $("#learning_obj_detail").fadeOut();
            $("#preview_btn").addClass('active');
            $("#preview_btn").attr("value", "Dettagli oggetto");
            $("#div_player").slideDown();
        }        
    }
     
</script>

<ul class="breadcrumb">

<?php if ($_SESSION['user']['role'] == 1000){ ?>
    
    <li<?= $section == "new" ? ' class="active"' : ''?>>
    	<a href="admin/objects/new">
            <img alt="nuovo oggetto" src="img/crea_oggetto.png" title="inserisci un nuovo oggetto">
            Crea oggetto
        </a>
    </li>
    
<?php } ?>
    
    <li><a href="admin/objects">Mostra tabella</a></li>
</ul>

<div id="objects-manager" class="container-fluid">

<?php
if ($section === 'view') require_once 'sections/object-detail.php';
elseif ($section === 'new') require_once 'sections/obect-new.php';
else require_once 'sections/objects-table.php';
?>

</div>