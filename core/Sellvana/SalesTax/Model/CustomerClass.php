<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_SalesTax_Model_CustomerClass extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_salestax_customer_class';
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['title'],
    ];

    public function getAllTaxClasses()
    {
        return $this->orm()->find_many_assoc('id', 'title');
    }
}
