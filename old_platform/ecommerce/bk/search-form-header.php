<!-- Left Header Navigation -->
<ul class="nav navbar-nav-custom hidden-lg hidden-md">
    <!-- Main Sidebar Toggle Button -->
    <li>
        <a href="javascript:void(0)" onclick="App.sidebar('toggle-sidebar');this.blur();">
            <i class="fa fa-bars fa-fw "></i>
        </a>
    </li>
    <!-- END Main Sidebar Toggle Button -->

</ul>
<!-- END Left Header Navigation -->


<div id="employee">
    <!-- Search Form -->
    <form action="#" method="post" class="navbar-form-custom" style="margin-bottom: 0;">
        <div class="form-group search hidden-print form-group-sm">
            <div id="search-employee" class="form-group search-query">
                <input type="text" class="form-control search-query ui-autocomplete-input" id="top-search" name="top-search" placeholder="Cerca Utente.." data-toggle="popover"
                       data-placement="bottom" data-trigger="hover" data-delay="{&quot;show&quot;:300,&quot;hide&quot;:300}" title=""
                       data-original-title="Inserire le prime lettere del nome, cognome o codice fiscale" autocomplete="off" aria-describedby="popover965202">
            </div>
        </div>
    </form>
    <!-- END Search Form -->

    <div id="cerca-utenti-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header text-center">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h2 class="modal-title"> Cerca Utenti</h2>
                </div>
                <!-- END Modal Header -->

                <!-- Modal Body -->
                <div class="modal-body">
                    <div class=" <?= !empty($section) && $section === 'employees' ? 'in' : '' ?>">
                        <div id="single-user">
                            Ricerca un utente
                        </div>
                    </div>
                </div>
                <!-- END Modal Body -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-primary close-modal" data-dismiss="modal"> Chiudi</button>
                </div>
            </div>
        </div>
    </div>
</div>

<ul class="nav navbar-nav-custom pull-right" ><!--style="min-height: 25px; height: 25px; margin: 0 5px 0 0; padding: 5px 0 5px 0;"-->
    <li><!--style="min-height: 25px; height: 25px;"-->
        <a href="#" > <!--style="min-height: 25px; height: 30px; padding: 0; "-->
            <i class="fa fa-question" style="font-size: 18px; color: #1087dd;"></i>
            <span class="h4 assistenza" style="color: #1087dd; "><b> Assistenza</b></span> <!--margin: 0;-->
        </a>
    </li>
</ul>