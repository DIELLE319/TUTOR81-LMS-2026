<?php 

require_once BASE_LIBRARY_PATH . 'class_company.php';
$comp_obj = new T81Company ();

$prov = $comp_obj->getProvinces();
?>
<!-- Modal Checkout -->
<div id="checkout-pagamento" class="modal fade" role="dialog" style="display: none;">
    <div class="modal-dialog modal-lg">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <!-- Intro -->
                <section class="site-section site-section-light site-section-top themed-background-dark" style="padding-top: 20px;">
                    <div class="text-center">
                        <h1 class="animation-slideDown"><strong>Checkout</strong></h1>
                    </div>
                </section>
                <!-- END Intro -->
            </div>
            <div class="modal-body">
                <!-- Checkout Process -->
                <section class="site-content site-section">
                    <form id="checkout-wizard" action="ec-checkout.php" method="post">
                        <!-- First Step -->
                        <div id="checkout-first" class="step">
                            <!-- Step Info -->
                            <ul class="nav nav-pills nav-justified checkout-steps push-bit">
                                <li class="active"><a  data-gotostep="checkout-first" style="cursor: default;"> <strong>1. MODIFICA ACQUISTO</strong></a></li>
                                <li id="noHover"><a  data-gotostep="checkout-fourth"> <strong>2. PAGAMENTO</strong></a></li>
                            </ul>
                            <!-- END Step Info -->
                            <div class="site-content site-section" style="min-height: 398px;">

                                <div class="table-responsive">
                                    <table class="table table-bordered table-vcenter">
                                        <thead>
                                        <tr>
                                            <th colspan="2">Prodotto</th>
                                            <th class="text-center">Quantit&agrave;</th>
                                            <th class="text-center">Prezzo</th>
                                            <th class="text-center">Totale</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td style="width: 200px;">
                                                <img class="checkoutImage" src="" alt="" style="width: 180px;">
                                            </td>
                                            <td style="width: 320px;">
                                                <strong class="checkoutTitle">-- Product title --</strong><br>
                                                <strong class="checkoutTipo text-success" style="text-transform: uppercase;">-- Product tipo --</strong><br>
                                                <br><span class="checkoutOre">-- ore --</span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-xs btn-danger" data-toggle="tooltip" id="dec"><i class="fa fa-minus"></i></button>
                                                <input class="amount checkoutQuantita step1-amount-css" style="cursor: default" type="number" min="1" max="999" value="1" readonly>
                                                <button type="button" class="btn btn-xs btn-success" data-toggle="tooltip" id="inc"><i class="fa fa-plus"></i></button>
                                            </td>
                                            <td class="text-right ">
                                                <strong>
                                                    € <input class="checkoutPrezzo prezzo step1-prezzo-css" title="prezzo" type="text" readonly maxlength="6" value="0" >
                                                </strong>
                                            </td>
                                            <td class="text-right ">
                                                <strong>
                                                    € <input class="checkoutSubTotale prezzo step1-prezzo-css" title="price" type='text' readonly maxlength="5" value="0">
                                                </strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-right h4 prezzo"><strong>Totale parziale</strong></td>
                                            <td class="text-right h4">
                                                <strong>
                                                    € <input class="checkoutTotSubTotale prezzo step1-tot-css" title="price" type="text" readonly maxlength="5" value="0" >
                                                </strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="4" class="text-right h4"><strong>IVA (22%)</strong></td>
                                            <td class="text-right h4">
                                                <strong>
                                                    € <input class="checkoutVatPrezzo prezzo_vat step1-tot-css" title="price" type="text" readonly maxlength="5" value="0" >
                                                </strong>
                                            </td>
                                        </tr>
                                        <tr class="active">
                                            <td colspan="4" class="text-right text-uppercase h4"><strong>Prezzo Totale</strong></td>
                                            <td class="text-right text-success h4">
                                                <strong>
                                                    € <input class="checkoutTotale prezzo_tot step1-tot-css" title="price" type="text" readonly maxlength="5" value="0">
                                                </strong>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-xs-6 col-lg-6">
                                    <div class="form-group text-left">
                                        <a href="index.html">
                                            <input title="Torna al Catalogo" type="button" class="btn btn-danger" data-dismiss="modal" id="return" value="Torna al Catalogo">
                                        </a>
                                    </div>
                                </div>
                                <div class="col-xs-6 col-lg-6">
                                    <div class="form-group text-right">
                                        <input type="submit" class="btn btn-primary" id="next" value="">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END First Step -->

                        <!-- Fourth Step -->
                        <div id="checkout-fourth" class="step">
                            <!-- Step Info -->
                            <ul class="nav nav-pills nav-justified checkout-steps push-bit">
                                <li class="checkout-tab"><a  data-gotostep="checkout-first"><i class="fa fa-check"></i> <strong>1. MODIFICA ACQUISTO</strong></a></li>
                                <li class="active"><a  data-gotostep="checkout-foruth" style="cursor: default;"><i class="fa fa-check"></i> <strong>2. PAGAMENTO</strong></a></li>
                            </ul>
                            <!-- END Step Info -->

                            <div class="row">
                                <div class="col-sm-6">
                                    <h4 class="page-header">Metodi di pagamento</h4>
                                    <div class="form-group">
                                        <label>Scegli</label>
                                        <div>
                                            <label class="radio-inline" for="checkout-payment-bonifico">
                                                <input type="radio" id="checkout-payment-bonifico" name="checkout-payments" value="bonifico" checked><i class="fa fa-exchange"></i>  Bonifico Bancario
                                            </label>
                                            <label class="radio-inline hidden" for="checkout-payment-paypal">
                                                <input type="radio" id="checkout-payment-paypal" name="checkout-payments" value="paypal" disabled><i class="fa fa-paypal"></i>  Paypal
                                            </label>
                                            <!--<label class="radio-inline" for="checkout-payment-carta">-->
                                            <!--<input type="radio" id="checkout-payment-carta" name="checkout-payments" value="Carta"><i class="fa fa-credit-card"></i> Carta di Credito-->
                                            <!--</label>-->
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6" id="bonifico">
                                    <h4 class="page-header"><i class="fa fa-exchange"></i> Bonifico Bancario </h4>
                                    <div class="form-group">
                                        <!--                                            <label for="checkout-payment-bank">Banca</label>-->
                                        <p class="pagamento-text" > Alla conferma dell'acquisto riceverete tramite e-Mail
                                            le nostre coordinate bancarie e la causale per effettuare il bonifico bancario. 
                                            Una volta ricevuto il pagamento (è possibile anticipare via e-mail il numero di cro) 
                                            riceverete le istruzioni per avviare il corso 
                                            <a href="javascript: void(0)">v. esempio</a>.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-sm-6" id="paypal" style="display: none;">
                                    <h4 class="page-header"><i class="fa fa-paypal"></i> Paypal</h4>
                                    <div class="form-group">
                                        <p><span>Alla conferma dell'acquisto verrete rediretti sulla pagina di Paypal per completare il pagamento.</span></p>
                                        <p><i>Con questo metodo di pagamento immediato le licenze saranno subito attive.</i></p>
                                        <!--<input type="text" id="checkout-payment-paypal-email" name="checkout-payment-email" class="form-control" placeholder="Email">-->
                                    </div>
                                    <!--<div class="form-group">-->
                                    <!--<label for="checkout-payment-number">Card Number</label>-->
                                    <!--<input type="text" id="checkout-payment-number" name="checkout-payment-number" class="form-control" placeholder="0000-0000-0000-0000">-->
                                    <!--</div>-->
                                </div>
                                <div class="col-sm-6" id="carta" style="display: none;">
                                    <h4 class="page-header"><i class="fa fa-credit-card"></i>Dati della Carta di credito</h4>
                                    <div class="form-group">
                                        <label for="checkout-payment-name-cart"> Titolare</label>
                                        <input type="text" id="checkout-payment-card-owner" name="checkout-payment-card-owner" class="form-control" placeholder="Nome Cognome">
                                    </div>
                                    <div class="form-group">
                                        <label for="checkout-payment-number-cart"> Numero di Carta di Credito</label>
                                        <input type="text" id="checkout-payment-card-number" name="checkout-payment-card-number" class="form-control" placeholder="xxxx-xxxx-xxxx-xxxx">
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <label for="checkout-payment-cvc-cart"> CVC</label>
                                                <input type="text" id="checkout-payment-card-cvc" name="checkout-payment-card-cvc" class="form-control" placeholder="xxx">
                                            </div>
                                            <div class="col-xs-6">
                                                <label for="checkout-payment-expire-cart"> Data di Scadenza</label>
                                                <input type="text" id="checkout-payment-card-expire" name="checkout-payment-card-expire" class="form-control" placeholder="MM/AA">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- email e dati azienda -->
                            <hr>
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group" style="margin-top: 30px;">
                                        <label for="emailSpedizione">Email</label>
                                        <input type="email" id="checkoutEmailSpedizione" name="emailSpedizione" class="form-control">
                                    </div>
                                    <div class="form-group" style="margin-top: 30px;">
                                        <label for="checkoutVatCode">Partita IVA</label>
                                        <input type="text" id="checkoutVatCode" name="checkoutVatCode" class="form-control" maxlength="11">
                                    </div>
                                    <div style="border: 1px solid #1bbae1;
                                                padding: 10px;
                                                border-radius: 4px;
                                                text-align: justify;
                                                display: none;" class="business-data">
                                        <p>Registrandoti i tuoi acquisti potranno procedere più velocemente. In futuro sarà sufficiente 
                                        inserire solo la partita IVA e il sistema ti riconoscerà immediatamente.</p>
                                    </div>
                                </div>
                                <div id="business-data" class="col-sm-6 business-data" style="display: none;">
                                    <div class="form-group" style="margin-top: 30px;">
                                        <label for="business_name">Ragione sociale</label>
                                        <input type="text" id="business_name" name="business_name" class="form-control" value="" maxlength="255">
                                    </div>
                                    <div class="form-group" style="margin-top: 30px;">
                                        <label for="address">Indirizzo</label>
                                        <input type="text" id="address" name="address" class="form-control" value="" maxlength="255">
                                    </div>
                                    <div class="form-group" style="margin-top: 30px;">
                                        <label for="city">Località</label>
                                        <input type="text" id="city" name="city" class="form-control" value="" maxlength="255">
                                    </div>
                                    <div class="form-group">
                                        <label for="province_id">Provincia</label>
                                        <select id="province_id" class="form-control">

                                            <option value="0">Seleziona una provincia</option>

                                            <?php foreach ($prov as $single) { ?>

                                                <option value="<?= $single['id'] ?>"><?= strtoupper($single['name']) ?></option>

                                            <?php } ?>

                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xs-6 col-lg-6">
                                    <div class="form-group text-left">
                                        <?php include "ecommerce/checkbox-tos.php";?>
                                    </div>
                                </div>
                                <div class="col-xs-6 col-lg-6">
                                    <div class="form-group text-right">
                                        <input type="hidden" value="" name="" class="checkoutId">
                                        <input type="button" class="btn btn-warning" id="back" value="Indietro">
                                        <input type="button" class="btn btn-success" id="checkout" value="Acquista">
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- END Fourth Step -->

                        <!-- Form Buttons -->


                        <!-- END Form Buttons -->
                    </form>

                </section>
                <!-- END Checkout Process -->
            </div>
        </div>

    </div>
</div>