<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'class_company.php';
require_once BASE_LIBRARY_PATH . 'class_safety.php';
require_once BASE_LIBRARY_PATH . 'class_report.php';
require_once BASE_LIBRARY_PATH . 'class_course_type.php';
require_once BASE_LIBRARY_PATH . 'class_course.php';
require_once BASE_LIBRARY_PATH . 'class_om.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

$company_obj = new T81Company();
$learning_project_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
//$id_course = $_GET["id"];
$course_type_obj = new T81CourseType();
$course_obj = new iWDCourse();
$om_obj = new T81DOM();
$lp_obj = new T81LearningProject();
$course = $lp_obj->getCourseEcommerceDetailFromLearningProject($learning_project_id);
$id_course = $course['course_id'];
//$course = $course_obj->getCourseDetailedListOfAvailableLearningProjectEcommerce(6, NULL, $id_course)[0];
$course_modules = $course_obj->getCourseModules($id_course);
$price_list_single = $course_obj->getPriceList($id_course);

// Point to right tutor in base of domain tutor81 alias add in any ecommerce page
$tutor = $company_obj->getCompanyByServerAlias($_SERVER['SERVER_NAME']);
if (!isset($tutor) || $tutor == false) {
    $tutor = $company_obj->getBusinessDetail(2);
}
$tutor["logo"] = T81Company::getEcommerceLogo($tutor);
$tutor["admin_id"] = $company_obj->getMainAdminOfCompany($tutor["id"]);
$color_dark = $tutor['id'] == 688 ? '#101313' : '#394263';
$color_light = $tutor['id'] == 688 ? '#ED1C23' : '#1BBAE1';
$price_value = number_format($price_list_single[0]["price"], 2, ',', ' '); 

require_once 'ecommerce/header.php'; ?>

