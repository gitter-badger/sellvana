<?php

class FCom_CustomField_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', array($this, 'install'));
        BMigrate::upgrade('0.1.0', '0.1.1', array($this, 'upgrade_0_1_1'));
        BMigrate::upgrade('0.1.1', '0.1.2', array($this, 'upgrade_0_1_2'));
    }

    public function install()
    {
        $tField = FCom_CustomField_Model_Field::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tField} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `field_type` enum('product') NOT NULL DEFAULT 'product',
            `field_code` varchar(50) NOT NULL,
            `field_name` varchar(50) NOT NULL,
            `table_field_type` varchar(20) NOT NULL,
            `admin_input_type` varchar(20) NOT NULL DEFAULT 'text',
            `frontend_label` text,
            `frontend_show` tinyint(1) not null default 1,
            `config_json` text,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tField = FCom_CustomField_Model_Field::table();
        $tFieldOption = FCom_CustomField_Model_FieldOption::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tFieldOption} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `field_id` int(10) unsigned NOT NULL,
            `label` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `field_id__label` (`field_id`,`label`),
            CONSTRAINT `FK_{$tFieldOption}_field` FOREIGN KEY (`field_id`) REFERENCES {$tField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tSet = FCom_CustomField_Model_Set::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tSet} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `set_type` enum('product') NOT NULL DEFAULT 'product',
            `set_code` varchar(100) NOT NULL,
            `set_name` varchar(100) NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");

        $tSet = FCom_CustomField_Model_Set::table();
        $tField = FCom_CustomField_Model_Field::table();
        $tSetField = FCom_CustomField_Model_SetField::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tSetField} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `set_id` int(10) unsigned NOT NULL,
            `field_id` int(10) unsigned NOT NULL,
            `position` smallint(5) unsigned DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `set_id__field_id` (`set_id`,`field_id`),
            KEY `set_id__position` (`set_id`,`position`),
            CONSTRAINT `FK_{$tSetField}_field` FOREIGN KEY (`field_id`) REFERENCES {$tField} (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `FK_{$tSetField}_set` FOREIGN KEY (`set_id`) REFERENCES {$tSet} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8;
        ");

        $tProductField = FCom_CustomField_Model_ProductField::table();
        $tProduct = FCom_Catalog_Model_Product::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tProductField} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `product_id` int(10) unsigned NOT NULL,
            `_fieldset_ids` text,
            `_add_field_ids` text,
            `_hide_field_ids` text,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_{$tProductField}_product` FOREIGN KEY (`product_id`) REFERENCES {$tProduct} (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }

    public function upgrade_0_1_1()
    {
        $tField = FCom_CustomField_Model_Field::table();
        $fieldName = 'frontend_show';
        if (BDb::ddlFieldInfo($tField, $fieldName)) {
            return false;
        }
        BDb::run( " ALTER TABLE {$tField} ADD {$fieldName} tinyint(1) not null default 1; ");
    }

    public function upgrade_0_1_2()
    {
        $tField = FCom_CustomField_Model_Field::table();
        BDb::run( " ALTER TABLE {$tField} ADD `sort_order` int(11) NOT NULL DEFAULT '0'");
    }
}