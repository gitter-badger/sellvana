<?php

class FCom_Admin_Controller_Modules extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'system/modules';
	protected static $_origClass = __CLASS__;
	protected $_modelClass = 'FCom_Core_Model_Module';
	protected $_gridHref = 'modules';
	protected $_gridTitle = 'Modules';
	protected $_recordName = 'Product';
	protected $_mainTableAlias = 'm';
    protected $_gridViewName = 'core/backbonegrid';
    public function getModulesData()
    {
        $config = BConfig::i();
        $coreLevels = $config->get('module_run_levels/FCom_Core');
        $adminLevels = $config->get('module_run_levels/FCom_Admin');
        $frontendLevels = $config->get('module_run_levels/FCom_Frontend');
        $modules = BModuleRegistry::i()->getAllModules();
        $autoRunLevelMods = array_flip(explode(',', 'FCom_Core,FCom_Admin,FCom_Admin_DefaultTheme,'.
            'FCom_Frontend,FCom_Frontend_DefaultTheme,FCom_Install'));


        try {
            $schemaVersions = BDbModule::i()->orm()->find_many_assoc('module_name');
            $schemaModules = array();
            foreach (BMigrate::getMigrationData() as $connection=>$migrationModules) {
                foreach ($migrationModules as $modName=>$migrData) {
                    $schemaModules[$modName] = 1;
                }
            }
        } catch (Exception $e) {
            BDebug::logException($e);
        }

        $data = array();
        $migrate = false;
        foreach ($modules as $modName=>$mod) {
            $r = BUtil::arrayMask((array)$mod, 'name,description,version,run_status,run_level,require,children_copy');
            $reqs = array();
            if (!empty($r['require']['module'])) {
                foreach ($r['require']['module'] as $req) {
                    $reqs[] = $req['name'];
                }
            }
            $r['requires'] = join(', ', $reqs);
            $r['required_by'] = join(', ', $mod->children_copy);
            $r['auto_run_level'] = isset($autoRunLevelMods[$r['name']]);
            $r['run_level_core'] = $r['auto_run_level'] ? 'AUTO' : (!empty($coreLevels[$modName]) ? $coreLevels[$modName] : '');
            //$r['run_level_admin'] = !empty($adminLevels[$modName]) ? $adminLevels[$modName] : '';
            //$r['run_level_frontend'] = !empty($frontendLevels[$modName]) ? $frontendLevels[$modName] : '';
            $r['schema_version'] = !empty($schemaVersions[$modName]) ? $schemaVersions[$modName]->get('schema_version') : '';
            $r['migration_available'] = !empty($schemaModules[$modName]) && $r['schema_version']!=$r['version'];
	        $r['id'] = !empty($schemaVersions[$modName]) ? $schemaVersions[$modName]->get('id') : '';
            $data[] = $r;
        }

        $r = (array)BRequest::i()->get('s');

        $gridId = 'modules';
        $pers = FCom_Admin_Model_User::i()->personalize();
        $s = !empty($pers['grid'][$gridId]['state']) ? $pers['grid'][$gridId]['state'] : array();
        //BDebug::dump($pers); exit;
        if (!empty($s['s'])) {
            usort($data, function($a, $b) use($s) {
                $a1 = !empty($a[$s['s']]) ? $a[$s['s']] : '';
                $b1 = !empty($b[$s['s']]) ? $b[$s['s']] : '';
                $sd = empty($s['sd']) || $s['sd']==='asc' ? 1 : -1;
                return $a1 < $b1 ? -$sd : ($a1 > $b1 ? $sd : 0);
            });
        }        
        return $data;
    }

	public function gridConfig()
	{
		$coreRunLevelOptions = FCom_Core_Model_Module::i()->fieldOptions('core_run_level');
		$areaRunLevelOptions = FCom_Core_Model_Module::i()->fieldOptions('area_run_level');
		$runStatusOptions = FCom_Core_Model_Module::i()->fieldOptions('run_status');
		$config = parent::gridConfig();

		$config['columns'] = array(
			array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
			//array('name' => 'id', 'label' => 'ID', 'index' => 'm.id', 'width' => 55, 'hidden' => true, 'cell' => 'integer'),
			array('name' => 'name', 'label' => 'Name', 'index' => 'name', 'width' => 150),
			array('name' => 'description', 'label' => 'Description', 'width' => 250),
			array('name' => 'version', 'label' => 'Code', 'width' => 90),
			array('name' => 'schema_version', 'label' => 'Schema', 'width' => 70, 'cell' => new BValue("FCom.Backgrid.SchemaVersionCell")),
			array('name' => 'run_status', 'label' => 'Status', 'options' => $runStatusOptions, 'width' => 80, 'cell' => new BValue("FCom.Backgrid.RunStatusCell")),
			array('name' => 'run_level', 'label' => 'Level', 'options' => $coreRunLevelOptions, 'width' => 100, 'cell' => new BValue("FCom.Backgrid.RunLevelCell")),
 			array('name' => 'run_level_core', 'label' => "Run Level (Core)", 'options' => $areaRunLevelOptions, 'width' => 220, 'editable' => true, 'editor' => 'select', 'cell' => new BValue("FCom.Backgrid.RunLevelSelectCell")),
			array('name' => 'requires', 'label' => 'Requires', 'width' => 250),
			array('name' => 'required_by', 'label' => 'Required By', 'width' => 800)
		);

		$config['data'] = $this->getModulesData();
        $config['data_mode'] = 'local';
        //$config['state'] =array(5,6,7,8);
		return $config;
	}

	/*
    public function action_index()
    {
        BLayout::i()->view('modules')->set('form_url', BApp::href('modules').(BRequest::i()->get('RECOVERY')==='' ? '?RECOVERY' : ''));
        $grid = BLayout::i()->view('core/backgrid')->set('grid', $this->gridConfig());
        BEvents::i()->fire('FCom_Admin_Controller_Modules::action_index', array('grid_view'=>$grid));
        $this->messages('modules')->layout('/modules');
    }*/

    public function action_index__POST()
    {
        if (BRequest::i()->xhr()) {
            $r = BRequest::i()->post();
            BConfig::i()->set('module_run_levels/FCom_Core/'.$r['module_name'], $r['run_level_core'], false, true);
            FCom_Core_Main::i()->writeConfigFiles('core');
            BResponse::i()->json(array('success'=>true));
        }
        try {
            $areas = array('FCom_Core', 'FCom_Admin', 'FCom_Frontend');
            $levels = BRequest::i()->post('module_run_levels');
            foreach ($areas as $area) {
                if (empty($levels[$area])) {
                    continue;
                }
                foreach ($levels[$area] as $modName=>$status) {
                    if (!$status) {
                        unset($levels[$area][$modName]);
                    }
                }
                BConfig::i()->set('module_run_levels/'.$area, $levels[$area], false, true);
            }
            FCom_Core_Main::i()->writeConfigFiles('core');
            BSession::i()->addMessage('Run levels updated', 'success', 'admin');
        } catch (Exception $e) {
            BDebug::logException($e);
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }
        BResponse::i()->redirect(BApp::href('modules'));
    }

    public function action_migrate__POST()
    {
        try {
            BMigrate::i()->migrateModules(true);
            BSession::i()->addMessage('Migration complete', 'success', 'admin');
        } catch (Exception $e) {
            BDebug::logException($e);
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }
        BResponse::i()->redirect(BApp::href('modules'));
    }
}
