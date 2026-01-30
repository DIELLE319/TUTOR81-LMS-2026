<?php

require_once 'config.php';

require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_user.php';

$company_obj = new T81Company();
$user_obj = new T81User();

// Point to right tutor in base of domain tutor81 alias add in any ecommerce page
$tutor = $company_obj->getCompanyByServerAlias($_SERVER['SERVER_NAME']);
if (!isset($tutor) || $tutor == false) {
    $tutor = $company_obj->getCompanyByID(2);//$company_obj->getCompanyByServerAlias("pre-amm.tutor81.com"); //hub.tutor81.local
}
$tutor["logo"] = T81Company::getEcommerceLogo($tutor);
$color_dark = $tutor['id'] == 688 ? '#101313' : '#394263';
$color_light = $tutor['id'] == 688 ? '#ED1C23' : '#1BBAE1';

$questionIndex = floor(rand(1, 3));

// Nel caso viene esplorata la pagina direttamente senza codice licenza viene mostrata la pagina direttamente

// Nel caso in cui ci sia un codice licenza viene richiesto il codice fiscale di verifica e una volta verificato
// viene fatto il submit della form verso AVVIACORSO_OLD_URL/prelogin.php

// Nel caso venga esplorata da un utente non ancora codificato viene aperta la pagina con la form di inserimento dati e
// una volta inserito viene fatto il submit della form verso AVVIACORSO_OLD_URL/prelogin.php


$licence_code = key_exists('course', $_GET) ? $_GET['course'] : "";

$user_cod_fisc = "";
$isFirstRegistration = false;
$isRegistration = false;
$isModalVerification = false;
$isModalRegistration = false;

if ($licence_code != "") {

    // Recupero i dati del learning_project_user
    require_once BASE_LIBRARY_PATH . 'class_course.php';
    $c_obj = new iWDCourse();
    $lpu = $c_obj->getLearningProjectUserByLicence($licence_code);

    if ($lpu != false)
    {
        
        // Verifico se il learning_project_user ha un utente associato
        if ($lpu["user_id"] > 0) {
            $user_detail = $user_obj->getDetail($lpu["user_id"]);
            $user_name = $user_detail['username'];
            
            // Avendo un utente associato mostro la modale del codice fiscale
            //$isRegistration = false;
            $isModalVerification = true;
            //$isModalRegistration = false;
        }
        else {
        
            // Se ho dati inviati via POST sto registrando un utente
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
                $learning_project_obj = new T81LearningProject();

                //TODO: check if alredy exist a user on licence code send directly to avviacorso
                //require_once BASE_LIBRARY_PATH . 'class_course.php';
                //$c_obj = new iWDCourse();
                //$lpu = $c_obj->getLearningProjectUserByLicence($licence_code);

                $user_nome = trim(filter_input(INPUT_POST, 'user_nome', FILTER_SANITIZE_STRING));
                $user_cognome = trim(filter_input(INPUT_POST, 'user_cognome', FILTER_SANITIZE_STRING));
                $user_cod_fisc = trim(filter_input(INPUT_POST, 'user_cod_fisc', FILTER_SANITIZE_STRING));
                $destination_email = trim(filter_input(INPUT_POST, 'user_email', FILTER_SANITIZE_STRING));
                //$type_id = $_POST['type_id'];

                //$company_id = $_POST['customercompany_id'];


                //$lpu = $learning_project_obj->getLearningProjectUserFromPassword($licence_code);
                $company_id = $lpu["id_company"];

                require_once BASE_LIBRARY_PATH . 'function.php';
                $username = strtolower($user_nome) . '.'. strtolower($user_cognome);
                $password = strtoupper($user_cod_fisc);

                try {
                    $user = $user_obj->getUserByTaxCode($user_cod_fisc);
                    if ($user) {
                        if ($user['company_id'] != $company_id) {
                            /* TODO: FIX ERROR 3
                            $tutor_reference = $user_obj->getDetail($lpu['company_id']);
                            $tutor_company = $company_obj->getBusinessDetail($tutor_reference['company_id']);
                            $tutor_user = $user_obj->getDetail($tutor_company['owner_user_id']);
                             */
                            throw new Exception(3);
                        }
                        $user_id = $user['id'];                        
                    } else {
                        $user_id = $user_obj->createUser($lpu["company_id"], 0, $user_nome, $user_cognome,
                            $username, $password, $destination_email, $company_id, $user_cod_fisc);
                        if(!is_numeric($user_id)) {
                            throw new Exception(4);
                        }
                    }
                    // Change learning_project_users user_id with new one for assign
                    //error_log("change learning project user - lpu_id: {$lpu['id']} - user_id {$user_id}",1, 'zaniol.roberto@gmail.com');
                    $res = $learning_project_obj->changeLearningProjectUserUserID($lpu["id"], $user_id);
                    if ($res) {
                        $isFirstRegistration = true;
                        //TODO: Send notification that a user is registered

                        require_once BASE_LIBRARY_PATH . 'class_notification.php';
                        $not_obj = new Tutor81Notification();

//                        $learningproject_id = $lpu["learning_project_id"];
//                        $learning_project = $learning_project_obj->getCourseDetailFromLearningProject($learningproject_id);
                        $lpu['user_id'] = $user_id;
                        $not_obj->notifyLearningUserRegistration($lpu);

                        // ho completato la registrazione quindi posso redirezionarlo ad avviacorso
                        // rccolgo i dati dell'utente per precompilare il form di verifica e inviarlo automaticamente a prelogin
                        $user_detail = $user_obj->getDetail($user_id);
                        $user_name = $user_detail['username'];

                    }
                    else {
                        // $error = "Non è stato possibile effettuare la registrazione si prega di contattare l'amministrazione";
                        throw new Exception(4);
                    }
                }
                catch (Exception $e) {
                    header ("location: ec-avvia-corso.php?err=" . $e->getMessage());
                    exit();
                }
            } else {
                // Se non ho un utente non associato e non sto effettuando la registrazione mostro la modale di registrazione
                $isRegistration = true;
                //$isModalVerification = false;
                $isModalRegistration = true;
            }
        }

        // redirect to avvia corso
        //header('Location: '.AVVIACORSO_URL."?course=".$licence_code); exit();
    }
