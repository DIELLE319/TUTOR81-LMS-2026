<?php
/**
 * Created by PhpStorm.
 * User: Davide
 * Date: 09/01/2016
 * Time: 18.26
 */

// Point to right tutor in base of domain tutor81 alias add in any ecommerce page
require_once 'lib/class_company.php';
require_once 'config.php';
$company_obj = new T81Company();
$tutor = $company_obj->getCompanyByServerAlias($_SERVER['SERVER_NAME']);
if (!isset($tutor) || $tutor == false) {
    $tutor = $company_obj->getCompanyByServerAlias("pre-amm.tutor81.com"); //hub.tutor81.local
}
$tutor["logo"] = T81Company::getEcommerceLogo($tutor);
$tutor["admin_id"] = $company_obj->getMainAdminOfCompany($tutor["id"]);

?>

<?php require_once 'ecommerce/header.php'; ?>
<body>
    <!-- Page Container -->
    <!-- In the PHP version you can set the following options from inc/config file -->
    <!-- 'boxed' class for a boxed layout -->
    <div id="page-container">

        <?php require_once 'ecommerce/site-header.php'; ?>

        <!-- Intro -->
        <section class="site-section site-section-light site-section-top themed-background-dark">
            <div class="container">
                <?php if( $_SESSION['user']['company']['is_tutor'] ) {  ?>
                <h1 class="text-center animation-slideDown"> <strong> Crea il tuo cliente e poi indica un referente in azienda</strong></h1>
                <?php }else{ ?>
                    <h1 class="text-center animation-slideDown"><i class="fa fa-plus"></i> <strong>Registrati velocemente</strong></h1>
                    <h2 class="h4 text-center animation-slideUp">Registrando la tua azienda potrai accedere facilmente alla tua area riservata
                        e controllare i corsi e lo stato di avanzamento. Le scadenze saranno monitorate e riceverai un avviso per gli aggiornamenti.</h2>
                <?php } ?>
            </div>
        </section>
        <!-- END Intro -->

        <!-- Sign Up -->
        <section class="site-content site-section">
            <div class="container">
                <div class="row">
                    <!-- Sign Up Form -->
                    <form action="#" method="post" id="form-sign-up" class="form-horizontal">
                        <div class="col-sm-6 col-md-offset-3 col-lg-4 col-lg-offset-1 site-block">

                            <div class="form-group">
                                <div class="col-xs-12">
                                    <div class="input-group" style="display: inline-flex;">
                                        <span class="hidden-xs hidden-sm" style="border: 1px solid #aad178; width: 45px; height: 40px; border-radius: 50%;  background-color: #aad178; color: #fff; font-size: 26px; text-align: center; margin-right: 10px; "><b> 1</b></span>
                                        <h4 style="margin: 0; font-size: 16px;"><b> Registra l'Azienda a cui devono essere associati i corsi </b></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="gi gi-radar"></i></span>
                                        <input type="text" id="business_name" name="business_name" class="form-control input-lg" placeholder="Ragione Sociale*">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="gi gi-address_book"></i></span>
                                        <input type="text" id="vat" name="vat" class="form-control input-lg" placeholder="P.iva*">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="gi gi-envelope"></i></span>
                                        <input type="email" id="email" name="email" class="form-control input-lg" placeholder="Email Azienda*">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="gi gi-phone_alt"></i></span>
                                        <input type="text" id="telephone" name="telephone" class="form-control input-lg" placeholder="Numero di telefono">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-offset-3 col-lg-4 col-lg-offset-2 site-block">

                            <div class="form-group">
                                <div class="col-xs-12">
                                    <div class="input-group" style="display: inline-flex;">
                                        <span class="hidden-xs hidden-sm" style="border: 1px solid #aad178; width: 45px; height: 40px; border-radius: 50%; background-color: #aad178; color: #fff; font-size: 26px; text-align: center; margin-right: 10px;"><b> 2</b></span>
                                        <h4 style="margin: 0; font-size: 16px;"><b> Indica ora il nominativo di chi deve gestire l'area riservata ai corsi </b></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-6">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-user-secret "></i></span>
                                        <input type="text" id="admin_surname" name="admin_surname" class="form-control input-lg" placeholder="Cognome*">
                                    </div>
                                </div>
                                <div class="col-xs-6">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="gi gi-user"></i></span>
                                        <input type="text" id="admin_name" name="admin_name" class="form-control input-lg" placeholder="Nome*">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="gi gi-asterisk"></i></span>
                                        <input type="text" id="admin_tax_code" name="admin_tax_code" class="form-control input-lg" placeholder="Codice fiscale*">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12">
                                    <div class="input-group">
                                        <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
                                        <input type="email" id="admin_email" name="admin_email" class="form-control input-lg" placeholder="Email*">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-12">
                                    <div class="input-group" style="background-color: #394263; border-radius: 5px;">
                                        <p style="font-size: small; padding: 5px 15px; margin: 0; color: #fff;"> A questo indirizzo E-Mail verrà inviato il nome utente e la password con cui potrai effettuare gli acquisti.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-sm-offset-3 col-md-offset-3 col-lg-4 col-lg-offset-4 site-block">
                            <div class="form-group form-actions">
                                <div class="col-xs-8">
                                    <?php include "ecommerce/checkbox-tos.php";?>

                                </div>
                                <div class="col-xs-4 text-right">
                                    <input type="hidden" name="op_type" id="op_type" value="nuova_company">
                                    <input type="hidden" name="is_tutor" id="is_tutor" value="0">
                                    <button id="buttonRegister" type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Registrati</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <!-- END Sign Up Form -->
                </div>
            </div>
        </section>
        <!-- END Sign Up -->

        <?php require_once 'ecommerce/footer.php'; ?>

    </div>
    <!-- END Page Container -->

    <!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
    <a href="#" id="to-top"><i class="fa fa-angle-up"></i></a>


    <!-- jQuery, Bootstrap.js, jQuery plugins and Custom JS code -->
    <script src="js/plugins.js"></script>
    <script src="js/app.min.js"></script>

    <script type="text/javascript" id="cookiebanner" src="js/cookiebanner.js"
            data-position="bottom" data-close-text="<button class='btn btn-default'>OK</button>"
            data-message="Questo sito utilizza cookies tecnici e cookies di profilazione per
        migliorare la tua esperienza di navigazione.<br>Se continui nella navigazione o
        clicchi su un elemento della pagina accetti il loro utilizzo. Se vuoi saperne
        di più o negare il consenso ai cookies leggi questa "
            data-linkmsg="Informativa estesa" data-moreinfo="javascript: $('#disclaimerModal .modal-body').load('tos.html').parents('#disclaimerModal').modal('show')">
    </script>



    <!-- Load and execute javascript code used only in this page -->
    <script type="application/javascript" src="js/pages/signup.min.js"></script>
    <script>
        $(function(){ Signup.init(); });

        $('#buttonRegister').click(function (e) {
            if ($('#form-sign-up').valid()) {
                //alert("Funzione di registrazione");
                $.isLoading({text: "Attendere prego ..."});
                var company_id = createCompanyShort();
                if (company_id > 0) {
                    // Create new user
                    alert("Azienda registrata correttamente con ID: " + company_id + " Ora entrerete nel vostro spazio aziendale.");
                    window.location = "bk-index.php" + company_id;
                    return false;
                }
                else {
                    $.isLoading("hide");
                }
                return false;
            }

        });
    </script>
</body>
