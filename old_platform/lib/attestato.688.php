<?php 
$tagvs = array('p' => array(0 => array('h' => 0, 'n' => 0), 1 => array('h' => 0, 'n'
=> 0)));
$pdf->setHtmlVSpace($tagvs);
?>
<style>
    body {
        font-family: helvetica, 'sans-serif';
    }
</style>
<table style="width: 100%;">
    <tbody>
        <tr style="text-align: center;">
            <td>
                <img src="<?= BASE_MEDIA_PATH . 'img/company/' . $tutor_company['id'] . '.png' ?>" style="height: 60px;" />
            </td>
        </tr>
        <tr style="margin: 0 auto; text-align: center;">
            <td>
                <span style="font-size: 1.8em; font-weight: bold;">ATTESTATO DI FREQUENZA<br /></span>
                <span style="font-size: small; font-weight: normal;">(ai sensi dell'art. 37 del decreto legislativo 9 aprile 2008 n. 81)</span>
            </td>
        </tr>
    </tbody>
</table>
<table border="1" cellpadding="5" style="width: 100%;">
    <tbody>
        <tr>
            <td>Si attesta che il/la Sig./Sig.ra: <span style="color: green;"><?= $learner_name ?></span></td>
            <td>Codice Fiscale: <span style="color: green;"><?= strtoupper($user_dett['tax_code']) ?></span></td>
        </tr>
        <tr>
            <td>Nato/a a: <span style="color: green;"><?= $born_data['born_city'] ?></span></td>
            <td>il: <span style="color: green;"><?= date('d/m/Y', strtotime($born_data['born_date'])) ?></span></td>
        </tr>
        <tr>
            <td colspan="2">Ruolo: <span style="color: green;"><?= $user_dett['business_function'] ?></span></td>
        </tr>
        <tr>
            <td colspan="2">Ha frequentato il corso di formazione e superato la verifica di apprendimento</td>
        </tr>
        <tr>
            <td colspan="2" height="150">
                <h2 style="text-align: center; color: green; border: none; font-size:1.5em; height: 100%; vertical-align: middle;">
                    <?= /*!empty($course_detail['learning_project_description']) ? $course_detail['learning_project_description'] : */$course_title ?>
                </h2>
            </td>
        </tr>
        <tr>
            <td>Monte ore frequentato: <span style="color: green;"><?= $total_elearning ?> ore</span></td>
            <td>Periodo di svolgimento del corso: dal <span style="color: green;"><?= $start_date->format("d-m-Y") ?></span> 
                al <span style="color: green;"><?= $end_date ?></span> </td>
        </tr>
        <tr>
            <td colspan="2">Settore di riferimento (Ateco 2007): <span style="color: green;"><?= $company['ateco'] ?></span></td>
        </tr>
        <tr>
            <td colspan="2">Soggetto che ha organizzato il corso: 
                <b style="color: green;"><?= $tutor_name ?></b> 
                <!--<span style="font-size: small">Centro di formazione accreditato Regione Lombardia nella sezione B del 01/08/2008, ID operatore 151972/2008</span>-->
            </td>
        </tr>
        <tr>
            <td colspan="2">Sede del corso: <span style="color: green;">corso erogato in modalità e-learning</span></td>
        </tr>
        <tr>
            <td colspan="2" style="color: gray;">L'attestato rilasciato ai sensi dell'Accordo del 
                    17 aprile 2025 sancito in conferenza permanente per i rapporti 
                    tra lo Stato, le Regioni e le Province Autonome di Trento 
                    e Bolzano è valido su tutto il territorio nazionale</td>
        </tr>
        <tr>
            <td colspan="2" >Numero progressivo di registrazione: <span style="color: green;"><?= $purchase_detail['accreditation_code'] ?></span></td>
        </tr>
        <tr>
            <td colspan="2">Soggetto realizzatore del corso
                <span style="text-align: center;"><br />SINTEX srl<!-- <br />dr. Roberto Zini--></span>
            </td>
        </tr>
        <tr>
            <td>Data: <?= $end_date ?></td>
            <td>Torbole Casaglia</td>
        </tr>
    </tbody>
</table>
<table cellpadding="5" style="width: 100%;">
    <tbody>
        <tr>
            <td style="color: gray; font-size: small; line-height: 2em;" height="30" width="800">
                SINTEX s.r.l. - via Artigianato, 9 - 25030 Torbole Casaglia (BS) - tel. 030.2150381 - fax 030.2650268 - sintex@farco.it - www.farco.it
            </td>
            <td style="text-align: right;" height="30" width="200">
                <img src="<?= BASE_MEDIA_PATH . 'img/company/farco.png'?>" height="30" />
            </td>
        </tr>
    </tbody>
</table>