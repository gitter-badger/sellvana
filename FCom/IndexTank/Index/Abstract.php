<?php

class FCom_IndexTank_Index_Abstract extends BClass
{
    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom_IndexTank_Index_Abstract
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    public function paginate($orm, $r, $d=array())
    {
        $rbak = $r;
        $r['sc'] = null;
        $res = $orm->paginate($r, $d);
        $res['state']['sc'] = $rbak['sc'];
        return $res;
    }
}