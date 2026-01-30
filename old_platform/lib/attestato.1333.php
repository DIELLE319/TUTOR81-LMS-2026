<style>
b {
    font-family: helvetica;
}
</style>
<table style="border: 0.3cm solid #354163; padding-left: 20px; padding-rigth: 20px; height: 21cm;">
    <tbody>
        <tr style="line-height: 100%;">
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
            <td style="width: 1.75cm;"></td>
        </tr>
        <tr>
            <td colspan="16" style="text-align: center; font-size:19px; line-height: 120%;">
                <span style="font-size: 23px;">ATTESTATO DI FREQUENZA</span><br>corso e-learning N° <?= $event_detail['certificate_number'] ?>
            </td>
        </tr>
        <tr>
            <td colspan="16" style="text-align: center; font-size: 13px; line-height: 120%;">
                <br>
                    <?= $is_DL81 ? "(ai sensi dell'art. 37 del decreto legislativo 9 aprile 2008 n. 81)<br>" : "" ?>
                    Il documento è valido su tutto il territorio nazionale
                    <?= !$is_DL81 ? "<br>" : "" ?>
            </td>
        </tr>
        <tr><td colspan="16">&nbsp;</td></tr>
        <tr style="line-height: 150%;">
            <td colspan="4">si attesta che: </td>
            <td colspan="12"><b><?= $learner_name ?></b></td>
        </tr>
        <tr style="line-height: 150%;">
            <td colspan="4">Codice fiscale: </td>
            <td colspan="12"><b><?= $learner_taxcode ?></b></td>
        </tr>
        <tr><td colspan="16">&nbsp;</td></tr>
        <tr style="line-height: 150%;">
            <td colspan="16" style="text-align: center;">ha superato il corso di formazione e ha superato la prova finale di apprendimento del corso:</td>
        </tr>
        <tr><td colspan="16">&nbsp;</td></tr>
        <tr>
            <td colspan="16" style="padding: 0; font-size: 17px; background-color: #d6d6d6; line-height: 300%; text-align: center;"><?= $course_title ?></td>
        </tr>
        <tr><td colspan="16">&nbsp;</td></tr>
        <tr style="line-height: 150%;">
            <td colspan="4">Percent. test validazione corso:</td>
            <td colspan="12"><b><?= $percentage_correct_answer_to_pass ?>%</b></td>
        </tr>
        <tr style="line-height: 150%;">
            <td colspan="4">Riferimento normativo: </td>
            <td colspan="12"><b><?= $law_reference ?></b></td>
        </tr>
        <tr style="line-height: 150%;">
            <td colspan="4">Durata del corso in elearning: </td>
            <td colspan="12"><b><?= $total_elearning ?> ore</b></td>
        </tr>
        <tr style="line-height: 150%;">
            <td colspan="4">Periodo di svolgimento: </td>
            <td colspan="12"><b>dal <?= $start_course_date; ?> al <?= $end_course_date; ?></b></td>
        </tr>
        <tr style="line-height: 150%;">
            <td colspan="4">Organizzato da: </td>
            <td colspan="12"><b><?= $company['business_name'] ?></b></td>
        </tr>
        <tr style="line-height: 150%;">
            <td colspan="4">Settore Ateco: </td>
            <td colspan="12"><b><?= $company['ateco'] ?></b></td>
        </tr>
        <tr><td colspan="16">&nbsp;</td></tr>
        <tr style="line-height: 150%;">
            <td colspan="7" style="text-align: center;"><u>Ente di formazione accreditato</u></td>
            <td colspan="7" style="text-align: center;"><u>Formatore</u></td>
            <td colspan="4"></td>
        </tr>
        <tr style="line-height: 150%;">
            <td colspan="7" ><b>PROMETEO SRL</b></td>
            <td colspan="7" ></td>
            <td colspan="4"></td>
        </tr>
        <tr style="line-height: 150%;">
            <td colspan="7"><b>Via Caduti del lavoro 11, 46010 Curtatone (MN)</b></td>
            <td colspan="7"></td>
            <td colspan="4"></td>
        </tr>
        <tr style="line-height: 150%;">
            <td colspan="7"><b>Largo dei Cavalieri 10, 20146 Milano (MI)</b></td>
            <td colspan="7"></td>
            <td colspan="4"></td>
        </tr>
        <tr style="line-height: 150%;">
            <td colspan="7"><b>Tel. 0376 290408</b></td>
            <td colspan="7"></td>
            <td colspan="4"></td>
        </tr>
        <tr style="line-height: 150%;">
            <td colspan="7"><b>www.prometeosrl.it</b></td>
            <td colspan="7"></td>
            <td colspan="4"></td>
        </tr>
        <tr><td colspan="16">&nbsp;</td></tr>
        <tr><td colspan="16">&nbsp;</td></tr>
        <tr><td colspan="16">&nbsp;</td></tr>
        <tr><td colspan="16">&nbsp;</td></tr>
        <tr><td colspan="16">&nbsp;</td></tr>
        <tr>
            <td colspan="16" style="text-align: center; font-size: 13px; line-height: 120%">
                L'attestato <?= $is_DL81 ? "rilasciato ai sensi dell'Accordo del 
                    17 aprile 2025 sancito in conferenza permanente per i rapporti 
                    tra lo Stato, le Regioni e le Province Autonome di Trento 
                    e Bolzano" : "" ?> è valido su tutto il territorio nazionale
                <?= !$is_DL81 ? "<br>" : "" ?>
            </td>
        </tr>
        <tr><td colspan="16">&nbsp;</td></tr>
    </tbody>
</table>