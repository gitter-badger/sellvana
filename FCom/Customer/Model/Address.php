<?php

class FCom_Customer_Model_Address extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_customer_address';
    protected static $_origClass = __CLASS__;

    public function as_html()
    {
        return '<div class="adr">'
            .'<div class="street-address">'.$this->street1.'</div>'
            .($this->street2 ? '<div class="extended-address">'.$this->street2.'</div>' : '')
            .($this->street3 ? '<div class="extended-address">'.$this->street3.'</div>' : '')
            .'<span class="locality">'.$this->city.'</span>,'
            .'<span class="region">'.$this->region.'</span>'
            .'<span class="postal-code">'.$this->postcode.'</span>'
            .'<div class="country-name">'.$this->country.'</div>'
            .'</div>';

    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->create_dt) $this->create_dt = BDb::now();
        $this->update_dt = BDb::now();
        return true;
    }

    public function install()
    {
        BDb::run("
CREATE TABLE IF NOT EXISTS ".static::table()." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `firstname` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `attn` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street1` text COLLATE utf8_unicode_ci NOT NULL,
  `street2` text COLLATE utf8_unicode_ci,
  `street3` text COLLATE utf8_unicode_ci,
  `city` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `county` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `county_id` int(11) DEFAULT NULL,
  `region` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `region_id` int(11) DEFAULT NULL,
  `postcode` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `create_dt` datetime NOT NULL,
  `update_dt` datetime NOT NULL,
  `lat` decimal(15,10) DEFAULT NULL,
  `lng` decimal(15,10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

    public static function import($data, $cust)
    {
        if ($cust->default_billing_id) {
            $addr = static::load($cust->default_billing_id);
            //$addr = Model::factory('FCom_Customer_Model_Address')->find_one($cust->default_billing_id);
        }
        if (empty($addr)) {
            $addr = static::create(array('customer_id' => $cust->id));
        }
        if (!empty($data['address']['country']) && strlen($data['address']['country'])>2) {
            $data['address']['country'] = FCom_Geo_Model_Country::i()->getIsoByName($data['address']['country']);
        }
        $addr->set($data['address']);
        $addr->save();

        if (!$cust->default_billing_id) {
            $cust->set('default_billing_id', $addr->id);
        }
        if (!$cust->default_shipping_id) {
            $cust->set('default_shipping_id', $addr->id);
        }
        $cust->save();

        return $addr;
    }
}