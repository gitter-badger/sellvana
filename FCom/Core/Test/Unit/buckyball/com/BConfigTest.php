<?php defined('BUCKYBALL_ROOT_DIR') || die();

class BConfig_Test extends PHPUnit_Framework_TestCase
{
    public function SetUp()
    {
        BConfig::i()->unsetConfig();
    }

    public function tearDown()
    {
        BConfig::i()->unsetConfig();
    }

    public function testAdd()
    {
        $config = BConfig::i();
        $set = ['key' => 'value'];
        $config->add($set);
        $this->assertEquals('value', $config->get('key'));
    }

    public function testNotSet()
    {
        $config = BConfig::i();
        $this->assertTrue(null == $config->get('key'));
    }

    public function testDoubleReset()
    {
        $config = BConfig::i();
        //set first time value
        $set = ['key' => 'value'];
        $config->add($set);
        $this->assertEquals('value', $config->get('key'));

        //set second time value2
        $set = ['key' => 'value2'];
        $config->add($set);
        $this->assertEquals('value2', $config->get('key'));
    }

}
