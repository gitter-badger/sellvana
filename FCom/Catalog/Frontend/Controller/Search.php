<?php

class FCom_Catalog_Frontend_Controller_Search extends FCom_Frontend_Controller_Abstract
{
    public function action_category()
    {
        $layout = BLayout::i();
        $category = FCom_Catalog_Model_Category::i()->load(BRequest::i()->params('category'), 'url_path');
        if (!$category) {
            $this->forward(true);
            return $this;
        }

        $productsORM = $category->productsORM();
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category.products_orm', array('data'=>$productsORM));
        $productsData = $category->productsORM()->paginate(null, array('ps'=>25));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_category.products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_category', $category)
            ->set('products_data', $productsData);

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

        $layout->view('catalog/product/list')->products_data = $productsData;

        FCom_Core::lastNav(true);

        $this->layout('/catalog/category');
    }

    public function action_search()
    {
        $layout = BLayout::i();
        $q = BRequest::i()->get('q');
        $qs = preg_split('#\s+#', $q, 0, PREG_SPLIT_NO_EMPTY);
        if (!$qs) {
            BResponse::i()->redirect(BApp::baseUrl());
        }
        $and = array();
        foreach ($qs as $k) $and[] = array('product_name like ?', '%'.$k.'%');
        $productsORM = FCom_Catalog_Model_Product::i()->factory()->where_complex(array('OR'=>array('manuf_sku'=>$q, 'AND'=>$and)));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search.products_orm', array('data'=>$productsORM));
        $productsData = $productsORM->paginate(null, array('ps'=>25));
        BPubSub::i()->fire('FCom_Catalog_Frontend_Controller_Search::action_search.products_data', array('data'=>&$productsData));

        BApp::i()
            ->set('current_query', $q)
            ->set('products_data', $productsData);

        FCom_Core::lastNav(true);
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Search: '.$q, 'active'=>true));
        $layout->view('catalog/search')->query = $q;
        $layout->view('catalog/product/list')->products_data = $productsData;

        $this->layout('/catalog/search');
    }
}