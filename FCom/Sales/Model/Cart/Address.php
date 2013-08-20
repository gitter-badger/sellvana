<?php

class FCom_Sales_Model_Cart_Address extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_cart_address';
    protected static $_origClass = __CLASS__;

    public function as_html($obj=null)
    {
        if (is_null($obj)) {
            $obj = $this;
        }
        $countries = FCom_Geo_Model_Country::i()->options();
        return '<div class="adr">'
            .'<div class="street-address">'.$obj->street1.'</div>'
            .($obj->street2 ? '<div class="extended-address">'.$obj->street2.'</div>' : '')
            .($obj->street3 ? '<div class="extended-address">'.$obj->street3.'</div>' : '')
            .'<span class="locality">'.$obj->city.'</span>, '
            .'<span class="region">'.$obj->region.'</span> '
            .'<span class="postal-code">'.$obj->postcode.'</span>'
            .'<div class="country-name">'.(!empty($countries[$obj->country]) ? $countries[$obj->country] : $obj->country).'</div>'
            .'</div>';

    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if (!$this->create_at) $this->create_at = BDb::now();
        $this->update_at = BDb::now();
        return true;
    }

    protected $validationRules = array(
        array('firstname', '@required'),
        array('firstname', '@alphanum'),
        array('lastname', '@required'),
        array('lastname', '@alphanum'),
        array('email', '@required'),
        array('email', '@email'),
        array("street1", '@required', "Missing required field"),
        array("city", '@required'),
        array("country", '@required'),
        array("region", '@required'),
        array("postcode", '@required'),
    );

    /**
     * Validate provided address data
     * Very basic validation for presence of required fields
     * @todo add element validators
     * @param array $data
     * @param array $rules
     * @return bool
     */
    public function validate($data, $rules = array())
    {
        $rules = array_merge($this->validationRules, $rules);
        BEvents::i()->fire($this->_origClass()."::validate:before", array("rules" => &$rules, "data" => &$data));
        $valid = BValidate::i()->validateInput($data, $rules, 'address-form');
        if (!$valid) {
            BEvents::i()->fire($this->_origClass()."::validate:failed", array("rules" => &$rules, "data" => &$data));
        }

        return $valid;
    }
}