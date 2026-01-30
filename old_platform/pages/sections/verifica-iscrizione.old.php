<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'check_session.php';

if ($_SESSION['user']['role'] != 1000 && $_SESSION['user']['role'] != 1 && $_SESSION['user']['role'] != 32) {
    require_once BASE_ROOT_PATH . '403.php';
    return false;
}

require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

$comp_obj = new T81Company();
$learn_obj = new T81LearningProject();

$company_id = $_SESSION['company']['id'];
$selected_learning_project = filter_input(INPUT_POST, 'courses', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
$selected_users = filter_input(INPUT_POST, 'users', FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_ARRAY);
$count_users = count($selected_users);
foreach ($selected_learning_project as $learning_project_id) {
    $purchased = $comp_obj->getPurchaseByCompany($company_id, $learning_project_id);
    $busy = $comp_obj->countSeatBusyLearningProject($company_id, $learning_project_id);
    $free_seat = (isset($purchased[0]) ? $purchased[0]['qta'] : 0) - $busy;
    if ($free_seat < $count_users)
        $to_buy[$learning_project_id] = $count_users - $free_seat;
}
if (isset($to_buy) && count($to_buy) > 0) {
        $cost_centre = $comp_obj->getCostCentre($_SESSION['tutor']['id']);
        ?>

        <div id="buy_license" class="well well-small">
            <h3>Per alcuni corsi non hai abbastanza licenze<br>
                <small>L'acquisto delle licenze mancanti verrà effettuato in automatico. 
                    Se vuoi puoi anche acquistarne in più rispetto al minimo già preimpostato.</small>
            </h3>
            <h4>Numero dipendenti selezionati: <?= $count_users ?></h4>
            <table id="courses-to-buy" class="table" style="margin-bottom: 0;">
                <tbody>
                    
        <?php foreach ($to_buy as $learning_project_id => $qty) { ?>

                    <tr class="form-inline" data-learning_project_id="<?= $learning_project_id ?>">
                        <td>&nbsp;</td>
                        <td> acquista <input type="number" class="form-control input-sm" 
                            <?= ' min="' . $qty . '"' ?><?= ' value="' . $qty . '"' ?> 
                            title="necessarie minimo <?= $qty ?>" style="width: 50px;"> nuove licenze </td>

            <?php if ($cost_centre) { ?>

                        <td> per il centro di costo 
                            <select class="input-sm form-control">

                <?php foreach ($cost_centre as $single_cost_centre) { ?>

                                <option value="<?= $single_cost_centre['id_cost_centre'] ?>"><?= $single_cost_centre['cost_centre'] ?></option>

                <?php } ?>

                            </select>
                        </td>

            <?php } ?>

                    </tr>

        <?php } ?>
                </tbody>
            </table>
        </div>

        <script>
            $('#courses-to-buy tr').each(function () {
                var learn_id = $(this).data('learning_project_id');
                $(this).find('td:first-child').append($('#courses > tr[data-learn_id="' + learn_id + '"] > td.course-title').text());
            });
        </script>

<?php } ?>

    <!-- richiesta codice accreditamento -->
    <div id="set_accreditation_code" class="well well-small" style="display: none;">
        <h3>Inserisci il codice di accreditamento<br>
            <small>Se possiedi un codice di accreditamento per il corso selezionato 
            inseriscilo ora nel campo corrispondente</small></h3>

        <table id="courses-to-be-credited" class="table">
            <tbody>
            <?php foreach ($selected_learning_project as $learning_project_id) {?>

                <tr data-learning_project_id="<?= $learning_project_id ?>">
                    <td></td>
                    <td>codice accreditamento <input type="text" class="input-mini"></td>
                </tr>

            <?php } ?>

            </tbody>
        </table>
    </div>
    <div id="complete-subscription" class="well well-small">
        <h3>Completa l'iscrizione e invia le mail delle licenze<br>
            <small>Con questo passaggio completi l'iscrizione ai corsi dei dipendenti selezionati</small>
        </h3>
        <div class="form-group">
            <div id="send_mail">
                <div class="checkbox">
                <label class="checkbox">
                    <input type="checkbox" checked> Invia una mail al corsista per informarlo dell'assegnazione del corso
                </label>
                </div>
                <div class="checkbox">
                <label class="checkbox">
                    <input type="checkbox"> Invia una mail al corsista contentente i dati 
                    di licenza per l'accesso al corso tramite il sito <em>avviacorso.tutor81.com</em>
                </label>
            </div>
        </div>
        <button type="button" class="btn btn-primary">Completa iscrizione e invia mail</button>
    </div>
</div>
<script>
    $('#complete-subscription').on('click','button',function(){
        if (confirm("Procedo con l'iscrizione ai corsi?")){
            $.isLoading({text: "Attendere il completamento ..."});
            $.ajax({
                type: "POST",
                url: "manage/license.php",
                data: {
                    op_type: "subscribe",
                    tutor_id: <?=$_SESSION['company']['owner_user_id']?>,
                    company_id: <?=$company_id?>,
                    user_id: <?=$_SESSION['user']['id']?>,
                    to_buy: $('#courses-to-buy tbody > tr').length > 0 ?
                            $('#courses-to-buy tbody > tr')
                                .map(function(){
                                    return JSON.stringify({learning_project_id: $(this).data('learning_project_id'),
                                                           qta: $(this).find('input').val(),
                                                           cost_centre: $(this).find('select').val() > 0 ? $(this).find('select').val() : 0
                                                       });
                                }).get() : false,
                    users: $('#enrollment-table tbody > tr.selected')
                            .map(function(){
                                var start = $(this).find('input[name="start"]').datepicker('getDate');
                                start = $.datepicker.formatDate('yy-mm-dd', start);
                                var end = $(this).find('input[name="end"]').datepicker('getDate');
                                end = $.datepicker.formatDate('yy-mm-dd', end);
                                return JSON.stringify({user_id: $(this).data('user_id'),
                                                       start: start,
                                                       end: end,
                                                       alert: $(this).find('input[name="alert"]').val()
                                                   });
                            }).get(),
                    courses: $('#courses-to-be-credited tbody > tr')
                            .map(function(){
                                return JSON.stringify({learning_project_id: $(this).data('learning_project_id'),
                                                       accreditation_code: $(this).find('input').val()});
                            }).get(),
                    send_assignation: $('#send_mail input')[0].checked,
                    send_license: $('#send_mail input')[1].checked
                }
            }).done(function(res){
                $.isLoading("hide");
                if (res > 0) window.location.href = 'index.php/home?setting=company';
                else {
                    alert("Errore. La procedura non è stata completata. Verifica nuovamente i dati.");
                    window.location.href = 'index.php/wizard?setting=subscribe';
                }
            });
        }   
    });
    
    $('#courses-to-be-credited tr').each(function () {
        var learn_id = $(this).data('learning_project_id');
        $(this).find('td:first-child').append($('#courses > tr[data-learn_id="' + learn_id + '"] > td.course-title').text());
    });
    
    $(function(){
        $('#step-button > li.current').next().removeClass('disabled');
    });
    
</script>