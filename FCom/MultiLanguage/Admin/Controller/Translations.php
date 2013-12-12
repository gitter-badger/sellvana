<?php

class FCom_MultiLanguage_Admin_Controller_Translations extends FCom_Admin_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'translations';
    protected $_gridTitle = 'All translations';
    protected $_recordName = 'Translation';

    /*public function gridConfig()
    {
        $formUrl = BApp::href("translations/form");
        $config = array();
        $columns = array(
            'module'=>array('label'=>'Module', 'width'=>250, 'editable'=>true),
            'locale' => array('label'=>'Locale', 'width'=>250, 'editable'=>true),
            'file'=>array('label'=>'File', 'width'=>60, 'editable'=>true)
        );

        $config['grid']['id'] = 'translation';
        $config['grid']['autowidth'] = false;
        $config['grid']['caption'] = 'All translations';
        $config['grid']['multiselect'] = false;
        $config['grid']['height'] = '100%';
        $config['grid']['columns'] = $columns;
        $config['navGrid'] = array('add'=>false, 'edit'=>true, 'del'=>false);
        $config['grid']['datatype'] = 'local';
        $config['grid']['editurl'] = '';
        $config['grid']['url'] = '';
        $config['custom'] = array('personalize'=>true, 'autoresize'=>true, 'hashState'=>true, 'export'=>true, 'dblClickHref'=>$formUrl.'?id=');

        //$data = BLocale::getTranslations();
        //print_r($data);exit;
        $data = array();
        $modules = BModuleRegistry::i()->getAllModules();
        foreach($modules as $module){
            if (!empty($module->translations)) {
                foreach($module->translations as $trlocale => $trfile) {
                    $data[] = array(
                        'module' => $module->name,
                        'locale' => $trlocale,
                        'file' => $trfile,
                        'id'=>$module->name.'/'.$trfile);
                }
            }
        }
        //print_r($data);exit;
        //exit;
        $config['grid']['data'] = $data;
        return $config;
    }*/

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $localeOptions = array();
        foreach (FCom_Geo_Model_Country::i()->options() as $iso => $name) {
            $localeOptions[$iso] = $iso;
        }
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'module', 'label' => 'Module', 'width' => 250),
            array('name' => 'locale', 'label' => 'Locale', 'width' => 50, 'options' => $localeOptions, 'editor' => 'select'),
            array('name' => 'file', 'label' => 'File', 'width' => 60),
            array('name' => 'id', 'label' => 'Id', 'width' => 200)
        );

        $data = array();
        $modules = BModuleRegistry::i()->getAllModules();
        foreach($modules as $module){
            if (!empty($module->translations)) {
                foreach($module->translations as $trlocale => $trfile) {
                    $data[] = array(
                        'module' => $module->name,
                        'locale' => strtoupper($trlocale),
                        'file' => $trfile,
                        'id'=>$module->name.'/'.$trfile);
                }
            }
        }
        $config['data'] = $data;
        //todo: just show buttons, need add event and process for this controller
        $config['actions'] = array(
            'delete' => true,
        );
        $config['filters'] = array(
            array('field' => 'module', 'type' => 'text'),
            array('field' => 'locale', 'type' => 'select'),
        );
        return $config;
    }

    public function action_form()
    {
        $id = BRequest::i()->params('id', true);
        list($module, $file) = explode("/", $id);

        if (!$file) {
            BDebug::error('Invalid Filename: '.$id);
        }
        $moduleClass = BApp::m($module);
        $filename = $moduleClass->baseDir().'/i18n/'.$file;

        $model = new stdClass();
        $model->id = $id;
        $model->source = file_get_contents($filename);
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
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href($this->_gridHref).'\'"><span>' .  BLocale::_('Back to list') . '</span></button>',
                'save' => '<button type="submit" class="st1 sz2 btn" onclick="return adminForm.saveAll(this)"><span>' .  BLocale::_('Save') . '</span></button>',
            ),
        ));
        BEvents::i()->fire(static::$_origClass.'::formViewBefore', $args);
    }

    public function action_form__POST()
    {
        if (empty($_POST)) {
            return;
        }
        $id = $_POST['file'];

        list($module, $file) = explode("/", $id);

        if (!$file) {
            BDebug::error('Invalid Filename: '.$id);
        }
        $moduleClass = BApp::m($module);
        if (!is_object($moduleClass)) {
            BDebug::error('Invalid Module name: '.$id);
        }

        $filename = $moduleClass->baseDir().'/i18n/'.$file;

        if (!is_writable($filename)) {
            BDebug::error('Not writeable filename: '.$filename);
        }

        if (!empty($_POST['source'])) {
            file_put_contents($filename, $_POST['source']);
        }

        BResponse::i()->redirect(BApp::href($this->_gridHref));
    }
}