//    else {
//        
//        $isModalVerification = false;
//    }

}

require_once 'ecommerce/header.php'; ?>

<body bgcolor="<?= $color_dark ?>" style="margin: 0; padding: 0;"> <!-- attribute yahoo="fix" -->
    <div class="modal fade container" id="registerBeforeStartCourseForm" role="dialog">
        <form id="avviaCorsoForm" name="avviaCorsoForm" method="POST" action="">
        <!--[if (gte mso 9)|(IE)]>
        <table width="600" align="center" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td>
        <![endif]-->
        <table align="center" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; text-align: center;  max-width: 600px;" class="content">
                    <tr><td style="padding: 15px 10px 15px 10px;"></td></tr>
                    <tr>
                        <td align="center" bgcolor="#<?= $color_light ?>" style=" color: #ffffff; font-family: Arial,  sans-serif; padding: 20px; font-weight: bold;">
                            <div class="logo pull-left" style="display: inline-block;">
                                <img src="/img/launch.png" alt="Launch Icon" width="50" height="50" style="display: inline-flex" />
                            </div>
                            <div class="title" style="display: inline-block;">
                                <h4> Inserisci le tue credenziali e avvia il corso</h4>
                            </div>
                            <div class="pull-right" style="display: inline-block;">
                                    <img src="<?=$tutor["logo"]?>" width="50"><br>
                                    <small> <a href="mailto:assistenza@tutor81.it" target="_blank">Chiedi Aiuto?</a> </small>
                            </div>
                        </td>

                    </tr>
                    <tr>
                        <td align="center" bgcolor="#ffffff" style="padding: 40px 20px 40px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 20px; line-height: 30px; border-bottom: 1px solid #f6f6f6;">
                            <b> Tutti i dati sono obbligatori per legge e saranno riportati nell'attestato finale</b>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#ffffff" style="padding: 10px 20px 5px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 15px; line-height: 24px;">
                            <input type="text" name="user_nome" title="Inserisci il nome" class="form-control" placeholder=" Nome*" required style="width: 50%; display: inline-block;"></td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 10px 20px 5px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 15px; line-height: 24px;">
                            <input type="text" name="user_cognome" title="Inserisci il cognome" class="form-control" placeholder=" Cognome*" required style="width: 50%; display: inline-block;"></td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 10px 20px 5px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 15px; line-height: 24px;">
                            <input type="text" name="user_cod_fisc" class="form-control" title="Inserisci il codice fiscale" placeholder=" Codice Fiscale*" required style="width: 50%; display: inline-block;"></td>
                    </tr>
                <!--    <tr>-->
                <!--        <td bgcolor="#ffffff" style="padding: 10px 20px 5px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 15px; line-height: 24px;">-->
                <!--            <div class="form-group" style="width: 50%; display: inline-block;">-->
                <!--                <select title="Scegli tipo utente" class="tipo_utente form-control input-sm" name="type_id" size="1" required style="color: grey; padding: 3px;">-->
                <!--                    <option value="0">Tipo Utente*</option>-->
                <!--                    <option value="1">Lavoratore</option>-->
                <!--                    <option value="3">Preposto</option>-->
                <!--                    <option value="7">Dirigente</option>-->
                <!--                </select>-->
                <!--            </div>-->
                <!--        </td>-->
                <!--    </tr>-->
                    <tr><td bgcolor="#ffffff" style="padding: 20px 20px 10px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 15px; line-height: 24px;"></td></tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 10px 20px 5px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 15px; line-height: 24px;">
                            <small>Come ti possiamo contattare in caso di esigenza:</small>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 10px 20px 5px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 15px; line-height: 24px;">
                            <input type="radio" checked required>
                            <input type="text" name="user_email" class="form-control" placeholder=" Email*" required style="width: 50%; display: inline-block;">
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 10px 20px 5px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 15px; line-height: 24px;">
                            <input type="radio" disabled>
                            <input type="text" class="form-control" placeholder=" SMS indica numero telefono" style="width: 50%; display: inline-block;">  </td>
                    </tr>
                    <tr>
                        <td bgcolor="#ffffff" style="padding: 10px 20px 20px 20px; color: #555555; width: 50%; font-family: Arial, sans-serif; font-size: 15px; line-height: 24px; border-bottom: 1px solid #f6f6f6;">
                            <div class="dati" style="display: inline-block;">
                                <input type="checkbox" checked required> <a href="https://www.tutor81.it/privacy/" target="_blank">Trattamento dei dati</a>
                            </div>
                            <div class="avvia-corso " style="display: inline-block; margin-left: 10px; ">
                                <button type="submit" class="btn btn-success btn-lg"> Avvia Corso</button>
                            </div>
                       </td>
                    </tr>

                </table>
        <!--[if (gte mso 9)|(IE)]>
                </td>
            </tr>
        </table>
        <![endif]-->
        </form>
    </div>

    <div id="page-container">

    <!-- Intro -->
    <section class="site-section site-section-light themed-background-dark">
        <div class="container text-center">
            <div class="row">
                <div class="text-center" style="min-height: 75px;">
                   <h1 class="animation-slideDown" style="font-size: 55px;"><img src="<?=$tutor["logo"]?>" width="170"></h1>
                </div>
                <div class="col-sm-11 col-md-12"> <h2 class="animation-slideDown" style="font-size: 45px;"><strong>Avvia il tuo corso e-learning </strong></h2></div>
            </div>

        </div>
    </section>
    <!-- END Intro -->

    <!-- Product List -->
    <section class="site-content site-section" style="min-height: 363px;">
        <div class="container">

            <form method="POST" id="loginCorsoForm" action="<?=AVVIACORSO_OLD_URL?>/prelogin.php" role="form">
                <div class="row">
                    <!-- Products -->
                    <div class="col-md-12 text-center">
                        <div class="row store-items" style="color: #394263;">
                            <!-- ALERT -->

                            <div class="col-sm-9 col-sm-offset-1 col-md-6 col-md-offset-1" data-toggle="animation-appear" data-animation-class="animation-fadeInQuick" data-element-offset="-100" style="padding-top: 6px;">
                                <div class="alert alert-danger" id="browser-alert" style="position: relative; top: 10px;">
                                    <p id="no-js">L'esecuzione di Javascript non è abilitata. Per procedere è necessario abilitarla.</p>
                                    <p id="no-cookie" style="display:none;">La registrazione dei cookie non è abilitata.</p>
                                    <p id="no-browser" style="display:none;">Il browser che stai utilizzando è obsoleto.
                                        Installa <a href="http://www.google.it/intl/it/chrome/browser/">Chrome</a>,
                                        <a href="http://www.mozilla.org/it/firefox/new/">Firefox</a> oppure aggiorna il tuo browser.</p>
                                    <p id="no-flash" style="display:none;">Con Internet Explorer 8 è necessario il plugin di Flash. <a href="http://get.adobe.com/it/flashplayer/">Installalo o aggiornalo</a>,
                                        oppure installa	<a href="http://www.google.it/intl/it/chrome/browser/">Chrome</a> o <a href="http://www.mozilla.org/it/firefox/new/">Firefox</a>
                                        <?= key_exists('err', $_GET) && $_GET['err'] == 1 ? '<p id="error">Le credenziali inserite sono errate</p>' : '' ?>
                                        <?= key_exists('err', $_GET) && $_GET['err'] == 2 ? '<p id="error">Account non abilitato. Rivolgiti al tuo referente.</p>' : '' ?>
                                        <?= key_exists('err', $_GET) && $_GET['err'] == 3 ? '<p id="error">ATTENZIONE questo codice fiscale è già esistente e associato ad una diversa Azienda, è necessario contattare ' . strtoupper($tutor_company['business_name']) . ' il Sig. ' . strtoupper($tutor_user['surname']) . ' ' . strtoupper($tutor_user['name']) . ' email ' . strtolower($tutor_company['email']) . '</p>' : '' ?>
                                        <?= key_exists('err', $_GET) && $_GET['err'] == 4 ? '<p id="error">Non è stato possibile effettuare la registrazione si prega di contattare l\'amministrazione</p>' : '' ?>
                                </div> <!-- /browser-alert -->
