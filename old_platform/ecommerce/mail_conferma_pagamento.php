<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
$company_obj = new T81Company();

// Point to right tutor in base of domain tutor81 alias add in any ecommerce page
/*$tutor = $company_obj->getCompanyByServerAlias($_SERVER['SERVER_NAME']);
if (!isset($tutor)) {
    $tutor = $company_obj->getCompanyByServerAlias("pre-amm.tutor81.com"); //hub.tutor81.local
}
$tutor["logo"] = HUB_URL."/media/img/company/".$tutor["id"].".png";*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Tutor81 Piattaforma di E-Learning</title>
    <meta name="viewport" content="width=device-width" />
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 12px; line-height: 20px;">
<!--[if (gte mso 9)|(IE)]>
<table width="600" align="center" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td>
<![endif]-->
<table align="center" border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 600px;" class="content">
    <tr>
        <td align="center" style="padding: 20px 0 0 0;">
            <h1>COME PAGARE IL CORSO E-LEARNING</h1>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding: 20px;">
            <h2>A seguito della tua richiesta di acquisto al corso: <br />
            <b style="color: #1BBAE1;"><?= T81LearningProject::formatTitle($learning_project["learning_project_title"])?> della durata di <?=$learning_project["total_duration"]?> ore</b>
            </h2>
        </td>
    </tr>
    <tr>
        <td>
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
                                <td style="padding: 0 0 20px 0;">
                                    Per ricevere il CODICE DI ACCESSO per questo corso effettua
                                    il pagamento mediante boniﬁco bancario alle seguenti coordinate:<br />
                                    Beneficiario: <b><?= strtoupper($tutor_company['business_name'])?></b><br />
                                    IBAN: <b><?= strtoupper($tutor_company['iban'])?></b><br />
                                    Causale: <b>ID<?= $purchase_id?></b><br />
                                    Quantit&aacute;: <b><?= $purchase['qta']?></b><br />
                                    Importo: <b>&euro; <?= $total?></b> (iva compresa)<br />
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
        <td>
            <table width="100" align="left" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td height="100" style="padding: 0 20px 20px 0;">
                        Ricevi Codice:
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
                                <td style="padding: 0 0 20px 0;">
                                    Il codice del corso verrà inviato a pagamento avvenuto,<br />
                                    <span style="font-weight: bold; font-size: 14px;">Puoi inviare il codice riferimento operazione (CRO)</span> a<br />
                                    questa Mail: <a href="mailto:<?= strtolower($tutor_company['email'])?>"><?= strtolower($tutor_company['email'])?></a><br />
                                    Provvederemo subito a sbloccare il codice del corso.<br />
                                    <br />
                                    Per avere istruzioni di come si avvia il corso <a href="http://www.tutor81.com/ilcorso">clicca qui</a>.
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
        <td>
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
                                <td style="padding: 0 0 20px 0;">
                                    <b>
                                    Ente formativo: <?= strtoupper($tutor_company["business_name"])?><br />
                                    Indirizzo: <?=$tutor_company["address"]?> &nbsp;-&nbsp; <?=$tutor_company["city"]?><br />
                                    E-Mail: <?= strtolower($tutor_company["email"])?>
                                    </b>
                                    <br />
                                    <br />
                                    L’attestato di formazione sarà rilasciato direttamente al corsista al termine del corso.
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
        <td style="padding: 15px;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td align="center" width="100%">
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