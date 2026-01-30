<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 03-nov-2015
 * File: home-admin.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';
if ($_SESSION['user']['role'] != 1000) {
    require_once '403.php';
    exit();
}
require_once 'lib/class_company.php';
$comp_obj = new T81Company();

$tutors = $comp_obj->getBusinessTutor();
$users_have_sessions = $comp_obj->getUsersHavingSessions();
?>
<div id="home-member" class="home dashboard container-fluid">
    <div class="row">
        <div class="col-sm-3 col-fixed col-menu">
            <div class="col-menu-title">
                <div class="alert alert-info text-center">
                    <span class="glyphicons display white" style="display: table-cell;"></span>
                    <h4 style="margin: 0; display: table-cell; width: 100%;">
                        ENTI FORMATIVI</h4>
                </div>
            </div>
            <div id="companies" class="panel panel-default main-menu">
                <div class="panel-heading main-menu-heading">
                    Seleziona
                    <a href="javascript: void(0);" class="createTutorModal pull-right">
                        <span class="glyphicon glyphicon-plus"></span> aggiungi
                    </a>
                </div>
                <div id="companies-list" class="main-menu-body">
                    <table class="table table-striped table-condensed">
                        <tbody>

                    <?php foreach ($tutors as $company){
                        $delete = false;
                        $users = $comp_obj->getAllUsersCompanyByID($company['id']);
                        if (!$users) {
                            $companies = $comp_obj->getCompanyByTutorCompany($company['id']);
                            if (!$companies) {
                                $purchases = $comp_obj->getPurchaseByCompany($company['id']);
                                if (!$purchases) $delete = true;
                                
                            }
                        }?>

                            <tr data-company_id="<?= $company['id'] ?>">
                                <td><?= strtoupper($company['business_name']) ?></td>
                                <td class="action text-right" style="width: 40px;">
                                    <?= $delete ? '<a href="javascript: void(0)" class="delete"><span class="glyphicon glyphicon-remove red"></span></a>' : '' ?>
                                    <a href="javascript: void(0)" class="edit"><span class="glyphicon glyphicon-pencil"></span></a>
                                    
                                </td>
                            </tr>

                    <?php } ?>
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div id="home-container" class="col-sm-7 col-sm-offset-3">
            <h2 class="text-center" style="margin-top: 0;">Benvenuto Amministratore:<br>
                <strong><?= strtoupper($_SESSION['user']['name'] . ' ' . $_SESSION['user']['surname'])?></strong></h2>
            <div class="panel-group" id="home-accordion" role="tablist" aria-multiselectable="true">
                <div class="panel panel-primary">
                    <div id="employee" class="planner-header panel-heading">
                        GESTIONE UTENTI PIATTAFORMA
                        <a class="collapse-control" role="button" data-toggle="collapse" 
                           data-parent="#home-accordion" href="#collapse-employees" 
                           aria-expanded="<?= $section === 'employees' ? 'true' : 'false' ?>" aria-controls="collapse-employees">
                        </a>
                        <form class="form-inline pull-right" style="margin-top: -5px; margin-right: 10px;">
                            <div class="form-group search hidden-print form-group-sm">
                                <div id="search-employee" class="input-group">
                                    <input type="text" class="form-control search-query" name="search" placeholder="Cerca..."
                                            data-toggle="popover" data-placement="top" 
                                            data-trigger="hover" data-delay='{"show":500,"hide":500}'
                                            title="Inserisci le prime lettere di nome, cognome, nome utente, email o codice fiscale">
                                    <div class="input-group-addon"><span class="glyphicon glyphicon-search"></span></div>
                                </div>
                            </div>
                        </form>
                        
                    </div>
                    <div id="collapse-employees" class="panel-collapse collapse <?= $section === 'employees' ? 'in' : '' ?>" role="tabpanel">
                        <div id="single-user" class="panel-body">
                            Ricerca un utente                        
                        </div>
                    </div>
                </div>
                <div id="purchases" class="panel panel-primary">
                    <div class="panel-heading"><span class="glyphicon glyphicon-euro"></span> 
                        ACQUISTI
                        &nbsp;&nbsp;&nbsp;
                        <a href="javascript: void(0)" class="not-invoiced white">
                            <span class="glyphicon glyphicon-unchecked"></span>&nbsp;Non Fatturati</a>
                        &nbsp;&nbsp;
                        <a href="javascript: void(0)" class="invoiced white">
                            <span class="glyphicon glyphicon-check"></span>&nbsp;Fatturati</a>
                        <a class="collapse-control" role="button" data-toggle="collapse" 
                           data-parent="#home-accordion" href="#collapse-purchases" 
                           aria-expanded="<?= $section === 'purchases' ? 'true' : 'false' ?>" aria-controls="collapse-purchases">
                        </a>
                    </div>
                    <div id="collapse-purchases" class="panel-collapse collapse <?= $section === 'purchases' ? 'in' : '' ?>" role="tabpanel">
                        <div class="panel-body">
                        </div>
                    </div>
                </div>
                <div id="monitor" class="panel panel-info">
                    <div class="panel-heading"><span class="glyphicon glyphicon-signal"></span> 
                        UTENTI ONLINE
                        <a class="collapse-control" role="button" data-toggle="collapse" 
                           data-parent="#home-accordion" href="#collapse-monitor" 
                           aria-expanded="<?= $section === 'monitor' ? 'true' : 'false' ?>" aria-controls="collapse-monitor">
                        </a>
                    </div>
                    <div id="collapse-monitor" class="panel-collapse collapse <?= $section === 'monitor' ? 'in' : '' ?>" role="tabpanel">
                        <div class="panel-body">
                        </div>
                    </div>
                </div>
                <div id="sessions" class="panel panel-info">
                    <div class="panel-heading"><span class="glyphicon glyphicon-refresh"></span> 
                        SESSIONI
                        <a class="collapse-control" role="button" data-toggle="collapse" 
                           data-parent="#home-accordion" href="#collapse-sessions" 
                           aria-expanded="<?= $section === 'sessions' ? 'true' : 'false' ?>" aria-controls="collapse-sessions">
                        </a>
                    </div>
                    <div id="collapse-sessions" class="panel-collapse collapse <?= $section === 'sessions' ? 'in' : '' ?>" role="tabpanel">
                        <div class="panel-body">

                        <?php if ($users_have_sessions) { ?>
                            
                            <div class="form-inline">
                                <input type="text" class="form-control" name="user_having_session" list="user-having-session" autocomplete="on">
                                <!--[if lt IE 9]><select id="user-having-session"><![endif]-->
                                <!--[if !IE]<!--><datalist id="user-having-session"><!--<![endif]-->
                                    
                            <?php foreach ($users_have_sessions as $user) { ?>

                                <option data-user_id="<?= $user['id'] ?>" value="<?= strtoupper("{$user['surname']} {$user['name']}") ?>">
                                    <?= strtoupper("{$user['surname']} {$user['name']}") ?>
                                </option>
                            
                            <?php } ?>
                            
                                <!--[if !IE]<!--></datalist><!--<![endif]-->
                                <!--[if lt IE 9]></select><![endif]-->
                            </div>
                            
                        <?php } ?>
                            
                            <div class="user-sessions"></div>

                        </div>
                    </div>
                </div>
                <div id="ticket" class="panel panel-warning">
                    <div class="panel-heading"><span class="glyphicon glyphicon-warning-sign"></span> 
                        TICKET ASSISTENZA
                        &nbsp;&nbsp;&nbsp;
                        <a href="javascript: void(0)" class="open white">
                            <span class="glyphicon glyphicon-folder-open"></span>&nbsp;Aperti</a>
                        &nbsp;&nbsp;
                        <a href="javascript: void(0)" class="closed white">
                            <span class="glyphicon glyphicon-folder-close"></span>&nbsp;Chiusi</a>
                        <a class="collapse-control" role="button" data-toggle="collapse" 
                           data-parent="#home-accordion" href="#collapse-ticket" 
                           aria-expanded="<?= $section === 'ticket' ? 'true' : 'false' ?>" aria-controls="collapse-ticket">
                        </a>
                    </div>
                    <div id="collapse-ticket" class="panel-collapse collapse <?= $section === 'ticket' ? 'in' : '' ?>" role="tabpanel">
                        <div class="panel-body">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group form-group-lg">
                <a  href="tutor/classroom/manager" class="btn btn-success btn-block btn-lg" style="white-space: normal;">
                    <span class="glyphicons bank white" style="vertical-align: top; margin-left: 24px;"></span>
                    <span>FORMAZIONE IN AULA</span>
                </a>
            </div>
            <br>
            <div id="home-graphs">
                <div class="graph-progress">
                    <h4 class="text-center">Stato corsi e-learning
                        <small>
                            <a href="javascript: void(0)">
                                <span class="glyphicon glyphicon-refresh"></span>
                            </a>
                        </small>
                    </h4>
                    <div class="graph" style="min-height: 300px;">
                    </div>
                </div>
            </div>
            <br>
            <br>
            <div style="padding: 5px; background-color: #F5F5F6; border-radius: 4px;">
                <h5 class="text-center"><strong>Di cosa hai bisogno?</strong></h5>
                <ul style="list-style: inherit; margin-left: 20px;">
                    <li>
                        Iscrivere un utente al corso
                    </li>
                    <li>
                        Spedire le licenze di un corso
                    </li>
                    <li>
                        Non ricordo la password
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CREA ENTE FORMATIVO -->
<div id="createTutorModal" class="modal fade" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        </div>
    </div>
    