<!--                                <h4 class="text-right" style="color: #fff; text-transform: uppercase;"> Non ricordo codice</h4>-->
                                <h4><b> Inserisci qui il tuo codice corso </b></h4>
                                <p>
                                    <a href="#helpUsernameModal" data-toggle="modal" style="color: #ff0000;"><b> Non so il mio codice corso ?</b></a></p>
                                <div class="avvia-corso-image" style="position:relative;">
                                    <img src="<?=URL_PLAYER?>img/donna-che-indica.png" class="img-responsive" style="position: absolute; top: -132px; left: 470px;">
                                </div>
                                <div class="inputCodeBox ">
                                    <div class="clearfix" style="background-color: <?= $color_dark ?>; padding: 20px; font-size: 20px; margin-bottom: 10px; color: #fff;">
<!--                                        <p> Inserisci qui i codice licenza che ti abbiamo spedito per email</p>-->
                                        <input class="noEnterSubmit" style="font-size: 40px; text-align:center; min-width: 50%; width: 100%; background-color: <?= $color_dark ?>; border: 0;" type="text" name="course_code" id="usernameOrCourseCode" value="<?=$licence_code?>">
                                    </div>

                                </div>

                            </div>
                            <div class="col-sm-9 col-sm-offset-1 col-md-6 col-md-offset-1" data-toggle="animation-appear" data-animation-class="animation-fadeInQuick" data-element-offset="-100">
                                <div class="col-sm-6 text-left">
                                    <style>
                                        #disclaimerModal, #disclaimer {
                                            color: #0e3b83;
                                        }
                                    </style>
                                    <?php include "ecommerce/checkbox-tos.php";?>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group text-right">
                                        <input id="authUsername" type="hidden" name="username" <?= !empty($user_name) ? 'value="'.$user_name.'"' : '' ?>>
                                        <input id="authTaxCode" type="hidden" name="tax_code" <?= !empty($user_cod_fisc) ? 'value="'.$user_cod_fisc.'"' : '' ?>>
                                        <input type="hidden" value="<?echo $isFirstRegistration;?>" name="isRegistration" id="isRegistration">
                                        <button type="button" class="btn btn-success btn-lg" id="normalFormLogin"> Avvia Corso</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END Products -->
                </div>
            </form>
                <div class="modal fade" id="checkTaxcodeModal" tabindex="-1" role="dialog"
                     aria-labelledby="checkTaxcodeModalLabel">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content" style="background-color: #EDEDEF;">
                            <div class="modal-body" style="padding: 50px;">
                                <div class="text-center">
                                    <span class="glyphicons glyphicons-very-lg keys pull-left"></span>
                                    <img src="<?=$tutor["logo"]?>" width="170">
                                </div>
                                <h3 class="questions" style="margin-top: 50px;">devi confermare l'identità:&nbsp;
                                    <strong>
                                        <span class="q1" style="display: none;">inserisci il giorno (numero) in cui sei nato e schiaccia invia</span>
                                        <span class="q2" style="display: none;">inserisci il mese (numero) in cui sei nato e schiaccia invia</span>
                                        <!--<span class="q3" style="display: none;">inserisci le prime 3 cifre del tuo codice fiscale e schiaccia invia</span>-->
                                    </strong>
                                </h3>
                                <br>
                                <br>
                                <div class="form-group">
                                    <input type="text" class="form-control input-lg"  name="check_code">
                                    <input id="questionIndex" type="hidden" name="question" value="<?=$questionIndex?>">
                                </div>
                                <br>
                                <div class="login-error"></div>
                                <br>
                                <div class="form-group text-center">
                                    <button class="btn btn-success btn-lg check-taxcode" id="taxCodeButton">INVIA</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



        </div>
    </section>
    <!-- END Product List -->



    <!-- Footer -->
    <footer class="">

        <!-- Intro -->
        <section class="site-section site-section-light site-section-top themed-background-dark">
            <div class="container">
                <div class="row text-center">
                    <div class="col-sm-4  animation-fadeIn">
                        <a href="https://vimeo.com/188692091" class="circle themed-background" target="_blank">
                            <i class="gi gi-facetime_video"></i>
                        </a>
                        <h4><strong> Guarda come funziona</strong></h4>
                    </div>
                    <div class="col-sm-4  animation-fadeIn">
                        <a href="mailto:assistenza@tutor81.it" class="circle themed-background">
                            <i class="gi gi-envelope"></i>
                        </a>
                        <h4><strong> Supporto tecnico</strong></h4>
                    </div>
                    <div class="col-sm-4  animation-fadeIn">
                        <a href="https://www.tutor81.it/regolamento/" class="circle themed-background" target="_blank">
                            <i class="fa fa-share-square-o "></i>
                        </a>
                        <h4><strong> Come si ottiene l'attestato</strong></h4>
                    </div>
                </div>
            </div>
        </section>
        <!-- END Intro -->

        <section class="site-section site-section-light" style="background-color: #F3F3F3;">
            <div class="container">
                <div class="row">
                    <div class="col-md-11 col-md-offset-1">
                        <div class="underFooterContent">
                            <p><b>IL CORSO PUO' ESSERE INTERROTTO </b>con il pulsante <b>ESCI/STOP </b>in alto a destra. Riaccedendo al corso questo ripartirà dall'ultimo punto utile.</p>
                            <p><b>I TEST </b> hanno una durata temporizzata di <i>30 secondi</i>, trascorsi i quali il corso si interrompe e dovrai riprendere la lezione.</p>
                            <p><b>PAUSA </b> puoi fermare temporaneamente il corso con il pulsante <b>Ferma,</b> ma solo per <i>30 secondi</i>, terminati i quali il corso viene interrotto</p>
                            <p><b>ASSISTENZA </b> in ogni momento è possibile inviare una segnalazione anche tramite mail con il pulsante <b>Richiedi Assistenza  </b></p>
                            <p><b>VIDEO A SCATTA </b> se la vostra banda adsl è satura è possibile che i videosi vedano e si sentano a scatti, in questo</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>


    </footer>
    <!-- END Footer -->

