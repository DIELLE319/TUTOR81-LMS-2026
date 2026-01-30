<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 26-feb-2015
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

$id_course_type = filter_input(INPUT_GET, 'id_course_type', FILTER_SANITIZE_NUMBER_INT);

require_once BASE_LIBRARY_PATH . 'class_course_type.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
$course_type_obj = new T81CourseType();
$comp_obj = new T81Company ();

$course_type = $course_type_obj->getCourseTypeDetail($id_course_type);
$provinces = $comp_obj->getProvinces();
$subfix = uniqid();
$multipart_params = "upload_type: 'classroom_scheduled_brochure', user_id: '{$_SESSION['user']['id']}', subfix: '$subfix'";
?>
<form id="new-classroom">
    <input type="hidden" name="course_type_id" value="<?= $id_course_type ?>">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3 class="modal-title">Creazione del corso in aula</h3>
    </div>
    <div class="modal-body">
        <div class="container-fluid">
            <div class="row">
                <div class="form-group col-sm-8">
                    <label for="course_code">Titolo del corso:</label>
                    <input type="text" class="form-control" name="course_code" disabled value="<?= $course_type['course_code'] ?>">
                </div>
                <div class="form-group col-sm-4">
                    <label for="duration">Durata:</label>
                    <input type="text" class="form-control" name="duration" disabled value="<?= $course_type['duration'] ?>">
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-12">
                    <label for="course_description">Descrizione:</label>
                    <textarea class="form-control" name="start_date" disabled ><?= $course_type['course_description'] ?></textarea>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-8">
                    <div class="form-group">
                        <label for="location">Sede:</label>
                        <input type="text" class="form-control" name="location" value="<?= $_SESSION['tutor']['city'] ?>">
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="province_id">Provincia:</label>
                        <select class="form-control" name="province_id">

                        <?php foreach ($provinces as $province) { ?>

                            <option value="<?= $province['id'] ?>"<?= $province['id'] == $_SESSION['tutor']['province_id'] ? ' selected' : '' ?>>
                                <?= strtoupper($province['name']) ?>
                            </option>

                        <?php } ?>

                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="start_date">Data inizio corso:</label>
                        <input type="text" class="form-control datepicker" name="start_date">
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="places">Posti:</label>
                        <input type="number" min="0" class="form-control" name="places">
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label for="price">Prezzo:</label>
                        <input type="number" min="0" class="form-control" name="price">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-12">
                    <label for="note">Dettagli del corso (orari, sede, ecc)</label>
                    <textarea class="form-control" name="note" style="resize: vertical;"></textarea>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-8">
                    <label for="contact_name">Responsabile del corso:</label>
                    <input type="text" class="form-control" name="contact_name" value="<?= $_SESSION['user']['name'] . ' ' . $_SESSION['user']['surname'] ?>">
                </div>
                <div class="form-group col-sm-4">
                    <label for="contact_email">Email:</label>
                    <input type="text" class="form-control" name="contact_email" value="<?= $_SESSION['user']['email'] ?>">
                </div>
            </div>
            <div id="filelist">
                <div class="row">
                    <div class="col-sm-12">
                        <label for="contact_name">Aggiungi locandina del corso:</label>
                        <div class="alert alert-warning">
                            Il tuo browser non supporta Flash, Silverlight o HTML5. Non è possibile caricare la locandina del corso 
                        </div>
                    </div>
                </div>
                <div id="upload" class="row hidden">
                    <div id="upload-button" class="col-sm-2">
                        <a class="btn btn-default" id="pickfiles" href="javascript: void(0);"><span class="glyphicon glyphicon-plus"></span></a>
                        <a class="btn btn-danger" id="removefiles" href="javascript: void(0);" style="display:none;"><span class="glyphicon glyphicon-remove"></span></a>
                    </div>
                    <div id="upload-progress" class="col-sm-4">
                        <div class="progress">
                            <div class="progress-bar progress-bar-success progress-bar-striped bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Esci</button>
        <button type="submit" class="btn btn-primary">Pubblica il corso</button>
    </div>
