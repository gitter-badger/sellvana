<?php

/**
 *todo:
 *  1. Show modules list from remote server
 *  2. Show module info
 *  3. Install module
 */

class FCom_MarketClient_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'market';
    protected $_modelClass = 'FCom_MarketClient_Model_Modules';
    //protected $_mainTableAlias = '';

    public function gridConfig()
    {
        $formUrl = BApp::href($this->_gridHref.'/form');
        $config = parent::gridConfig();
        $columns = array(
            'mod_name'=>array('label'=>'Code', 'width'=>250, 'editable'=>true),
            'name'=>array('label'=>'Module', 'width'=>250, 'editable'=>true),
            'version' => array('label'=>'Local Version', 'width'=>250, 'editable'=>true),
            'market_version' => array('label'=>'Market Version', 'width'=>250, 'editable'=>true, 'sortable'=>true),
            'description' => array('label'=>'Description', 'width'=>250, 'editable'=>true),
            'need_upgrade' => array('label'=>'Notice', 'width'=>250, 'editable'=>true, 'sortable'=>true,
                'options'=>array('1'=>'Need upgrade!', '0'=>'Latest version'))
        );

        $config['grid']['id'] = __CLASS__;
        $config['grid']['autowidth'] = false;
        $config['grid']['caption'] = 'All modules';
        $config['grid']['multiselect'] = false;
        $config['grid']['height'] = '100%';
        $config['grid']['columns'] = $columns;
        $config['navGrid'] = array('add'=>false, 'edit'=>true, 'del'=>false);
        $config['custom'] = array('personalize'=>true, 'autoresize'=>true, 'hashState'=>true, 'export'=>true, 'dblClickHref'=>$formUrl.'?id=');

        return $config;
    }

    public function action_market()
    {
        $this->view('market/market')->url = FCom_MarketClient_Main::i()->getSsoUrl();
        $this->layout('/market/market');
    }

    public function action_form()
    {
        $modName = BRequest::i()->params('mod_name', true);
        if (!$modName) {
            $modid = BRequest::i()->params('id', true);
            $mod = FCom_MarketClient_Model_Modules::i()->load($modid);
            if($mod) {
                $modName = $mod->mod_name;
            }
        }

        //echo $moduleId;exit;

        try {
            $modules = FCom_MarketClient_Main::i()->getModules(array($modName));
            $module = $modules[$modName];
            if (!empty($module['require'])) {
                $module['require'] = BUtil::fromJson($module['require']);
                //check requirements with current state
                //1. check modules
                if (!empty($module['require']['module'])) {
                    $installedmodules = BModuleRegistry::i()->debug();
                    foreach($module['require']['module'] as &$modreq) {
                        if (!isset($installedmodules[$modreq['name']])) {
                            $modreq['error'] = 'Required module not exist';
                            continue;
                        } else {
                            if (!empty($modreq['version']['from'])  &&
                                    version_compare($modreq['version']['from'], $installedmodules[$modreq['name']]->version, '>')) {
                                $modreq['error'] = 'Installed module version too low';
                                continue;
                            }
                            if (!empty($modreq['version']['to'])  &&
                                    version_compare($modreq['version']['to'], $installedmodules[$modreq['name']]->version, '<')) {
                                $modreq['error'] = 'Installed module version too high';
                                continue;
                            }
                        }

                    }
                }
                // 2. check for classes
                if (!empty($module['require']['class'])) {
                    foreach($module['require']['class'] as &$modreq) {
                        if (!class_exists($modreq['name'])) {
                            $modreq['error'] = 'Required class not exist';
                        }
                    }
                }

                // 3. check for php extensions
                if (!empty($module['require']['phpext'])) {
                    foreach($module['require']['phpext'] as &$modreq) {
                        if (!extension_loaded($modreq['name'])) {
                            $modreq['error'] = 'Required PHP extension not loaded';
                        }
                    }
                }
            }
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error');
            BResponse::i()->redirect(BApp::href("market"), 'error');
        }

        $model = new stdClass();
        $model->id = $modName;
        $model->module = $module;

        $modulesInstalled = FCom_MarketClient_Model_Modules::i()->getAllModules();

        $needUpgrade = false;
        $localVersion = '';
        if (!empty($modulesInstalled[$module['mod_name']])) {
            $needUpgrade = version_compare($module['version'], $modulesInstalled[$module['mod_name']]->version) > 0 ? true : false;
            $localVersion = $modulesInstalled[$module['mod_name']]->version;
        }
        $model->local_version = $localVersion;
        $model->need_upgrade = $needUpgrade;

        $model->messages = BSession::i()->messages();

        $view = $this->view($this->_formViewName)->set('model', $model);
        $this->formViewBefore(array('view'=>$view, 'model'=>$model));

        $this->layout($this->_formLayoutName);
        $this->processFormTabs($view, $model, 'edit');
    }

    public function formViewBefore($args)
    {

        $m = $args['model'];

        $args['view']->set(array(
            'form_id' => BLocale::transliterate($this->_formLayoutName),
            'form_url' => BApp::href($this->_formHref).'?id='.$m->id,
            'actions' => array(
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href($this->_gridHref).'\'"><span>Back to list</span></button>',
            ),
        ));
        BEvents::i()->fire(static::$_origClass.'::formViewBefore', $args);
    }

    public function action_install()
    {
        $modName = BRequest::i()->params('mod_name', true);
        if (!$modName) {
            $modid = BRequest::i()->params('id', true);
            $mod = FCom_MarketClient_Model_Modules::i()->load($modid);
            if($mod) {
                $modName = $mod->mod_name;
            }
        }

        try {
            $modules = FCom_MarketClient_Main::i()->getModules(array($modName));
            $module = $modules[$modName];
        } catch(Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error');
            BResponse::i()->redirect(BApp::href("market/form")."?mod_name={$modName}", 'error');
        }

        try {
            $moduleFile = FCom_MarketClient_Main::i()->download($modName);
        } catch(Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error');
            BResponse::i()->redirect(BApp::href("market/form")."?mod_name={$modName}", 'error');
        }

        if (!$moduleFile) {
            BSession::i()->addMessage("Permissions denied to write into file: ".$moduleFile, 'error');
            BResponse::i()->redirect(BApp::href("market/form")."?mod_name={$modName}");
        }

        $dlcPath = BConfig::i()->get('fs/dlc_dir');
        $modNameParts = explode("_", $modName);
        if (count($modNameParts) == 2) {
            $dlcPath .= '/'.$modNameParts[0];
            BUtil::ensureDir($dlcPath);
        }

        $ftpenabled = BConfig::i()->get('modules/FCom_MarketClient/ftp/enabled');
        if ($ftpenabled) {
            $modulePath = dirname($moduleFile).'/'.$modName;
            $res = FCom_MarketClient_Main::i()->extract($moduleFile, $modulePath);
            //copy modulePath by FTP to marketPath
            if (!$res) {
                BSession::i()->addMessage("Permissions denied to write into storage dir: ".$modulePath);
                BResponse::i()->redirect(BApp::href("market/form")."?mod_name={$modName}", 'error');
            }
            $conf = BConfig::i()->get('modules/FCom_MarketClient/ftp');
            if (empty($conf['port'])) {
                $conf['port'] = $conf['type'] =='ftp' ? 21 : 22;
            }
            $ftpClient = new BFtpClient($conf);
            $errors = $ftpClient->ftpUpload($modulePath, $dlcPath);
            if ($errors) {
                foreach($errors as $error) {
                    BSession::i()->addMessage($error);
                }
                BResponse::i()->redirect(BApp::href("market/form")."?mod_name={$modName}", 'error');
            }

        } else {
            $res = FCom_MarketClient_Main::i()->extract($moduleFile, $dlcPath);
            if (!$res) {
                $error = FCom_MarketClient_Main::i()->getErrors();
                if ($error) {
                    BSession::i()->addMessage($error, 'error');
                } else {
                    BSession::i()->addMessage("Permissions denied to write into storage dir: ".$dlcPath, 'error');
                }
                BResponse::i()->redirect(BApp::href("market/form")."?mod_name={$modName}");
            }
        }

        if ($res) {
            $modExist = FCom_MarketClient_Model_Modules::orm()->where('mod_name', $modName)->find_one();
            if ($modExist) {
                $modExist->version = $module['version'];
                $modExist->description = $module['short_description'];
                $modExist->save();
            } else {
                $data = array('name' => $module['name'], 'mod_name' => $modName,
                    'version' => $module['version'], 'description' => $module['short_description']);
                FCom_MarketClient_Model_Modules::orm()->create($data)->save();
            }
        }
        BSession::i()->addMessage("Module successfully uploaded.");
        BResponse::i()->redirect(BApp::href("market/form")."?mod_name={$modName}");
        //BResponse::i()->redirect("index");
        //$this->forward('index');
    }

}