</div>

<script> 
$(function(){
    
    fitMainMenu();
    
    /**
     * Ricerca con autocompletamento degli utenti
     * é possibile ricercare in base a:
     * Nome, Cognome, Username, Email o Codice Fiscale
     */
    $("#search-employee .search-query").autocomplete({
        source: "lib/employee_search.php",
        minLength: 2,
        select: function(event, ui) {
            var user_id = ui.item.id;
            if(user_id > 0) {
                $('#single-user')
                    .html('<img src="img/loading_gif.gif"><p>caricamento in corso ...</p>')
                    .load("pages/sections/employee-detail.php", {user_id: user_id});
                $('#employee [aria-expanded="false"]').click();
            }
        },
 
        html: true, // optional (jquery.ui.autocomplete.html.js required)
 
      // optional (if other layers overlap autocomplete list)
        open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 1000);
        }
    });
    
    function sessionSelection(selected) {
        var user_id = $('#user-having-session').find('option').filter(function ()
                        {
                            return this.value == selected;
                        }).data('user_id');
        if (user_id) {
            $('#sessions .user-sessions')
                .html('<img class="preloader" src="img/preloader-snake-blue.gif">')
                .load('report/sessions.php?user_id=' + user_id);
        }            
    }
    
    if (!Modernizr.input.list) {
        var options = $('#user-having-session').children();
        var controls = $('input[name="user_having_session"]').parent();
        $('input[name="user_having_session"]').remove();
        if (!(controls.children('select').length > 0)) {
            $('#user-having-session').remove();
            $('<select id="user-having-session" class="form-control">' +
                    '<option data-user_id="0" value="">Seleziona un utente</option>' +
                    '</select>').prependTo(controls).append(options);
        }
        $('body').on('change', '#user-having-session', function (e) {
            sessionSelection($(this).val());
        });
    } else {
        $('body').on('input', 'input[name="user_having_session"]', function (e) {
            sessionSelection($(this).val());
        });
    }
    
    
    $('#purchases .panel-body').load("pages/sections/purchases-admin.php");
    $('#purchases').on('click', '.not-invoiced, .invoiced', function(){
        var invoiced = $(this).hasClass('invoiced');
        $('#purchases .panel-body').load('pages/sections/purchases-admin.php?invoiced=' + invoiced);
        $('#purchases [aria-expanded="false"]').click();
    });
    $('#monitor .panel-body').load("pages/monitor.php", {tutor_id: <?= $_SESSION['tutor']['id'] ?>, area: '<?= $area ?>'});
    $('#ticket .panel-body').load("pages/sections/ticket.php");
    $('#ticket').on('click', '.open, .closed', function(){
        var closed = $(this).hasClass('closed');
        $('#ticket .panel-body').load('pages/sections/ticket.php?closed=' + closed);
        $('#ticket [aria-expanded="false"]').click();
    });
    //clearInterval(online);
