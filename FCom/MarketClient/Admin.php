<?php

class FCom_MarketClient_Admin extends BClass
{
    static public function bootstrap()
    {
    }

    public function hookFindModulesForUpdates($args)
    {
        $modulesNotification = &$args['modulesNotification'];
        //find modules which have updates
        try {
            if (!BDb::ddlFieldInfo(FCom_MarketClient_Model_Modules::table(), 'need_upgrade')) {
                return;
            }
            $res = FCom_MarketClient_Model_Modules::orm()->where('need_upgrade', 1)->find_many();
        } catch (Exception $e) {
            return;
        }
        $data = array();
        foreach($res as $r) {
            $obj = new stdClass();
            $obj->url = 'marketclient/form?id='.$r->id;
            $obj->module = $r->mod_name;
            $obj->text = $r->mod_name . ' have a new version';
            $data[] = $obj;
        }
        if (!empty($data)) {
            $modulesNotification['Updates'] = $data;
        }

        // find modules with dependencies errors
        //todo: probably need to move this code somewhere else
        $modules = BModuleRegistry::i()->debug();
        $data = array();
        foreach($modules as $modName => $mod) {
            if (!empty($mod->errors)) {
                foreach($mod->errors as $error) {
                    $obj = new stdClass();
                    $obj->url = 'modules';
                    $obj->module = $modName;
                    $obj->text = $modName .' have '.$error['type'].' conflict with '.$error['mod'];
                    $data[] = $obj;
                }
            }
        }
        if (!empty($data)) {
            $modulesNotification['Errors'] = $data;
        }

    }

}