</div>

    <!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
    <a href="#" id="to-top"><i class="fa fa-angle-up"></i></a>

    <div class="modal fade" id="helpUsernameModal" tabindex="-1" role="dialog"
         aria-labelledby="helpUsernameModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="background-color: #EDEDEF;">
                <div class="modal-body" style="padding: 40px 40px 0 40px;">
                    <div class="text-center">
<!--                        <span class="glyphicons glyphicons-very-lg circle_info pull-left"></span>-->
                        <i class="fa fa-info-circle fa-4x pull-left"></i>
                        <img src="img/tutor81_logo_2016.png">
                    </div>
                    <h4>Se non ricordi il tuo codice corso prova ad inserire il tuo Nome e Cognome devi scriverlo in questo modo esempio:</h4>
                    <h4 class="text-center"><strong> PAOLO.ROSSI </strong></h4>
                    <h4>Metti il punto tra il nome e il cognome e poi rispondi alle domande sul tuo codice fiscale</h4>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="helpTaxcodeModal" tabindex="-1" role="dialog"
         aria-labelledby="helpTaxcodeModalLabel">
        <div class="modal-dialog" role="document" >
            <div class="modal-content" style="background-color: #DC3925; color: #fff;">
                <div class="modal-body" style="padding: 50px;">
                    <div class="text-center">
