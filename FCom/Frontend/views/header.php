<header class="header">
    <div class="header-top">
        <strong class="logo">Fulleron</strong>
        <nav class="sup-links">
            <ul>
                <?php if(FCom_Customer_Model_Customer::isLoggedIn()):?>
                    <li><strong><a href="<?=BApp::href('customer/myaccount')?>">My account</a></strong></li>
                    <li class="header-sup-signin">
                        Hello <?=FCom_Customer_Model_Customer::sessionUser()->email?>
                        <strong><a href="<?=BApp::href('logout')?>">Logout</a></strong>
                    </li>

                <?php else: ?>
                    <li class="header-sup-signin">Hello there! <strong><a href="<?=BApp::href('login')?>">Sign in</a></strong></li>
                    <li class="header-sup-wishlist"><a href="<?=BApp::href('customer/register')?>">Sign up</a></li>
                <?php endif; ?>
                <?=$this->view('cart/header')?>
                <li><a href="<?=BApp::href('wishlist')?>">Your Wishlist</a></li>
            </ul>
        </nav>
    </div>
    <div class="header-bottom">
        <div class="site-nav-container">
            <nav class="site-nav">
                <ul>
                    <li><a href="<?=BApp::baseUrl()?>">Home</a>
                    <?=$this->view('nav')?>
                </ul>
            </nav>
        </div>
    </div>
</header>