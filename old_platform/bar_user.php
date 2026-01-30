<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 25-lug-2015
 * File: bar-tutor.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
require_once 'lib/check_session.php';

$brand = file_exists("media/img/company/{$_SESSION['tutor']['id']}.png") ?
        '<img src="media/img/company/' . $_SESSION['tutor']['id'] . '.png" alt="logo Ente Formativo" style="height: 44px;"/>' :
        '<span style="line-height: 44px;">' . strtoupper($_SESSION['tutor']['business_name']) . '</span>';
$business_name = $area === 'company' ? strtoupper($_SESSION['company']['business_name']) : strtoupper($_SESSION['tutor']['business_name']);
$tutor_address = ucwords($_SESSION['tutor']['address']);
$user_name = strtoupper("{$_SESSION['user']['name']} {$_SESSION['user']['surname']}");
//$user_name = ($_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32) ? strtoupper("{$_SESSION['user']['name']} {$_SESSION['user']['surname']}") : '';
?>
<nav class="navbar navbar-fixed-top navbar-user">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-tutor" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Menu di navigazione</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?= $_SESSION['user']['role'] == 1000 ? 'tutor' : 'index.php' ?>" 
               style="position: absolute; line-height: 16px; padding-top: 0;">
                <span style="font-size: 14px;"><strong>Ente formativo</strong></span><br>
            <?= $brand ?>
            </a>
            <!--
            <div class="navbar-text">
                <h3><?= $business_name ?></h3>
                <p><?= $tutor_address ?><br><?= $user_name?></p>
            </div>
            -->
        </div>
        <div id="navbar-tutor" class="navbar-collapse collapse text-center">
            <ul class="nav navbar-nav" style="display: inline-block; float: none;">
                <li>
                    <a href="javascript:history.back()" class="btn">
                        <span class="glyphicons left_arrow"></span>
                    </a>
                </li>
                
                <?php if ($_SESSION['user']['role'] == 1000 || $_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32 || $_SESSION['user']['role'] == 2) { ?>
                
                <li>
                    <span class="handwrite2 text-nowrap text-center" style="position: absolute; top: -17px; left: 0px; width: 100%;">Download</span>
                    <a href="<?= $area ?>/download" class="btn">
                        <span class="glyphicons paperclip"></span>
                    </a>
                </li>
                
                <li>
                    <span class="handwrite2 text-nowrap text-center" style="position: absolute; top: -17px; left: 0px; width: 100%;">Catalogo corsi</span>
                    <a href="<?= $area ?>/elearning-projects" class="btn">
                        <span class="glyphicons book"></span>
                    </a>
                    <!-- <ul class="list-unstyled text-left">
                        <li><a href="<?= $area ?>/elearning-projects" class="handwrite2 text-nowrap">Catalogo corsi</a></li>
                        <li><a href="#" class="handwrite2 text-nowrap">Elenco autori</a></li>
                        <li><a href="<?= $area ?>/objects" class="handwrite2 text-nowrap">Oggetti multimediali</a></li>
                    </ul> -->
                </li>
                
                <?php } ?>
                
                <li>
                    <span class="handwrite2 text-nowrap text-center" style="position: absolute; top: -17px; left: 0px; width: 100%;">Istruzioni</span>
                    <a href="http://www.tutor81.com/#!la-piattaforma/o335o" class="btn">
                        <span class="glyphicons circle_question_mark"></span>
                    </a>
                </li>
                <li>
                    <span class="handwrite2 text-nowrap text-center" style="position: absolute; top: -17px; left: 25px; width: 100%;">Bisogno di aiuto?</span>
                    <a href="mailto:assistenza@tutor81.it" class="btn">
                        <span class="glyphicons envelope"></span>
                    </a>
                </li>
                <li>
                    <a href="javascript: void(0)" onclick="return SnapEngage.startLink();" class="btn">
                        <span class="glyphicons conversation"></span>
                    </a>
                </li>
                
                <li>
                    <span class="handwrite2 text-nowrap text-center" style="position: absolute; top: -17px; left: 0px; width: 100%;">Logout</span>
                    <a href="logout.php" class="btn">
                        <span class="glyphicons log_out"></span>
                    </a>
                </li>
                    
            </ul>
            <a class="navbar-brand" href="<?= $_SESSION['user']['role'] == 1000 ? 'index.php' : 'javascript: void(0)' ?>" style="position: absolute; right: 0;">
                <img src="img/tutor81_logo_2016.png">
            </a>
        </div>
    </div>
</nav>