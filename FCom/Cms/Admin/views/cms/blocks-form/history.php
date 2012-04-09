<?php
    $m = $this->model;

    echo $this->view('jqgrid')->set('config', array(
        'grid'=>array(
            'id' => 'cms_blocks_form_history',
            'url' => BApp::href('cms/blocks/history/'.$m->id.'/grid_data'),
            'editurl' => BApp::href('cms/blocks/history/'.$m->id.'/grid_data'),
            'columns' => array(
                'id' => array('label'=>'ID', 'hidden'=>true),
                'ts' => array('label'=>'TimeStamp', 'formatter'=>'date'),
                'version' => array('label'=>'Version'),
                'user_id' => array('label'=>'User', 'options'=>FCom_Admin_Model_User::options()),
                'username' => array('Label'=>'User Name', 'hidden'=>true),
                'comments' => array('labl'=>'Comments'),
            ),
        ),
        'custom'=>array('personalize'=>true),
        'filterToolbar' => array('stringResult'=>true, 'searchOnEnter'=>true, 'defaultSearch'=>'cn'),
    ));
?>