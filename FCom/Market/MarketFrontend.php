<?php

class FCom_Market_Frontend extends BClass
{
    public static function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /market/manifest', 'FCom_Market_Frontend_Controller.manifest');
    }
}