//
//    online = setInterval(function() {
//        $('#monitor .panel-body').load("pages/monitor.php", {tutor_id: <?php//= $_SESSION['tutor']['id'] ?>//, area: '<?php//= $area ?>//'});
//    }, 10000);
//
    $('.graph-progress .graph').load('graphs/tutor-total-progress-bar.php');

    //$('#feedback .panel-body').load('report/feedback.php?is_tutor=true&company_id=<?= $_SESSION['tutor']['id'] ?>');
    
    $('.graph-progress h3 a').click(function(){
        $('.graph-progress .graph')
            .html('<img class="preloader" src="img/preloader-snake-blue.gif">')
            .load('graphs/tutor-total-progress-bar.php');
    });
    
    $('#sessions select').change(function(){
        $('#sessions .user-sessions')
            .html('<img class="preloader" src="img/preloader-snake-blue.gif">')
            .load('report/sessions.php?user_id=' + $(this).val());
    });

    $('#companies td.action > a').click(function(e){
       e.stopPropagation(); 
    });
    
    /* ******** MODAL CREA NUOVO ENTE FORMATIVO ********** */
    $('.createTutorModal').click(function(){
       $('#createTutorModal').modal().find('.modal-content').load('modals/new-tutor.php');
    });
    
    /* link alla pagina dell'azienda */
    $('#companies tbody > tr').click(function(){
        location.href = 'tutor/home?tutor_id=' + $(this).data('company_id');
    });
    
    /* Apre la modal per la modifica dell'azienda */
    $('#companies tbody > tr .action .edit').click(function(){
        var company_id = $(this).parents('tr').data('company_id');
        $('#simpleModal').modal()
                .find('.modal-content')
                .html('<img src="img/loading_gif.gif" />')
                .load('modals/edit-company.php?company_id=' + company_id);
    });
    
    /* Elimina l'azienda */
    $('#companies tbody > tr .action .delete').click(function(){
        var company_id = $(this).parents('tr').data('company_id');
        var company_name = $(this).parents('tr').children().first().text();
        var deleted = false;
        if (confirm("vuoi eliminare l'azienda " + company_name + "?")) { 
            deleted = deleteCompany(company_id);
        }
        if (deleted) location.reload();
    }); 
    
    $(window).resize(function(){fitMainMenu();});

});
</script>