<?php

class FCom_Checkout_Admin extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
    }
}

