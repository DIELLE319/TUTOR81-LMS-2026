<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------/
 * 18-ago-2015
 * File: pages/ticket.php 
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 * Gestione dei ticket di assistenza
 */
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_ticket.php';

$ticket_obj = new Tutor81Ticket();

$closed = filter_input(INPUT_GET, 'closed', FILTER_VALIDATE_BOOLEAN);

$tickets = $ticket_obj->getListTickets($closed);
?>
<div id="tickets">
    <div>
        <div class="panel-group" id="ticket_accordion">

            <?php
            if ($tickets) {
                foreach ($tickets as $ticket) {
                    $creation_date_ticket = DateTime::createFromFormat('Y-m-d H:i:s', $ticket['creation_date_ticket']);
                    $threads = $ticket_obj->getTicketThreads($ticket['id_ticket']);
                    ?>

                    <div class="panel panel-default single-ticket" data-ticket_id="<?= $ticket['id_ticket'] ?>" data-staff_id="<?= $ticket['staff_id'] ?>">
                        <div class="panel-heading">
                            <h4 class="panel-title">
                                <a data-toggle="collapse" data-parent="#ticket_accordion"
                                   href="#collapse_ticket_<?= $ticket['id_ticket'] ?>"> <?= $ticket['code'] ?> 
                                </a>
                            <small>
                                &nbsp; - 
                                <?= $creation_date_ticket->format('d/m/Y H:m:s') ?> &nbsp; - &nbsp;
                                <?php if(!empty($ticket['user_id'])){
                                    echo ucwords("{$ticket['surname']} {$ticket['name']}") . "&nbsp; - &nbsp;"
                                        . strtolower($ticket['user_email']) . "&nbsp; - &nbsp;"
                                        . strtoupper($ticket['business_name']) . "&nbsp; - &nbsp;"
                                        . strtoupper(substr($ticket['title'], strpos($ticket['title'], ' - ') + 3)) . ' (id:' . $ticket['learning_project_id'] . ')';
                                } ?>
                            </small>
                            </h4>
                        </div>
                        <div <?= 'id="collapse_ticket_' . $ticket['id_ticket'] .'"' ?> class="panel-collapse collapse">

                            <div class="panel-body">
                                <ul class="list-group">
                                    
                                    <?php foreach ($threads as $thread) { ?>

                                        <li class="list-group-item">
                                            <div class="list-group-item-heading">
                                                <small>da <em><?= $thread['staff_id'] > 0 ? 'STAFF' : 'UTENTE' ?></em>
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

                                <?php if ($closed) { ?>

                                    <div class="clearfix">
                                        <div class="button pull-right">
                                            <button class="btn btn-warning open-ticket">RIAPRI TICKET</button>
                                        </div>
                                    </div>

                                <?php } else { ?>

                                    <div class="new-thread clearfix">
                                        <div class="button pull-right">
                                            <button class="btn btn-default close-new-thread" style="display:none;">ANNULLA</button>
                                            <button class="btn btn-primary send-new-thread" style="display:none;">INVIA</button>
                                            <button class="btn btn-default presend">INVIA UN MESSAGGIO</button>
                                            <button class="btn btn-success close-ticket">TICKET RISOLTO</button>
                                        </div>
                                    </div>

                                <?php } ?>

                            </div>
                        </div>
                    </div>

                <?php }
            }
            ?>

        </div><!-- /#ticket_accordion -->

    </div>

    <!-- cerca ticket per codice -->
    <div>
        <form class="form-inline">
            <label for="ticket_code" class="control-label">Cerca un ticket
                <input type="text" id="ticket_code" class="form-control" name="ticket_code" placeholder="es. ticket.516511d568">
            </label>
            <button id="search-ticket" class="btn btn-default">CERCA</button>
        </form>
    </div>
    <br>
    <div id="anonymous_ticket" class="panel-group" style="display: none;">
        <div class="panel panel-default single-ticket" id="anonymous_ticket_accordion" data-ticket_id="" data-staff_id="">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <a data-toggle="collapse" data-parent="#anonymous_ticket_accordion" href="#collapse_ticket_anonymous"></a>
                </h4>
            </div>
            <div id="collapse_ticket_anonymous" class="panel-collapse collapse">
                <div class="panel-body">
                    <ul class="list-group">

                    </ul>

                    <div class="clearfix">
                        <div class="button pull-right">
                            <button class="btn btn-warning open-ticket">RIAPRI TICKET</button>
                        </div>
                    </div>

                    <div class="new-thread clearfix">
                        <div class="button pull-right">
                            <button class="btn btn-default close-new-thread" style="display:none;">ANNULLA</button>
                            <button class="btn btn-default send-new-thread" style="display:none;">INVIA</button>
                            <button class="btn btn-default presend">INVIA UN MESSAGGIO</button>
                            <button class="btn btn-success close-ticket">TICKET RISOLTO</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /#tickets -->

<script>
    

    $('body').on('click', '#tickets .presend', function (e) {
        e.preventDefault();
        $(this).hide()
                .siblings()
                .show()
                .parents('.new-thread')
                .prepend('<form class="form-horizontal">' +
                        '<div class="form-group">' +
                        '<label for="object" class="col-sm-3 control-label">Oggetto*</label>' +
                        '<div class="col-sm-9 controls">' +
                        '<input type="text" class="form-control" name="object" placeholder="Oggetto del ticket" required>' +
                        '</div>' +
                        '</div>' +
                        '<div class="form-group">' +
                        '<label for="body" class="col-sm-3 control-label">Messaggio*</label>' +
                        '<div class="col-sm-9 controls">' +
                        '<textarea class="form-control" name="body" rows="5" required></textarea>' +
                        '</div>' +
                        '</div>' +
                        '</form>');
    });

    $('body').on('click', '#tickets .close-new-thread', function (e) {
        e.preventDefault();
        $(this)
                .hide()
                .siblings('button.send-new-thread')
                .hide()
                .siblings('.presend')
                .show()
                .parent()
                .siblings('form')
                .remove();
    });


    $('body').on('click', '#tickets .send-new-thread', function (e) {
        e.preventDefault();
        $(this).prop("disabled", true);
        var form = $(this).parent().siblings('form');
        var ticket_id = $(this).parents('.single-ticket').data("ticket_id");
        var staff_id = $(this).parents('.single-ticket').data("staff_id");
        var object = form.find('input[name="object"]').val();
        var body = form.find("textarea").val().replace(new RegExp("\n", 'g'), "<br />");
        if (object == "" || object == "undefined"
                || body == "" || body == "undefined") {
            alert("Compilare tutti i campi con l'asterisco (obbligatori).");
            $(this).prop("disabled", false);
        } else {
            if (staff_id == 0) {
                $.post("manage/ticket.php", {
                    op_type: 'assign_ticket',
                    id_ticket: ticket_id,
                    staff_id: <?= $_SESSION['user']['id'] ?>
                });
            }
            $.post("manage/ticket.php", {
                op_type: 'send_new_thread',
                ticket_id: ticket_id,
                staff_id: staff_id != 0 ? staff_id : <?= $_SESSION['user']['id'] ?>,
                user_id: 0,
                thread_type: 'R',
                object: object,
                body: body,
                source: 'Web'
            }, function (data) {
                if (data > 0) {
                    alert("Messaggio inviato.");
                    location.reload();
                } else {
                    alert("Invio del messaggio non risucito. Verificare i dati e riprovare");
                    $(this).prop("disabled", false);
                }
            }
            );
        }
    });

    $('body').on('click', '#search-ticket', function (e) {
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
                $('#anonymous_ticket .panel-heading h4 a').text(code);
                $('#anonymous_ticket_accordion').attr('data-ticket_id', ticket_threads.ticket.id_ticket);
                for (var i = 0; i < threads.length; i++) {
                    var from = threads[i].staff_id > 0 ? 'STAFF' : 'UTENTE';
                    $('#anonymous_ticket .panel-body ul.list-group')
                            .append('<li class="list-group-item">' +
                                    '<div class="list-group-item-heading">' +
                                    '<small>da <em>' + from + '</em> ' + threads[i].creation_date_thread + ': </small> <strong>' + threads[i].object + '</strong></div>' +
                                    '<blockquote><p class="list-group-item-text">' + threads[i].body + '</p></blockquote>' +
                                    '</li>');
                }
                $('#anonymous_ticket').show();
                if (ticket_threads.ticket.closed == 0)
                    $('#anonymous_ticket .open-ticket').hide();
                else
                    $('#anonymous_ticket .new-thread').hide();

            }
            $('#search-ticket').prop("disabled", false);
        });
    });

    $('body').on('click', '#tickets .close-ticket', function (e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop("disabled", true);
        if (confirm("Il ticket verrà chiuso definitivamente. Procedo?")) {
            $.post("manage/ticket.php", {
                op_type: 'close_open_ticket',
                id_ticket: btn.parents('.single-ticket').data('ticket_id'),
                closed: 1
            }, function (closed) {
                if (closed) {
                    alert("Ticket chiuso");
                    location.reload();
                } else {
                    alert('Errore nella chiusura del ticket');
                    btn.prop("disabled", false);
                }
            }
            );
        } else {
            btn.prop("disabled", false);
        }
    });

    $('body').on('click', '#tickets .open-ticket', function (e) {
        e.preventDefault();
        var btn = $(this);
        btn.prop("disabled", true);
        if (confirm("Il ticket verrà riaperto. Procedo?")) {
            $.post("manage/ticket.php", {
                op_type: 'close_open_ticket',
                id_ticket: btn.parents('.single-ticket').data('ticket_id'),
                closed: 0
            }, function (open) {
                if (open) {
                    alert("Ticket aperto");
                    location.reload();
                } else {
                    alert('Errore nella riapertura del ticket');
                    btn.prop("disabled", false);
                }
            }
            );
        } else {
            btn.prop("disabled", false);
        }
    });

</script>