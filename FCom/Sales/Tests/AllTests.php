<?php

class FCom_Sales_Tests_AllTests
{

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Sales');

        $suite->addTestSuite('FCom_Sales_Tests_Model_OrderTest');


        return $suite;
    }
}