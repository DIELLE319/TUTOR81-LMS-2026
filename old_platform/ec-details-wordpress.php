<?php
/**
 * Created by PhpStorm.
 * User: endrit
 * Date: 3/29/2017
 * Time: 4:50 PM
 */

require_once 'config.php';
//require_once BASE_LIBRARY_PATH . 'check_session.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_safety.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_om.php';

$company_obj = new T81Company();
$id_course = $_GET["id"];
$course_type_obj = new T81CourseType();
$course_obj = new iWDCourse();
$learn_obj = new T81DOM();
$course = $course_obj->getCourseDetailedListOfAvailableLearningProjectEcommerce(6, NULL, $id_course)[0];
$course_modules = $course_obj->getCourseModules($id_course);
$price_list_single = $course_obj->getPriceList($id_course);

// Point to right tutor in base of domain tutor81 alias add in any ecommerce page
$tutor = $company_obj->getCompanyByServerAlias($_SERVER['SERVER_NAME']);
if (!isset($tutor) || $tutor == false) {
    $tutor = $company_obj->getCompanyByServerAlias("pre-amm.tutor81.com"); //hub.tutor81.local
}
$tutor["logo"] = T81Company::getEcommerceLogo($tutor);
$tutor["admin_id"] = $company_obj->getMainAdminOfCompany($tutor["id"]);

?>

<?php $price_value = number_format($price_list_single[0]["price"], 2, ',', ' '); ?>
<?php require_once 'ecommerce/header.php'; ?>

<?php
$wordpress_url = "{$_SERVER['REQUEST_URI']}";

$escaped_url = htmlspecialchars($wordpress_url, ENT_QUOTES, 'UTF-8');
//echo '<a href="' . $escaped_url . '">' . $escaped_url . '</a>';
//echo '<p style="color:#fff;"> /ec-details-wordpress.php?id='.$course["course_id"].'</p>';
//echo '<p style="color:#fff;"> ' . $tutor["id"] . '</p>';

if ($wordpress_url == "/ec-details-wordpress.php?id=".$course['course_id']) {

    echo '<link href="css/wordpressCSS/' . $tutor["id"] . '.css" rel="stylesheet"/>';
} ?>

