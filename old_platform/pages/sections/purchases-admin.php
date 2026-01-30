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

$invoiced = filter_input(INPUT_GET, 'invoiced', FILTER_VALIDATE_BOOLEAN) ? : false;

$admin_purchases = $report_obj->getAllPurchase();
?>
    
<div id="purchases-admin" class="<?= $invoiced ? 'purchases-invoiced' : 'purchases-not-invoiced'; ?>">
    
    
    <form class="form-inline">
        
        <?php if ($invoiced) { ?>
        
        <div class="form-group search hidden-print form-group-sm">
            <div id="search-invoice-date" class="input-group">
                <input type="search" class="form-control search-query search-invoice-date" 
                       data-filter-column="0" placeholder="Data fattura ..." 
                       data-toggle="popover" data-placement="bottom" 
                       data-trigger="hover" data-delay='{"show":500,"hide":500}'
                       title="Filtra acquisti per data di fatturazione">
                <div class="input-group-addon"><span class="glyphicon glyphicon-search"></span></div>
            </div>
        </div>
        
        <?php } ?>
        
        <div class="form-group search hidden-print form-group-sm">
            <div id="search-purchaser" class="input-group">
                <input type="search" class="form-control search-query search-purchaser" 
                       data-filter-column="<?= $invoiced ? '2' : '1'; ?>" placeholder="Cliente ..."
                       data-toggle="popover" data-placement="bottom" 
                       data-trigger="hover" data-delay='{"show":500,"hide":500}'
                       title="Filtra acquisti per cliente">
                <div class="input-group-addon"><span class="glyphicon glyphicon-search"></span></div>
            </div>
        </div>
        
        <div class="pull-right">
        
        <?php if ($invoiced) { ?>
            
            <button type="button" class="btn btn-default export-selected">Esporta selezionati</button>
        
        <?php } else { ?>
        
            <button type="button" class="btn btn-default invoice-selected">Crea fattura</button>
        
        <?php } ?>
        
        </div>
    </form>
    
    
    
    
    <table class="table-purchases table table-hover table-condensed">
	<thead>
            <tr>
                <?= $invoiced ? '<th data-sorter="shortDate" data-date-format="ddmmyyyy">Data di fatturazione</th>' : '' ?>
		<th class="purchases-id">N° Ord.</th>
                <th>Cliente</th>
                <th>Ruolo</th>
                <th data-sorter="shortDate" data-date-format="ddmmyyyy" class="purchases-data">data di acquisto</th>
		<th class="purchase-cost-centre">Centro di costo</th>
		<th class="purchases-corso">Nome Corso</th>
		<th class="purchases-qta">QTA</th>
		<th class="purchases-nota">Nota</th>				
                <th class="check-fatturati">Seleziona</th>
            </tr>
	</thead>
        <tbody>
	
    <?php
    if (isset($admin_purchases)){
        foreach ($admin_purchases as $purchase){
            if ($invoiced != (bool)$purchase['invoiced']) continue;
            $creation_date = $report_obj->formatDate($purchase['creation_date'], 'd/m/Y');
            if ($invoiced) $invoice_date = $report_obj->formatDate($purchase['invoice_date'], 'd/m/Y'); ?>
            
            <tr class="<?= $purchase['is_tutor'] ? ' warning' : ($purchase['is_partner'] ? ' success' : '') ?>"
                 data-tutor_purchase_id="<?=$purchase['id']?>">
                <?= $invoiced ? "<td>$invoice_date</td>" : ''; ?>
		<td><?=$purchase['id']?></td>
                <td><?= $purchase['business_name'] ?></td>
                <td><?= $purchase['is_tutor'] ? 'Ente Formativo' : ($purchase['is_partner'] ? 'Partner' : 'Azienda')?></td>
		<td><?=$creation_date?></td>
		<td><?=$purchase['cost_centre']?></td>
		<td><?=strtoupper(substr($purchase['title'], strpos($purchase['title'], ' - ') + 3))?></td>
		<td class="purchases-qta"><?=$purchase['qta']?></td>			
		<td class="purchases-nota">
                    <input type="text" value="<?=$purchase['nota']?>"
			<?='id="d_nota_' . $purchase['id'] . '"'?>
			onchange="updateNota('<?=$purchase['id']?>','d_nota_')" />
                </td>
		<td class="select">
                    <input type="checkbox">
                </td>                
            </tr>
		
        <?php }
    } ?>
            
	</tbody>
    </table>
</div>
<script>
    
    $('#purchases-admin [data-toggle="popover"]').popover();

$(function () {

    var $table = $('.table-purchases').tablesorter({
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
            filter_startsWith: false

        }

    });
    
    $("#purchases-admin .search-query").bind('search keyup', function (e) {
        var $t = $(this);
        col = $t.data('filter-column');
        filter = [];

        filter[col] = $t.val();
        $('.table-purchases').trigger('search', [ filter ]);
        return false;
    });
    
    
    /*
     * fattura gli acquisti selezionati
     */
    $('#purchases-admin .invoice-selected').click(function(){
        var selected = $('#purchases-admin .table-purchases td.select > input:checked').parents('tr');
        var invoices = selected.map(function(){
                                        return $(this).data('tutor_purchase_id');
                                    }).get();
        bootbox.confirm('<p class="text-center">HAI SELEZIONATO GLI ORDINI<br>'
                + invoices.join(' - ')
                + '<br>CONFERMA ESPORTAZIONE IN FATTURA',
            function(result){
                if (result) {
                    $.post('manage/purchase.php',
                    {
                        op_type: 'save_invoice_date',
                        invoice_date: Date(),
                        invoices: invoices
                    }, function(data){
                        if (data > 0) {
                            selected.remove();
                        }
                    })
                }
            });
    });
    
});

</script>
