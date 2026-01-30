<?php
require_once "config.php";
require_once BASE_LIBRARY_PATH . "class_learning_project.php";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Fondazione Libellula Piattaforma di E-Learning</title>
    <meta name="viewport" content="width=device-width" />
    <style type="text/css">
        @media only screen and (max-width: 550px), screen and (max-device-width: 550px) {
            body[yahoo] .buttonwrapper { background-color: transparent !important; }
            body[yahoo] .button { padding: 0 !important; }
            body[yahoo] .button a { background-color: #F7EFE4; padding: 15px 25px !important; }
        }

        @media only screen and (min-device-width: 601px) {
            .content { width: 600px !important; }
            .col387 { width: 387px !important; }
        }
    </style>
</head>
<body bgcolor="#F7EFE4" style="margin: 0; padding: 0;" yahoo="fix">
<!--[if (gte mso 9)|(IE)]>
<table width="600" align="center" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td>
<![endif]-->
<table align="center" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 600px;" class="content">
    <tr>
        <td style="padding: 15px 10px 15px 10px;">
<!--            <table border="0" cellpadding="0" cellspacing="0" width="100%">-->
<!--                <tr>-->
<!--                    <td align="center" style="color: #aaaaaa; font-family: Arial, sans-serif; font-size: 12px;">-->
<!--                        Email not displaying correctly?  <a href="#" style="color: #1bbae1;">View it in your browser</a>-->
<!--                    </td>-->
<!--                </tr>-->
<!--            </table>-->
        </td>
    </tr>
    <tr>
        <td align="center" bgcolor="#DB4949" style="padding: 15px 15px 0 15px ; color: #ffffff; font-family: 'Helvetica Neue', sans-serif; font-weight: bold;">
            <div style="background-color:  #FD765D; padding-bottom: 10px;">
                <img src="<?=HUB_URL?>/media/img/company/2978-white.png" alt="Logo Fondazione Libellula" style="height: 44px; padding-top: 20px;"/><br />
                <h2 style="font-size: 16px; padding-top: 15px;"> Devi svolgere un corso obbligatorio</h2>
            </div>
        </td>
    </tr>
    <tr>
        <td align="center" bgcolor="#DB4949" style="padding: 5px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 20px; line-height: 30px;">
            <p style="color: #fff;"> Licenza per il corso: <b> <?= T81LearningProject::formatTitle($learning_project["learning_project_title"])?></b></p>
        </td>
    </tr>
    <tr>
        <td align="center" bgcolor="#FD765D" style=" color: #555555; font-family: Arial, sans-serif; font-size: 20px; line-height: 30px; ">
            <div style="border: 15px; border-style: none solid ; padding: 20px; border-color:#DB4949;">
                <table bgcolor="#F7EFE4" border="0" cellspacing="0" cellpadding="0" class="buttonwrapper">
                    <tr>
                        <td height="50" style=" padding: 0 25px 0 25px; font-family: Arial, sans-serif; font-size: 14px; font-weight: bold;" class="button">
                            <p style="padding: 20px;">
                            <span> Buongiorno <b style="color: #DB4949;"><?= $assigned_user ? $assigned_user["surname"]." ".$assigned_user["name"] : ""?></b>,</span><br/>
                            <span> Sei stato iscritto al seguente corso: <b style="color: #DB4949;"> <?=T81LearningProject::formatTitle($learning_project["learning_project_title"])?></b> </span><br/>
                            <span> Potrai iniziare a partire dal giorno: <b style="color: #DB4949;"><?=date('d/m/Y',strtotime($lpu_detail["starting_from"]))?></b> </span><br/>
                            <span> E terminare entro il giorno: <b style="color: #DB4949;"><?=date('d/m/Y',strtotime($lpu_detail["finish_within"]))?></b> </span><br/>
                            <span> Il tuo referente per questo corso è: <b style="color: #DB4949;"><?=$tutor_user["surname"]?> <?=$tutor_user["name"]?> - <?= strtolower($tutor_user["email"]) ?></b></span><br/>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
    <tr>
        <td align="center" bgcolor="#FD765D" style=" color: #555555; font-family: Arial, sans-serif; font-size: 20px; line-height: 30px; ">
            <div style="border: 15px; border-style: none solid ; padding: 20px; border-color:#DB4949; ">
                <table bgcolor="#F7EFE4" border="0" cellspacing="0" cellpadding="0" class="buttonwrapper">
                    <tr>
                        <td align="center" height="50" style=" padding: 0 25px 0 25px; font-family: Arial, sans-serif; font-size: 20px; font-weight: bold;" class="button">
                            <h4 style="text-align: center; margin-bottom:5px;" >
                                Per accedere al corso clicca su avvia corso e inserisci
                                il tuo nome utente in questo modo:</h4>
                            <h2 style="text-align: center; text-decoration: none; padding: 15px;"><?=$assigned_user["username"]?></h2>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
    <tr style="margin-bottom: 20px;">
        <td align="center" bgcolor="#F7EFE4" style=" color: #555555; font-family: Arial, sans-serif; font-size: 20px; line-height: 30px; ">
            <div style="border: 15px; border-style: none solid ; padding: 20px; border-color:#DB4949; ">
                <span style="font-size: 14px;"><b>Se vuoi avviare il corso clicca qui</b></span>
                <table bgcolor="#9b59b6" border="0" cellspacing="0" cellpadding="0" class="buttonwrapper">
                    <tr>
                        <td align="center" height="50" style=" padding: 30px 25px 30px 25px; font-family: Arial, sans-serif; background-color: #FD765D; font-size: 20px; font-weight: bold;" class="button">
                            <a href="<?=$url_avviacorso?>" style="color: #F7EFE4; text-align: center; text-decoration: none; padding: 30px 15px;">AVVIA CORSO</a>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
    <tr>
        <td bgcolor="#FD765D" style=" font-family: Arial, sans-serif; font-size: 12px; line-height: normal;">
            <div style=" border: 15px;  border-style:  none solid none solid; padding: 15px ; border-color:#DB4949; ">
                <h2 style="text-align: center; margin-bottom: 10px; font-weight: 400; ">
                    <a href="https://www.tutor81.it/come-si-avvia-un-corso/">ISTRUZIONI PER IL CORSO</a>
                </h2>
            </div>
        </td>
    </tr>
    <tr>
        <td align="left" bgcolor="#FD765D" style=" color: #F7EFE4 ; font-family: Arial, sans-serif; font-size: 12px; line-height: 18px;">
            <div style=" border: 15px;  border-style:  none solid none solid; padding: 15px ; border-color:#DB4949; ">
                <p>Il tuo referente per questo corso può essere contattato per E-Mail scrivendo a <?=$tutor_company["business_name"]?><br/>
                    <?=$tutor_company["address"]?>, <?=$tutor_company["city"]?><br />
                    E-Mail: <a href="mailto:<?= $tutor_user["email"] ?>"><?= $tutor_user["email"] ?></a>
                </p>
                <p> Al termine del corso potrai scaricare il tracciato di avvenuta formazione</p>
                <p> IL CORSO PU&Ograve; ESSERE INTERROTTO  con il pulsante ESCI in alto a sinistra. Riaccendeno al corso questo ripartir&agrave; dall'ultimo punto utile.</p>
                <p> PAUSA: puoi fermare temporaneamente il corso con il pulsante Ferma, ma solo per 30 secondi, terminati i quali il corso viene interrotto.</p>
                <p> ASSISTENZA TECNICA: In ogni momento &egrave; possibile inviare una segnalazione anche tramite mail dal pulsante Richiedi Assistenza oppure scrivete a <a href="mailto:<?=$tutor_user["email"]?>"><?=$tutor_user["email"]?></a></p>
            </div>
        </td>
    </tr>
    <tr>
        <td style="padding: 0 0 30px 0;" >
            <div style=" border: 15px;  border-style:  none solid none solid; border-color:#DB4949; ">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td align="center" width="100%" style="padding: 0 20px; color: #fff;  background-color: #DB4949; font-family: Arial, sans-serif; font-size: 12px;">
                            <h3>FONDAZIONE LIBELLULA IMPRESA SOCIALE</br>
                                Viale Ortles, 54/4 - 25064 Milano
                            </h3>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>
<!--[if (gte mso 9)|(IE)]>
</td>
</tr>
</table>
<![endif]-->
</body>
</html>