<?php
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';

$course_obj = new iWDCourse();
$company_obj = new T81Company();

$learning_project_usercount = $course_obj->countLearningProjectUsersNotAssigned($_SESSION['tutor']['id']);
$not_assigned_ecommerce_licences = $learning_project_usercount;

$selected = isset($_REQUEST['scelta']) ? filter_var($_REQUEST['scelta'], FILTER_SANITIZE_STRING) : '';

require_once 'ecommerce/bk/header.php'; ?>
<div id="sidebar">
    <!-- Wrapper for scrolling functionality -->
    <div id="sidebar-scroll">
        <!-- Sidebar Content -->
        <div class="sidebar-content">

            <!--style="padding-top: 5px; padding-bottom: 5px; display: inline-block;"-->
            <!-- User Info -->
            <div class="sidebar-section sidebar-header sidebar-user clearfix sidebar-nav-mini-hide">

                <div class="data-ente-content" style="overflow: hidden;">
                    <p class="sidebar-tutor" style="margin-bottom: 5px;"><?= $_SESSION['tutor']['business_name']?></p>

                    <div class="data-ente" >
                        <span><i class="gi gi-road"></i><em><?= $_SESSION['tutor']['address'] ?></em> </span><br />
                        <span><i class="gi gi-google_maps"></i><em><?= $_SESSION['tutor']['city'] ?></em> </span>
                        <input type="hidden" name="userID" id="userID" value="<?=$_SESSION['user']['id']?>">
                        <input type="hidden" name="companyID" id="companyID" value="<?=$_SESSION['user']['company']['id']?>">
                        <input type="hidden" name="isTutor" id="isTutor" value="<?=$_SESSION['user']['company']['is_tutor']?>">
                        <input type="hidden" name="tutorCompanyID" id="tutorCompanyID" value="<?=$_SESSION['tutor']['id']?>">
                        <input type="hidden" name="tutorEmail" id="tutorEmail" value="<?=$_SESSION['tutor']['email']?>">
                    </div>
                    <div class="sidebar-user-avatar" style="position: relative;">
                        <a data-toggle="modal" href="#profile_modal">
                            <i class="gi gi-user " style="color: #fff; font-size: 15px; position: absolute; line-height: 15px;top: 5px;left: 5px;"></i>
                            <!--<img src="img/image-logo_T81.png">-->
                        </a>
                    </div>
                    <div class="sidebar-user-name"><?= $_SESSION['company']['business_name']?> <br /><?= $_SESSION['user']['name'] ?> <?= $_SESSION['user']['surname'] ?></div>
                    <div class="sidebar-user-links text-center">
                        <!--<a href="#" data-toggle="tooltip" data-placement="bottom" title="Profile"><i class="gi gi-user"></i></a>-->
                        <a href="mailto:<?= $_SESSION['user']['email'] ?>" data-toggle="tooltip" data-placement="bottom" title="Scrivi"><i class="gi gi-envelope"></i></a>
                        <!-- Opens the user settings modal that can be found at the bottom of each page (page_footer.html in PHP version) -->
                        <!--<a href="javascript:void(0)" class="enable-tooltip" data-placement="bottom" title="Settings" onclick="$('#modal-user-settings').modal('show');"><i class="gi gi-cogwheel"></i></a>-->
                        <a href="bk-logout.php" data-toggle="tooltip" data-placement="bottom" title="Esci"><i class="gi gi-exit"></i></a>
                    </div>
                </div>

            </div>

            <?php
            if ($_SESSION['user']['role'] == 2) { // role 2 --> referente aziendale
                ?>

                <!-- Sidebar Navigation COMPANY-->
                <ul class="sidebar-nav sidebar-menu-left">
                        
                    <li class="sidebar-header" style="font-size: 18px;">
                        <span class="sidebar-nav-mini-hide"><b> E-learning</b></span>
                    </li>
                    <li>
                        <a href="/bk-homepage.php?scelta=home" class="<?= ($selected == "home" ? "active" : "") ?>"  title="Home Page">
                            <i class="gi gi-home sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide"> Home Page</span></a>
                    </li>
                    <li>
                        <a href="/bk-employee.php?scelta=lavoratori" class="<?= ($selected == "lavoratori" ? "active" : "") ?>"  title="Lavoratori">
                            <i class="gi gi-user sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide"> Elenco utenti</span></a>
                    </li>
                    <li class="disabled" style="display: none;"> <!-- collegare solo assegnazione -->
                        <a href="/bk-index.php?scelta=catalogo" class="<?= ($selected == "catalogo" ? "active" : "") ?>"  title="Catalogo Corsi">
                            <i class="gi gi-pie_chart sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide"> Iscrivi ai corsi</span></a>
                    </li>
                    <li> <!-- collegare tabella acquisti senza prezzi -->
                        <a href="/bk-sold-courses.php?scelta=acquistati" class="<?= ($selected == "acquistati" ? "active" : "") ?>"  title="Corsi Acquistati">
                            <i class="gi gi-money sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide"> Corsi Acquistati</span></a>
                    </li>
                    <li>
                        <a href="/bk-ecommerce.php?scelta=ecommerce&filter=not_expired" class="<?= ($selected == "ecommerce" ? "active" : "") ?>" title="Codici corso">
                            <i class="gi gi-shopping_cart sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide"> Codici Corso </span></a>
                    </li>
                    <li> <!-- visualizzare tutti i corsi senza elenco a discesa -->
                        <a href="/bk-progress.php?scelta=corsiAttivi" class="<?= ($selected == "corsiAttivi" ? "active" : "") ?>" title="progress">
                            <i class="gi gi-signal sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide"> Corsi attivi</span></a>
                    </li>
                    <!-- START Not needed but don't delete -->
                    <li style="display: none;">
                        <ul style="display: none;">
                            <li>
                                <a href="#" class="active-courses-link"><span class="sidebar-nav-mini-hide active-courses-legend"> In svolgimento</span></a>
                            </li>
                            <li>
                                <a href="#" class="active-courses-link"><span class="sidebar-nav-mini-hide active-courses-legend"> Non avviati</span></a>
                            </li>
                            <li>
                                <a href="#" class="active-courses-link"><span class="sidebar-nav-mini-hide active-courses-legend"> Completati</span></a>
                            </li>
                            <li>
                                <a href="#" class="active-courses-link"><span class="sidebar-nav-mini-hide active-courses-legend"> Scaduti</span></a>
                            </li>
                        </ul>
                    </li>
                    <!-- END -->
                    <li class="sidebar-header-title disabled">
                        <a href="/bk-clienti.php?scelta=inLinea" class="<?= ($selected == "inLinea" ? "active" : "") ?> embeddedPage" title="monitor" data-url="/index.php" >
                            <i class="fa fa-group sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide header-text"><b> In linea</b></span></a>
                    </li>
                    <li>
                        <a href="bk-feedback.php"><i class="glyphicon glyphicon-thumbs-up sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide disabled"> Feedback corsi</span></a>
                    </li>
                    <li class="sidebar-header disabled" style="font-size: 18px; margin-top: 20px; display: none;">
                        <span class="sidebar-nav-mini-hide"><b> Gestione Azienda</b></span>
                    </li>
                    <li class="sidebar-header-title disabled" style="display: none;">
                        <a href="/bk-clienti.php?scelta=azienda" class="<?= ($selected == "azienda" ? "active" : "") ?> embeddedPage" title="profile" data-url="/index.php">
                            <i class="gi gi-factory sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide header-text"><b> Dati azienda</b></span></a>
                    </li>
                    <li style="display: none;" class="disabled">
                        <a href="/bk-clienti.php" name="unita-reparti" data-url="/index.php">
                            <span class="sidebar-nav-mini-hide under-header-text"> Crea unità e raparti</span></a>
                    </li>
                    <li style="display: none;" class="sidebar-header-title disabled">
                        <a href="/bk-clienti.php?scelta=personale" class="<?= ($selected == "personale" ? "active" : "") ?> embeddedPage" title="employees" data-url="/index.php">
                            <i class="gi gi-user sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide header-text"><b> Personale</b></span></a>
                    </li>
                    <li>
                        <!-- <a href="#"><span class="sidebar-nav-mini-hide under-header-text"> Formazione da fare</span></a>-->
                        <input type="hidden" id="pageIndexSelector" value="">
                    </li>

                </ul>

            <?php }
            elseif ( $_SESSION['user']['role'] == 1         // role 1 --> amministratore ente
                    || $_SESSION['user']['role'] == 32      // role 2 --> amministratore socio
                    || $_SESSION['user']['role'] == 1000) { // role 1000 --> superutente
                ?>

                <!-- Sidebar Navigation TUTOR-->
                <ul class="sidebar-nav sidebar-menu-left">
                    
                    <li class="disabled"><a class="disabled" href="javascript:void(0)"><i class="gi gi-home sidebar-nav-icon"></i>HOME</a></li>
                    <li>
                        <a href="/bk-homepage.php?scelta=home" class="<?= ($selected == "home" ? "active" : "") ?>"  title="Home Page">
                            <span class="sidebar-nav-mini-hide">Home Page</span></a>
                    </li>
                    
                    <?php if ($_SESSION['user']['role'] == 1000) { ?>
                    
                    <li>
                        <a href="/bk-clienti.php?scelta=tutors" class="<?= ($selected == "tutors" ? "active" : "") ?>">
                            <span class="sidebar-nav-mini-hide">elenco enti formativi</span></a>
                    </li>
                    
                    <?php } ?>
                    
                    <li role="separator" class="divider"><hr style="margin: 0;"></li>
                    <li class="disabled"><a class="disabled" href="javascript:void(0)"><i class="gi gi-pie_chart sidebar-nav-icon"></i>VENDITA</a></li>
                    
                    <?php if (!$_SESSION['tutor']['is_tutor_with_single_company']) { ?>
                    
                    <li>
                        <a href="/bk-clienti.php?scelta=companies&new=company">
                            <span class="sidebar-nav-mini-hide" style="color: #ff0000; font-weight: bold;">crea cliente</span></a>
                    </li>
                    
                    <?php } ?>
                    <li>
                        <a href="/bk-index.php?scelta=catalogo" class="<?= ($selected == "catalogo" ? "active" : "") ?>"  title="vendi Corsi">
                            <span class="sidebar-nav-mini-hide" style="color: #ff0000; font-weight: bold;"> <?= $_SESSION['tutor']['is_tutor_with_single_company'] ? 'iscrivi ai' : 'vendi' ?> corsi</span></a>
                    </li>
                    <li>
                        <a href="/bk-sold-courses.php?scelta=venduti" class="<?= ($selected == "venduti" ? "active" : "") ?>"  title="Corsi Venduti">
                            <span class="sidebar-nav-mini-hide">corsi <?= $_SESSION['tutor']['is_tutor_with_single_company'] ? 'acquistati' : 'venduti' ?></span></a>
                    </li>
                    <li>
                        <a href="/bk-ecommerce.php?scelta=ecommerce" class="<?= ($selected == "ecommerce" ? "active" : "") ?>" title="Ecommerce">
                            <span class="sidebar-nav-mini-hide">licenze </span><i class="badge label-danger"> <?=$not_assigned_ecommerce_licences?></i></a>
                    </li>
                    <li role="separator" class="divider"><hr style="margin: 0;"></li>
                    <li class="disabled"><a class="disabled" href="javascript:void(0)"><i class="gi gi-signal sidebar-nav-icon"></i>CORSI</a></li>
                    <li>
                        <a href="bk-monitor.php?scelta=monitor" class="<?= ($selected == "monitor" ? "active" : "") ?>">
                            </i><span class="sidebar-nav-mini-hide" style="color: #e67e22; font-weight: bold;">online</span>
                        </a>
                    </li>
                    <li>
                        <a href="/bk-progress.php?scelta=corsiAttivi" class="<?= ($selected == "corsiAttivi" ? "active" : "") ?>" title="progress">
                            <span class="sidebar-nav-mini-hide">attivati</span></a>
                    </li>
                    <li>
                        <a href="/bk-employee.php?scelta=attestati" class="<?= ($selected == "attestati" ? "active" : "") ?>"  title="Attestati">
                            <span class="sidebar-nav-mini-hide">completati</span></a>
                    </li>
                    <li>
                        <a href="/bk-update-needs.php?scelta=update_done_false" class="<?= ($selected == "update_done_false" ? "active" : "") ?>"  title="update">
                            <span class="sidebar-nav-mini-hide text-warning"><b>da ripetere</b></span></a>
                    </li>
                    <li>
                        <a href="/bk-update-needs.php?scelta=update_done_true" class="<?= ($selected == "update_done_true" ? "active" : "") ?>"  title="update">
                            <span class="sidebar-nav-mini-hide text-warning"><b>ripetuti/n.c.</b></span></a>
                    </li>
                    
                    <li role="separator" class="divider"><hr style="margin: 0;"></li>
                    <li class="disabled"><a class="disabled" href="javascript:void(0)"><i class="gi gi-building sidebar-nav-icon"></i>ARCHIVIO</a></li>
                    
                    <?php if (!$_SESSION['tutor']['is_tutor_with_single_company']) { ?>
                    
                    <li>
                        <a href="/bk-clienti.php?scelta=companies" class="<?= ($selected == "companies" ? "active" : "") ?>">
                            <span class="sidebar-nav-mini-hide">elenco clienti</span></a>
                    </li>
                    
                    <?php } ?>
                    
                    <li>
                        <a href="/bk-employee.php?scelta=lavoratori" class="<?= ($selected == "lavoratori" ? "active" : "") ?>"  title="Lavoratori">
                            <span class="sidebar-nav-mini-hide">elenco utenti</span></a>
                    </li>
                    <li>
                        <a href="bk-feedback.php">
                            <span class="sidebar-nav-mini-hide">feedback</span></a>
                    </li>
                    <li>
                        <a href="bk-sessions.php">
                            <span class="sidebar-nav-mini-hide">tracciamento</span></a>
                    </li>
                    <!-- <li <?= $_SESSION['user']['role'] == 1000 ? '' : 'class="disabled"'?>>
                        <a href="/bk-aggiungi-notizia.php?scelta=notizia" class="<?= ($selected == "notizia" ? "active" : "") ?>">
                            <i class="gi gi-text_width sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide"> Aggiungi notizia</span></a>
                    </li>
                    <li <?= $_SESSION['user']['role'] == 1000 ? '' : 'class="disabled"'?>>
                        <a href="#">
                            <i class="gi gi-list sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide"> Elenco notizie</span></a>
                    </li> -->
                    <li class="disabled" style="display: none;">
                        <a href="#"><i class="fa fa-envelope-o sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide"> Invia corso di prova</span></a>
                    </li>
                    
                <?php if ($_SESSION['user']['role'] == 1000) { ?>
                    
                    <li role="separator" class="divider">
                        <hr style="margin: 0;">
                    </li>
                    <li class="disabled"><a class="disabled" href="javascript:void(0)">
                            <i class="gi gi-lock sidebar-nav-icon"></i>AREA RISERVATA</a>
                    </li>
                    <li>
                        <a href="/bk-plans.php?scelta=edit" class="<?= ($selected == "edit" ? "active" : "") ?>">
                            <span class="sidebar-nav-mini-hide">Edita piani</span></a>
                    </li>
                    <li>
                        <a href="/bk-company-plans.php?scelta=company-plans-active" class="<?= ($selected == "company-plans-active" ? "active" : "") ?>">
                            <span class="sidebar-nav-mini-hide">Piani aziende Attivi</span></a>
                    </li>
                    <li>
                        <a href="/bk-company-plans.php?scelta=company-plans-suspended" class="<?= ($selected == "company-plans-suspended" ? "active" : "") ?>">
                            <span class="sidebar-nav-mini-hide">Piani aziende Sospesi</span></a>
                    </li>
                    <li style="display: none;">
                        <a href="#"><i class="fa fa-television sidebar-nav-icon"></i><span class="sidebar-nav-mini-hide"> Catalogo multimediale</span></a>
                    </li>
                <?php } ?>
                </ul>

            <?php } else {
                header("Location: logout.php.php");
                exit();
            } ?>

        </div>
        <!-- END Sidebar Content -->
    </div>
    <!-- END Wrapper for scrolling functionality -->
</div>
<script>
$('.sidebar-menu-left li:not(.disabled) a').click(function(){$.isLoading({text: "Attendere ..."})});
</script>