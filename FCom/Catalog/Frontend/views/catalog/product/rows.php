<?
//$loggedIn = Denteva_Model_User::i()->isLoggedIn();
//Denteva_Model_Manuf::i()->cachePreloadFrom($this->products, 'manuf_id');
$loggedIn = FCom_Customer_Model_Customer::isLoggedIn();
?>
<? foreach ($this->products as $p): ?>
<tr id="tr-product-<?=$p->id?>">
    <td class="first a-center">
        <label class="compare-label"><input type="checkbox" name="compare" class="compare-checkbox" value="<?=$p->id?>"> <?= BLocale::_("Compare"); ?></label>
    </td>
    <td>
        <img src="<?=$this->q($p->thumbUrl(85, 60))?>" width="85" height="60" class="product-img" alt="<?=$this->q($p->product_name)?>"/>
    </td>
    <td>
        <h3 class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></h3>
        <span class="sku"><?= BLocale::_("Part"); ?> #: <?=$this->q($p->manuf_sku)?></span>
        <span class="rating">
            <span class="rating-out"><span class="rating-in" style="width:35px"></span></span>
            3.5 of 5 (<a href="#">16 reviews</a>)
        </span>
    </td>
    <td class="actions last a-left">
        <div class="price-box <?=$loggedIn?'logged-in':'logged-out'?>">
            <? if ($loggedIn):?><span class="availability in-stock"><?= BLocale::_("In Inventory"); ?></span><? endif ?>
            <span class="price-label"><?= BLocale::_("As low as"); ?></span>
            <p><span class="price">$<?=number_format($p->base_price)?></span><span class="supplier">Darby Dental</span></p>
            <div class="price-range">
                <strong><a href="#" class="vendor-count">13 <?= BLocale::_("Vendors"); ?></a></strong>: $24-$49
            </div>
            <div class="tt tooltip">
                <div class="tt-arrow"></div>
                <div class="tt-header">13 <?= BLocale::_("Vendors"); ?></div>
                <div class="tt-content">
                    <ul>
                        <li><span class="label">Darby Dental</span><span class="lowest-price"><?= BLocale::_("Lowest Price"); ?></span><span class="price">$24</span></li>
                        <li><span class="label">Darby Dental</span><span class="price">$24</span></li>
                    </ul>
                </div>
            </div>
            <button class="button btn-add-to-cart" onclick="add_cart(<?=$p->id?>, 1)">+ <?= BLocale::_("Add to Cart"); ?></button>
        </div>
    </td>
</tr>
<? endforeach ?>