<body>
<!-- Page Container -->
<!-- In the PHP version you can set the following options from inc/config file -->
<!-- 'boxed' class for a boxed layout -->
<div id="page-container">

    <!-- Intro -->
    <section class="site-section site-section-light site-section-top themed-background-dark"
             style="background-color: #1BBAE1;">

        <div class="container ">
            <div class="row acquista-btn">
                <div class="col-sm-12 col-md-6 text-left">
                    <h1 class="animation-slideDown" style="margin-top: 0; margin-bottom:0;">
                        <strong class="productTitle"
                                style="font-size:34px; font-weight:800;"><?= $course["title"] ?></strong>
                    </h1>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4 text-left">
                    <p class="animation-slideDown destinatario" style="margin-top: 10px;">
                        <b>Destinatari:</b> <?= $course["destinatari"] ?> </p>
                    <div class="animation-slideDown time"><b> Durata: </b>
                        <small class="productOre"><?= $course["duration"] ?>
                            or<?php if ($course["duration"] == "1") { ?>a<?php } else { ?>e<?php } ?></small>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-2" style="margin-top: 20px;">
                    <div class="animation-slideDown">
                        <a type="button" class="buttonAcquista btn btn-primary pull-right"
                           style="background-color: #2E354F; font-size: 22px;" href="#"><i
                                class="gi gi-shopping_cart"></i><b style="color: #1BBAE1;"> Acquista</b></a>
                        <?php foreach ($price_list_single as $indexPrice => $price) {
                            //print_r($price)
                            ?>
                            <div class="row productPriceListRow"
                                 style="display: none; border: 1px #0000cc; border-style: none solid solid solid;">
                                <div class="col-sm-6 amountRange" style="border-right: 1px solid #0000cc;">
                                    <?php echo $price["lower_limit"] . "-" . $price["upper_limit"]; ?>
                                </div>
                                <div class="col-sm-6 amountPrice"> € <?php echo $price["price"]; ?></div>
                            </div>
                        <?php } ?>
                        <span class="h2 productPriceHolder pull-right"><strong> € <span
                                    class="productPrice"><?= $price_value ?></span></strong></span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- END Intro -->

    <!-- Product View -->
    <section class="site-content site-section">
        <div class="container" style="margin-top: 10px;">
            <div class="row">
                <!-- Product Details -->
                <div class="col-md-12 col-lg-12">
                    <div class="row" data-toggle="lightbox-gallery">
                        <!-- Images -->
                        <div class="col-md-6 push-bit">

                            <a href="<?php
                            $videolink = $course['video_courses'] == trim("") ? 'https://player.vimeo.com/video/188692091' : $course['video_courses'];
                            echo $videolink;
                            ?>" class="popup-vimeo">
                                <iframe src="<?= $videolink; ?>" class="video-responsive" width="550" height="310"
                                        frameborder="0" scrolling="no" allowfullscreen></iframe>
                                <!--                                <iframe src="https://player.vimeo.com/video/188678448" class="video-responsive" width="550" height="310" frameborder="0" scrolling="no" allowfullscreen></iframe>-->
                            </a>
                            <div class="row push-bit">
                                <div class="col-lg-6">
                                    <a href="<?= $videolink; ?>" class="popup-vimeo">
                                        <iframe src="https://player.vimeo.com/video/188678448" class="video-responsive1"
                                                width="257" height="150" frameborder="0" allowfullscreen></iframe>
                                    </a>
                                </div>
                                <div class="col-lg-6">
                                    <a href="https://player.vimeo.com/video/169293976" class="popup-vimeo">
                                        <iframe src="https://player.vimeo.com/video/169293976" class="video-responsive2"
                                                width="257" height="150" frameborder="0" allowfullscreen></iframe>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- END Images -->

                        <!-- Info -->
                        <div class="col-md-4 push-bit">
                            <div class="clearfix">
                                <!-- <div class="type"> <b>Tipo: </b><b class="productTipo course-type">-->
                                <?php //=$course["Tipo"]?><!--</b></div>-->
                                <div class="description">
                                    <span> <b>Descrizione: </b> </span><?= $course['single_description'] ?> </div>
                            </div>
                            <hr>
                            <p class="riferimento"><span> <b>Riferimento normativo: </b><?= $course['reference_law'] ?> </span>
                            </p>
                            <div class="validità">
                                <span> <b>Validità del corso: </b><?= $course['course_validita'] ?> </span></div>
                            <hr>
                            <span class="productId" style="display: none;"><?= $course["learning_project_id"] ?></span>
                            <img class="productImageSrc img-responsive" style="display: none;"
                                 src="<?= HUBMEDIA_URL ?>/img/courses/<?php echo $course["ecommerce_image_filename"]; ?>"
                                 alt="">
                        </div>
                        <!-- END Info -->
                        <div class="col-md-2 push-bit">
                            <div class="left-side-aligned"
                                 style="background-color: #d9d9d9; padding:5px; border-radius: 5px;">
                                <div><p><b style="color: #00a7d0;">Il corso va concluso entro: </b>
                                    <h4> <?= $course['execution_time'] ?> gg</h4></p></div>
                                <br><br>
                                <div>
                                    <p><b style="color: #00a7d0;">Didattica: </b>
                                        <small> <?= $course['didactics_course'] ?></small>
                                    </p>
                                </div>
                                <br><br>
                                <div><p><b style="color: #00a7d0;">Percentuale di risposte esatte
                                            : </b><?= $course['percentage_answer_to_pass'] ?> %</p></div>
                            </div>
                        </div>

                        <!-- More Info Tabs -->
                        <div class="row">
                            <div class="col-sm-10 site-block">
                                <ul class="nav nav-tabs push-bit" data-toggle="tabs">
                                    <li class="active"><a href="#product-specs" style="cursor: pointer!important;">
                                            Programma del corso</a></li>
                                    <li><a href="#product-description"
                                           style="cursor: pointer!important; ">Regolamento</a></li>
                                </ul>

                                <div class="tab-content">
                                    <div class="tab-pane active" id="product-specs">
                                        <div class="widget">
                                            <div class="widget-advanced">
                                                <?php
                                                $num_mod = 1;
                                                foreach ($course_modules as $module) {
                                                    $course_lessons = $course_obj->getCourseLessonsByModuleID($module['id']);
                                                    ?>
                                                    <!-- Widget Header -->
                                                    <div class="text-center push-bit"
                                                         style="background-color: #fff; padding: 0 5px;">
                                                        <h3 style="display: inline-block; margin-top: 0;"> Programma
                                                            Formativo</h3>
                                                        <small><a style="cursor: pointer!important;"><img
                                                                    src="img/video48.png" width="15"
                                                                    height="15">Video</a></small>
                                                        <small><a style="cursor: pointer!important;"><img
                                                                    src="img/slide48.png" width="15"
                                                                    height="15">Slide</a></small>
                                                        <small><a style="cursor: pointer!important;"><img
                                                                    src="img/doc48.png" width="15" height="15">.pdf</a>
                                                        </small>
                                                        <small><a style="cursor: pointer!important;"><img
                                                                    src="img/web48.png" width="15" height="15">Web</a>
                                                        </small>
                                                    </div>

                                                    <div class="widget-header text-center themed-background-dark"
                                                         style="padding: 0 15px 30px; height: 100px;">
                                                        <h3 class="widget-content-light">
                                                            <a href="#"> Modulo <?= $num_mod++ ?>
                                                                : <?= $module['title'] ?></a>
                                                        </h3>

                                                    </div>
                                                    <!-- END Widget Header -->
                                                    <!-- Widget Main -->
                                                    <div class="widget-main">
                                                        <a href="javascript:void(0)"
                                                           class="widget-image-container animation-fadeIn">
                                                            <span class="widget-icon themed-background"><i
                                                                    class="fa fa-globe" style="color: #fff;"></i></span>
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-default pull-right">
                                                            <?= sizeof($course_lessons) ?> lezioni
                                                            <!--                                                    ,<i class="fa fa-clock-o"></i> -->
                                                            <?php //= $module['duration'] ?><!-- or-->
                                                            <?php //if ($module['duration'] == "1") {?><!--a-->
                                                            <?php //} else {?><!--e--><?php //} ?>
                                                        </a>
                                                        Descrizione:<?= $module['description'] ?>
                                                        <hr>

                                                        <!-- Lessons -->
                                                        <div class="lesson_box" id="lesson_module_<?= $module['id'] ?>">
                                                            <!--                                                    <h2 class="subtitle_detail">Lezioni inserite</h2>-->
                                                            <div>
                                                                <ul id="sortable_<?= $module['id'] ?>"
                                                                    style="list-style-type: none; padding: 0;">
                                                                    <?php foreach ($course_lessons as $lesson) { ?>
                                                                        <li id="lesson_<?= $lesson['id'] ?>"
                                                                            class="lesson">
                                                                            <table class="table table-vcenter">
                                                                                <thead>
                                                                                <tr class="active">
                                                                                    <th><strong>
                                                                                            Lezione <?= $lesson['position'] ?>
                                                                                            : <?= $lesson['title'] ?>
                                                                                    </th>
                                                                                    <!--                                                                        <th class="text-right"><small><em> -->
                                                                                    <?php //= $lesson['duration'] ?><!-- or-->
                                                                                    <?php //if ($lesson['duration'] == "1") {?><!--a-->
                                                                                    <?php //} else {?><!--e-->
                                                                                    <?php //} ?><!--</em></small></th>-->
                                                                                </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                <tr>
                                                                                    <td>
                                                                                        <ul style="list-style-type: none; padding: 0;">
                                                                                            <?php
                                                                                            $lesson_id = $lesson['id'];
                                                                                            $res = $learn_obj->getLearningObjectByLessonID($lesson_id);
                                                                                            foreach ($res as $single) {
                                                                                                if ($single['learning_object_type_id'] == 1) {
                                                                                                    $icon = "img/video48.png";
                                                                                                } elseif ($single['learning_object_type_id'] == 2) {
                                                                                                    $icon = "img/slide48.png";
                                                                                                } elseif ($single['learning_object_type_id'] == 3) {
                                                                                                    $icon = "img/doc48.png";
                                                                                                } elseif ($single['learning_object_type_id'] == 4) {
                                                                                                    $icon = "img/web48.png";
                                                                                                }
                                                                                                ?>

                                                                                                <li class="lesson-om">
                                                                                                    <table
                                                                                                        style="width: 100%;">
                                                                                                        <tr>
                                                                                                            <td><img
                                                                                                                    src="<?= $icon ?>">
                                                                                                                <span
                                                                                                                    style="color: #1bbae1; cursor: default;"><?= $single['title'] ?></span> <?= "({$single['duration']} min)" ?>
                                                                                                            </td>
                                                                                                        </tr>
                                                                                                    </table>
                                                                                                </li>

                                                                                            <?php } ?>

                                                                                        </ul>
                                                                                    </td>
                                                                                </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        </li>
                                                                    <?php } ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <!-- END Lessons -->
                                                    </div>
                                                    <!-- END Widget Main -->
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <!-- END Course Widget -->
                                    </div>

                                    <div class="tab-pane" id="product-description">
                                        <div class="regola1">
                                            <p class="regola-title" style="margin-bottom: 5px;"><b class="text-blue">
                                                    Regole durante il corso:</b></p>
                                            <ul>
                                                <li> l'utente può collegarsi senza limiti di tempo e orario</li>
                                                <li> il corso può essere avviato e proseguire solo in modalità online
                                                </li>
                                                <li> in caso di risposta errata il corso prosegue</li>
                                                <li> un corso può essere interrotto in ogni momento e ripreso
                                                    dall'ultimo punto utile visionato
                                                </li>
                                                <li> un corso una volta avviato deve essere terminato entro un dato
                                                    tempo (v. termine impostato dall'Amm.re)
                                                </li>
                                                <li> ai test si deve rispondere entro 30 secondi, trascorsi i quali il
                                                    sistema interrompe l'avanzamento del corso
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="regola2">
                                            <p><b class="text-blue regola-title">Tempo di fruizione:</b> Gli oggetti
                                                didattici essendo distribuiti solo "online" permettono un tracciamento
                                                continuo del discente, nel caso del docutest (dispense o documenti in
                                                genere) viene consentito il logout. Il tempo stabilito per la lettura e
                                                lo studio è definito dalla circolare della Regione Lombardia ed è così
                                                espresso: <br>4.000 battute = 16 minuti di formazione; 8.000 battute =
                                                32 minuti di formazione; ecc. <br> Nei documenti, è quindi riportata la
                                                durata in minuti e secondi che sono necessari per leggere il documento.
                                                <br>La piattaforma impedisce, al discente di proseguire alle lezioni
                                                successive prima che il tempo assegnato per ciascun documento sia
                                                completamente esaurito.</p>
                                        </div>
                                        <div class="regola3">
                                            <p><b class="text-blue regola-title"> Percorso obbligato: </b> il corso si
                                                svolge lungo un percorso obbligato sviluppato dal sistema, il discente
                                                non può e non deve fare nulla, ogni elemento didattico viene proposto,
                                                per l'utente non è possibile spostarsi lungo le varie lezioni o gli
                                                oggetti multimediali, oppure rivederli. Tale scelta è effettuata per
                                                garantire il raggiungimento dell'obbiettivo formativo stabilito e
                                                impedire al discente di distrarsi sapendo di poter rivedere gli oggetti
                                                multimediali a piacimento. Solo il tutor didattico ha facoltà (su
                                                richiesta del discente o del Datore di Lavoro) di consentire di rivedere
                                                un oggetto multimediale. Il percorso obbligato può essere disattivato
                                                dall'Amm.re consentendo ai discenti di muoversi liberamente nel player.
                                        </div>
                                        <div class="regola4">
                                            <h2><b class="text-blue"> Verifiche di apprendimento </b></h2>
                                            <p>la verifica di apprendimento principale privilegiata dalla didattica
                                                Tutor81 sono i test in itinere rilasciati ogni 5-10 minuti, tale metodo
                                                infatti consente di aumentare l'attenzione del discente, interagire con
                                                esso per correggere gli eventuali errori, verificarne la presenza. I
                                                questionari e i test sono rilasciati dalla piattaforma in modalità
                                                random.</p>
                                            <p>Il sistema preleva dal magazzino test le domande in modalità casuale, in
                                                questo modo il tipo e il numero di domande offerte al discente sono
                                                sempre differenti</p>
                                            <p>Ai test si deve rispondere entro 30 secondi, trascorsi i quali il sistema
                                                interrompe l'avanzamento del corso</p>
                                            <p>L'avanzamento del percorso formativo del discente viene monitorato anche
                                                attraverso il controllo degli accessi effettuati (data, orario,
                                                identificazione della postazione).</p>
                                            <p><b>test in itinere: </b>il test a tempo interrompe il filmato con domande
                                                sviluppate specificatamente sull'argomento trattato. Ricevendo immediato
                                                esito della risposta data, il discente ha la percezione di interagire
                                                con il sistema chiedendo aiuto anche al Tutor didattico. In caso di
                                                risposta non corretta il sistema ne illustra e spiega i motivi.
                                                quesito immediato: in qualsiasi istante il corsista può interrompere i
                                                filmati o le slide e formulare un quesito al Tutor didattico per essere
                                                contatto tramite email. Ogni quesito viene tracciato dal sistema e
                                                riportato nell'attestato finale.
                                            </p>

                                            <p><b class="text-blue regola-title_small"> questionario finale in
                                                    presenza: </b>il questionario finale in presenza conclude il corso
                                            </p>
                                            <p><b class="text-blue regola-title_small"> efficacia apprendimento: </b>il
                                                Tutor didattico ha facoltà di fare ripetere l'intero percorso formativo
                                                o parte di esso.</p>
                                        </div>

                                        <div class="regola5">
                                            <h2 class="text-blue"><b> La didattica</b></h2>
                                            <p>La didattica è progettata per COINVOLGERE in questo modo si attua un
                                                reale processo di FORMAZIONE in elearning.<br> Il format video grazie al
                                                suo impatto multimediale viene utilizzato come elemento motivazionale,
                                                successivamente vengono utilizzati web object, slide test e quiz
                                                game.<br> I testi vengono scritti per tramite di esperti e tecnici con
                                                ventennale esperienza nel campo della prevenzione e protezione nei
                                                luoghi di lavoro, per poi essere trasformati in FORMAT multimediali da
                                                tecnici e grafici di videografica.</p>

                                            <p>L'apprendimento si realizza mediante gli OGGETTI MULTIMEDIALI o o
                                                learning object, codificati con la sigla OM (oggetti multimediali)
                                                seguiti dal numero di riferimento, ogni aggiornamento e revisione viene
                                                siglato con REV.</p>
                                            <p style="margin-bottom: 5px;">A seconda dell'obbiettivo che si intende
                                                raggiungere i learning object riportano la seguente dicitura: </p>
                                            <ul style="list-style-type: none; padding-left: 10px;">
                                                <li><b class="big-number">1.</b> MOTIVAZIONALE</li>
                                                <li><b class="big-number">2.</b> SAPERE</li>
                                                <li><b class="big-number">3.</b> SAPER FARE</li>
                                            </ul>
                                            <p> Nello specifico i learning object sono:</p>
                                            <p> VIDEO TEST oggetto didattico in formato video che al suo interno
                                                contiene 1 o più test temporizzati (trasmessi in modalità random) E'
                                                così possibile controllare la presenza del discente e l'apprendimento
                                                per ogni argomento trattato in tempo reale.</p>
                                            <p> QUIZ GAME Il quiz interattivo permette di effettuare delle simulazioni,
                                                il discente è chiamato a rispondere a quesiti più o meno complessi.</p>
                                            <p> SLIDE - TEST si tratta di slide con elevata connotazione grafica che
                                                vengono intervallate da test temporizzati</p>
                                        </div>


                                    </div>
                                </div>
                            </div>
                            <!-- END More Info Tabs -->
                        </div>
                        <!-- More Info Tabs -->
                    </div>
                </div>
                <!-- END Product Details -->
            </div>
        </div>
    </section>
    <!-- END Product View -->

</div>
<!-- END Page Container -->

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-up"></i></a>
<script src="js/pages/ecomDetail.js"></script>
</body>
</html>
