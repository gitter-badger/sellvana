<?php

class FCom_Admin_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.2', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
        BMigrate::upgrade('0.1.1', '0.1.2', array($this, 'upgrade_0_1_2'));
    }

    public function install()
    {
        $tRole = FCom_Admin_Model_Role::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tRole} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `role_name` VARCHAR(50) NOT NULL,
            `permissions_data` TEXT NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tUser = FCom_Admin_Model_User::table();
        $tRole = FCom_Admin_Model_Role::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tUser} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `superior_id` int(10) unsigned DEFAULT NULL,
            `username` varchar(255) NOT NULL,
            `is_superadmin` tinyint(4) NOT NULL DEFAULT '0',
            `role_id` int(11) unsigned DEFAULT NULL,
            `email` varchar(255) NOT NULL,
            `password_hash` varchar(255) NOT NULL,
            `firstname` varchar(100) DEFAULT NULL,
            `lastname` varchar(100) DEFAULT NULL,
            `phone` varchar(50) DEFAULT NULL,
            `phone_ext` varchar(50) DEFAULT NULL,
            `fax` varchar(50) DEFAULT NULL,
            `status` char(1) NOT NULL DEFAULT 'A',
            `tz` varchar(50) NOT NULL DEFAULT 'America/Los_Angeles',
            `locale` varchar(50) NOT NULL DEFAULT 'en_US',
            `create_dt` datetime NOT NULL,
            `update_dt` datetime DEFAULT NULL,
            `token` varchar(20) DEFAULT NULL,
            `token_dt` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `UNQ_email` (`email`),
            UNIQUE KEY `UNQ_username` (`username`),
            CONSTRAINT `FK_{$tUser}_role` FOREIGN KEY (`role_id`) REFERENCES {$tRole} (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
            CONSTRAINT `FK_{$tUser}_superior` FOREIGN KEY (`superior_id`) REFERENCES {$tUser} (`id`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tPersonalize = FCom_Admin_Model_Personalize::table();
        $tUser = FCom_Admin_Model_User::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tPersonalize} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` int(10) unsigned NOT NULL,
            `data_json` text,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tPersonalize}_user` FOREIGN KEY (`user_id`) REFERENCES {$tUser} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function upgrade_0_1_1()
    {
        $tUser = FCom_Admin_Model_User::table();

        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tUser, 'is_superadmin')) {
            return;
        }

        try {
            BDb::run("
                ALTER TABLE {$tUser}
                ADD COLUMN `is_superadmin` TINYINT DEFAULT 0 NOT NULL AFTER `username`
                , ADD COLUMN `role_id` INT NULL AFTER `is_superadmin`
                , ADD COLUMN `token` varchar(20) DEFAULT NULL
                ;
            ");
        } catch (Exception $e) { }

        FCom_Admin_Model_Role::i()->install();
        BDb::run("
            UPDATE {$tUser} SET is_superadmin=1;
        ");
    }

    public function upgrade_0_1_2()
    {
        $tUser = FCom_Admin_Model_User::table();
        BDb::ddlClearCache();
        if (BDb::ddlFieldInfo($tUser, 'token_dt')) {
            return;
        }
        try {
            BDb::run("
                ALTER TABLE {$tUser} ADD COLUMN `token_dt` DATETIME NULL AFTER `token`;
            ");
        } catch (Exception $e) { }
    }
}