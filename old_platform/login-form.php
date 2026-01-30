
<!-- Log In -->
<section class="site-content site-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-6 col-sm-offset-3 col-lg-4 col-lg-offset-4 site-block">
                <!-- Log In Form -->
                <form method="POST" action="lib/autenticate.php" id="form-log-in" class="form-horizontal">
                    <div class="form-group">
                        <div class="col-xs-12">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                                <input type="text" id="username" name="username" class="form-control input-lg" placeholder="Username">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="gi gi-asterisk"></i></span>
                                <input type="password" id="password" name="password" class="form-control input-lg" placeholder="Password">
                            </div>
                        </div>
                    </div>
                    <div class="form-group form-actions">
                        <div class="col-xs-7">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="tos_authorized" checked required>
                                    <a id="disclaimer" href="#disclaimerModal" data-toggle="modal" data-remote="modals/tos.html"><small> Termini e condizioni d'uso</small></a>
                                </label>
                            </div>
                            <label class="switch switch-primary">
                                <!--<input type="checkbox" id="login-remember-me" name="login-remember-me" checked><span></span>-->
                            </label>
                            <!--<small>Ricordami</small>-->
                        </div>
                        <div class="col-xs-5 text-right">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-arrow-right"></i> Accedi</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php if(isset($_GET['err'])){
                            if ($_GET['err'] == 1) $error = "Credenziali inserite errate.";
                            elseif ($_GET['err'] == 2) $error = "Verifica account.";
                            elseif ($_GET['err'] == 3) $error = "Conferma l'accettazione dei termini e delle condizioni d'uso.";
                            elseif ($_GET['err'] == 4) $error = "La licenza è scaduta. Contatta Tutor81 per rinnovarla.";
                            ?>

                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <strong>Errore!</strong> <?= $error ?>
                            </div>

                        <?php } ?>
                    </div>
                </form>
                <div class="text-center">
                    <a href="#recoverModal" data-toggle="modal"><small>Password dimenticata?</small></a>
                </div>
                <div class="text-center">
                    <a href="mailto:assistenza@tutor81.it"><small>Richiedi assistenza</small></a>
                </div>
                <!-- END Log In Form -->
            </div>
        </div>
        <hr>
    </div>
</section>
<!-- END Log In -->

<!-- Support Links -->
<section class="site-content site-section">
    <div class="container">
        <div class="row row-items text-center">
            <div class="col-sm-4 animation-fadeIn">
                <a href="https://tutor81.it/istruzioni-uso-tutor81/come-si-vende-un-corso-online-sicurezza/" class="circle themed-background">
                    <i class="gi gi-facetime_video"></i>
                </a>
                <h4><strong>Come si assegna un corso</strong></h4>
            </div>
            <div class="col-sm-4 animation-fadeIn">
                <a href="mailto:assistenza@tutor81.it" class="circle themed-background">
                    <i class="gi gi-envelope"></i>
                </a>
                <h4><strong>Supporto tecnico</strong></h4>
            </div>
            <div class="col-sm-4 animation-fadeIn">
                <a href="https://www.tutor81.it/come-si-avvia-un-corso/" class="circle themed-background">
                    <i class="fa fa-share-square-o "></i>
                </a>
                <h4><strong>Come si avvia un corso</strong></h4>
            </div>
        </div>
    </div>
</section>
<!-- END Support Links -->