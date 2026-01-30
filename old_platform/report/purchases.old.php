<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 02-lug-2015
 * File: report/purchases.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] == 0 || $_SESSION['user']['role'] == 8 || $_SESSION['user']['role'] == 16) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';

$company_obj = new T81Company();
$report_obj = new Report();

$company_id = filter_input(INPUT_GET, 'company', FILTER_SANITIZE_NUMBER_INT);

/* ***** VISTA ACQUISTI AZIENDA ***** */
if (!empty($company_id) || $area === 'company') {
    // acquisti company
    $company_id = $company_id ? : $_SESSION['company']['id'];
    $company_purchases = $report_obj->getAllPurchasesByCompany($company_id);
    
    
    
/* ***** VISTA ACQUISTI ENTE FORMATIVO O SOCIO ***** */
} elseif (($area === 'tutor' || $area === 'member') && ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32)) {
    // acquisti ente formativo e sue aziende
    $tutor_purchases = $report_obj->getAllPurchasesByTutor($_SESSION['tutor']['id']);
    $has_cost_centre = $company_obj->getCostCentre($_SESSION ['tutor']['id']) ? true : false;
    /* ***** ACQUISTI SOCIO ***** */
    if ($area === 'member' && $_SESSION['user']['role'] == 32) {
        // acquisti enti formativi a cui il socio ha venduto una licenza
        $member_purchases = $report_obj->getAllPurchasesByMemberTutors($_SESSION['user']['tutor_id']);
    }
?>

<div>
    <table class="table-purchases table table-hover table-condensed">
	<thead>
            <tr>
		<th class="purchases-id">N° Ord.</th>
                <th>Cliente</th>
                <th>Ruolo</th>
		<th data-sorter="shortDate" data-date-format="ddmmyyyy" class="purchases-data">data di acquisto</th>
	
            <?php if($has_cost_centre){?>
					
		<th class="purchase-cost-centre">Centro di costo</th>
				
            <?php }?>
                
		<!-- <th class="purchases-acquirente">Acquirente</th> -->
		<th class="purchases-corso">Nome Corso</th>
		<th class="purchases-qta">QTA</th>
				
            <?php if ($_SESSION['user']['role'] == 1000){?>
				
		<th class="purchases-nota">Nota</th>				
		<th class="check-fatturati">Fatturati</th>
				
            <?php }?>
			
            </tr>
	</thead>
        <tbody>
	
        <?php
        if (isset($tutor_purchases)){
        foreach ($tutor_purchases as $purchase){
            $creation_date=$report_obj->formatDate($purchase['creation_date'], 'd/m/Y');?>
            
            <tr class="<?= $purchase['is_tutor'] ? ' warning' : ($purchase['is_partner'] ? ' success' : '') ?>">
		<td><?=$purchase['id']?></td>
                <td><?= $purchase['business_name'] ?></td>
                <td><?= $purchase['is_tutor'] ? 'Ente Formativo' : ($purchase['is_partner'] ? 'Partner' : 'Azienda')?></td>
		<td><?=$creation_date?></td>
				
            <?php if($has_cost_centre){?>
					
		<td><?=$purchase['cost_centre']?></td>
					
            <?php }?>
					
		<!-- <td><?=ucwords($purchase['surname']." ".$purchase['name'])?></td> -->
		<td><?=strtoupper(substr($purchase['title'], strpos($purchase['title'], ' - ') + 3))?></td>
		<td class="purchases-qta"><?=$purchase['qta']?></td>
			
            <?php if ($_SESSION['user']['role'] == 1000){?>
			
		<td class="purchases-nota">
                    <input type="text" value="<?=$purchase['nota']?>"
			<?='id="d_nota_' . $purchase['id'] . '"'?>
			onchange="updateNota('<?=$purchase['id']?>','d_nota_')" />
                </td>
		<td class="fatturati">
                    <input type="checkbox"
			<?='id="d_inv_' . $purchase['id'] . '"'?>
			onclick="changeInvoiced('<?=$purchase['id']?>','d_inv_')"
			<?=($purchase['invoiced']) ? 'checked="checked"' : ''?> />
                </td>
                
            <?php } ?>
                
            </tr>
		
        <?php }} ?>
            
            <?php
            if (isset($member_purchases)){
                foreach ($member_purchases as $tutor => $tutor_purchases){
                    foreach ($tutor_purchases as $purchase) {
                    $creation_date=$report_obj->formatDate($purchase['creation_date'], 'd/m/Y');?>
            
            <tr class="danger">
		<td><?=$purchase['id']?></td>
                <td><?= $tutor ?></td>
                <td>Ente Formativo</td>
		<td><?=$creation_date?></td>
					
                <?php if($has_cost_centre){?>
					
                <td>&nbsp;</td>
					
                <?php }?>
					
		<!-- <td><?=ucwords($purchase['surname']." ".$purchase['name'])?></td> -->
		<td><?=strtoupper(substr($purchase['title'], strpos($purchase['title'], ' - ') + 3))?></td>
		<td class="purchases-qta"><?=$purchase['qta']?></td>
			
                <?php if ($_SESSION['user']['role'] == 1000){?>
			
		<td class="purchases-nota">
                    <input type="text" value="<?=$purchase['nota']?>"
			<?='id="d_nota_' . $purchase['id'] . '"'?>
			onchange="updateNota('<?=$purchase['id']?>','d_nota_')" />
                </td>
		<td class="fatturati">
                    <input type="checkbox"
			<?='id="d_inv_' . $purchase['id'] . '"'?>
			onclick="changeInvoiced('<?=$purchase['id']?>','d_inv_')"
			<?=($purchase['invoiced']) ? 'checked="checked"' : ''?> />
                </td>
                
                <?php } ?>
                
            </tr>
		
                    <?php                    
                    }
                }    
            }
            ?>
            
	</tbody>
    </table>
</div>

<?php

    
    
    

    
/* ***** VISTA ACQUISTI ADMIN ***** */
} elseif ($area === 'admin') {
    // acquisti superadmin
    $admin_purchases = $report_obj->getAllPurchase();
}

?>
<script>

$(function () {

    $('.table-purchases').tablesorter({
        theme: 'greyT81',
        sortList: [[0, 1]],
        // hidden filter input/selects will resize the columns, so try to minimize the change
        widthFixed: true,
        // initialize zebra striping and filter widgets
        widgets: ["filter"],
        // headers: { 5: { sorter: false, filter: false } },
        widgetOptions: {
            filter_columnFilters: false,
            // extra css class applied to the table row containing the filters & the inputs within that row
            filter_cssFilter: '',
            // If there are child rows in the table (rows with class name from "cssChildRow" option)
            // and this option is true and a match is found anywhere in the child row, then it will make that row
            // visible; default is false
            filter_childRows: false,
            // if true, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
            // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus
            filter_hideFilters: true,
            // Set this option to false to make the searches case sensitive
            filter_ignoreCase: true,
            // class added to filtered rows (rows that are not showing); needed by pager plugin
            filter_filteredRow: 'filtered',
            // jQuery selector string of an element used to reset the filters
            filter_reset: '.reset',
            // Delay in milliseconds before the filter widget starts searching; This option prevents searching for
            // every character while typing and should make searching large tables faster.
            filter_searchDelay: 300,
            // Set this option to true to use the filter to find text from the start of the column
            // So typing in "a" will find "albert" but not "frank", both have a's; default is false
            filter_startsWith: false,
            // if false, filters are collapsed initially, but can be revealed by hovering over the grey bar immediately
            // below the header row. Additionally, tabbing through the document will open the filter row when an input gets focus
            filter_hideFilters : true

        }

    });

});

</script>
