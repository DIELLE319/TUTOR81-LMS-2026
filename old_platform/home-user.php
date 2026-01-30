<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 30-lug-2015
 * File: home-user.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_user.php';
require_once BASE_LIBRARY_PATH . 'class_permissions.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';

$user_obj = new T81User();
$perm_obj = new Permissions();
$learn_obj = new T81LearningProject();
$course_obj = new iWDCourse();

$user_id = $_SESSION['user']['id'];

$user = $user_obj->getDetail($user_id);
$role = $perm_obj->getUserRole($user_id);
$company = $user_obj->getUserCompany($user_id);

$in_progress = $user_obj->getUserLearningActivity($user_id, -1, 0);
$completed = $user_obj->getUserLearningActivity($user_id, 1, 1);
$max_gap = 90; //giorni in cui si può rivedere il corso terminato
?>


<h1>
    <?= ucwords(strtolower("{$_SESSION['user']['name']} {$_SESSION['user']['surname']}")) ?> 
    <small> <?= $role['short_desc_role'] ?></small>
</h1>

<hr>

<div id="user_courses_in_progres" class="user-activity">
    <h3>Corsi in attività</h3>

    <?php if ($in_progress) { ?>

        <table class="table tablesorter">
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>Titolo corso</th>
                    <th>Programmato/Avviato</th>
                    <th>da terminare entro</th>
                    <th style="width: 200px">Stato avanzamento</th>
                    <th style="width: 200px">Risposte</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>

                <?php
                foreach ($in_progress as $user_activity) {

                    $course = $learn_obj->getCourseDetailFromLearningProject($user_activity['learning_project_id']);

                    $num_lo = $learn_obj->get_num_learning_objects($user_activity['learning_project_id']);
                    $num_exe_lo = $learn_obj->get_num_lo_executed($user_activity['id']);
                    if ($num_exe_lo != 0) {
                        $execution_percentage = round($num_exe_lo / $num_lo * 100);
                    } else {
                        $execution_percentage = 0;
                    }

                    if ($execution_percentage >= 100) $execution_percentage = 99;
                    
                    $today = new DateTime('now', new DateTimeZone('Europe/Rome'));
                    $programmato = isset($user_activity['starting_from']) && $user_activity['starting_from'] != '0000-00-00' ?
                            DateTime::createFromFormat('Y-m-d', $user_activity['starting_from'], new DateTimeZone('Europe/Rome')) :
                            DateTime::createFromFormat('Y-m-d H:i:s', $user_activity['creation_date'], new DateTimeZone('Europe/Rome'));


                    if (isset($user_activity['finish_within']) && $user_activity['finish_within'] != '0000-00-00') {
                        $termine = DateTime::createFromFormat('Y-m-d', $user_activity['finish_within'], new DateTimeZone('Europe/Rome'));
                    } else {
                        $termine = clone $programmato;
                        $termine->add(new DateInterval("P{$course['max_execution_time']}D"));
                    }

                    $avviato = isset($user_activity['start_date_time']) && $user_activity['start_date_time'] != '0000-00-00 00:00:00' ?
                            DateTime::createFromFormat('Y-m-d H:i:s', $user_activity['start_date_time'], new DateTimeZone('Europe/Rome')) : false;
                    
                    $attivo = (int)$programmato->diff($today)->format('%R%a') >= 0;
                    $scaduto = (int)$today->diff($termine)->format('%R%a') < 0;
                    ?>

                    <tr class="<?= $avviato ? 'started' : '' ?>"
                        data-license_id="<?= $user_activity['id'] ?>"
                        data-license_pwd="<?= $user_activity['learning_project_pwd'] ?>"
                        data-learning_event_id="<?= $user_activity['learning_event_id'] ?>"
                        data-learning_project_id="<?= $user_activity['learning_project_id'] ?>">
                        <td>
                            <?= !$scaduto && $attivo ?
                            '<a class="play-course btn btn-default">AVVIA CORSO <i class="icon-play"></i></a>'
                            :
                            (!$attivo ?
                            '<b>Corso non attivo</b>
                            <a href="#" tabindex="0" class="pop_info" data-toggle="popover" 
                            data-original-title="Cosa significa?" data-container="body" 
                            data-content="Il corso è stato programmato per iniziare in una data futura. 
                            Se lo desideri rivolgiti al tuo referente per richiedere di anticiparlo.">
                            <i class="icon-question-sign"></i></a>'
                            : 
                            '<b>Corso scaduto</b>
                            <a href="#" tabindex="0" class="pop_info" data-toggle="popover" 
                            data-original-title="Cosa significa?" data-container="body" 
                            data-content="Il termine programmato per completare il corso è 
                            stato superato. Rivolgiti al tuo referente per richiedere una proroga.">
                            <i class="icon-question-sign"></i></a>'); ?>
                        </td>
                        <td class="title"><?= strtoupper(substr($user_activity['title'], strpos($user_activity['title'], ' - ') + 3)) ?></td>
                        <td><?= $avviato ? $avviato->format('d/m/Y') : $programmato->format('d/m/Y') ?></td>
                        <td><?= $termine->format('d/m/Y') ?></td>
                        <td>

        <?php if ($execution_percentage > 0) { ?>

                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped active" style="width: <?= $execution_percentage ?>%;"><?= $execution_percentage > 0 ? "$execution_percentage %" : '' ?></div>
                                </div>

        <?php } ?>

                        </td>
                        <td class="questions_bar">

        <?php if ($execution_percentage > 0) { ?>

                                <img src="img/loading_gif.gif"> in caricamento ...

        <?php } ?>

                        </td>
                        <td>
                            <div class="btn-group action">
                                <button class="btn btn-default btn-xs dropdown-toggle"
                                        data-toggle="dropdown">
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <!-- dropdown menu links -->
                                    <!-- <li <?= $execution_percentage == 0 ? ' class="disabled"' : '' ?>>
                                        <a class="sessioni" tabindex="-1" href="javascript: void(0)">Registro
                                            Sessioni</a>
                                    </li> -->
                                    <li><a class="description-course" tabindex="-1"
                                           href="javascript: void(0)">Descrizione corso</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>


    <?php } ?>

            </tbody>

        </table>
<?php } else { ?>

        <p>Nessun corso attivo</p>

<?php } ?>

    <form id="play-course" action="<?= URL_PLAYER . 'lib/ec-login.php' ?>" method="POST">
        <input type="hidden" name="username" value="<?= $user['username'] ?>">
        <input type="hidden" name="password" value="">
        <input type="hidden" name="mode" value="learning">
        <input type="hidden" name="tos_authorized" value="on">
    </form>

</div>
<!-- /#user_courses_in_progress -->

<hr>

<div id="user_courses_completed" class="user-activity">
    <h3>Corsi completati</h3>

<?php if ($completed) { ?>
        <table class="table tablesorter">
            <thead>
                <tr>
                    <th>&nbsp;</th>
                    <th>Titolo corso</th>
                    <th>Avviato</th>
                    <th>Terminato</th>
                    <th style="width: 200px">Risposte</th>
                    
                <?php if (false) {//($company['test_in_the_presence'] !== "NO") {?>

                    <th>Test</th>

                <?php } ?>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>

    <?php
    foreach ($completed as $user_activity) {

        $course = $learn_obj->getCourseDetailFromLearningProject($user_activity['learning_project_id']);
        //$course_need_test_in_the_presence = $course_obj->courseHasCustomCategory($course['id'], 17);
        $course_need_test_in_the_presence = false; //per impedire all'utente di caricare il proprio test
                
        $avviato = isset($user_activity['start_date_time']) && $user_activity['start_date_time'] != '0000-00-00 00:00:00' ?
                DateTime::createFromFormat('Y-m-d H:i:s', $user_activity['start_date_time'], new DateTimeZone('Europe/Rome')) : false;


        $terminato = isset($user_activity['end_date_time']) && $user_activity['end_date_time'] != '0000-00-00 00:00:00' ?
                DateTime::createFromFormat('Y-m-d H:i:s', $user_activity['end_date_time'], new DateTimeZone('Europe/Rome')) : false;

        $now = new DateTime("now", new DateTimeZone('Europe/Rome'));
        $gap_time = $terminato->diff($now);
        
        $need_test_in_the_presence = $course_need_test_in_the_presence == true
                        && $company['test_in_the_presence'] !== "NO" 
                        && !file_exists(BASE_MEDIA_PATH . "test_in_presenza/test_licenza_{$user_activity['id']}.pdf"); // necessario upload test in presenza
        
        ?>

                    <tr data-license_id="<?= $user_activity['id'] ?>"
                        data-license_pwd="<?= $user_activity['learning_project_pwd'] ?>"
                        data-learning_event_id="<?= $user_activity['learning_event_id'] ?>"
                        data-learning_project_id="<?= $user_activity['learning_project_id'] ?>">
                        <td><?= $gap_time->days <= $max_gap ? '<a class="replay-course btn btn-default">RIVEDI CORSO <i class="icon-play"></i></a>' : 'Non disponibile' ?></td>
                        <td><?= strtoupper(substr($user_activity['title'], strpos($user_activity['title'], ' - ') + 3)) ?></td>
                        <td><?= $avviato ? $avviato->format('d/m/Y') : 'non registrato' ?></td>
                        <td><?= $terminato ? $terminato->format('d/m/Y') : 'non registrato' ?></td>
                        <td class="questions_bar"><img src="img/loading_gif.gif"> in
                            caricamento ...</td>
                        
                        <?php if (false){//($company['test_in_the_presence'] !== "NO") {?>
                    
                        <td>
                            
                        <?php if (!$course_need_test_in_the_presence) {?>
                            
                            &nbsp;
                               
                        <?php } else {
                            if (file_exists(BASE_MEDIA_PATH . "test_in_presenza/test_licenza_{$user_activity['id']}.pdf")){ ?>
                        
                            <a href="media/test_in_presenza/test_licenza_<?=$user_activity['id']?>.pdf" target="_blank">
                                <img src="img/course_archive.png">
                            </a>
                        
                            <?php } else { ?>
                            
                            <a href="javascript: void(0);" tabindex="0" class="pop_info test upload" data-toggle="popover" 
                            data-original-title="Uploda test in presenza" data-container="body" data-placement="left"
                            data-content="Carica sul server il Test in Presenza da te effettuato.">
                            <i class="icon-exclamation-sign"></i></a>
                            
                            <?php }
                        } ?>
                            
                        </td>
                    
                    <?php } ?>
                        <td>
                            <div class="btn-group action">
                                <button class="btn btn-default btn-xs dropdown-toggle<?=$need_test_in_the_presence ? ' btn-danger' : ''?>"
                                        data-toggle="dropdown">
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu pull-right">
                                    <!-- dropdown menu links -->
                                <?php if ($course_need_test_in_the_presence == true && $company['test_in_the_presence'] !== "NO") {?>
                                        
                                    <li>
                                        <a class="test upload" 
                                           tabindex="-1" href="javascript: void(0)" target="_blank">Upload Test in presenza</a>
                                    </li>

                                <?php } 
                                $file_path = BASE_MEDIA_PATH . "attestati/attestato_licenza_{$user_activity['id']}.pdf"; ?>
                                    
                                    <li><a class="attestato<?= !file_exists($file_path) ? ' genera' : '' ?>"
                                            tabindex="-1" href="javascript: void(0)">Attestato</a></li>
                                    <!-- <li><a class="sessioni" tabindex="-1" href="javascript: void(0)">Registro
                                            Sessioni</a></li> -->
                                    <li><a class="description-course" tabindex="-1"
                                           href="javascript: void(0)">Descrizione corso</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>
    <?php } else { ?>

        <p>Nessun corso completato</p>

    <?php } ?>

</div>
<!-- /#user_courses_completed -->


<!-- ---- MODALS ---- -->

<div id="mySimpleModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>


<div id="myLargeModal" class="modal fade bs-example-modal-lg">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        </div>
    </div>
</div>

<div id="avvioModal" class="modal fade" tabindex="-1" role="dialog"
     aria-labelledby="avvioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                        aria-hidden="true">×</button>
                <h3 id="avvioModalLabel">Avvia corso <small>Titolo corso</small></h3>
            </div>
            <div class="modal-body">
                <p>Selezionare le caselle opportunamente e cliccare su AVVIA CORSO</p>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="in_working_time" checked> Sessione effettuata in orario di lavoro
                        <a href="#" tabindex="0" class="pop_info"
                           data-toggle="popover" data-original-title="Orario di lavoro" data-container="body"
                           data-content="Selezionare se la sessione di studio viene effettuata in orario di lavoro">
                            <i class="icon-info-sign"></i>
                        </a>
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="disclaimer" checked> Accettazione termini e condizioni d'uso
                        <a href="#" tabindex="0" class="pop_info"
                           data-toggle="popover" data-original-title="Termini e condizioni d'uso" data-container="body"
                           data-content="Per procedere con il corso è necessario selezionare questa casella.
                           Per visualizzare i termini e le condizioni d'uso del servizio cliccare sul link
                           a sinistra in fondo alla pagina.">
                            <i class="icon-info-sign"></i>
                        </a>
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="autocert" checked> Autocertificazione esecuzione in prima persona
                        <a href="#" tabindex="0" class="pop_info"
                           data-toggle="popover" data-original-title="Autocertificazione esecuzione" data-container="body"
                           data-content="Io sottoscritto certifico di eseguire il corso in prima persona.">
                            <i class="icon-info-sign"></i>
                        </a>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Annulla</button>
                <button class="btn btn-primary start_course">AVVIA CORSO</button>
            </div>
        </div>
    </div>
</div>
    
<div id="uploaderModal" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                        aria-hidden="true">&times;</button>
                <h3>Carica test di fine corso</h3>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <a href="javascript: void(0)" class="btn" data-dismiss="modal">Chiudi</a>
            </div>
        </div>
    </div>
</div>
    


<!-- ---- END MODALS ---- -->

<script>
    $(function () {

        $('.user-activity > table').tablesorter({
            theme: 'greyT81',
            dateFormat: "ddmmyyyy", // set the default date format

            sortList: [[3, 0]],
            // hidden filter input/selects will resize the columns, so try to minimize the change
            widthFixed: true,
        });

        $('.pop_info').popover({trigger: "hover"});

        /* ************** AVVIA IL CORSO ****************** */
        $('.user-activity > table .play-course').click(function () {
            var password = $(this).parents('tr').data('license_pwd');
            var title = $(this).parent().next().text();
            $('#play-course input[name="password"]').val(password);
            $('#avvioModal h3 > small').html(title).parents('#avvioModal').modal('show');
        });

        $('#avvioModal .start_course').click(function () {
            if ($('#avvioModal input[name="disclaimer"]')[0].checked && $('#avvioModal input[name="autocert"]')[0].checked) {
                if ($('#avvioModal input[name="in_working_time"]')[0].checked) {
                    $('#play-course').append('<input type="hidden" name="in_working_time" value="1">').submit();
                } else {
                    $('#play-course').submit();
                }
            } else
                alert("Per procedere è necessario accettare i termini e le condizioni d'uso del servizio e autocertificare l'esecuzione del corso in prima persona.");
        })

        $('.user-activity > table .replay-course').click(function () {
            var password = $(this).parents('tr').data('license_pwd');
            $('#play-course input[name="password"]').val(password).siblings('input[name="mode"]').val('replica').parents('form').submit();
        });

        $('#user_courses_in_progres > table > tbody > tr.started')
                .add('#user_courses_completed > table > tbody > tr').each(function () {
            var row = $(this);
            $.post('manage/course.php',
                    {
                        op_type: "get_count_questions",
                        learning_event_id: $(this).data("learning_event_id"),
                        learning_project_id: $(this).data("learning_project_id")
                    }, function (data) {
                var questions = JSON.parse(data);
                var total_answers = parseInt(questions["correct"]) + parseInt(questions["wrong"]);
                row.find('td.questions_bar')
                        .empty()
                        .append('<div class="progress">' +
                                '<div class="progress-bar progress-bar-success" style="width: ' + questions["correct"] / total_answers * 100 + '%;">' + (questions["correct"] > 0 ? questions["correct"] : '') + '</div>' +
                                '<div class="progress-bar progress-bar-danger" style="width: ' + questions["wrong"] / total_answers * 100 + '%;">' + (questions["wrong"] > 0 ? questions["wrong"] : '') + '</div>' +
                                '</div>');
            });
        });


        /* ************** MOSTRA ATTESTATO ****************** */
        $('#user_courses_completed tbody tr li:not(.disabled) a.attestato').click(function (e) {
            var license_id = $(this).parents('tr').data('license_id');
            if ($(this).hasClass('genera')) {
                window.open('lib/genera.php?course_id=' + license_id, '_blank');
            } else {
                window.open('manage/render_document.php?doc_type=attestato_elearning&license_id=' + license_id, '_blank');
            }
        });


        /* ************** MOSTRA DESCRIZIONE CORSO ****************** */
        $('.user-activity tbody tr li:not(.disabled) a.description-course').click(function (e) {
            var learn_id = $(this).parents('tr').data('learning_project_id');
            $('#simpleModal')
                .modal()
                .find('.modal-content')
                .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                .load("modals/show-course.php", {learn_id: learn_id, setting: 'description'});
        });


        /* ************** MOSTRA SESSIONI CORSO ****************** */
        $('.user-activity tbody tr li:not(.disabled) a.sessioni').click(function (e) {
            var learning_event_id = $(this).parents('tr').data('learning_event_id');
            $('#myLargeModal')
                .modal()
                .find('.modal-content')
                .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                .load('modals/sessions.php?learning_event_id='+learning_event_id)
        });
        
<?php if ($company['test_in_the_presence'] !== "NO") {?>

    
    /* *********************** UPLOAD *************************/
    
    $('#user_courses_completed table').on('click','.test.upload',function(e){
        e.preventDefault();
        var license_id = $(this).parents('tr').data('license_id');
            $('#uploaderModal .modal-body')
                    .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                    .load('modals/uploader.php?upload_type=test_in_the_presence&learning_project_user_id=' + license_id)
                    .parent('#uploaderModal')
                    .modal();
    });
    
    /* ************** SVUOTA UPLOADERMODAL ****************** */
        $('#uploaderModal').on('hidden', function (e) {
            if($(this).find('#filelist').hasClass('loaded')){
                location.reload();
            }
            $(this).find(".modal-body").empty();
        });
        
    
<?php } ?>

    });

</script>