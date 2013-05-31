<?php

class FCom_Sales_Admin extends BClass
{
    public static function bootstrap()
    {
        FCom_Sales_Main::bootstrap();
        
        BRouting::i()
            ->get('/orders', 'FCom_Sales_Admin_Controller_Orders.index')
            ->any('/orders/.action', 'FCom_Sales_Admin_Controller_Orders')

            ->get('/orderstatus', 'FCom_Sales_Admin_Controller_OrderStatus.index')
            ->any('/orderstatus/.action', 'FCom_Sales_Admin_Controller_OrderStatus')
        ;

        BLayout::i()->addAllViews('Admin/views')->afterTheme('FCom_Sales_Admin::layout');
    }

    public static function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'admin/header', 'do'=>array(
                    array('addNav', 'order', array('label'=>'Orders', 'pos'=>300)),
                    array('addNav', 'order/orders', array('label'=>'Orders', 'href'=>BApp::href('orders'))),
                    array('addNav', 'order/orderstatus', array('label'=>'Order Status', 'href'=>BApp::href('orderstatus'))),
                )),
            ),

            '/orders'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('admin/grid')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'order/orders'))),
            ),
            '/orders/form'=>array(
                array('layout', 'base'),
                array('layout', 'form'),
                array('hook', 'main', 'views'=>array('admin/form')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'order/orders'))),
                array('view', 'admin/form', 'set'=>array(
                    'tab_view_prefix' => 'order/orders-form/',
                ), 'do'=>array(
                    array('addTab', 'main', array('label'=>'Order Info', 'pos'=>10)),
                )),
            ),
            '/orderstatus'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('admin/grid')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'order/orderstatus'))),
            ),
            '/orderstatus/form'=>array(
                array('layout', 'base'),
                array('layout', 'form'),
                array('hook', 'main', 'views'=>array('admin/form')),
                array('view', 'admin/header', 'do'=>array(array('setNav', 'order/orderstatus'))),
                array('view', 'admin/form', 'set'=>array(
                    'tab_view_prefix' => 'order/orderstatus-form/',
                ), 'do'=>array(
                    array('addTab', 'main', array('label'=>'Order Status', 'pos'=>10)),
                )),
            ),
        ));
    }
}