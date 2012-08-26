<?php

class FCom_IndexTank_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
#echo "<pre>"; debug_print_backtrace(); print_r(BFrontController::i()->currentRoute()); exit;
        $category = FCom_Catalog_Model_Category::i()->load(BRequest::i()->params('category'), 'url_path');
        if (!$category) {
            $this->forward(true);
            return $this;
        }

        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $sc = BRequest::i()->get('sc');
        $f = BRequest::i()->get('f');
        $v = BRequest::i()->get('v');
        $page = BRequest::i()->get('p');
        $resultPerPage = BRequest::i()->get('ps');

        if (empty($f['category'])){
            $categoryKey = FCom_IndexTank_Index_Product::i()->getCategoryKey($category);
            $f['category'] = $categoryKey. ":".$category->node_name;
        }

        $productsData = FCom_IndexTank_Search::i()->search($q, $sc, $f, $v, $page, $resultPerPage);
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category.products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_category', $category)
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core::lastNav(true);

        $head = $this->view('head');
        $crumbs = array('home');
        foreach ($category->ascendants() as $c) {
            if ($c->node_name) {
                $crumbs[] = array('label'=>$c->node_name, 'href'=>$c->url());
                $head->addTitle($c->node_name);
            }
        }
        $crumbs[] = array('label'=>$category->node_name, 'active'=>true);
        $head->addTitle($category->node_name);
        $layout->view('breadcrumbs')->crumbs = $crumbs;

        $layout->view('catalog/search')->query = $q;
        $layout->view('catalog/search')->public_api_url = FCom_IndexTank_Search::i()->publicApiUrl();
        $layout->view('catalog/search')->index_name = FCom_IndexTank_Search::i()->indexName();
        $layout->view('catalog/product/list')->products_data = $productsData;
        $layout->view('indextank/product/filters')->state = $productsData['state'];

        $this->layout('/catalog/category');
    }

    public function action_search()
    {
        $req = BRequest::i();
        $q = $req->get('q');
        if (!$q) {
            BResponse::i()->redirect(BApp::href());
        }
        $sc = $req->get('sc');
        $f = $req->get('f');
        $v = $req->get('v');
        $page = $req->get('p');
        $resultPerPage = $req->get('ps');

        if(false == BConfig::i()->get('modules/FCom_IndexTank/index_name')){
            die('Please set up correct API URL at Admin Setting page');
        }

        $productsData = FCom_IndexTank_Search::i()->search($q, $sc, $f, $v, $page, $resultPerPage);
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search.products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core::lastNav(true);
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Search: '.$q, 'active'=>true));
        $layout->view('catalog/search')->query = $q;
        $layout->view('catalog/search')->public_api_url = FCom_IndexTank_Search::i()->publicApiUrl();
        $layout->view('catalog/search')->index_name = FCom_IndexTank_Search::i()->indexName();
        $layout->view('catalog/product/list')->products_data = $productsData;
        $layout->view('indextank/product/filters')->state = $productsData['state'];

        $this->layout('/catalog/search');
    }



}
