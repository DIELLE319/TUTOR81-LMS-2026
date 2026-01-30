<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 15-giu-2015
 * File: modals/uploader.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

$upload_type = filter_input(INPUT_GET, 'upload_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$learning_project_user_id = filter_input(INPUT_GET, 'learning_project_user_id', FILTER_SANITIZE_NUMBER_INT);

$multipart_params = false;
if ($upload_type === "test_in_the_presence" && $learning_project_user_id > 0){
    $multipart_params = "upload_type: 'test_in_the_presence', learning_project_user_id: $learning_project_user_id";
}

?>
<div id="filelist" data-license_id="<?=$learning_project_user_id?>">Il tuo browser non supporta Flash, Silverlight o HTML5</div>
<br />
 
<div id="container">
    <p>Clicca sul pulsante per caricare il file. E' possibile caricare file in formato pdf di dimensione massima 5MB.</p>
    <a class="btn btn-default" id="pickfiles" href="javascript:;">Seleziona file</a>
</div>
 
<br />
<div id="upload-progress" class="progress">
  <div class="progress-bar progress-bar-success progress-bar-striped bar" style="width: 0%">
  </div>
</div>
 
 
<script type="text/javascript">
 
    var uploader = new plupload.Uploader({
        runtimes : 'html5,flash,silverlight,html4',

        browse_button : 'pickfiles', // you can pass in id...
        container: document.getElementById('container'), // ... or DOM Element itself

        url : 'manage/upload.php',

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
                document.getElementById('filelist').innerHTML = '';

            },

            FilesAdded: function(up, files) {
                if (files.length > 1) {
                    alert("E' possibile caricare un solo file.");
                } else {
                    plupload.each(files, function(file) {
                        document.getElementById('filelist').innerHTML += '<div id="' + file.id + '">' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
                    });
                    uploader.refresh(); // Reposition Flash/Silverlight
                    uploader.start();
                }
            },
            
            FileUploaded: function (up, file, response) {
                
                if (response.response == "SUCCESS"){
                    uploader.destroy();
                    $('#container').remove();
                    $('#filelist').empty().addClass('loaded').append('<h3>File caricato con successo.</h3>');
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