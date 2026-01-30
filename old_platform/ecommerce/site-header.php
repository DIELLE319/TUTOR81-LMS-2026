<!-- Site Header -->
<header style="background-color: #FFF<?= '';//$color_dark ?>;">
    <div class="container" >
        <!-- Site Logo -->
        <div class="navbar-logo" style="position: relative;">
            <a href="#" class="site-logo">
                <img src="<?=$tutor["logo"]?>" alt="<?=$tutor["logo"]?>" height="45"/>
            </a>
            <div class="hidden-xs" style="display: inline-block; position: relative; top: -5px;">
                <p class="ente-formativo" style="line-height: 14px; font-size: 12px; margin-left: 10px;">
                <i><?=strtoupper($tutor["business_name"])?><br>
                <?=$tutor["address"]?><br>
                <?=!empty($tutor["telephone"]) ? 'Telefono: ' . $tutor["telephone"] : '' ?><br>
                Email: <?=$tutor["email"]?>
                </i>
                </p>
            </div>
            <input type="hidden" name="tutorAdminID" id="tutorAdminID" value="<?=$tutor["admin_id"]?>">
            <input type="hidden" name="tutorCompanyID" id="tutorCompanyID" value="<?=$tutor["id"]?>">
        </div>

        <!-- Site Logo -->

        <!-- Site Navigation -->
        <nav>
            <!-- Menu Toggle -->
            <!-- Toggles menu on small screens -->
            <a href="javascript:void(0)" class="btn btn-default site-menu-toggle visible-xs visible-sm">
                <i class="fa fa-bars"></i>
            </a>
            <!-- END Menu Toggle -->

            <!-- Main Menu -->
            <ul class="site-nav">
                <!-- Toggles menu on small screens -->
                <li class="visible-xs visible-sm">
                    <a href="javascript:void(0)" class="site-menu-toggle text-center">
                        <i class="fa fa-times"></i>
                    </a>
                </li>
                <!-- END Menu Toggle -->
                <!-- <li style="margin-left: 0;">
                    <a href="/ec-catalogo-corsi.php"> Catalogo Corsi</a>
                </li> -->
                <li>
                    <a href="#">Garanzia certificazione</a>
                </li>
                <!-- <li>
                    <a href="#"> Come puoi pagare</a>
                </li> -->
                <li>
                    <a href="https://www.tutor81.it/avviacorso">Istruzioni corso</a>
                </li>
<!--                <li>-->
<!--                    <a href="#"> Fai una prova</a>-->
<!--                </li>-->
                <li>
                    <a href="https://avviacorso.tutor81.com" class="btn btn-danger">Avvia Corso</a>
                </li>
                <li>
                    <?php if( isset($_SESSION['user']) && $_SESSION['user']['company']['is_tutor'] ) {  ?>
                        <a href="/bk-index.php" class="btn btn-warning">Home Tutor</a>
                    <?php }else{ ?>
                        <a href="/ec-login.php" class="btn btn-primary">Accedi</a>
                    <?php } ?>
                </li>
            </ul>
            <!-- END Main Menu -->
        </nav>
        <!-- END Site Navigation -->
    </div>
</header>
<!-- END Site Header -->