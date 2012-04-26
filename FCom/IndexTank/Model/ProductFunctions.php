<?php

class FCom_IndexTank_Model_ProductFunctions extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_indextank_product_functions';

    public function get_list()
    {
        $functions = FCom_IndexTank_Model_ProductFunctions::i()->orm()->find_many();
        $result = array();
        foreach($functions as $f){
            $result[$f->name] = $f;
        }
        return $result;
    }

}
