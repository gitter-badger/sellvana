<?php

class FCom_CustomField_Tests_AllTests
{

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit CustomField');

        $suite->addTestSuite('FCom_CustomField_Tests_Model_FieldTest');
        $suite->addTestSuite('FCom_CustomField_Tests_Model_FieldOptionTest');

        return $suite;
    }
}
