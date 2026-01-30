<div class="inviaRows" style="position: relative;">
    <div class="form-group needed-fields">
        <label class="col-md-5 text-left input-label hidden"> Dove va spedito il codice di accesso?</label>
        <input type="email" class="email form-control input-sm" placeholder="E-Mail destinatario*" autocomplete="off">
        <i class="fa fa-envelope license-email-icon"></i>
    </div>
    <div class="form-group needed-fields">
        <label class="col-md-5 text-left input-label hidden">Data di inizio corso (max 90 gg)</label>
        <input type="tel" class="datepicker form-control input-sm start_date" placeholder="Inizio corso"  value="" >
        <i class="fa fa-calendar license-start-date-icon"></i>
    </div>
    <div class="form-group needed-fields">
        <label class="col-md-5 text-left input-label hidden">Data di fine corso (max 90 gg)</label>
        <input type="tel" class="datepicker form-control input-sm end_date" placeholder="Fine corso" value="">
        <i class="fa fa-calendar license-end-date-icon"></i>
    </div>
    <div class="form-group needed-fields">
        <label class="col-md-5 text-left input-label hidden">Quanti giorni prima bisogna avvisare il corsista di terminare?</label>
        <section class="btn-group btn-group-sm" style="display: table;">
            <button class="btn btn-alt btn-default alert_dec"><i class="fa fa-minus"></i></button>
            <button class="btn btn-alt btn-default alert_inc pull-right"><i class="fa fa-plus"></i></button>
            <input class="alert_days alert_days_amount step1-amount-css" title="Allerta" value="15"
                   style="background-color: #d9d9d9;cursor:default;width: 30px;height: 30px;text-align: center;border: none;color: #000;">
            <i class="fa fa-bell license-alert-icon"></i>
        </section>
    </div>
    <div class="form-group optional-fields">
        <label class="col-md-3 text-right input-label-optional hidden">Cognome</label>
        <input type="text" id="search-user-surname" class="cognome form-control search-query input-sm" placeholder="Cognome"
               name="surname" autocomplete="off" aria-describedby="popover965202" data-toggle="popover" data-placement="top"
               data-trigger="hover" data-content="Cerca l'utente al quale associare la licenza per cognome">
    </div>
    <div class="form-group optional-fields">
        <label class="col-md-3 text-right input-label-optional hidden">Nome</label>
        <input type="text" id="search-user-name" class="nome form-control search-query input-sm" placeholder="Nome"
               name="name" autocomplete="off" data-toggle="popover" data-placement="top" data-trigger="hover" data-content="Nome del utente al quale associare la licenza">
    </div>
    <div class="form-group optional-fields">
        <label class="col-md-3 text-right input-label-optional hidden" style="height: 23px;">Codice Fiscale</label>
        <input type="text" autocomplete="off" class="cod_fisc form-control input-sm" name="tax_code" placeholder="Codice Fiscale">
    </div>
    <div class="form-group optional-fields" id="tipoUtente">
        <label class="col-md-3 text-right input-label-optional hidden" style="height: 23px;">Tipo utente</label>
        <select title="Tipo utente" class="tipo_utente form-control input-sm" name="type_id" size="1" style="padding: 4px 8px;">
            <option value="0">Tipo Utente</option>
            <option value="1">Lavoratore</option>
            <option value="3">Preposto</option>
            <option value="7">Dirigente</option>
        </select>
    </div>
    <div class="form-group optional-fields">
        <label class="col-md-3 text-right input-label-optional hidden" style="height: 23px;">Codice corso</label>
        <input type="text" class="accreditation_code form-control input-sm" name="accreditation_code" placeholder="Codice corso">
    </div>
    <input type="hidden" class="unita" value="0">
    <input type="hidden" class="reparto" value="0">
    <input type="hidden" name="licenceUserID" class="licenceUserID" value="0">
</div>
<script>
    $('.datepicker').datepicker();
</script>