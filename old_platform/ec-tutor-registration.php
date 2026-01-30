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

$plans = $company_obj->getPlans();
?>

<?php require_once 'ecommerce/header.php'; ?>
<body>
<!-- Page Container -->
<!-- In the PHP version you can set the following options from inc/config file -->
<!-- 'boxed' class for a boxed layout -->
<div id="page-container">

    <!-- Sign Up -->
    <section class="site-content site-section">
        <div class="container">
            <div class="row">
                <!-- Sign Up Form -->
                <form action="#" method="post" id="form-sign-up" class="form-horizontal">
                    <div class="col-sm-6 col-md-offset-3 col-lg-4 col-lg-offset-1 site-block">
                        <h4>Indica i dati dell'Ente Formativo:</h4>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="gi gi-radar"></i></span>
                                    <input type="text" id="business_name" name="business_name"
                                           class="form-control input-lg" placeholder="Ragione Sociale*">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="gi gi-address_book"></i></span>
                                    <input type="text" id="vat" name="vat" class="form-control input-lg"
                                           placeholder="P.iva*">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="gi gi-envelope"></i></span>
                                    <input type="email" id="email" name="email" class="form-control input-lg"
                                           placeholder="Email Azienda*">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="gi gi-phone_alt"></i></span>
                                    <input type="text" id="telephone" name="telephone" class="form-control input-lg"
                                           placeholder="Numero di telefono">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="gi gi-credit_card"></i></span>
                                    <input type="text" id="iban-register" name="iban" class="form-control input-lg"
                                           placeholder="Iban">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <label class="col-md-3 control-label" for="licence">Licenza</label>
                                <div class="col-md-9">
                                    <select id="licence" name="licence" class="form-control">
                                        <option value="">Tipo di licenza</option>
                                        <?php foreach ($plans as $license) {
                                            if (($license['for_tutor'] && $license['plan_price'] == 0)) { ?>
                                                <option value="<?= $license['id'] ?>">
                                                    <?= $license['short_desc_plan'] ?>
                                                </option>
                                            <?php }
                                        } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-offset-3 col-lg-4 col-lg-offset-2 site-block">
                        <h4>Indica l'utente dell'Ente Formativo:</h4>
                        <div class="form-group">
                            <div class="col-xs-6">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="gi gi-user"></i></span>
                                    <input type="text" id="admin_name" name="admin_name" class="form-control input-lg"
                                           placeholder="Nome*">
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-user-secret "></i></span>
                                    <input type="text" id="admin_surname" name="admin_surname"
                                           class="form-control input-lg" placeholder="Cognome*">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="gi gi-asterisk"></i></span>
                                    <input type="text" id="admin_tax_code" name="admin_tax_code"
                                           class="form-control input-lg" placeholder="Codice fiscale*">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
                                    <input type="email" id="admin_email" name="admin_email"
                                           class="form-control input-lg" placeholder="Email*">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-phone-square"></i></span>
                                    <input type="text" id="admin_telephone" name="admin_telephone"
                                           class="form-control input-lg" placeholder="Numero di telefono">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-sm-offset-3 col-md-offset-3 col-lg-4 col-lg-offset-4 site-block">
                        <div class="form-group form-actions">
                            <div class="col-xs-8">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="register-terms" checked required>
                                        <a id="disclaimer" href="#disclaimerModal" data-toggle="modal"
                                           data-remote="modals/tos.html">
                                            <small>Termini e condizioni d'uso</small>
                                        </a>
                                    </label>
                                </div>

                            </div>
                            <div class="col-xs-4 text-right">
                                <input type="hidden" name="op_type" id="op_type" value="nuova_company">
                                <input type="hidden" name="is_tutor" id="is_tutor" value="1">
                                <button id="buttonRegister" type="submit" class="btn btn-primary"><i
                                        class="fa fa-plus"></i> Registrati
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <!-- END Sign Up Form -->
            </div>
        </div>
    </section>
    <!-- END Sign Up -->

</div>
<!-- END Page Container -->

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-up"></i></a>


<!-- jQuery, Bootstrap.js, jQuery plugins and Custom JS code -->
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

<script type="text/javascript" id="cookiebanner" src="js/cookiebanner.js"
        data-position="bottom" data-close-text="<button class='btn btn-default'>OK</button>"
        data-message="Questo sito utilizza cookies tecnici e cookies di profilazione per
        migliorare la tua esperienza di navigazione.<br>Se continui nella navigazione o
        clicchi su un elemento della pagina accetti il loro utilizzo. Se vuoi saperne
        di più o negare il consenso ai cookies leggi questa "
        data-linkmsg="Informativa estesa"
        data-moreinfo="javascript: $('#disclaimerModal .modal-body').load('tos.html').parents('#disclaimerModal').modal('show')">
</script>


<!-- Load and execute javascript code used only in this page -->
<script type="application/javascript" src="js/pages/signup.min.js"></script>
<script>
    $(function () {
        Signup.init();
    });

    $('#buttonRegister').click(function (e) {
        if ($('#form-sign-up').valid()) {
            //alert("Funzione di registrazione");
            $.isLoading({text: "Attendere prego ..."});
            var company_id = createCompanyShort();
            if (company_id > 0) {
                // Create new user
                alert("Azienda registrata correttamente con ID: " + company_id + " Ora entrerete nel vostro spazio aziendale.");
                window.location = "ec-login.php";
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
