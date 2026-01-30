<?php 
require_once 'config.php';
require_once BASE_LIBRARY_PATH . 'class_learning_project.php';

?>
<td class="text-center  whiteFontOnClick" data-sort="<?= $tipo["order"] ?>">
    <label class="label"
           style="text-transform: capitalize; background-color:<?= $tipo["color"] ?>">
        <?= $tipo["label"] ?>
    </label>
</td>
<td class="text-center  whiteFontOnClick" data-sort="<?= $rischio['order'] ?>">
    <label class="label"
           style="text-transform: capitalize; background-color: <?= $rischio['color'] ?>">
        <?= $rischio["label"] ?>
    </label>
</td>
<td class="text-center"><?= $single['learning_project_id'] ?></td>
<td class="text-left courseTitle whiteFontOnClick" style="color: <?php if (isset($single["Tipo"]) && $single["Tipo"] == 'base') echo '#394263'; else echo '#1bbae1'; ?>"><?= T81LearningProject::formatTitle($single["title"]) ?></td>
<td class="text-center whiteFontOnClick"><?= $single["duration"] ?></td>

<td class="text-center ">
    <?php $price_range_selector = isset($price_list_single[0]["name"]) ? $price_list_single[0]["name"] : "";
    switch ($price_range_selector) {
        case "price range 1": ?>
            <?php $price_value = number_format($price_list_single[0]["price"], 2, ',', ' '); ?>
            <span class="store-item-price themed-color-dark " data-contentwrapper=".mycontent" rel="popover"
                  id="course<?php echo $index_row; ?>" style="cursor: default;">
                    € <span class="productPrice"><?= $price_value ?></span>
                </span>
            <div class="productPriceList" style="display: none;">
                <div class="well-lg" style="border: 1px solid #00a7d0;">
                    <div class="row" style="border: 1px #0000cc; border-style: solid solid solid solid;">
                        <div class="col-sm-6" style="border-right: 1px solid #0000cc;"><b>Qantit&agrave;</b></div>
                        <div class="col-sm-6"><b>Prezzo</b></div>
                    </div>
                    <?php foreach ($price_list_single as $indexPrice => $price) {
                        //print_r($price)
                        ?>
                        <div class="row productPriceListRow"
                             style="border: 1px #0000cc; border-style: none solid solid solid;">
                            <div class="col-sm-6 amountRange" style="border-right: 1px solid #0000cc;">
                                <?php echo $price["lower_limit"] . "-" . $price["upper_limit"]; ?>
                            </div>
                            <div class="col-sm-6 amountPrice ">€ <?php echo $price["price"]; ?></div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php break; ?>
        <?php case "Single":
        $price_value = number_format($price_list_single[0]["price"], 2, ',', ' '); ?>
        <span class="store-item-price themed-color-dark whiteFontOnClick" data-contentwrapper=".priceList"
              id="course<?php echo $index_row; ?>" style="cursor: default;">
                    € <span class="productPrice "><?= $price_value ?></span>
                </span>
        <?php break; ?>
    <?php default:
        echo "---"; ?>
    <?php } ?>

</td>

<td class="text-center whiteFontOnClick">
    <span
        class="tuoCosto text-danger">&euro; 
            <?php echo isset($price_list_single[0]["price"]) ? number_format($price_list_single[0]["price"] * (1-$_SESSION["user"]["plan"]["discount"]/100), 2, ',', ' ') : 0; ?></span>
</td>

<td class="text-center">
    <span class="productId" style="display: none;"><?= $single['learning_project_id'] ?></span>
    <input type="hidden" title="learningProjectID" class="rowLearningProjectID" value="<?= $single['course_id'] ?>">
    <button type="button" class="btn btn-xs checkout-vendi" id="invia_Licenze_row_<?= $index_row ?>"
            data-toggle="modal">
        <?php
        if ($_SESSION['user']['role'] == 2) {
            echo '<i class="fa fa-arrow-right"></i><i class="fa fa-user"></i>';
        } else {
            echo '<i class="fa fa-shopping-cart" style="font-size: large;"></i>';
        }
        ?>
    </button>
</td>
