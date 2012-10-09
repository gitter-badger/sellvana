<?php

class FCom_IndexTank_Cron extends BClass
{
    public static function bootstrap()
    {
        FCom_Cron::i()
            ->task('* * * * *', 'FCom_IndexTank_Cron.indexAll');

        BPubSub::i()
            ->on('FCom_IndexTank_Index_Product::add', 'FCom_IndexTank_Index_Product::onProductIndexAdd');
    }

    public function indexAll()
    {
        set_time_limit(0);

        $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->getIndexingStatus();
        if ($indexingStatus->status == 'pause') {
            return;
        }

        $this->indexActiveProducts();
        $this->removeDisabledProducts();
    }

    protected function indexActiveProducts()
    {
        $products = $this->gerProducts(0);
        if (!$products) {
            return;
        }
        //before index
        $this->setProductsStatus(1, $products);
        //index
        try {
            FCom_IndexTank_Index_Product::i()->add($products);
        } catch(Exception $e) {
            //do not update products index status because of exception
            return true;
        }
        //after index
        $this->setProductsStatus(2, $products);

        FCom_IndexTank_Model_IndexingStatus::i()->updateInfoStatus();
    }

    protected function removeDisabledProducts()
    {
        $products = $this->gerProducts(1);
        if (!$products) {
            return;
        }
        //before index
        $this->setProductsStatus(1, $products);
        //index
        try {
            FCom_IndexTank_Index_Product::i()->deleteProducts($products);
        } catch(Exception $e) {
            //do not update products index status because of exception
            return true;
        }
        //after index
        $this->setProductsStatus(2, $products);

        FCom_IndexTank_Model_IndexingStatus::i()->updateInfoStatus();
    }

    /**
     * Return products list for indexing
     * @param type $disabled -
     * if 1 then disabled product will deleted
     * if 0 then active product will be updated
     * @return array
     */
    protected function gerProducts($disabled)
    {
        $orm = FCom_Catalog_Model_Product::orm('p')->select('p.*')
                ->where('disabled', $disabled)
                ->where_in("indextank_indexed", array(1,0));

        $batchSize = BConfig::i()->get('modules/FCom_IndexTank/index_products_limit');
        if (!$batchSize) {
            $batchSize = 500;
        }
        $offset = 0;
        $products = $orm->offset($offset)->limit($batchSize)->find_many();
        if (!$products) {
            return;
        }
        return $products;
    }

    /**
     * Set status for list of products before/after indexing
     * @param type $status -
     * if 1 - indexing status
     * if 2 - indexed status
     * @param Array $products - list of products objects
     */
    public function setProductsStatus($status, $products)
    {
        if (!is_array($products)) {
            $products = array($products);
        }
        $productIds = array();
        foreach ($products as $p) {
            $productIds[] = $p->id();
        }
        $updateQuery = array();
        if ($status) {
            $updateQuery = array("indextank_indexed" => $status, "indextank_indexed_at" => date("Y-m-d H:i:s"));
        } else {
            $updateQuery = array("indextank_indexed" => $status);
        }
        FCom_Catalog_Model_Product::i()->update_many( $updateQuery,
                    "id in (".implode(",", $productIds).")");
    }
}