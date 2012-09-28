<?php

class FCom_Market_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
        BMigrate::upgrade('0.1.1', '0.1.2', array($this, 'upgrade_0_1_2'));
        BMigrate::upgrade('0.1.2', '0.1.3', array($this, 'upgrade_0_1_3'));
    }

    public function install()
    {
        $tModules = FCom_Market_Model_Modules::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tModules} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `mod_name` VARCHAR( 255 ) NOT NULL DEFAULT '',
            `name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `version` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
            `description` text COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

    public function upgrade_0_1_1()
    {
        $pModules = FCom_Market_Model_Modules::table();
        BDb::run( " ALTER TABLE {$pModules} ADD `need_upgrade` tinyint(1) NOT NULL DEFAULT '0'");
    }

    public function upgrade_0_1_2()
    {
        $pModules = FCom_Market_Model_Modules::table();
        BDb::run( " ALTER TABLE {$pModules} MODIFY `description` text DEFAULT NULL");
    }

    public function upgrade_0_1_3()
    {
        $pModules = FCom_Market_Model_Modules::table();
        BDb::run( " ALTER TABLE {$pModules} ADD `market_version` varchar(50) DEFAULT NULL");
    }
}