<!--                        <span class="glyphicons glyphicons-very-lg warning_sign pull-left"></span>-->
                        <img class="pull-left" src="img/warning-sign.PNG">
                        <h1 class="animation-slideDown" style="font-size: 55px;"><img src="<?=$tutor["logo"]?>" width="170"></h1>
                    </div>
                    <h3 class="questions" style="margin-top: 50px;">Codice fiscale errato</h3>
                    <h4>Se non riesci ad accedere perché le domande sul tuo
                        codice fiscale non sono accettate dal sistema, è
                        probabile che il codice fiscale sia stato inserito in modo
                        scorretto da chi ha creato questo corso.</h4>
                    <h4>Puoi scrivere direttamente a <a href="mailto:<?= $tutor["email"] ?> "><?=$tutor["email"]?></a> chiedendo la verifica del
                        codice fiscale oppure
                        <a href="javascript: void(0);" class="help">clicca qui</a>
                        per avvisarlo del problema.</h4>
                </div>
                <div class="modal-footer" style="background-color: #f04124;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="background-color: #EDEDEF;">
                <div class="modal-body" style="padding: 40px 40px 0 40px;">
                    <div class="text-center">
                        <span class="glyphicons glyphicons-very-lg message_full pull-left"></span>
                        <h1 class="animation-slideDown" style="font-size: 55px;"><img src="<?=$tutor["logo"]?>" width="170"></h1>
                    </div>
                    <h3>RICHIEDI ASSISTENZA</h3>
                    <p>Compila i tuoi dati</p>
                    <form class="form-horizontal" name="help">
                        <div class="form-group">
                            <label for="name" class="col-sm-2 control-label">Nome*</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="name" placeholder="Nome">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="surname" class="col-sm-2 control-label">Cognome*</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="surname" placeholder="Cognome">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="company" class="col-sm-2 control-label">Azienda*</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="company" placeholder="Azienda">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="taxcode" class="col-sm-2 control-label">Codice fiscale*</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" name="taxcode" placeholder="Codice fiscale">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email" class="col-sm-2 control-label">Email*</label>
                            <div class="col-sm-10">
                                <input type="email" class="form-control" name="email" placeholder="Email">
                            </div>
                        </div>
                        <fieldset>
                            <legend>Che problema hai?</legend>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="problem" value="1"> Non ricordo il nome utente
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="problem" value="2"> Non riconosce il mio nome utente
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="problem" value="3"> Non riconosce il mio codice fiscale
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="problem" value="4"> Non so cosa fare
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                    <p>L'assistenza tecnica risponde immediatamente negli orari di lavoro.</p>
                </div>
                <div class="modal-footer">
                    <button id="sendhelpButton" type="button" class="btn btn-default send-help" >Invia</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Chiudi</button>
                </div>
            </div>
        </div>
    </div>


    <script>
        console.log("FIRST REGISTRATION: ");
        console.log($("#isRegistration"));
        console.log($("#isRegistration").val());
        $(document).ready(function () {

            <?php if(key_exists('err', $_GET)){ ?>

            $('#browser-alert').show().find('#error').show();

            <?php } ?>

            if ($("#isRegistration").val()) {
                //alert("REDIRECT FIRST REGISTRATION: " + $("#authTaxCode").val() + " AND " + $('#usernameOrCourseCode').val());
                $("#loginCorsoForm").submit();
            }

            function verificaCookie() {
                document.cookie = 'verifica_cookie';
                var testcookie = (document.cookie.indexOf('verifica_cookie') != -1) ? true : false;
                return testcookie;
            }

            function scriviCookie(nomeCookie,valoreCookie,durataCookie) {
                var scadenza = new Date();
                var adesso = new Date();
                scadenza.setTime(adesso.getTime() + (parseInt(durataCookie)));
                document.cookie = nomeCookie + '=' + escape(valoreCookie) + '; expires=' + scadenza.toGMTString() + '; path=/';
            }

            function leggiCookie(nomeCookie) {
                if (document.cookie.length > 0) {
                    var inizio = document.cookie.indexOf(nomeCookie + "=");
                    if (inizio != -1) {
                        inizio = inizio + nomeCookie.length + 1;
                        var fine = document.cookie.indexOf(";",inizio);
                        if (fine == -1) fine = document.cookie.length; {
                            return unescape(document.cookie.substring(inizio,fine));
                        }
                    } else {
                        return "";
                    }
                }
                return "";
            }

            $('.modal .help').click(function(){
                $(this).parents('.modal').modal('hide');
                $('#helpModal').modal();
            });

            $('#avviaCorsoForm input[type=text]').on('change invalid', function() {
                var textfield = $(this).get(0);

                // 'setCustomValidity not only sets the message, but also marks
                // the field as invalid. In order to see whether the field really is
                // invalid, we have to remove the message first
                textfield.setCustomValidity('');

                if (!textfield.validity.valid) {
                    textfield.setCustomValidity('Compilare questo campo');
                }
            });


            $('#normalFormLogin').click(function(e){
                e.preventDefault();
                if ($('#register-terms').is(':checked')){
                    var tentativo = leggiCookie('tentativo');
                    tentativo = tentativo ? tentativo : 0;
                    if (tentativo < 4) {
                        $.ajax({
                            url: 'ecommerce/license.php',
                            async: false,
                            type: "POST",
                            data: {
                                op_type: "get_user_by_course_code_or_username",
                                loginstring: $('#usernameOrCourseCode').val()
                            },
                            success: function(user){
                                console.log(user);
                                //alert("");
                                if (user == 0) {
                                    location.href = "ec-avvia-corso.php?err=1";
                                } else {
                                    user = $.parseJSON(user);
                                    if (user.user_id == 0) {
                                        // redirect to registration form
                                        location.href = "ec-avvia-corso.php?course=" + $('#usernameOrCourseCode').val();
                                    } else {
                                        q = Math.floor(Math.random() * 2) + 1;
                                        scriviCookie('tentativo','1',60000);
                                        $('#authUsername').val(user.username);
                                        //$('#checkTaxcodeModal .username').html(user.username.toUpperCase());
                                        $('#checkTaxcodeModal .questions .q' + q).show().siblings().hide();
                                        //$('#checkTaxcodeModal input[name="user_id"]').val(user.id);
                                        $('#checkTaxcodeModal input[name="question"]').val(q);
                                        $('#checkTaxcodeModal').modal();
                                    }
                                }
                            }
                        });
                    } else {
                        alert('Hai già fatto 3 tentativi. Attendi un minuto prima di riprovare.');
                    }
                } else {
                    alert("Per procedere devi accettare i termini e le condizioni d'uso.");
                }

            });

            $('#taxCodeButton').click(function(e){
                e.preventDefault();
                console.log("VERIFICA CODICE FISCALE");
                var tentativo = leggiCookie('tentativo');
                tentativo = tentativo ? tentativo : 0;
                if (tentativo > 3) {
                    alert('Hai già fatto 3 tentativi. Attendi un minuto prima di riprovare.');
                    return false;
                }
                $.ajax({
                    url: 'ecommerce/license.php',
                    async: false,
                    type: "POST",
                    data: {
                        op_type: "check_tax_code_validation",
                        code: $('#checkTaxcodeModal input[name="check_code"]').val(),
                        usernane_or_course: $('#usernameOrCourseCode').val(),
                        question: $('#questionIndex').val()
                    },
                    success: function(tax_code){
                        if (tax_code == 0) {
                            scriviCookie('tentativo',++tentativo,60000);
                            if (tentativo < 4) {
                                q = Math.floor(Math.random() * 3) + 1;
                                $('#questionIndex').val(q);
                                $('#checkTaxcodeModal').find('.q' + q).show().siblings().hide();
                                $('.login-error').html('<h4 class="text-center">Hai ancora ' + (4 - tentativo) + ' tentativ' + (tentativo == 3 ? 'o.' : 'i.') + '</h4>');
                            } else {
                                //$('.login-error').html('<h4 class="text-center">Hai già fatto tre tentativi. Attendi un minuto prima di riprovare.</h4>');
                                $('#checkTaxcodeModal').modal('hide');
                                $('#helpTaxcodeModal').modal();
                            }
                        }
                        else {
                            $("#authTaxCode").val(tax_code);
                            //alert("REDIRECT WIDTH: " + tax_code + " AND " + $('#usernameOrCourseCode').val());
                            $("#loginCorsoForm").submit();
                        }
                    }
                });
            });

            $('#sendhelpButton').click(function(){
                //alert("Invio richiesta ticket");
                console.log("Click invio richiesta di aiuto");
                var name = $('#helpModal input[name="name"]').val();
                var surname = $('#helpModal input[name="surname"]').val();
                var company = $('#helpModal input[name="company"]').val();
                var taxcode = $('#helpModal input[name="taxcode"]').val();
                var email = $('#helpModal input[name="email"]').val();
                if (name == "" || surname == "" || company == "" || taxcode == "" || !validateEmail(email)) {
                    alert("Compilare correttamente tutti i campi.");
                    return false;
                }

                $.ajax({
                    url: 'ecommerce/license.php',
                    async: false,
                    type: "POST",
                    data: {
                        op_type: 'send_help_request',
                        name: name,
                        surname: surname,
                        company_name: company,
                        taxcode: taxcode,
                        email: email,
                        problem: $('#helpModal input[name="problem"]:checked').parent().text(),
                        problem_id: $('#helpModal input[name="problem"]:checked').val(),
                        username: $('#username').val()
                    },
                    success: function(data){
                        console.log("Ritorno invio notifica di help");
                        console.log(data);
                        alert('Messaggio inviato');
                    }
                });
                $('#helpModal').modal('hide');
            });

            <?php if ($isModalRegistration) { ?>
                $('#registerBeforeStartCourseForm').modal();
                $('#registerBeforeStartCourseForm').show();
            <?php } if ($isModalVerification) { ?>
                $('#checkTaxcodeModal').modal();
                $('#checkTaxcodeModal').show();
            <?php } ?>

            $('#browser-alert').hide().find('#no-js').hide();
            <?php if(key_exists('err', $_GET)){ ?>
            $('#browser-alert').show().find('#error').show();
            <?php } ?>



            $("#usernameOrCourseCode").focus();
            var coockie_accept = verificaCookie();
            if (coockie_accept == false){
                $('#browser-alert').show().find('#no-coockie').show();
            }

//            if (bowser.msie) {
//                if (bowser.version < 8) {
//                    $('#browser-alert').show().find('#no-browser').show();
//                } else if (bowser.version < 9 && !swfobject.hasFlashPlayerVersion("10.3")) {
//                    $('#browser-alert').show().find('#no-flash').show();
//                } else {
//                    $('#prelogin').show();
//                }
//            } else if (bowser.chrome && bowser.version < 17) {
//                $('#browser-alert').show().find('#no-browser').show();
//            } else if (bowser.firefox && bowser.version < 14) {
//                $('#browser-alert').show().find('#no-browser').show();
//            } else if (coockie_accept == true) {
//                $('#prelogin').show();
//            }


            // Prendendo un numero random faccio apparire una delle domande

            scriviCookie('tentativo','<?=$questionIndex?>',60000);
            $('#checkTaxcodeModal').find('.q' + <?=$questionIndex?>).show().siblings().hide();
//            $('#checkTaxcodeModal input[name="user_id"]').val();
//            $('#checkTaxcodeModal input[name="question"]').val(q);


            // make enter not submit course code
            $('.noEnterSubmit').keypress(function(e){
                if ( e.which == 13 ) return false;
                //or...
//                if ( e.which == 13 ) e.preventDefault();
            });

        });

    </script>
</body>

