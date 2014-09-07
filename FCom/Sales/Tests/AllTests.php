<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Tests_AllTests
{

    public function main()
    {
        PHPUnit_TextUI_TestRunner::run($this->suite());
    }

    public function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Sales');

        $suite->addTestSuite('FCom_Checkout_Tests_Model_CartTest');
        $suite->addTestSuite('FCom_Checkout_Tests_Model_CartAddressTest');
        $suite->addTestSuite('FCom_Sales_Tests_Model_OrderTest');


        return $suite;
    }
}
