<?php

class FCom_CustomField_Tests_AllTests
{

    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(static::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit CustomField');

        $suite->addTestSuite('FCom_CustomField_Tests_Model_FieldTest');
        $suite->addTestSuite('FCom_CustomField_Tests_Model_FieldOptionTest');
        $suite->addTestSuite('FCom_CustomField_Tests_Model_SetTest');

        return $suite;
    }
}
