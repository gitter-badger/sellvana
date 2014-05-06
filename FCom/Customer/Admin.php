<?php

class FCom_Customer_Admin extends BClass
{
    public static function bootstrap()
    {
        FCom_Admin_Model_Role::i()->createPermission([
            'api/customers' => 'Customers',
            'api/customers/view' => 'View',
            'api/customers/update' => 'Update',
            'customers' => 'Customers',
            'customers/manage' => 'Manage',
            'customers/import' => 'Import',
        ]);

        FCom_Admin_Controller_MediaLibrary::i()->allowFolder('storage/import/customers');
    }

    public function onGetDashboardWidgets($args)
    {
        $view = $args['view'];
        $view->addWidget('customers-list', [
            'title' => 'Recent Customers',
            'icon' => 'group',
            'view' => 'customer/dashboard/customers-list',
            'async' => true,
        ]);
    }

    public function onControllerBeforeDispatch($args)
    {
        if (BApp::m('FCom_PushServer')->run_status === BModule::LOADED
            && BConfig::i()->get('modules/FCom_Customer/newcustomer_realtime_notification')
        ) {
            FCom_PushServer_Model_Client::i()->sessionClient()->subscribe('customers_feed');
        }
    }
}
