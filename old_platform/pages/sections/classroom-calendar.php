<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32 ) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_classroom.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';

$classroom_obj = new T81Classroom();
$comp_obj = new T81Company ();

$course_type_id = filter_input(INPUT_GET, 'course_type_id', FILTER_SANITIZE_NUMBER_INT);

$classroom_scheduled = $classroom_obj->getClassroomsAvailable();

if (!$classroom_scheduled) {?>
    
<div id="classroom-scheduled">
    <h3>Non ci sono aule programmate</h3>
</div>

<?php 
    return false;
}

$prov = $comp_obj->getProvinces();
$mesi = array();
$mesi[1] = 'Gennaio';
$mesi[2] = 'Febbraio';
$mesi[3] = 'Marzo';
$mesi[4] = 'Aprile';
$mesi[5] = 'Maggio';
$mesi[6] = 'Gugno';
$mesi[7] = 'Luglio';
$mesi[8] = 'Agosto';
$mesi[9] = 'Settembre';
$mesi[10] = 'Ottobre';
$mesi[11] = 'Novembre';
$mesi[12] = 'Dicembre';
?>
<div id="classroom-scheduled">
    <table class="table table-condensed tablesorter">
        <thead>
            <tr>
                <th>Provincia</th>
                <th>Titolo Corso</th>
                <th>Mese</th>
                <th data-sorter="shortDate" data-date-format="ddmmyyyy">Data</th>
                <th>posti</th>
                <th>Organizzatore</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            
        <?php foreach ($classroom_scheduled as $classroom){ 
            if (!$classroom['places_available']) continue;
            $start_time = new DateTime($classroom['start_time']);
            $end_time = new DateTime($classroom['end_time']);
            if ($classroom['start_date'] != '0000-00-00') {
                $start_date = new DateTime($classroom['start_date']);
                $start_date = $start_date->format('d/m/Y');
            } else {
                $anno = new DateTime('now');
                $start_date = "01/{$classroom['month']}/" . $anno->format('Y');;
            }
            ?>

            <tr data-id_classroom_scheduled="<?= $classroom['id_classroom_scheduled'] ?>"
                data-start_time="<?= $start_time->format('H:i') ?>"
                data-end_time="<?= $end_time->format('H:i') ?>"
                data-location="<?= $classroom['location'] ?>">
                <td><?= $classroom['province'] ?></td>
                <td class="course_code" data-toggle="tooltip" data-html="true" title="<?= nl2br(html_entity_decode($classroom['note'])) ?>">
                    <?= $classroom['course_code'] ?>
                </td>
                <td class=""><?= $mesi[$classroom['month']] ?></td>
                <td class="start_date" <?= $classroom['start_date'] == '0000-00-00' ? ' style="visibility:hidden;"' : '' ?>>
                    <?= $start_date ?>
                </td>
                <td class="places_available"><?= $classroom['places_available'] ?></td>
                <td><?= strtoupper($classroom['business_name']) ?></td>
                <td><button class="btn btn-default btn-xs booking_classroom">Iscrivi</button></td>
            </tr>            
            
        <?php } ?>            
            
        </tbody>
    </table>
</div>
<!-- Modal -->
<div class="modal fade" id="classroomBookingModal" tabindex="-1" role="dialog" aria-labelledby="classroomBookingLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="classroomBookingLabel">Modulo di adesione al corso di formazione IN AULA</h4>
        </div>
        <div class="modal-body">
            <div class="classroom-detail">
                
            </div>
            
            <form class="form-inline">
                <div class="form-group">
                    <label for="booked_places">Iscrivi numero</label>
                    <input type="number" class="form-control" name="booked_places" min="1" value="1" style="width: 75px;">
                    <input type="hidden" name="classroom_scheduled_id">
                </div>
            <form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
            <button type="button" class="btn btn-primary save-modal">Salva</button>
        </div>
    </div>
  </div>
</div>
<script>

    $(function () {

        $('#classroom-scheduled table').tablesorter({
            theme: 'greyT81',
            sortList: [[3, 0]],
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
        
        /* ****** APRE MODAL PRENOTAZIONE AULA ****** */
        $('#classroom-scheduled .booking_classroom').click(function(){
            var selected = $(this).parents('tr');
            $('#classroomBookingModal .classroom-detail')
                .html('<dl class="dl-horizontal">' +
                        '<dt>CORSO:</dt>' +
                        '<dd>' + selected.find('.course_code').text() + '<dd>' +
                        '<dt>Aula di formazione di:</dt>' +
                        '<dd>' + selected.data('location') + '</dd>' +
                        '<dt>data:</dt>' +
                        '<dd>' + selected.find('.start_date').text() + '</dd>' +
                        '<dt>ora inizio:</dt>' +
                        '<dd>' + selected.data('start_time') + '</dd>' +
                        '<dt>ora fine:</dt>' +
                        '<dd>' + selected.data('end_time') + '</dd>' +
                    '</dl>')
                .parents('.modal')
                .modal('show')
                .find('form input[name="booked_places"]')
                .prop('max',selected.find('.places_available').text())
                .siblings('input[name="classroom_scheduled_id"]')
                .val(selected.data('id_classroom_scheduled'));
        });
        
        /* ****** SALVA PRENOTAZIONE AULA ****** */
        $('#classroomBookingModal .save-modal').click(function(){
            $.isLoading({text: "Attendere il completamento ..."});
            $.post('manage/classroom.php',{
                    op_type: 'booking_classroom',
                    classroom_scheduled_id: $('#classroomBookingModal input[name="classroom_scheduled_id"]').val(),
                    booked_places: $('#classroomBookingModal input[name="booked_places"]').val(),
                    reserved_by_user_id: <?= $_SESSION['user']['id'] ?>,
                    reserved_by_tutor_id: <?= $_SESSION['tutor']['id'] ?>
                }, function(data){
                    $.isLoading( "hide" );
                    if (data === "SUCCESS") {
                        alert ("Prenotazione aula effettuata.");
                    } else if (data === "NON_NOTIFIED") {
                        alert ("Errore: la prenotazione è stata effettuata regolarmente, ma c'è stato un errore nell'invio delle email di conferma.");
                    } else if (data === "ERROR") {
                        alert ("Errore: la prenotazione non è stata effettuata. Riprova.");
                    }
                    location.reload();
            });
        });

    });

</script>