<?php

class FCom_MultiSite_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'multisite';
    protected $_modelClass = 'FCom_MultiSite_Model_Site';
    protected $_gridTitle = 'Multi Sites';
    protected $_recordName = 'Site';
    protected $_mainTableAlias = 's';
    protected $_permission = 'multi_site';
    protected $_formViewPrefix = 'multisite/sites-form/';
    protected $_navPath = 'system/multisite';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('type'=>'row_select'),
            array('name' => 'id', 'label' => 'ID', 'index'=>'s.id'),
            array('name' => 'name', 'label'=>'Site Name', 'index'=>'s.name'),
            array('name' => 'match_domains', 'label'=>'Match Domains', 'index'=>'s.match_domains'),
            array('name' => 'default_theme', 'label'=>'Default Theme', 'index'=>'s.default_theme'),
            array('name' => 'mode_by_ip', 'label'=>'Mode by IP', 'index'=>'s.mode_by_ip'),
            array('name' => 'create_at', 'label'=>'Created', 'index'=>'s.create_at', 'formatter'=>'date'),
            array('name' => 'update_at', 'label'=>'Updated', 'index'=>'s.update_at', 'formatter'=>'date'),
        );
        $config['actions'] = array(
            'delete' => true,
        );
        $config['filters'] = array(
            array('field' => 'name', 'type' => 'text'),
            array('field' => 'match_domains', 'type' => 'text'),
            array('field' => 'default_theme', 'type' => 'text'),
            array('field' => 'mode_by_ip', 'type' => 'text'),
        );
        return $config;
    }
}