<body>
<!-- Page Container -->
<!-- In the PHP version you can set the following options from inc/config file -->
<!-- 'boxed' class for a boxed layout -->
<div id="page-container">

    <?php require_once 'ecommerce/site-header.php'; ?>

    <!-- Intro -->
    <section class="site-section site-section-light site-section-top themed-background-dark" style="background-color: <?= $color_light ?>;">

        <div class="container ">
            <div class="row acquista-btn">
                <div class="col-sm-12 col-md-6 text-left">
                    <h1 class="animation-slideDown" style="margin-top: 0; margin-bottom:0;" ><strong class="productTitle" style="font-size:34px; font-weight:800;"><?= T81LearningProject::formatTitle($course["title"])?></strong></h1>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-4 text-left">
                    <p class="animation-slideDown destinatario" style="margin-top: 10px;" ><b>Destinatari:</b> <?=$course["destinatari"]?> </p>
                    <div class="animation-slideDown time"><b> Durata: </b><small class="productOre"><?=$course["duration"]?> or<?php if ($course["duration"] == "1") {?>a<?php } else {?>e<?php } ?></small></div>
                </div>
                <div class="col-xs-12 col-sm-6 col-md-2" style="margin-top: 20px;">
                    <div class="animation-slideDown">
                        <a type="button" class="buttonAcquista btn btn-primary pull-right" style="background-color: #2E354F; font-size: 22px;" href="#" ><i class="gi gi-shopping_cart"></i><b style="color: <?= $color_light ?>;"> Acquista</b></a>
                        <?php foreach ($price_list_single as $indexPrice=>$price) {
                            //print_r($price)
                            ?>
                            <div class="row productPriceListRow" style="display: none; border: 1px #0000cc; border-style: none solid solid solid;">
                                <div class="col-sm-6 amountRange" style="border-right: 1px solid #0000cc;">
                                    <?php echo $price["lower_limit"]."-".$price["upper_limit"]; ?>
                                </div>
                                <div class="col-sm-6 amountPrice"> € <?php echo $price["price"]; ?></div>
                            </div>
                        <?php } ?>
                        <span class="h2 productPriceHolder pull-right" ><strong> € <span class="productPrice"><?=$price_value?></span></strong></span>
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
                            $videolink = $course['video_courses']== trim("") ? 'https://player.vimeo.com/video/188692091' : $course['video_courses'];
                            echo $videolink;
                            ?>" class="popup-vimeo">
                                <iframe src="<?=$videolink;?>" class="video-responsive" width="550" height="310" frameborder="0" scrolling="no" allowfullscreen></iframe>
                                <!--                                <iframe src="https://player.vimeo.com/video/188678448" class="video-responsive" width="550" height="310" frameborder="0" scrolling="no" allowfullscreen></iframe>-->
                            </a>
                        </div>
                        <!-- END Images -->

                        <!-- Info -->
                        <div class="col-md-4 push-bit">
                            <span class="productId" style="display: none;"><?=$course["learning_project_id"]?></span>
                            <div class="clearfix">
                                <div class="description"><span> <b>Descrizione: </b> </span>
                                    <div style="max-height: 90px; overflow: auto; margin-bottom: 10px;"><?=$course['lp_description']?> </div>
                                </div>
                                <div style="max-height: 90px; overflow: auto; margin-bottom: 10px;"><span> <b>Requisiti minimi per accedere: </b> </span><?=$course['requirements']?> </div>
                                <div style="max-height: 90px; overflow: auto; margin-bottom: 10px;"><span> <b>Riferimento normativo: </b> </span><?=$course['reference_law']?> </div>
                                <div style="max-height: 90px; overflow: auto; margin-bottom: 10px;"><span> <b>Obiettivi del corso: </b> </span><?=$course['targets']?> </div>
                                <div style="max-height: 90px; overflow: auto; margin-bottom: 10px;"><span> <b>Integrazione in aula: </b> </span><?=$course['external_integration']?> </div>
                                <div style="max-height: 90px; overflow: auto; margin-bottom: 10px;"><span> <b>Validità: </b> </span><?=$course['course_validita']?> </div>
                            </div>
                            
                            <div>
                                
                            </div>
                        </div>
                        <!-- END Info -->
                        <div class="col-md-2 push-bit">
                            <div style="padding:5px; border-radius: 5px; border: 1px solid #000;">
                                <div>
                                    <p style="line-height: 18px;">
                                        <b>Come pagare questo corso</b>
                                    <br>
                                    <span class="small">Puoi pagare comodamento con bonifico bancario 
                                    e inviarci subito il CRO e ti inviamo subio la licenza</span>
                                    </p>
                                </div>
                                <div>
                                    <p style="line-height: 18px;">
                                        <b>Come ottieni l'attestato </b>
                                        <br>
                                    <span class="small">Il corso si ritiene concluso solo
                                    dopo aver visionato tutte le lezioni previste e superato positivamente
                                    l'80% dei test</span>
                                </p>
                                </div>
                                <div>
                                    <p style="line-height: 18px; margin-bottom: 0;">
                                        <b>Il corso scade dopo</b>
                                    <br>
                                    <span class="small">180 giorni dal primo avvio del corso</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        </div>
                                            <!-- More Info Tabs -->
                        <div class="row">
                            <div class="col-sm-10 site-block">
                            <ul class="nav nav-tabs push-bit" data-toggle="tabs">
                                <li class="active" ><a href="#product-specs" style="cursor: pointer!important;"> Programma del corso</a></li>
                                <li><a href="#product-description" style="cursor: pointer!important; ">Regolamento</a></li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane active" id="product-specs">
                                    
                                <?php
                                    $num_mod = 1;
                                    foreach ($course_modules as $module) {
                                    $course_lessons = $course_obj->getCourseLessonsByModuleID($module['id']);
                                    ?>
                                    <h4>
                                        <strong>Modulo <?= $num_mod++ ?>: <?= $module['title'] ?></strong>
                                    </h4>
                                    <div>

                                                <!-- Lessons -->
                                        <div id="lesson_module_<?= $module['id'] ?>">
<!--                                                    <h2 class="subtitle_detail">Lezioni inserite</h2>-->
                                            <div>
                                                <ul style="list-style-type: none; padding: 0;" >
                                                    <?php foreach ($course_lessons as $lesson) { ?>
                                                    <li style="margin-top:10px;">Lezione <?= $lesson['position'] ?>: <?= $lesson['title'] ?>
                                                        <ul style="list-style-type: none; padding: 0;">
                                                            <?php
                                                            $lesson_id = $lesson['id'];
                                                            $res = $om_obj->getLearningObjectByLessonID($lesson_id);
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
                                                                    <table style="width: 100%;" >
                                                                        <tr>
                                                                            <td><img src="<?= $icon ?>" style="width: 24px;"> <span class="small" style="color: #1bbae1; cursor: default;"><?= $single['title'] ?></span></td>
                                                                        </tr>
                                                                    </table>
                                                                </li>

                                                            <?php } ?>

                                                        </ul>
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

                                <div class="tab-pane" id="product-description">
                                    <div class="regola1">
                                    <p class="regola-title" style="margin-bottom: 5px;"><b class="text-blue"> Regole durante il corso:</b> </p>
                                    <ul>
                                        <li> l'utente può collegarsi senza limiti di tempo e orario</li>
                                        <li> il corso può essere avviato e proseguire solo in modalità online</li>
                                        <li> in caso di risposta errata il corso prosegue</li>
                                        <li> un corso può essere interrotto in ogni momento e ripreso dall'ultimo punto utile visionato</li>
                                        <li> un corso una volta avviato deve essere terminato entro un dato tempo (v. termine impostato dall'Amm.re)</li>
                                        <li> ai test si deve rispondere entro 30 secondi, trascorsi i quali il sistema interrompe l'avanzamento del corso</li>
                                    </ul>
                                    </div>
                                    <div class="regola2" >
                                        <p> <b class="text-blue regola-title">Tempo di fruizione:</b> Gli oggetti didattici essendo distribuiti solo "online" permettono un tracciamento continuo del discente, nel caso del docutest (dispense o documenti in genere) viene consentito il logout. Il tempo stabilito per la lettura e lo studio è definito dalla circolare della Regione Lombardia ed è così espresso: <br>4.000 battute = 16 minuti di formazione; 8.000 battute = 32 minuti di formazione; ecc. <br> Nei documenti, è quindi riportata la durata in minuti e secondi che sono necessari per leggere il documento. <br>La piattaforma impedisce, al discente di proseguire alle lezioni successive prima che il tempo assegnato per ciascun documento sia completamente esaurito.</p>
                                    </div>
                                    <div class="regola3">
                                        <p> <b class="text-blue regola-title"> Percorso obbligato: </b> il corso si svolge lungo un percorso obbligato sviluppato dal sistema, il discente non può e non deve fare nulla, ogni elemento didattico viene proposto, per l'utente non è possibile spostarsi lungo le varie lezioni o gli oggetti multimediali, oppure rivederli. Tale scelta è effettuata per garantire il raggiungimento dell'obbiettivo formativo stabilito e impedire al discente di distrarsi sapendo di poter rivedere gli oggetti multimediali a piacimento. Solo il tutor didattico ha facoltà (su richiesta del discente o del Datore di Lavoro) di consentire di rivedere un oggetto multimediale. Il percorso obbligato può essere disattivato dall'Amm.re consentendo ai discenti di muoversi liberamente nel player.
                                    </div>
                                    <div class="regola4">
                                        <h2> <b class="text-blue"> Verifiche di apprendimento </b></h2>
                                        <p>la verifica di apprendimento principale privilegiata dalla didattica Tutor81 sono i test in itinere rilasciati ogni 5-10 minuti, tale metodo infatti consente di aumentare l'attenzione del discente, interagire con esso per correggere gli eventuali errori, verificarne la presenza. I questionari e i test sono rilasciati dalla piattaforma in modalità random.</p>
                                        <p>Il sistema preleva dal magazzino test le domande in modalità casuale, in questo modo il tipo e il numero di domande offerte al discente sono sempre differenti</p>
                                        <p>Ai test si deve rispondere entro 30 secondi, trascorsi i quali il sistema interrompe l'avanzamento del corso</p>
                                        <p>L'avanzamento del percorso formativo del discente viene monitorato anche attraverso il controllo degli accessi effettuati (data, orario, identificazione della postazione).</p>
                                        <p><b>test in itinere: </b>il test a tempo interrompe il filmato con domande sviluppate specificatamente sull'argomento trattato. Ricevendo immediato esito della risposta data, il discente ha la percezione di interagire con il sistema chiedendo aiuto anche al Tutor didattico. In caso di risposta non corretta il sistema ne illustra e spiega i motivi.
                                            quesito immediato: in qualsiasi istante il corsista può interrompere i filmati o le slide e formulare un quesito al Tutor didattico per essere contatto tramite email. Ogni quesito viene tracciato dal sistema e riportato nell'attestato finale.
                                        </p>

                                        <p> <b class="text-blue regola-title_small"> questionario finale in presenza: </b>il questionario finale in presenza conclude il corso</p>
                                        <p> <b class="text-blue regola-title_small"> efficacia apprendimento:  </b>il Tutor didattico ha facoltà di fare ripetere l'intero percorso formativo o parte di esso.</p>
                                    </div>

                                    <div class="regola5">
                                        <h2 class="text-blue"><b> La didattica</b></h2>
                                        <p>La didattica è progettata per COINVOLGERE in questo modo si attua un reale processo di FORMAZIONE in elearning.<br> Il format video grazie al suo impatto multimediale viene utilizzato come elemento motivazionale, successivamente vengono utilizzati web object, slide test e quiz game.<br> I testi vengono scritti per tramite di esperti e tecnici con ventennale esperienza nel campo della prevenzione e protezione nei luoghi di lavoro, per poi essere trasformati in FORMAT multimediali da tecnici e grafici di videografica.</p>

                                        <p>L'apprendimento si realizza mediante gli OGGETTI MULTIMEDIALI o o learning object, codificati con la sigla OM (oggetti multimediali) seguiti dal numero di riferimento, ogni aggiornamento e revisione viene siglato con REV.</p>
                                        <p style="margin-bottom: 5px;">A seconda dell'obbiettivo che si intende raggiungere i learning object riportano la seguente dicitura: </p>
                                        <ul style="list-style-type: none; padding-left: 10px;">
                                            <li><b class="big-number">1.</b> MOTIVAZIONALE</li>
                                            <li><b class="big-number">2.</b> SAPERE</li>
                                            <li><b class="big-number">3.</b> SAPER FARE</li>
                                        </ul>
                                        <p> Nello specifico i learning object sono:</p>
                                        <p> VIDEO TEST oggetto didattico in formato video che al suo interno contiene 1 o più test temporizzati (trasmessi in modalità random) E' così possibile controllare la presenza del discente e l'apprendimento per ogni argomento trattato in tempo reale.</p>
                                        <p> QUIZ GAME Il quiz interattivo permette di effettuare delle simulazioni, il discente è chiamato a rispondere a quesiti più o meno complessi.</p>
                                        <p> SLIDE - TEST si tratta di slide con elevata connotazione grafica che vengono intervallate da test temporizzati</p>
                                    </div>


                                </div>
                            </div>
                            </div>
                            <!-- END More Info Tabs -->

                            <!-- Most Viewed Courses Block -->
                            <div class="col-md-2">
                                <div class="block">
                                     <!--Most Viewed Courses Title--> 
                                    <div class="block-title">
                                        <h3><strong> Corsi Correlati</strong></h3>
                                    </div>
                                     <!--END Most Viewed Courses Title--> 
                                     <!--Most Viewed Courses Content--> 
                                        <div class="left-side-aligned">
                                            <?php
                                            //$course_list = $course_obj->getListActiveCourse($course['subcategory_id']);
                                            $coursetypes = T81CourseType::$course_type_filter_keys;
                                            $course_list = $course_obj->getCourseDetailedListOfAvailableLearningProjectByCompany ($tutor['id'], NULL, $course['subcategory_id']);
                                            $keys_type = array('nd', 'base', 'aggiornamento');
                                            $keys_risk = array('nd', 'basso', 'medio', 'alto', 'Tutti');
                                            $categories = array(array());
                                            foreach ($course_list as $single_course) {
                                                //$course_custom_categories = $course_obj->getCourseCustomCategories($single_course['id']);
                                                $course_type = $course_obj->getCourseType($single_course['course_id']);
                                                $course_risk = $course_obj->getCourseRisk($single_course['course_id']);
                                                $type_name = $course_type['definition'] ? : 'nd';
                                                $risk_name = $course_risk['definition'] ? : 'nd';
                                                if (!isset($categories[$type_name][$risk_name])) {
                                                    $categories[$type_name][$risk_name] = $single_course;
                                                }
                                            }
                                            foreach ($keys_type as $type_name){

                                                if (isset($categories[$type_name])){
                                                    $n = 0;
                                                    foreach ($keys_risk as $risk_name){
                                                        if (isset($categories[$type_name][$risk_name])){
                                                            $learn_id = $categories[$type_name][$risk_name]['learning_project_id'];
                                                            if ($learn_id == $learning_project_id || $learn_id == 85 || $learn_id == 92) continue;
                                                            $learn_title = T81LearningProject::formatTitle($categories[$type_name][$risk_name]['title']);
                                                            $related_course = $categories[$type_name][$risk_name];
                                                            $prices = $om_obj->getPriceFromLicense($related_course['course_id'],1);
                                                            $single_price = (int)$prices['price'];
                                                            ?>

                                                            <div class="single-product" data-learning_project_id="<?= $learn_id ?>" data-price="<?=$single_price;?>">
                                                                <h4 class="course-title"><a href="/ec-course-detail.php?id=<?= $learn_id ?>"><b><?= ucwords(strtolower($learn_title)) ?></b></a></h4>
                                                                <p class="text-right">Euro <?=$single_price;?> 
                                                                    <!--<a href="/ec-course-detail.php?id=<?= $learn_id ?>" class="btn btn-default">Dettagli »</a>-->
                                                                </p>
                                                            </div>

                                                            <?php
                                                        }
                                                    }
                                                }
                                            }
                                            ?>
                                        </div>
                                     <!--END Most Viewed Courses Content--> 
                                </div>
                            </div>
                             <!--END Most Viewed Courses Block--> 
                        </div>
                    <!-- More Info Tabs -->
                    </div>
                </div>
                <!-- END Product Details -->
            </div>
        </div>
    </section>
    <!-- END Product View -->

    <?php include_once 'ecommerce/modal-checkout.php'; ?>

    <!-- Footer -->
    <?php require_once 'ecommerce/footer.php'; ?>
    <!-- END Footer -->

</div>
<!-- END Page Container -->

<!-- Scroll to top link, initialized in js/app.min.js - scrollToTop() -->
<a href="#" id="to-top"><i class="fa fa-angle-up"></i></a>
<script src="js/pages/ecomDetail.js"></script>
</body>
</html>