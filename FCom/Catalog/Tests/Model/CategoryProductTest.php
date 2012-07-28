<?php

class FCom_Catalog_Tests_Model_CategoryProductTest extends FCom_Test_DatabaseTestCase
{
    public function getDataSet()
    {
        return $this->createFlatXmlDataSet(__DIR__.'/CategoryProductTest.xml');
    }

    public function testAddEntry()
    {
        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_product'), "Pre-Condition");
        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_category'), "Pre-Condition");
        $this->assertEquals(1, $this->getConnection()->getRowCount('fcom_category_product'), "Pre-Condition");

        $productId = 2;
        $categoryId = 1;
        FCom_Catalog_Model_CategoryProduct::create(array('product_id' => $productId, 'category_id' => $categoryId))->save();

        $this->assertEquals(2, $this->getConnection()->getRowCount('fcom_category_product'), "Insert failed");
    }
}