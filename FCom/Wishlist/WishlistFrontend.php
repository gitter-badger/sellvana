<?php

class FCom_Wishlist_Frontend extends BClass
{
    public static function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /wishlist', 'FCom_Wishlist_Frontend_Controller.wishlist')
            ->route( 'POST /wishlist', 'FCom_Wishlist_Frontend_Controller.wishlist_post');

        BLayout::i()->addAllViews('Frontend/views')
                ->afterTheme('FCom_Wishlist_Frontend::layout');
    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'head', 'do'=>array(
                    array('js', '{FCom_Wishlist}/Frontend/js/fcom.wishlist.js'),
                )
            )),
            '/wishlist'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('wishlist'))
            )
        ));
    }
}