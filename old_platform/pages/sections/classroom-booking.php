<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 30-set-2015
 * File: pages/sections/classroom-booking.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_classroom.php';

$classroom_obj = new T81Classroom();

$tutor_id = $_SESSION['tutor']['id'];

$booking_not_confirmed = $classroom_obj->getClassroomBooking($tutor_id, FALSE);
$booking_confirmed = $classroom_obj->getClassroomBooking($tutor_id);

if ($booking_not_confirmed) {?>
<div id="classroom-booking">
    <!-- <div class="alert alert-danger text-center">HAI RICEVUO UNA PRENOTAZIONE</div> -->
    <div id="classroom-booking-not-confirmed" class="panel panel-danger">
        <div class="panel-heading">
            Prenotazioni da confermare
        </div>
        <table class="table table-condensed tablesorter">
            <thead>
                <tr>
                    <th>Provincia</th>
                    <th>Sede</th>
                    <th>Titolo corso</th>
                    <th>data</th>
                    <th>Dettagli</th>
                    <th>Posti liberi</th>
                    <th class="hidden">Posti prenotati</th>
                    <th>Prenotazione</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                
            <?php foreach ($booking_not_confirmed as $booking){
                $start_date = new DateTime($booking['start_date']);
                ?>
                
                <tr data-id_classroom_booking="<?= $booking['id_classroom_booking'] ?>">
                    
                    <td>
                        <?= $booking['province'] ?>
                    </td>
                    <td><?= $booking['location'] ?></td>
                    <td><?= $booking['course_code'] ?></td>
                    <td><?= $start_date->format('d/m/Y') ?></td>
                    <td><?= $booking['note'] ?></td>
                    <td><?= $booking['places_available'] ?></td>
                    <td class="hidden"><input type="number" class="form-control" min="1" 
                               style="width: 50px;" name="booked_places"
                                <?= 'value="' . $booking['booked_places'] . '"'?>></td>
                    <td>
                        <a href="javascript: void(0);" class="from <?= $booking['from_ecommerce'] ? 'ecommerce' : ''?>">
                            <?= $booking['from_ecommerce'] ? $booking['customer_name'] : $booking['business_name']?>
                        </a>
                    </td>
                    <td>
                        <a href="javascript: void(0);" class="confirm">
                            <span class="glyphicon glyphicon-ok"></span>
                        </a>
                        <a href="javascript: void(0);" class="delete">
                            <span class="glyphicon glyphicon-remove"></span>
                        </a>
                    </td>
                </tr>

            <?php } ?>
                
            </tbody>
        </table>
    </div>
</div>
<script>    
    $('#classroom-booking-not-confirmed .from').click(function(){
        var id_classroom_booking = $(this).parents('tr').data('id_classroom_booking');
        $('#simpleModal')
                .modal()
                .find('.modal-content')
                .load('modals/classroom-booked.php?id_classroom_booking=' + id_classroom_booking);
    });
    
    
    $('#classroom-booking-not-confirmed .confirm').click(function(){
        var booking = $(this).parents('tr');
        $.post('manage/classroom.php', {
            op_type: 'confirm_classroom_booking',
            id_classroom_booking: booking.data('id_classroom_booking'),
            booked_places: booking.find('input[name="booked_places"]').val()
        },function(data){
            if (data > 0) {
                bootbox.alert('Prenotazione confermata.', function(){
                    location.reload();
                });
            } else {
                bootbox.alert('Errore. La prenotazione non è stata confermata.');
            }
        });
    });
    
    $('#classroom-booking-not-confirmed .delete').click(function(){
        var booking = $(this).parents('tr');
        $.post('manage/classroom.php', {
            op_type: 'delete_classroom_booking',
            id_classroom_booking: booking.data('id_classroom_booking')
        },function(data){
            if (data > 0) {
                bootbox.alert('Prenotazione cancellata.', function(){
                    location.reload();
                });
            } else {
                bootbox.alert('Errore. La prenotazione non è stata cancellata.');
            }
        });
    });
</script>
<?php }