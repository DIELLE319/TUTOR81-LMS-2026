<?php
/**
 * Created by PhpStorm.
 * User: Davide
 * Date: 01/01/2016
 * Time: 15.26
 */
?>
<?php
if (isset($_GET['recover'])) {
    require_once 'lib/class_user.php';
    $user_obj = new T81User();
    $recover = $user_obj->checkRecoverPasswordCode($_GET['recover']);
}
?>
<?php require_once 'ecommerce/header.php'; ?>

<body>
    <!-- Page Container -->
    <!-- In the PHP version you can set the following options from inc/config file -->
    <!-- 'boxed' class for a boxed layout -->
    <div id="page-container">

        <!-- Intro -->
        <section style="padding-top: 40px;" class="site-section site-section-light site-section-top themed-background-dark">
            <div class="container">
                <h1 class="text-center animation-slideDown"><i class="fa fa-arrow-right"></i> <strong>Entra</strong></h1>
                <h2 class="h3 text-center animation-slideUp">Entra in piattaforma e gestisci i corsi!</h2>
            </div>
        </section>
        <!-- END Intro -->

        <?php require_once 'login-form.php'; ?>

        <?php require_once 'ecommerce/footer.php'; ?>
    </div>
    <!-- END Page Container -->

    <!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
    <a href="#" id="to-top"><i class="fa fa-angle-up"></i></a>

    <script src="js/plugins.js"></script>
    <script src="js/app.min.js"></script>

    <!-- -------------------------------------------------------------------------------- -->
    <!-- ------------------------------ MODAL  TOS -------------------------------------- -->
    <!-- -------------------------------------------------------------------------------- -->
    <div class="modal fade" id="disclaimerModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
            </div>
        </div>
    </div>

    <!-- -------------------------------------------------------------------------------- -->
    <!-- ------------------------ MODAL RECUPERA CREDENZIALI ---------------------------- -->
    <!-- -------------------------------------------------------------------------------- -->
    <div class="modal fade" id="recoverModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span><span class="sr-only"></span>
                    </button>
                    <h4 class="modal-title" id="myModalLabel">Recupera credenziali</h4>
                </div>
                <div class="modal-body">
                    <form class="form-recover" method="POST" action="lib/recover.php">
                        <h5>Hai dimenticato le tue credenziali?</h5>
                        <p>Compila i campi e clicca su RECUPERA. Riceverai il tuo nome utente e un link per la rigenerazione della password</p>
                        <div class="row">
                            <div class="col-sm-6">
                                <input type="text" name="email" class="form-control" placeholder="email" required>
                            </div>
                            <div class="col-sm-6">
                                <input type="text" name="tax_code" class="form-control" placeholder="codice fiscale" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Annulla</button>
                    <button id="recover" type="button" class="btn btn-default" data-dismiss="modal">Recupera</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(function () {

            $('.form-signin').submit(function (e) {
                if ($('input[name="username"]').val() == "")
                    alert('Compilare il campo username');
                else if ($('input[name="password"]').val() == "")
                    alert('Compilare il campo password');
                else if (!$('input[name="tos_authorized"]')[0].checked)
                    alert('Selezionare la casella dei termini e condizioni d\'uso');
                else
                    return;
                e.preventDefault();
            });

            $('#recover').click(function (e) {
                if (!validateEmail($('input[name="email"]').val()) || !controllaCF($('input[name="tax_code"]').val())) {
                    alert("Compila tutti i campi correttamente.");
                    return false;
                }

                $.isLoading({text: "Attendere ..."});
                $.post('manage/user.php', {
                    op_type: 'recover_password',
                    email: $('input[name="email"]').val(),
                    tax_code: $('input[name="tax_code"]').val()
                }, function (data) {
                    if (data > 0)
                        alert("Verifica la tua mail. Riceverai il tuo nome utente e un link, valido per le prossime 24 ore, con il quale reimpostare la tua password.");
                    else
                        alert("I dati inseriti non corrispondono a nessun utente iscritto alla piattaforma. " +
                                "Verificali attentamente. Ricorda che l'email che devi inserire è quella registrata nella nostra piattaforma.");
                    $.isLoading("hide");
                }
                );
            });

            <?php if (isset($recover) && $recover) { ?>

                if (confirm('Hai richiesto di reimpostare la password. Clicca su OK per completare la procedura e poter accedere utilizzando come password il tuo codice fiscale.')) {
                    $.isLoading({text: "Attendere ..."});
                    $.post('manage/user.php', {
                        op_type: 'reset_user_password',
                        user_id: <?= $recover['user_id'] ?>
                    }, function (data) {
                        if (data > 0)
                            alert('Per accedere alla piattaforma puoi ora utilizzare il tuo codice fiscale. All\'accesso ti verrà chiesto di modificarla.' +
                                    '\nTi abbiamo inviato questa comunicazione anche per posta elettronica.');
                        $.isLoading("hide");
                    });
                }

            <?php } ?>

        });
    </script>


    <script type="text/javascript" id="cookiebanner" src="js/cookiebanner.js"
        data-position="bottom" data-close-text="<button class='btn btn-default'>OK</button>"
        data-message="Questo sito utilizza cookies tecnici e cookies di profilazione per
        migliorare la tua esperienza di navigazione.<br>Se continui nella navigazione o
        clicchi su un elemento della pagina accetti il loro utilizzo. Se vuoi saperne
        di più o negare il consenso ai cookies leggi questa "
        data-linkmsg="Informativa estesa" data-moreinfo="javascript: $('#disclaimerModal .modal-body').load('tos.html').parents('#disclaimerModal').modal('show')">
    </script>

    <!-- Load and execute javascript code used only in this page -->
    <script type="application/javascript" src="js/pages/login.min.js"></script>
    <script>$(function(){ Login.init(); });</script>
</body>