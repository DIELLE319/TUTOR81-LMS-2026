
<section class="invia_Licenze_row" id="invia_Licenze_row_<?=$index_row?>">
    <div class="row inviaRows" style=" font-size: 11px; color: #000; margin: 2px 0;">
        <div class="col-xs-3 col-sm-3 col-lg-2"><input type="text" id="email" class="form-control" placeholder="invia email" style="height: 23px; font-size: 11px; padding:1px 5px;"></div>
        <div class="col-xs-3 col-sm-3 col-lg-1"><input type="text" id="data_inizio" class="form-control" placeholder="data inizio" style="height: 23px; font-size: 11px; padding:1px 5px;"></div>
        <div class="col-xs-3 col-sm-3 col-lg-1"><input type="text" id="data_fine" class="form-control" placeholder="data inizio" style="height: 23px; font-size: 11px; padding:1px 5px;"></div>
        <div class="col-xs-3 col-sm-3 col-lg-1" style="margin-bottom: 2px;">
            <div class="alert-date" style="padding: 1px 0;">
                <button type="button" class="btn dec btn-xs btn-success" data-toggle="tooltip" style="background-color: #d3d3d3; border-color:#d3d3d3; color: #808080;" data-original-title="" title=""><i class="fa fa-minus"></i></button>
                <input class="amount productQuantita step1-amount-css" style="cursor: default;width: 20px;text-align: center;border: none; border-radius:5px;background-color: #d3d3d3;margin-top: 3px;font-size: 11px;padding-bottom: 3px;" value="15" type="text">
                <button type="button" class="btn inc btn-xs btn-danger" data-toggle="tooltip" style="background-color: #d3d3d3; border-color:#d3d3d3; color: #808080;" data-original-title="" title="" onclick="addRow('ecom-orders');"><i class="fa fa-plus"></i></button>
            </div>
        </div>
        <div class="col-xs-3 col-sm-3 col-lg-1"><input type="text" id="nome" class="form-control" placeholder=" Nome" style="height: 23px; font-size: 11px; padding:1px 5px;"></div>
        <div class="col-xs-3 col-sm-3 col-lg-1"><input type="text" id="cognome" class="form-control" placeholder=" Cognome" style="height: 23px; font-size: 11px; padding:1px 5px;"></div>
        <div class="col-xs-3 col-sm-3 col-lg-1"><input type="text" id="codice_Fiscale" class="form-control" placeholder=" Codice Fiscale" style="height: 23px; font-size: 11px; padding:1px 5px;"></div>
        <div class="col-xs-3 col-sm-3 col-lg-1"><input type="text" id="data_assunzione" class="form-control" placeholder="data assunzione" style="height: 23px; font-size: 11px; padding:1px 5px;"></div>
        <div class="col-xs-4 col-sm-4 col-lg-1">
            <div class="dropdown">
                <button class="btn btn-xs btn-default dropdown-toggle" type="button" id="unita" data-toggle="dropdown"> Unita <span class="caret"></span></button>
                <ul class="dropdown-menu select-type-unita" role="menu" aria-labelledby="unita" style="min-width: 70px; font-size: 11px; ">
                    <li role="presentation"><a role="menuitem" tabindex="0" href="#"> Unita 1</a></li>
                    <li role="presentation"><a role="menuitem" tabindex="0" href="#"> Unita 2</a></li>
                    <li role="presentation"><a role="menuitem" tabindex="0" href="#"> Unita 3</a></li>
                </ul>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-lg-1">
            <div class="dropdown">
                <button class="btn btn-xs btn-default dropdown-toggle" type="button" id="reparto" data-toggle="dropdown"> Reparto <span class="caret"></span></button>
                <ul class="dropdown-menu select-type-reparto" role="menu" aria-labelledby="reparto" style="font-size: 11px; min-width: 70px; ">
                    <li role="presentation"><a role="menuitem" tabindex="0" href="#"> Reparto 1</a></li>
                    <li role="presentation"><a role="menuitem" tabindex="0" href="#"> Reparto 2</a></li>
                    <li role="presentation"><a role="menuitem" tabindex="0" href="#"> Reparto 3</a></li>
                </ul>
            </div>
        </div>
        <div class="col-xs-4 col-sm-4 col-lg-1">
            <div class="dropdown" >
                <button class="btn btn-xs btn-default dropdown-toggle" type="button" id="utente" data-toggle="dropdown"> Tipo <span class="caret"></span></button>
                <ul class="dropdown-menu select-type-utente" role="menu" aria-labelledby="utente" style="min-width: 70px; font-size: 11px;">
                    <li role="presentation"><a role="menuitem" tabindex="0" href="#">Lavoratore</a></li>
                    <li role="presentation"><a role="menuitem" tabindex="0" href="#">Preposto</a></li>
                    <li role="presentation"><a role="menuitem" tabindex="0" href="#">Dirigente</a></li>
                </ul>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-xs btn-primary pull-right" style="margin: 0 5px 5px 0;"><i class="fa fa-share"></i> Invia</button>
</section>
