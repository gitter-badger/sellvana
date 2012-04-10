<?php

class FCom_IndexTank_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{

    public function action_search()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $sc = BRequest::i()->get('sc');
        $r = BRequest::i()->get(); // GET request
        $q = trim($q);
        if (!$q) {
            BResponse::i()->redirect(BApp::baseUrl());
        }

        if ($sc){
            FCom_IndexTank_Index_Product::i()->order_by($sc);
        }

        $productsORM = FCom_IndexTank_Index_Product::i()->search($q);

        $productsData = array();
        if ( $productsORM ) {
            //BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_orm', array('data'=>$productsORM));
            //$productsData = $productsORM->paginate(null, array('ps'=>25));
            //BPubSub::i()->fire('FCom_Catalog_Frontend_Controller::action_search.products_data', array('data'=>&$productsData));

            $productsData = $this->paginate($productsORM, $r, array('ps' => 25));
        }

        BApp::i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core::lastNav(true);
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Search: '.$q, 'active'=>true));
        $layout->view('indextank/search')->query = $q;
        $layout->view('indextank/product/list')->products_data = $productsData;

        $this->layout('/indextank/search');
        BResponse::i()->render();
    }

    public function paginate($orm, $r, $d=array())
    {
        $d = (array)$d; // make sure it's array

        if (!empty($r['s']) && !empty($d['s']) && is_array($d['s'])) { // limit by these values only
            if (!in_array($r['s'], $d['s'])) $r['s'] = null;
            $d['s'] = null;
        }

        $s = array( // state
            'p'  => !empty($r['p'])  && is_numeric($r['p']) ? $r['p']  : (isset($d['p'])  ? $d['p']  : 1), // page
            'ps' => !empty($r['ps']) && is_numeric($r['ps']) ? $r['ps'] : (isset($d['ps']) ? $d['ps'] : 100), // page size
            's'  => !empty($r['s'])  ? $r['s']  : (isset($d['s'])  ? $d['s']  : ''), // sort by
            'sd' => !empty($r['sd']) ? $r['sd'] : (isset($d['sd']) ? $d['sd'] : 'asc'), // sort dir
            'rs' => !empty($r['rs']) ? $r['rs'] : null,
            'rc' => !empty($r['rc']) ? $r['rc'] : null,
            'sc' => !empty($r['sc']) ? $r['sc'] : null,
        );

        $cntOrm = clone $orm; // clone ORM to count
        $s['c'] = $cntOrm->count(); // total row count
        unset($cntOrm); // free mem

        $s['mp'] = ceil($s['c']/$s['ps']); // max page
        if (($s['p']-1)*$s['ps']>$s['c']) $s['p'] = $s['mp']; // limit to max page
        $s['rs'] = max(0, isset($s['rs']) ? $s['rs'] : ($s['p']-1)*$s['ps']); // start from requested row or page

        $orm->offset($s['rs'])->limit($s['ps']); // limit rows to page
        $rows = $orm->find_many(); // result data
        $s['rc'] = $rows ? sizeof($rows) : 0; // returned row count

        return array('state'=>$s, 'rows'=>$rows);
    }

}
