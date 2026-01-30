    <td class="text-center"><?=$single["Tipo"]?></td>
    <td class="text-left "style="color: #00a7d0;"><?=$single["title"]?></td>
    <td class="text-center "><?=$single["duration"]?></td>
    <td class="text-center">
        <button type="button" class="btn dec btn-xs btn-danger" data-toggle="tooltip" style="background-color: #d3d3d3; border-color:#d3d3d3; color: #808080;"><i class="fa fa-minus"></i></button>
        <input class="amount productQuantita step1-amount-css" style="cursor: default;width: 35px;text-align: center;border: none;" type="text" min="1" max="999" value="1" maxlength="3">
        <button type="button" class="btn inc btn-xs btn-success" data-toggle="tooltip" style="background-color: #d3d3d3; border-color:#d3d3d3; color: #808080;"><i class="fa fa-plus"></i></button>
    </td>
    <td class="text-center"><span class="tuoCosto">Compreso</span></td>
    <td class="text-center">

        <?php switch ($price_list_single[0]["name"]) {
            case "price range 1": ?>
                <?php echo "*"; $price_value = number_format($price_list_single[0]["price"], 2, ',', ' '); ?>
                <span class="store-item-price themed-color-dark " data-contentwrapper=".mycontent" rel="popover" id="course<?php echo $index; ?>" style="cursor: default;">
                            € <span class="productPrice"><?=$price_value?></span>
                        </span>
                <div class="productPriceList" style="display: none;">
                    <div class="well-lg" style="border: 1px solid #00a7d0;">
                        <div class="row" style="border: 1px #0000cc; border-style: solid solid solid solid;">
                            <div class="col-sm-6" style="border-right: 1px solid #0000cc;"><b>Qantit&agrave;</b></div>
                            <div class="col-sm-6"><b>Prezzo</b></div>
                        </div>
                        <?php foreach ($price_list_single as $indexPrice=>$price) {
                            //print_r($price)
                            ?>
                            <div class="row productPriceListRow" style="border: 1px #0000cc; border-style: none solid solid solid;">
                                <div class="col-sm-6 amountRange" style="border-right: 1px solid #0000cc;">
                                    <?php echo $price["lower_limit"]."-".$price["upper_limit"]; ?>
                                </div>
                                <div class="col-sm-6 amountPrice">€ <?php echo $price["price"]; ?></div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <?php break; ?>
            <?php case "Single":
                $price_value = number_format($price_list_single[0]["price"], 2, ',', ' '); ?>
                <span class="store-item-price themed-color-dark " data-contentwrapper=".priceList" id="course<?php echo $index; ?>" style="cursor: default;">
                            € <span class="productPrice"><?=$price_value?></span>
                        </span>
                <?php break; ?>
            <?php } ?>

    </td>
    <td class="text-center">
        <button type="button" class="btn btn-xs btn-success checkout-vendi" data-target="#invia_Licenze_Modal" data-toggle="modal" style="background: transparent; border: transparent; color: #00a7d0;"><i class="fa fa-shopping-cart"></i></button>
    </td>
