<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_IndexTank_Tests_AllTests
{

    public function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit IndexTank');

        $suite->addTestSuite('FCom_IndexTank_Tests_Index_ProductTest');
        $suite->addTestSuite('FCom_IndexTank_Tests_Model_ProductFieldTest');
        $suite->addTestSuite('FCom_IndexTank_Tests_Model_ProductFunctionTest');

        return $suite;
    }
}
