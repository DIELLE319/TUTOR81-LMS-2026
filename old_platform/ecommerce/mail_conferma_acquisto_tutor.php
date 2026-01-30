<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
$company_obj = new T81Company();

// Point to right tutor in base of domain tutor81 alias add in any ecommerce page
$tutor = $company_obj->getCompanyByServerAlias($_SERVER['SERVER_NAME']);
if (!isset($tutor)) {
    $tutor = $company_obj->getCompanyByServerAlias("pre-amm.tutor81.com"); //hub.tutor81.local
}
$tutor["logo"] = HUB_URL."/media/img/company/".$tutor["id"].".png";
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Tutor81 Piattaforma di E-Learning</title>
    <meta name="viewport" content="width=device-width" />
    <style type="text/css">
        @media only screen and (max-width: 550px), screen and (max-device-width: 550px) {
            body[yahoo] .buttonwrapper { background-color: transparent !important; }
            body[yahoo] .button { padding: 0 !important; }
            body[yahoo] .button a { background-color: #31313A; padding: 15px 25px !important; }
        }

        @media only screen and (min-device-width: 601px) {
            .content { width: 600px !important; }
            .col387 { width: 387px !important; }
        }
    </style>
</head>
<body bgcolor="#31313A" style="margin: 0; padding: 0;" yahoo="fix">
<!--[if (gte mso 9)|(IE)]>
<table width="600" align="center" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td>
<![endif]-->
<table align="center" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 600px;" class="content">
    <tr>
        <td style="padding: 15px 10px 15px 10px;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td align="center" style="color: #aaaaaa; font-family: Arial, sans-serif; font-size: 12px;">
                        <!--        Email not displaying correctly?  <a href="#" style="color: #9b59b6;">View it in your browser</a>-->
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center" bgcolor="#21C1B8" style="padding: 20px; color: #ffffff; font-family: Arial, sans-serif; font-size: 36px; font-weight: bold;">
            <img src="<?=$tutor["logo"]?>" alt="<?=$tutor["logo"]?>" style="padding: 20px; height: 44px;"/><br />
        </td>
    </tr>
    <tr>
        <td align="center" bgcolor="#ffffff" style="padding: 40px 20px 40px 20px; color: #555555; font-family: Arial, sans-serif; font-size: 20px; line-height: 30px; border-bottom: 1px solid #f6f6f6;">
            <b>Nuova vendita di corsi e-learning!</b><br/>

        </td>
    </tr>
    <tr>
        <td align="center" bgcolor="#ffffff" style="padding: 20px; color: #555555; font-family: Arial, sans-serif; font-size: 20px; line-height: 30px; border-bottom: 1px solid #f6f6f6;">
            Hai venduto il corso:<br/>
            <b><?=  T81LearningProject::formatTitle($learning_project["learning_project_title"])?> della durata di <?=$learning_project["total_duration"]?> ore</b><br/>
        </td>
    </tr>
    <tr>
        <td bgcolor="#ffffff" style="padding: 20px 20px 0 20px; border-bottom: 1px solid #f6f6f6;">
            <table width="100" align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td height="100" style="padding: 0 20px 20px 0;">
                        Pagamento:

                    </td>
                </tr>
            </table>
            <!--[if (gte mso 9)|(IE)]>
            <table width="387" align="left" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td>
            <![endif]-->
            <table class="col387" align="left" border="0" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 387px;">
                <tr>
                    <td>
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="padding: 0 0 20px 0; color: #555555; font-family: Arial, sans-serif; font-size: 12px; line-height: 21px;">
                                    La vendita ha generato le licenze richieste che sono gestibili dal
                                    vostro pannello di amministrazione.<br/>
                                    Dettagli della vendita:<br/>
                                    Codice vendita: <b>ID <?=$purchase_id?></b><br/>
                                    Importo: <b>&euro; <?=$price*$purchase['qta']?></b><br/>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if (gte mso 9)|(IE)]>
            </td>
            </tr>
            </table>
            <![endif]-->
        </td>
    </tr>
    <tr>
        <td bgcolor="#ffffff" style="padding: 20px 20px 0 20px; border-bottom: 1px solid #f6f6f6;">
            <table width="100" align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td height="100" style="padding: 0 20px 20px 0;">
                        Ricevi licenza:
                    </td>
                </tr>
            </table>
            <!--[if (gte mso 9)|(IE)]>
            <table width="387" align="left" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td>
            <![endif]-->
            <table class="col387" align="left" border="0" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 387px;">
                <tr>
                    <td>
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="padding: 0 0 20px 0; color: #555555; font-family: Arial, sans-serif; font-size: 12px; line-height: 24px;">
                                    Per qualsiasi problema inerente a questa specifica vendita
                                    ricordiamo che potete inviarci una segnalazione per E-Mail
                                    a <a href="<?=$tutor['email']?>"><?=$tutor['email']?></a>
                                    oppure telefonare allo <b><?=$tutor['telephone']?></b>.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if (gte mso 9)|(IE)]>
            </td>
            </tr>
            </table>
            <![endif]-->
        </td>
    </tr>
    <tr>
        <td bgcolor="#ffffff" style="padding: 20px 20px 0 20px; border-bottom: 1px solid #f6f6f6;">
            <table width="100" align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td height="100" style="padding: 0 20px 20px 0;">
                        Ente Formativo:
                    </td>
                </tr>
            </table>
            <!--[if (gte mso 9)|(IE)]>
            <table width="387" align="left" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td>
            <![endif]-->
            <table class="col387" align="left" border="0" cellpadding="0" cellspacing="0" style="width: 100%; max-width: 387px;">
                <tr>
                    <td>
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="padding: 0 0 20px 0; color: #555555; font-family: Arial, sans-serif; font-size: 12px; line-height: 24px;">
                                    <?=$tutor["business_name"]?><br>
                                    <?=$tutor["address"]?> <span>&nbsp;</span> <?=$tutor["city"]?><br>
                                    E-Mail: <?=$tutor["email"]?><br>
                                    Per ulteriori informazioni sul funzionamento e le modalità di avviamento del corso  preghiamo
                                    di visitare questa <a href="<?=HUB_URL?>">pagina</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if (gte mso 9)|(IE)]>
            </td>
            </tr>
            </table>
            <![endif]-->
        </td>
    </tr>
    <tr>
        <td align="center" bgcolor="#dddddd" style="padding: 15px 10px 15px 10px; color: #555555; font-family: Arial, sans-serif; font-size: 12px; line-height: 18px;">
            <b>Tutor81</b>
        </td>
    </tr>
    <tr>
        <td style="padding: 15px 10px 15px 10px;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td align="center" width="100%" style="color: #999999; font-family: Arial, sans-serif; font-size: 12px;">
                        2016 &copy; <a href="#" style="color: #9b59b6;">Tutor81</a>
                    </td>
                </tr>
            </table>
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