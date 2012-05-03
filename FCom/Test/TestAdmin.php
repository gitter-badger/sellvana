<?php

class FCom_Test_Admin extends BClass
{
    /**
     * Bootstrap routes, events and layout for Admin part
     */
    static public function bootstrap()
    {
        BFrontController::i()
            ->route('GET /tests/index', 'FCom_Test_Admin_Controller_Tests.index');

        BLayout::i()->addAllViews('Admin/views')->afterTheme('FCom_Test_Admin::layout');
    }


    /**
     * Itialized base layout, navigation links and page views scripts
     */
    static public function layout()
    {
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'admin/header', 'do'=>array(
                        array('addNav', 'tests', array('label'=>'Tests', 'pos'=>100)),
                        array('addNav', 'tests/index', array('label'=>'All tests', 'href'=>BApp::href('tests/index')))
                    ))),
                '/tests/index'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('tests/index')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'test/index'))),
                )));
    }
}