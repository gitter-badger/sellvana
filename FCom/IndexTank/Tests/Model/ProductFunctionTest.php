<?php
class FCom_IndexTank_Tests_Model_ProductFunctionTest extends PHPUnit_Framework_TestCase
{
    public function testListArray()
    {
        $list = FCom_IndexTank_Model_ProductFunction::i()->getList();
        $this->assertTrue(is_array($list));
    }
}