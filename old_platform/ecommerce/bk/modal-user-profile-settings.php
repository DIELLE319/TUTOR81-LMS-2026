<!-- Modal -->
<div id="profile_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-sm">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Amministrazione Piattaforma</h4>
            </div>
            <div class="modal-body ">
                <div class="modal-profile-user-image-content text-center">
                    <div class="modal-profile-user-avatar">
                        <i class="gi gi-user"></i>
                    </div>
                    <span class="modal-profile-username"><?= $_SESSION['tutor']['business_name']?></span>
                </div>
                <div class="modal-profile-user-info">
                    <div class="row">
                        <div class="col-xs-6 col-sm-5"><label for="user-edit-name"> Nome</label></div>
                        <div class="col-xs-6 col-sm-7">
                            <input type="text" readonly id="user-edit-name" value="<?= $_SESSION['user']['name'] ?>" class="form-control" /></div>

                        <div class="col-xs-6 col-sm-5"><label for="user-edit-surname"> Cognome</label></div>
                        <div class="col-xs-6 col-sm-7">
                            <input type="text" readonly id="user-edit-surname" value="<?= $_SESSION['user']['surname'] ?>" class="form-control" /></div>

                        <div class="col-xs-6 col-sm-5"><label for="user-edit-code"> Codice Fiscale</label></div>
                        <div class="col-xs-6 col-sm-7">
                            <input type="text" readonly id="user-edit-code" value="<?= $_SESSION['user']['tax_code']?>" class="form-control" /></div>

                        <div class="col-xs-6 col-sm-5"><label for="user-edit-email"> Email</label></div>
                        <div class="col-xs-6 col-sm-7">
                            <input type="text" readonly id="user-edit-email" value="<?= $_SESSION['user']['email'] ?>" class="form-control" /></div>

<!--                            <div class="col-xs-12">-->
<!--                                <label class="radio-inline" for="example-inline-radio1"><input type="radio" id="example-inline-radio1" name="example-inline-radios" value="option1"> Activa</label>-->
<!--                                <label class="radio-inline" for="example-inline-radio2"><input type="radio" id="example-inline-radio2" name="example-inline-radios" value="option2"> Disattiva</label>-->
<!--                            </div>-->

                        <div class="col-xs-12"><hr></div>
                        <div class="col-xs-6 col-sm-5"><label for="user-edit-username"> Nome Utente</label></div>
                        <div class="col-xs-6 col-sm-7">
                            <input type="text" readonly id="user-edit-username" value="<?= $_SESSION['user']['username'] ?>" class="form-control" />
                        </div>
                        <form id="form-change-password" action="/bk-homepage.php?scelta=home&edit=pswd" method="POST" style="display: none;">
                            <div class="col-xs-6 col-sm-5"><label for="user-edit-pswd">Password</label></div>
                            <div class="col-xs-6 col-sm-7"><input type="password" id="user-edit-pswd" name="user-edit-pswd" class="form-control" required/></div>
                            <div class="col-xs-6 col-sm-5"><label for="user-confirm-pswd">Conferma</label></div>
                            <div class="col-xs-6 col-sm-7"><input type="password" id="user-confirm-pswd" class="form-control" required/></div>
                            <div class="text-center">
                                <button type="button" class="btn btn-warning" id="cancel-new-pswd">Annulla</button>
                                <button type="button" class="btn btn-success" id="save-new-pswd">Salva</button>
                            </div>
                        </form>
                        <div class="text-center"><a href="javascript: void(0)" id="change-password">Cambia password</a></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal"><i class="fa fa-times" aria-hidden="true"></i> Esci</button>
<!--                <button type="submit" class="btn btn-success" data-dismiss="modal"><i class="fa fa-check" aria-hidden="true"></i> Salva</button>-->
            </div>
        </div>

    </div>
</div>
<script>
$('#change-password').click(function(){
    $(this).hide();
    $('#form-change-password').show();
});

$('#cancel-new-pswd').click(function(){
    $('#form-change-password').hide().find('input').val('');
    $('#change-password').show();
});

$('#save-new-pswd').click(function(){
    var new_pswd = $('#user-edit-pswd').val();
    if (new_pswd.length < 6){
        alert("La password deve essere lunga almeno 6 caratteri.");
        $('#user-edit-pswd').focus();
    } else if ($('#user-confirm-pswd').val() !== new_pswd) {
        alert("Password e conferma password non coincidono.");
        $('#user-confirm-pswd').focus();
    } else {
        $('#form-change-password').submit();
    }
});

</script>