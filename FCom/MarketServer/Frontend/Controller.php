<?php

class FCom_MarketServer_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_market()
    {
        $modules = BModuleRegistry::getAllModules();
        $customerId = FCom_Customer_Model_Customer::sessionUserId();
        $options = FCom_MarketServer_Model_Account::i()->getOptions($customerId);

        //get remote modules manifest
        if ($options && !empty($options->site_url) ) {
            $manifest = BUtil::fromJson(file_get_contents($options->site_url.'/market/manifest'));
        }

        //check modules difference
        foreach ($modules as $ind => $mod) {
            $modules[$ind]->need_upgrade = false;
            if (!empty($manifest[$mod->name])) {
                if (version_compare($mod->version, $manifest[$mod->name]) > 0) {
                    $modules[$ind]->need_upgrade = true;
                } else {
                    $modules[$ind]->need_upgrade = false;
                }
            }
        }
        //todo: filter only public modules
        //show modules and description
        $this->view('market/list')->modules = $modules;
        $this->layout('/market/list');
    }

    public function action_view()
    {
        $m = BRequest::get('m');

        $mod = BApp::m($m);
        $this->view('market/view')->module = $mod;
        $this->layout('/market/view');
    }
}