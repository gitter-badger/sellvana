<?php return array(
    'modules' => array(
        'FCom_Wishlist' => array(
            'version' => '0.1.0',
            'root_dir' => '',
            'migrate' => 'FCom_Wishlist_Migrate',
            'translations' => array('de' => 'de.php'),
            'require' => array('module'=>array('FCom_Catalog'=>'0.2.1', 'FCom_Customer'=>'0.1.3')),
            'tests' => "FCom_Wishlist_Tests_AllTests",
            'bootstrap' => array('file'=>'Wishlist.php', 'callback'=>'FCom_Wishlist::bootstrap'),
            'areas' => array(
                'FCom_Frontend' => array(
                    'bootstrap' => array('file'=>'WishlistFrontend.php', 'callback'=>'FCom_Wishlist_Frontend::bootstrap'),
                ),
            ),
            'description' => "Wishlist functionality",
        ),
    ),
);