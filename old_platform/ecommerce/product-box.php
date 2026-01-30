<?php
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';
?>
<div class="col-md-6" data-toggle="animation-appear" data-animation-class="animation-fadeInQuick"
     data-element-offset="-100">
    <div
        class="store-item <?php if ($single["Tipo"] == 'nuovo') echo 'store-item-green'; else echo 'store-item-blue'; ?>">
        <div class="store-item-rating"
             style="text-transform: uppercase; background-color: #1BBAE1; font-weight: 800;  color: white;">
            <span class="productTipo pull-left"><?= $single["Tipo"] ?></span> <span
                class="top-subcategory"><?= $single["subcategory"] ?></span> <span
                class="productOre pull-right"><?= $single["duration"] ?>
                or<?php if ($single["duration"] == "1") { ?>a<?php } else { ?>e<?php } ?></span>
        </div>
        <!--        position: absolute; font-size: 40px; cursor: pointer ; -->
        <div class="store-item-image" style="height: 260px; position: relative;">
            <a href=" <?= isset($wordpress_url) && $wordpress_url == "/ec-wordpress.php" ? "/ec-details-wordpress.php?id=" . $single['learning_project_id'] : "/ec-course-detail.php?id=" . $single['learning_project_id'] ?> ">
                <img src="/img/video-icon.png" class="player-img"
                     style="position: absolute; top:120px; left: 160px; width: 80px; z-index: 1;">
                <img class="productImageSrc img-responsive"
                     src="<?= HUBMEDIA_URL ?>/img/courses/<?php echo $single["ecommerce_image_filename"]; ?>" alt="">
            </a>
        </div>
        <div class="store-item-info clearfix">
            <?php switch ($price_list_single[0]["name"]) {
                case "price range 1": ?>
                    <?php $price_value = number_format($price_list_single[0]["price"], 2, ',', ' '); ?>
                    <span class="store-item-price themed-color-dark pull-right" data-popover-content=".productPriceList"
                          data-placement="top" rel="popover" id="course<?php echo $index; ?>" style="cursor: pointer;">
                            € <span class="productPrice"><?= $price_value ?> </span>
                        </span>
                    <div class="productPriceList hide">
                        <div class="well-lg" style="padding: 5px;">
                            <div class="row" style="border: 1px #1BBAE1; border-style: solid solid solid solid;">
                                <div class="col-sm-6" style="border-right: 1px solid #1BBAE1;"><b>Qantit&agrave;</b>
                                </div>
                                <div class="col-sm-6"><b>Prezzo</b></div>
                            </div>
                            <?php foreach ($price_list_single as $indexPrice => $price) {
                                //print_r($price)
                                ?>
                                <div class="row productPriceListRow"
                                     style="border: 1px #1BBAE1; border-style: none solid solid solid;">
                                    <div class="col-sm-6 amountRange "
                                         style="border-right: 1px solid #1BBAE1; padding: 3px;">
                                        <?php echo $price["lower_limit"] . "-" . $price["upper_limit"]; ?>
                                    </div>
                                    <div class="col-sm-6 amountPrice">€ <?php echo $price["price"]; ?></div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php break; ?>
                <?php case "Single":
                    $price_value = number_format($price_list_single[0]["price"], 2, ',', ' '); ?>
                    <span class="store-item-price themed-color-dark pull-right" id="course<?php echo $index; ?>"
                          style="cursor: pointer;">
                            € <span class="productPrice"><?= $price_value ?></span>
                        </span>
                    <?php break; ?>
                <?php } ?>
            <a href=" <?= isset($wordpress_url) && $wordpress_url == "/ec-wordpress.php" ? "/ec-details-wordpress.php?id=" . $single['learning_project_id'] : "/ec-course-detail.php?id=" . $single['learning_project_id'] ?> ">
                <strong class="nome productTitle"><?= T81LearningProject::formatTitle($single["title"]) ?></strong></a><br>
            <!--            <div class="row">-->
            <!--                <div class="col-sm-5 pull-left" style="padding-left: 15px; padding-right: 5px;">-->
            <!--                    <small> 5 online + 3 in aula</small>-->
            <!--                </div>-->
            <!--                <div class="col-sm-7 text-right" style="padding-left: 5px; padding-right: 15px;">-->
            <small>
                <i class="fa fa-shopping-cart text-muted"></i>
                <span class="productId" style="display: none;"><?= $single['learning_project_id'] ?></span>
                <a class="buttonAcquista " href="#" type="button"> Acquista</a>
            </small>
            <!--                </div>-->
            <!--            </div>-->
            <input type="hidden" value="<?= $index ?>" name="productIndex" class="productIndex">
        </div>
    </div>
</div>
<!-- Tooltip Popover function -->

<script>
    $('.store-item-rating:contains("nuovo")').css('background-color', '#aad178');

    $(function () {
        $('[rel="popover"]').popover({
            container: 'body',
            html: true,
            trigger: 'hover',
            content: function () {
                var clone = $($(this).data('popover-content')).clone(true).removeClass('hide');
                return clone;
            }
        }).click(function (e) {
            e.preventDefault();
        });
    });
</script>