<?php
$prod = $this->product;
$cat = $this->category;
?>
<div class="page-main-wrapper">
	<div class="page-main">
        <div class="product-view">
            <div class="product-essential">
                <form action="" method="post" onsubmit="return false;">
                    <input type="hidden" name="id" value="<?=$prod->id?>">
                    <div class="col-product-shop">
                        <h1 class="product-name"><?=$this->q($prod->product_name)?></h1>
                        <p class="no-rating"><a href="<?=Bapp::href('prodreviews/add')?>?pid=<?=$prod->id?>"><?= BLocale::_("Be the first to review this product") ?></a></p>
                        <div class="price-box">
                            <span class="price">$<?=number_format($prod->base_price, 2)?></span>
                        </div>
                        <div class="add-to-cart">

                            <?=$this->view('cart/add2cart', array('prod' => $prod))?>

                            <?=$this->view('wishlist/add2wishlist', array('prod' => $prod))?>

                            <label class="compare-label"><input type="checkbox" name="compare" class="compare-checkbox" value="<?=$prod->id?>"> <?= BLocale::_("Compare") ?></label>

                            <?=$this->view('compare/block')?>

                        </div>
                        <div class="short-description">
                            <?=$this->s($prod->description)?>
                        </div>
                        <div>
                            <?=$this->view('customfields/product', array('prod' => $prod))?>
                        </div>
                    </div>

                    <?php
                    $mediaList = FCom_Catalog_Model_ProductMedia::i()->orm()->where('product_id', $prod->id())->where('media_type', 'I')->find_many();
                    ?>
                    <div class="col-product-image">
                        <div class="product-img">
                        	<img src="<?=$prod->thumbUrl(400, 400)?>" alt="<?=$this->q($prod->product_name)?>" title="<?=$this->q($prod->product_name)?>" width="400" height="400"/></div>
                        <div class="additional-views">
                            <ul>
                                <?php foreach($mediaList as $media):?>
                                <li>
                                    <a href="<?=$media->getUrl()?>" rel="lightbox[prod_<?=$prod->id?>]" title=""><img src="<?=$media->getUrl()?>" width="45" height="45" alt=""></a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="clearer"></div>
                </form>
            </div>
            <div class="product-collateral" id="tabs">
            	<ul class="tabs">
            		<li><a href="#manufacturer"><?= BLocale::_("Manufacturer Web Page") ?></a></li>
            		<li><a href="#overview"><?= BLocale::_("Overview") ?></a></li>
            		<li><a href="#specifications"><?= BLocale::_("Specifications") ?></a></li>
            		<li><a href="#reviews"><?= BLocale::_("Reviews") ?></a></li>
            		<li><a href="#similar"><?= BLocale::_("Similar Products") ?></a></li>
            		<li><a href="#family"><?= BLocale::_("Family Products") ?></a></li>
            		<li><a href="#accessories"><?= BLocale::_("Accessories") ?></a></li>
            	</ul>
                <div class="panes">
                    <div class="tab-content box-description">
                        <h4><?= BLocale::_("Overview") ?></h4>
                            Tray Acrylic Material

                            1 lb package contains: 1 lb powder, 8oz liquid.

                            3 lb package contains: 3 lbs powder, 16oz liquid.
                    </div>
                    <div class="tab-content box-tags">
                        <h4><?= BLocale::_("Specifications") ?></h4>
                    </div>
                    <div class="tab-content">
                        <?php echo $this->hook('prodreviews-reviews', array('product' => $prod)) ?>
                        <?//=$this->view('prodreviews/reviews', array('prod' => $prod, 'reviews' => $this->product_reviews))?>
                    </div>
                    <div class="tab-content">
                    	<h4><?= BLocale::_("Similar Products") ?></h4>
                    	<table class="product-list">
				        	<colgroup><col width="30">
				        	<col width="60">
				        	<col>
				        	<col width="180">
				            </colgroup><tbody>
<?=$this->view('catalog/product/rows')->set('products', FCom_Catalog_Model_ProductLink::i()->productsByType($prod->id, 'similar')) ?>
				            </tbody>
				        </table>
				  	</div>
        		</div>
                <div class="tab-content">
                    	<h4><?= BLocale::_("Related Products") ?></h4>
                    	<table class="product-list">
				        	<colgroup><col width="30">
				        	<col width="60">
				        	<col>
				        	<col width="180">
				            </colgroup><tbody>
<?=$this->view('catalog/product/rows')->set('products', FCom_Catalog_Model_ProductLink::i()->productsByType($prod->id, 'related')) ?>
				            </tbody>
				        </table>
				  	</div>
        		</div>
                <div class="tab-content">
                    <h4><?= BLocale::_("Family Products") ?></h4>
                    <table class="product-list">
                        <colgroup><col width="30">
                        <col width="60">
                        <col>
                        <col width="180">
                        </colgroup><tbody>
<?=$this->view('catalog/product/rows')->set('products', FCom_Catalog_Model_Product::i()->orm()->offset(20)->limit(10)->find_many()) ?>
                        </tbody>
                    </table>
                </div>
                <div class="tab-content">
                    <h4><?= BLocale::_("Accessories") ?></h4>
                    <table class="product-list">
                        <colgroup><col width="30">
                        <col width="60">
                        <col>
                        <col width="180">
                        </colgroup><tbody>
<?=$this->view('catalog/product/rows')->set('products', FCom_Catalog_Model_Product::i()->orm()->offset(30)->limit(10)->find_many()) ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>