<?php return array(
    'modules' => array(
        'FCom_ShippingUps' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'depends' => array('FCom_Checkout'),
            'bootstrap' => array('file'=>'Ups.php', 'callback'=>'FCom_ShippingUps_Ups::bootstrap'),
            'areas' => array(
                'FCom_Admin' => array(
                    'bootstrap' => array('file'=>'ShippingUpsAdmin.php', 'callback'=>'FCom_ShippingUps_Admin::bootstrap'),
                ),
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'ShippingUpsFrontend.php', 'callback'=>'FCom_ShippingUps_Frontend::bootstrap'),
                ),
            ),
            'description' => "Universal post service shipping module for checkout",
        ),
    ),
);