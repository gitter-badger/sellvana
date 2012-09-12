<?php

class FCom_Market_Admin extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Admin/views');
        BPubSub::i()
                ->on('BLayout::theme.load.after', 'FCom_Market_Admin::layout')
                ->on('BLayout::hook.find_modules_to_update', 'FCom_Market_Admin.hookFindModules')
                ;

        BFrontController::i()
            ->route('GET /market', 'FCom_Market_Admin_Controller.index')
            ->route('GET|POST /market/.action', 'FCom_Market_Admin_Controller')

        ;
    }

    public function hookFindModules($args)
    {
        $modulesToUpdate = &$args['modulesToUpdate'];
        try {
            if (!BDb::ddlFieldInfo(FCom_Market_Model_Modules::table(), 'need_upgrade')) {
                return;
            }
            $res = FCom_Market_Model_Modules::orm()->where('need_upgrade', 1)->find_many();
        } catch (Exception $e) {
            return;
        }
        $modulesToUpdate += $res;
    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'admin/header', 'do'=>array(
                    array('addNav', 'market', array('label'=>'Market', 'pos'=>100)),
                    array('addNav', 'market/market', array('label'=>'Market Center', 'href'=>BApp::href('market/market'))),
                    array('addNav', 'market/index', array('label'=>'My modules', 'href'=>BApp::href('market/index'))),
                )),
            ),
            '/market'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('admin/grid')),
                    array('view', 'admin/header', 'do'=>array(array('setNav', 'market'))),
                ),
             '/market/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('admin/form')),
                    array('view', 'admin/form', 'set'=>array(
                        'tab_view_prefix' => 'market/',
                    ), 'do'=>array(
                        array('addTab', 'main', array('label'=>'Market', 'pos'=>10))
                    )),
             ),
            '/market/market'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('market/market')),
            ),
        ));
    }
}