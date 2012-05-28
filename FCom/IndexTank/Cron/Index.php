<?php

class Fcom_IndexTank_Cron_Index extends BClass
{
    static public function index_all()
    {
        //first finish not finsihed
        $this->index_all_in_indexing();
        //then finit not indexed
        $this->index_all_not_indexed();
    }

    protected function index_all_in_indexing()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where("indextank_indexed", 1);
        $totalRecords = $orm->count();
        if(!$totalRecords){
            return;
        }
        $batchSize = BConfig::i()->get('modules/FCom_IndexTank/index_products_limit');
        if(0 == $batchSize){
            $batchSize = 500;
        }
        $offset = 0;
        $counter = 0;
        $products = $orm->offset($offset)->limit($batchSize)->find_many();
        while($products) {
            $counter += count($products);
            $productIds = array();
            foreach($products as $p){
                $productIds[] = $p->id();
            }
            //before indexing
            //indexing
            FCom_IndexTank_Index_Product::i()->add($products, $batchSize);
            //after indexing
            FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 2, "indextank_indexed_at" => date("Y-m-d H:i:s")),
                    "id in (".implode(",", $productIds).")");

            $percent = round(($counter/$totalRecords)*100, 2);
            $this->update_info_status("index_all_crashed", $percent);

            $offset += $batchSize;
            //get new batch of data
            $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where("indextank_indexed", 1);
            $products = $orm->offset($offset)->limit($batchSize)->find_many();
            unset($orm);
        }
    }

    protected function index_all_not_indexed()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where("indextank_indexed", 0);
        $totalRecords = $orm->count();
        if(!$totalRecords){
            return;
        }
        $batchSize = BConfig::i()->get('modules/FCom_IndexTank/index_products_limit');
        if(0 == $batchSize){
            $batchSize = 500;
        }
        $offset = 0;
        $counter = 0;
        $products = $orm->offset($offset)->limit($batchSize)->find_many();
        while($products) {
            $counter += count($products);
            $productIds = array();
            foreach($products as $p){
                $productIds[] = $p->id();
            }
            //before index
            FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 1, "indextank_indexed_at" => date("Y-m-d H:i:s")),
                    "id in (".implode(",", $productIds).")");
            //index
            FCom_IndexTank_Index_Product::i()->add($products, $batchSize);
            //after index
            FCom_Catalog_Model_Product::i()->update_many(
                    array("indextank_indexed" => 2, "indextank_indexed_at" => date("Y-m-d H:i:s")),
                    "id in (".implode(",", $productIds).")");

            $percent = round(($counter/$totalRecords)*100, 2);
            $this->update_info_status("index_all_new", $percent);

            $offset += $batchSize;
            //get new batch of data
            $orm = FCom_Catalog_Model_Product::i()->orm('p')->select('p.*')->where("indextank_indexed", 0);
            $products = $orm->offset($offset)->limit($batchSize)->find_many();
            unset($orm);
        }
    }

    protected function update_info_status($task, $percent)
    {
        $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->orm()->where("task", $task);
        if (!$indexingStatus){
            $indexingStatus = FCom_IndexTank_Model_IndexingStatus::i()->orm()->create();
            $indexingStatus->task = $task;
        }
        $indexingStatus->info = "Indexed {$percent}% documents";
        $indexingStatus->updated_at = date("Y-m-d H:i:s");
        $indexingStatus->save();
    }
}