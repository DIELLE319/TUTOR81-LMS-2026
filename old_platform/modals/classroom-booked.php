<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 01-ott-2015
 * File: modals/classroom-booked.php
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

$id_classroom_booking = filter_input(INPUT_GET, 'id_classroom_booking', FILTER_SANITIZE_NUMBER_INT);

require_once BASE_LIBRARY_PATH . 'class_classroom.php';
$classroom_obj = new T81Classroom();

$classroom_booked = $classroom_obj->getClassroomBooked($id_classroom_booking);

if ($classroom_booked['tutor_id'] != $_SESSION['tutor']['id']) {
    require_once BASE_ROOT_PATH . '403.php';
    return false; 
}

$start_date = new DateTime($classroom_booked['start_date']);

if ($classroom_booked['from_ecommerce']){
?>

<!-- BOOKING FROM ECOMMERCE -->
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3 class="modal-title">Prenotazione al corso in aula</h3>
</div>
<div class="modal-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-8">
                <div class="form-group">
                    <label for="course_code">Titolo del corso:</label>
                    <input type="text" class="form-control" name="course_code" disabled value="<?= $classroom_booked['course_code'] ?>">
                </div>
                <div class="form-group">
                    <label for="location">Sede del corso:</label>
                    <input type="text" class="form-control" name="location" disabled value="<?= $classroom_booked['location'] ?>">
                </div>
                <div class="form-group">
                    <label for="start_date">Data inizio corso:</label>
                    <input type="text" class="form-control" name="start_date" disabled value="<?= $start_date->format('d/m/Y') ?>">
                </div>
                <div class="form-group">
                    <label for="contact">Referente:</label>
                    <input type="text" class="form-control" name="contact" disabled value="<?= $classroom_booked['user_name'] . ' ' . $classroom_booked['user_surname'] ?>">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="business_name">Organizzato da:</label>
                    <input type="text" class="form-control" name="business_name" disabled value="<?= $classroom_booked['business_name'] ?>">
                </div>
                <div class="form-group">
                    <label for="province">Provincia:</label>
                    <input type="text" class="form-control" name="province" disabled value="<?= $classroom_booked['province'] ?>">
                </div>
                <div class="form-group">
                    <label for="price">Prezzo:</label>
                    <input type="text" class="form-control" name="price" disabled value="<?= $classroom_booked['price'] ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="text" class="form-control" name="email" disabled value="<?= $classroom_booked['email'] ?>">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="note">Dettagli del corso (orari, sede, ecc)</label>
                    <textarea class="form-control" name="note" disabled style="resize: none;"><?= $classroom_booked['note'] ?></textarea>
                </div>
            </div>
        </div>
        <h4>Lasciaci i tuoi dati la prenotazione viene inviata all'Organizzatore del corso che ti contatterà al più presto</h4>
        <div class="row">
            <div class="col-sm-8 col-sm-height col-top">
                <div class="form-group">
                    <label for="Name">Nome*</label>
                    <input type="text" class="form-control" name="customer_name" disabled value="<?= $classroom_booked['customer_name'] ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="text" class="form-control" name="customer_email" disabled value="<?= $classroom_booked['customer_email'] ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Telefono</label>
                    <input type="text" class="form-control" name="customer_phone" disabled value="<?= $classroom_booked['customer_phone'] ?>">
                </div>                    
            </div>
            <div class="alert col-sm-4 col-sm-height col-full-height grayT81-bg">
                <div class="inside text-center">
                    <h4>Serve aiuto?</h4>
                    <br>
                    <p>La prenotazione non costituisce obbligatoriamente impegno di acquisto.</p>
                    <br>
                    <p>Se hai altre richieste scrivi a <a href="mailto:info@tutor81.com">info@tutor81.com</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Esci</button>
</div>

<?php } else { 
    require_once BASE_LIBRARY_PATH . 'class_company.php';
    $company_obj = new T81Company();
    $reserved_tutor = $company_obj->getBusinessDetail($classroom_booked['reserved_by_tutor_id']);
    $reserved_user = $company_obj->getDetail($classroom_booked['reserved_by_user_id']);
    ?>

<!-- BOOKING FROM PLATFORM -->
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3 class="modal-title">Prenotazione al corso in aula</h3>
</div>
<div class="modal-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-8">
                <div class="form-group">
                    <label for="course_code">Titolo del corso:</label>
                    <input type="text" class="form-control" name="course_code" disabled value="<?= $classroom_booked['course_code'] ?>">
                </div>
                <div class="form-group">
                    <label for="location">Sede del corso:</label>
                    <input type="text" class="form-control" name="location" disabled value="<?= $classroom_booked['location'] ?>">
                </div>
                <div class="form-group">
                    <label for="start_date">Data inizio corso:</label>
                    <input type="text" class="form-control" name="start_date" disabled value="<?= $start_date->format('d/m/Y') ?>">
                </div>
                <div class="form-group">
                    <label for="contact">Referente:</label>
                    <input type="text" class="form-control" name="contact" disabled value="<?= $classroom_booked['user_name'] . ' ' . $classroom_booked['user_surname'] ?>">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="business_name">Organizzato da:</label>
                    <input type="text" class="form-control" name="business_name" disabled value="<?= $classroom_booked['business_name'] ?>">
                </div>
                <div class="form-group">
                    <label for="province">Provincia:</label>
                    <input type="text" class="form-control" name="province" disabled value="<?= $classroom_booked['province'] ?>">
                </div>
                <div class="form-group">
                    <label for="price">Prezzo:</label>
                    <input type="text" class="form-control" name="price" disabled value="<?= $classroom_booked['price'] ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="text" class="form-control" name="email" disabled value="<?= $classroom_booked['email'] ?>">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="note">Dettagli del corso (orari, sede, ecc)</label>
                    <textarea class="form-control" name="note" disabled style="resize: none;"><?= $classroom_booked['note'] ?></textarea>
                </div>
            </div>
        </div>
        <h4>Lasciaci i tuoi dati la prenotazione viene inviata all'Organizzatore del corso che ti contatterà al più presto</h4>
        <div class="row">
            <div class="col-sm-8 col-sm-height col-top">
                <div class="form-group">
                    <label for="reserved_by_user">Nome</label>
                    <input type="text" class="form-control" name="reserved_by_user" disabled value="<?= $reserved_user['name'] . ' ' . $reserved_user['surname'] ?>">
                </div>
                <div class="form-group">
                    <label for="reserved_by_tutor">Ente Formativo</label>
                    <input type="text" class="form-control" name="reserved_by_tutor" disabled value="<?= $reserved_tutor['business_name'] ?>">
                </div>
                <div class="form-group">
                    <label for="email">email</label>
                    <input type="text" class="form-control" name="email" disabled value="<?= $reserved_user['email'] ?>">
                </div>
                <div class="form-group">
                    <label for="booked_places">Posti prenotati</label>
                    <input type="text" class="form-control" name="booked_places" disabled value="<?= $classroom_booked['booked_places'] ?>">
                </div>
            </div>
            <div class="alert col-sm-4 col-sm-height col-full-height grayT81-bg">
                <div class="inside text-center">
                    <h4>Serve aiuto?</h4>
                    <br>
                    <p>La prenotazione non costituisce obbligatoriamente impegno di acquisto.</p>
                    <br>
                    <p>Se hai altre richieste scrivi a <a href="mailto:info@tutor81.com">info@tutor81.com</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Esci</button>
</div>

<?php }