</form>
<script>
    /* ***** INIZIALIZZAZIONE CAMPO DATA ***** */
    $('#new-classroom input[name="start_date"]').datepicker({
        format: "dd/mm/yyyy",
        startDate: '0d',
        todayBtn: "linked",
        language: "it",
        autoclose: true,
        todayHighlight: true
    });
    
    $('#new-classroom').submit(function(e){
        e.preventDefault();
        $.isLoading({text: "Attendere il completamento ..."});
        var course_type_id = $('#new-classroom input[name="course_type_id"]').val();
        var is_valid = true;
        var planned_date = $('#new-classroom input[name="start_date"]').datepicker('getDate');
        var new_classroom = {
            'month': planned_date.getMonth(),
            'location': $('#new-classroom input[name="location"]').val(),
            'province_id': $('#new-classroom select[name="province_id"]').val(),
            'places': $('#new-classroom input[name="places"]').val(),
            'contact_email': $('#new-classroom input[name="contact_email"]').val(),
            'price': $('#new-classroom input[name="price"]').val()
        };
        for (var key in new_classroom) {
            if (!new_classroom[key]) {
                is_valid = false;
                break;
            }
        }
        if (!is_valid) {
            $.isLoading("hide");
            alert('Compilare tutti i campi.');
            return false;
        }
        new_classroom['month'] += 1;
        new_classroom['start_date'] = planned_date.getFullYear() + '-' + new_classroom['month'] + '-' + planned_date.getDate();
        new_classroom['course_type_id'] = course_type_id;
        new_classroom['created_by'] = <?= $_SESSION['user']['id'] ?>;
        new_classroom['tutor_id'] = <?= $_SESSION['tutor']['id'] ?>;
        new_classroom['published'] = 1;
        new_classroom['published_in_ecommerce'] = 1;
        new_classroom['note'] = $('#new-classroom textarea[name="note"]').val();
        new_classroom['contact_name'] = $('#new-classroom input[name="contact_name"]').val();
        $.post('manage/classroom.php',
            {
                op_type: 'new_classroom',
                new_classroom: new_classroom,
                subfix: '<?= $subfix ?>'
            },
            function (data) {
                $.isLoading("hide");
                if (data > 0) {
                    $('#new-classroom').parents('.modal').modal('hide');
                    bootbox.alert('Programmazione corso completata.', function(){
                        location.href = "tutor/classroom/manager";
                    });
                } else {
                    bootbox.alert('Errore: la programmazione non è stata salvata. Verifica i dati e riprova.');
                }
            }
        );
    });
    
    var uploader = new plupload.Uploader({
        runtimes : 'html5,flash,silverlight,html4',

        browse_button : 'pickfiles', // you can pass in id...
        container: document.getElementById('filelist'), // ... or DOM Element itself

        url : "<?= BASE_WEBSITE_PATH . 'manage/upload.php' ?>",

        filters : {
            max_file_size : '5mb',
            mime_types: [
                {title : "application/pdf", extensions : "pdf"}
            ]
        },
        multi_selection: false, 

        multipart_params: <?="{" . $multipart_params . "}"?>,

        // Flash settings
        flash_swf_url : 'js/upload/Moxie.swf',

        // Silverlight settings
        silverlight_xap_url : 'js/upload/js/Moxie.xap',
     
 
        init: {
            PostInit: function() {
                $('#filelist .alert').remove();
                $('#upload').removeClass('hidden');
            },

            FilesAdded: function(up, files) {
                if (files.length > 1) {
                    alert("E' possibile caricare un solo file.");
                } else {
                    plupload.each(files, function(file) {
                        $('#upload').append('<div id="' + file.id + 
                                '" class="col-sm-6 text-right">' + file.name + 
                                ' (' + plupload.formatSize(file.size) + ') <b></b></div>');
                    });
                    uploader.refresh(); // Reposition Flash/Silverlight
                    uploader.start();
                }
            },
            
            FileUploaded: function (up, file, response) {
                
                if (response.response == "SUCCESS"){
                    uploader.destroy();
                    //$('#container').remove();
                    $('#upload')
                            .addClass('loaded')
                            .find('#pickfiles').hide();
                            //.siblings('#removefiles').show();
                } else {
                    alert('Errore, file non caricato, riprova.');
                    document.getElementById(file.id).remove();
                }
                
            },

            UploadProgress: function(up, file) {
                document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
                $('#upload-progress .bar').width(file.percent + "%");
            },

            Error: function(up, err) {
                document.getElementById(err.file.id).remove();
                alert("Errore durante il caricamento del file " + err.file.name + "\n" + err.message);
            }


        }
    });
 
    uploader.init();
    
</script>