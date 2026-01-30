<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 31-lug-2015
 * File: pages/help.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_ticket.php';
require_once BASE_LIBRARY_PATH . 'class_user.php';

$ticket_obj = new Tutor81Ticket();
$user_obj = new T81User();

$user = $user_obj->getDetail($_SESSION['user']['id']);

$topics = $ticket_obj->getTicketTopics();

$tickets = $ticket_obj->getTicketByUserid($user['id']);
?>
<div id="workspace">
    <div class="row">
        <div class="col-lg-6">

            <h3>Che problema hai?</h3>
            <div class="panel-group" id="faq_accordion">

                <?php foreach ($topics as $topic) { ?>

                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a role="button" class="accordion-toggle" data-toggle="collapse" data-parent="#faq_accordion"
                                    href="#collapse_topic_<?= $topic['id_ticket_topic'] ?>"><?= $topic['topic'] ?></a>
                            </h4>
                        </div>
                        <div<?= ' id="collapse_topic_' . $topic['id_ticket_topic'] . '"' ?> class="panel-collapse collapse">
                            <div class="panel-body">
                                <?= $topic['notes'] ?>
                            </div>
                        </div>
                    </div>

                <?php } ?>

            </div>

            <div class="button">
                <button class="btn btn-default"
                        onclick="$('#faq .collapse.in').collapse('toggle');
                                                        $('#box_message').show()">HO ANCORA BISOGNO DI AIUTO</button>
            </div>
            <br>
            <div id="box_message" style="display: none">
                <form id="new-ticket" class="form-horizontal" action="POST">
                    <h3>Apri un ticket di assistenza</h3>
                    
                    <div class="form-group">
                        <label class="col-xs-3 control-label">Tipo</label>
                        <div class="col-xs-9 radio">
                            
                        <label>
                            <input type="radio" name="type_help" id="type_help_tech" value="T" checked> Tecnica
                        </label>
                            <span>&nbsp;</span>
                        <label>
                            <input type="radio" name="type_help" id="type_help_did" value="D"> Didattica
                        </label>
                    </div>
                    </div>
                    <div class="form-group">
                        <label for="ticket_topic" class="col-xs-3 control-label">Problema</label>
                        <div class="col-xs-9">
                            <select class="form-control controls" id="ticket_topic" name="ticket_topic">
                                <option value="0">non definito, altro</option>

                                <?php foreach ($topics as $topic) { ?>

                                    <option value="<?= $topic['id_ticket_topic'] ?>"><?= $topic['topic'] ?></option>

                                <?php } ?>

                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="user_email" class="col-xs-3 control-label">Email*</label>
                        <div class="col-xs-9 controls">
                            <input type="email" id="user_email" name="user_email"
                               value="<?= $user['email'] ?>" class="form-control "
                               placeholder="inserisci la tua email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="object" class="col-xs-3 control-label">Oggetto*</label>
                        <div class="col-xs-9 controls">
                            <input type="text" id="object" class="form-control"
                                   name="object" placeholder="Oggetto del ticket" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="body" class="col-xs-3 control-label">Messaggio*</label>
                        <div class="col-xs-9 controls">
                            <textarea id="body" class="form-control" name="body" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="text-right">
                        <button id="send-new-ticket" class="btn btn-primary">Invia</button>
                    </div>
                </form>
            </div>


        </div>

        <div id="ticket" class="col-lg-6">
            <h3>I tuoi Ticket</h3>

            <!-- Ticket esistenti -->
            <div class="panel-group" id="ticket_accordion">

                <?php
                if ($tickets) {
                    foreach ($tickets as $ticket) {
                        $threads = $ticket_obj->getTicketThreads($ticket['id_ticket']);
                        ?>

                        <div class="panel panel-default" data-ticket_id="<?= $ticket['id_ticket'] ?>" data-closed="<?= $ticket['closed'] ?>">
                            <div class="panel-heading">
                                <a class="accordion-toggle" data-toggle="collapse" data-parent="#ticket_accordion"
                                   href="#collapse_ticket_<?= $ticket['id_ticket'] ?>"> <?= $ticket['code'] ?> 

                    <?php if ($ticket['closed']) { ?><span class="pull-right">risolto</span><?php } ?>

                                </a>
                            </div>
                            <div<?= ' id="collapse_ticket_' . $ticket['id_ticket'] .'"' ?> class="panel-collapse collapse">
                                <div class="panel-body">

                                    <ul class="list-group">
                                        
                    <?php foreach ($threads as $thread) { ?>

                                            <li class="list-group-item">
                                                <div class="list-group-item-heading">
                                                    <small>
                                                        da <em><?= $thread['staff_id'] > 0 ? 'STAFF' : 'UTENTE' ?></em>
                                                        <?= date('\i\l d/m/Y \a\l\l\e \o\r\e H.i.s', strtotime($thread['creation_date_thread'])) ?>:
                                                    </small>
                                                    &nbsp; 
                                                    <strong><?= html_entity_decode($thread['object']) ?></strong>
                                                </div>
                                                <blockquote>
                                                    <p class="list-group-item-text"><?= html_entity_decode($thread['body']) ?></p>
                                                </blockquote>
                                            </li>

        <?php } ?>

                                    </ul>
                                    <div class="new-thread clearfix">
                                        <form class="form-horizontal" style="display:hide;">

                                        </form>
                                        <div class="text-right">
                                            <button class="btn btn-default close-new-thread" style="display:none;">ANNULLA</button>
                                            <button class="btn btn-primary send-new-thread" style="display:none;">INVIA</button>
                                            <button class="btn btn-warning presend">INVIA UN MESSAGGIO</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php }
                }
                ?>

            </div><!-- /#ticket_accordion -->

            <!-- cerca ticket per codice -->
            <div>
                <form class="form-inline">
                    <div class="form-group">
                        <label for="ticket_code" class="control-label">Cerca un ticket </label>
                        <input type="text" class="form-control" id="ticket_code" name="ticket_code" placeholder="es. ticket.516511d568">
                        <button id="search-ticket" class="btn btn-primary">CERCA</button>
                    </div>
                </form>
                <br>
                <div id="anonymous_ticket" style="display: none;">
                    <div class="panel-group" id="anonymous_ticket_accordion">
                        <div class="panel panel-default" data-ticket_id="" data-closed="">
                            <div class="panel-heading">
                                <a class="accordion-toggle" data-toggle="collapse" data-parent="#anonymous_ticket_accordion" href="#collapse_ticket_anonymous"></a>
                            </div>
                            <div id="collapse_ticket_anonymous" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <ul class="list-group">

                                    </ul>
                                    <div class="new-thread clearfix">
                                        <form class="form-horizontal" style="display:hide;">

                                        </form>
                                        <div class="button pull-right">
                                            <button class="btn btn-default close-new-thread" style="display:none;">ANNULLA</button>
                                            <button class="btn btn-default send-new-thread" style="display:none;">INVIA</button>
                                            <button class="btn btn-default presend">INVIA UN MESSAGGIO</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {

        $('#send-new-ticket').click(function (e) {
            $(this).prop("disabled", true);
            e.preventDefault();
            var email_reg_exp = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-]{2,})+\.)+([a-zA-Z0-9]{2,})+$/;
            var user_id = <?= $user['id'] ?>;
            var user_email = $("#user_email").val();
            var type_help = $('input[name="type_help"]:checked').val();
            var ticket_topic_id = $('#ticket_topic').val();
            var object = $('#object').val();
            var body = $("#body").val().replace(new RegExp("\n", 'g'), "<br />");
            if (user_email == "" || user_email == "undefined"
                    || object == "" || object == "undefined"
                    || body == "" || body == "undefined") {
                alert("Compilare tutti i campi con l'asterisco (obbligatori).");
                $(this).prop("disabled", false);
            } else if (!email_reg_exp.test(user_email)) {
                alert('Inserire un indirizzo email corretto.');
                $("#user_email").select();
                $(this).prop("disabled", false);
            } else {
                $.post("manage/ticket.php", {
                    op_type: 'send_new_ticket',
                    user_id: user_id,
                    type_help: type_help,
                    ticket_topic_id: ticket_topic_id,
                    user_email: user_email,
                    object: object,
                    body: body,
                    source: 'Web'
                }, function (data) {
                    if (data > 0) {
                        alert("Ticket inviato.");
                        location.reload();
                    } else {
                        alert("Invio del ticket non risucito. Verificare i dati e riprovare");
                        $(this).prop("disabled", false);
                    }
                });
            }
        });

        $('button.presend').click(function (e) {
            e.preventDefault();
            $(this)
                    .hide()
                    .siblings()
                    .show()
                    .parents('.new-thread')
                    .prepend('<form class="form-horizontal">' +
                            '<div class="form-group">' +
                            '<label for="object" class="col-xs-3 control-label">Oggetto*</label>' +
                            '<div class="col-xs-9 controls">' +
                            '<input type="text" class="form-control" name="object" placeholder="Oggetto del ticket" required>' +
                            '</div>' +
                            '</div>' +
                            '<div class="form-group">' +
                            '<label for="body" class="col-xs-3 control-label">Messaggio*</label>' +
                            '<div class="col-xs-9 controls">' +
                            '<textarea class="form-control" name="body" rows="5" required></textarea>' +
                            '</div>' +
                            '</div>' +
                            '</form>');
        });

        $('button.close-new-thread').click(function (e) {
            e.preventDefault();
            $(this)
                    .hide()
                    .siblings('button.send-new-thread')
                    .hide()
                    .siblings('button.presend')
                    .show()
                    .parent()
                    .siblings('form')
                    .remove();
        });


        $('button.send-new-thread').click(function (e) {
            e.preventDefault();
            $(this).prop("disabled", true);
            var form = $(this).parent().siblings('form');
            var ticket_id = $(this).parents('.panel').data("ticket_id");
            var closed = $(this).parents('.panel').data("closed");
            var user_id = <?= $_SESSION['user']['id'] ?>;
            var object = form.find('input[name="object"]').val();
            var body = form.find("textarea").val().replace(new RegExp("\n", 'g'), "<br />");
            if (object == "" || object == "undefined"
                    || body == "" || body == "undefined") {
                alert("Compilare tutti i campi con l'asterisco (obbligatori).");
                $(this).prop("disabled", false);
            } else {
                $.post("manage/ticket.php", {
                    op_type: 'send_new_thread',
                    ticket_id: ticket_id,
                    staff_id: 0,
                    user_id: user_id,
                    thread_type: 'R',
                    object: object,
                    body: body,
                    source: 'Web',
                    closed: closed
                }, function (data) {
                    if (data > 0) {
                        alert("Messaggio inviato.");
                        location.reload();
                    } else {
                        alert("Invio del messaggio non risucito. Verificare i dati e riprovare");
                        $(this).prop("disabled", false);
                    }
                });
            }
        });

        $('#search-ticket').click(function (e) {
            e.preventDefault();
            $(this).prop("disabled", true);
            $('#anonymous_ticket .panel-body ul.list-group').empty();
            var code = $('#ticket_code').val();
            $.post("manage/ticket.php", {
                op_type: 'get_ticket_by_code',
                code: code
            }, function (data) {
                if (data == 0) {
                    alert("Ticket non trovato");
                } else {
                    var ticket_threads = jQuery.parseJSON(data);
                    var threads = ticket_threads.threads;
                    if (ticket_threads.ticket.closed == 1)
                        code = code + '<span class="pull-right">risolto</span>';
                    $('#anonymous_ticket .panel-heading a').html(code);
                    $('#anonymous_ticket .panel').attr('data-ticket_id', ticket_threads.ticket.id_ticket).attr('data-closed', ticket_threads.ticket.closed);
                    for (var i = 0; i < threads.length; i++) {
                        var from = threads[i].staff_id > 0 ? 'STAFF' : 'UTENTE';
                        $('#anonymous_ticket .panel-body ul.list-group')
                                .append('<li class="list-group-item">' +
                                        '<h4 class="list-group-item-heading">' +
                                        '<small>da <em>' + from + '</em> ' + threads[i].creation_date_thread + ': </small> ' + threads[i].object + '</h4>' +
                                        '<p class="list-group-item-text">' + threads[i].body + '</p>' +
                                        '</li>');
                    }
                    $('#anonymous_ticket').show()

                }
                $('#search-ticket').prop("disabled", false);
            });
        });

    });
</script>