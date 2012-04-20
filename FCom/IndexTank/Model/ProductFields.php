<?php

class FCom_IndexTank_Model_ProductFields extends FCom_Core_Model_Abstract
{
    protected static $_table = 'from_indextank_product_fields';

    public function get_list()
    {
        $product_fields = FCom_IndexTank_Model_ProductFields::i()->orm()->find_many();
        $result = array();
        foreach($product_fields as $p){
            $result[$p->field_name] = $p;
        }
        return $result;
    }

    public function get_fulltext_list()
    {
        $product_fields = FCom_IndexTank_Model_ProductFields::i()->orm()->where('type', 'fulltext')
                ->where_not_in('field_name', array('timestamp', 'match'))
                ->find_many();
        $result = array();
        foreach($product_fields as $p){
            $result[$p->field_name] = $p;
        }
        return $result;
    }
}
