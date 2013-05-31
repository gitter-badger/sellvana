<?php

class FCom_CustomField_Frontend extends BClass
{
    public static function bootstrap()
    {
        FCom_CustomField_Main::bootstrap();

        BEvents::i()
            ->on('BLayout::hook.custom-fields-filters', 'FCom_CustomField_Main.hookCustomFieldFilters')
        ;

        BLayout::i()->addAllViews('Frontend/views');
    }
}