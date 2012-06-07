<?php
$homeUrl = BApp::href();
$sampleLandingUrl = BApp::href('category/landing');
$sampleCatProdUrl = BApp::href('category/products');

$cart = FCom_Checkout_Model_Cart::sessionCart();
$itemNum = 0;
$itemPrice = 0;
if($cart){
    $itemPrice = round($cart->subtotal,2);
    $itemNum = ceil($cart->item_num);
}
?>
<header class="header">
    <div class="header-top">
        <strong class="logo">Fulleron</strong>
        <nav class="sup-links">
            <ul>
                <?php if(FCom_Customer_Model_Customer::isLoggedIn()):?>
                <li class="header-sup-signin">Hello <?=FCom_Customer_Model_Customer::sessionUser()->email?>
                    <strong><a href="<?=BApp::href('logout')?>">Logout</a></strong></li>
                <?php else: ?>
                    <li class="header-sup-signin">Hello there! <strong><a href="<?=BApp::href('login')?>">Sign in</a></strong></li>
                <?php endif; ?>
                <li class="header-sup-cart"><a href="<?=BApp::href('cart')?>">Your Cart <span class="count">(<?=$itemNum?>)</span> | Total: $<?=$itemPrice?></a></li>
                <li class="header-sup-wishlist"><a href="<?=BApp::href('wishlist')?>">Your Wishlist</a></li>
            </ul>
        </nav>
    </div>
    <div class="header-bottom">
        <div class="site-nav-container">
            <nav class="site-nav">
                <ul>
                    <li class="active"><a href="<?=$homeUrl?>">Home</a></li>
                    <li><a href="<?=$sampleLandingUrl?>">Apparel</a></li>
                    <li><a href="<?=$sampleLandingUrl?>">Books</a></li>
                    <li><a href="<?=$sampleLandingUrl?>">Electronics</a>
                        <ul>
                            <li><a href="<?=$sampleCatProdUrl?>">Laptops, Tablets &amp; Netbooks</a></li>
                            <li><a href="<?=$sampleCatProdUrl?>">Desktops &amp; Servers</a></li>
                            <li><a href="<?=$sampleCatProdUrl?>">Computer Accessories &amp; Peripherals</a></li>
                            <li><a href="<?=$sampleCatProdUrl?>">External drives, mice, networking</a></li>
                            <li><a href="<?=$sampleCatProdUrl?>">Computer Parts &amp; Components</a></li>
                            <li><a href="<?=$sampleCatProdUrl?>">Software</a></li>
                            <li><a href="<?=$sampleCatProdUrl?>">PC Games</a></li>
                            <li><a href="<?=$sampleCatProdUrl?>">Printers &amp; Ink</a></li>
                            <li><a href="<?=$sampleCatProdUrl?>">Office &amp; School Supplies</a></li>
                        </ul>
                    </li>
                    <li><a href="<?=$homeUrl?>">Grocery</a></li>
                    <li><a href="<?=$homeUrl?>">Pets</a></li>
                    <li><a href="<?=$homeUrl?>">Sports</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>