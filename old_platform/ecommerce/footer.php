<!-- Footer -->
<footer class="site-footer site-section">
    <div class="container">
        <!-- Footer Links -->
        <div class="row">
            <div class="col-sm-6 col-md-4">
                <h2 class="footer-heading piattaforma"><b>La Piattaforma</b></h2>
                <ul class="footer-nav list-inline">
                    <li><a href="<?= $tutor["site_url"] ?>">La nostra azienda</a></li>
                    <li><a href="mailto:<?= $tutor["email"] ?>">Contatti</a></li>
                    <li><a href="mailto:assistenza@tutor81.it">Supporto tecnico</a></li>
                </ul>
            </div>
            <!--			<div class="col-sm-6 col-md-3">-->
            <!--				<h4 class="footer-heading">Condizioni</h4>-->
            <!--				<ul class="footer-nav list-inline">-->
            <!--					<li><a href="javascript:void(0)">Licenze</a></li>-->
            <!--				</ul>-->
            <!--			</div>-->
            <div class="col-sm-6 col-md-4">
                <h4 class="footer-heading">Seguici</h4>
                <ul class="footer-nav footer-nav-social list-inline">
                    <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                    <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                    <li><a href="#"><i class="fa fa-google-plus"></i></a></li>
                    <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
                    <li><a href="#"><i class="fa fa-rss"></i></a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="push-up text-center">
                    <span>
                    <?= strtoupper($tutor["business_name"]) ?><br>                    
                    <br>
                    <?= $tutor["address"] ?><br>
                    Telefono: <?= $tutor["telephone"] ?><br>
                    <br>
                    Email: <?= $tutor["email"] ?><br>
                    P.IVA: <?= $tutor["vat"] ?><br>
                    Cod. Fiscale: <?= $tutor["vat"] ?>
                    </span>
                </div>
                <!--<h4 class="footer-heading"><span>by <a href="/ec-login.php">Tutor81</a></span></h4>-->
                <!--<ul class="footer-nav list-inline">-->
                <!--<li> by <a href="#">Tutor81</a></li>-->
                <!--</ul>-->
            </div>
        </div>
        <!-- END Footer Links -->
    </div>
</footer>
<!-- END Footer -->