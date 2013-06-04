<?php

class FCom_Install_Controller extends FCom_Core_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        if (BRequest::i()->method()==='POST') {
            $sData =& BSession::i()->dataToUpdate();
            $w = BRequest::i()->post('w');
            $sData['w'] = !empty($sData['w']) ? BUtil::arrayMerge($sData['w'], $w) : $w;
        }

        return true;
    }

    public function action_index()
    {
        $this->layout('/');

        $errors = BDebug::i()->getCollectedErrors();
        BLayout::i()->view('index')->errors = $errors;
        
        $this->messages('index', 'install');
    }

    public function action_index__POST()
    {
        $sData = BSession::i()->data();
        if (empty($sData['w']['agree']) || $sData['w']['agree']!=='Agree') {
            BResponse::i()->redirect(BApp::href('?error=1'));
        }
        $step = 1;
        if (BConfig::i()->get('db')) {
            $step = 2;
            if (class_exists('FCom_Admin_Model_User') && BDb::ddlTableExists(FCom_Admin_Model_User::table())
                && FCom_Admin_Model_User::i()->orm('u')->find_one()
            ) {
                $step = 3;
            }
        }
        BResponse::i()->redirect(BApp::href('install/step'.$step));
    }

    public function action_step1()
    {
        $this->layout('/step1');
        $sData =& BSession::i()->dataToUpdate();
        if (empty($sData['w']['db'])) {
            $sData['w']['db'] = array('host'=>'localhost', 'dbname'=>'fulleron', 'username'=>'root', 'password'=>'', 'table_prefix'=>'');
        }
        $this->messages('step1', 'install');
    }

    public function action_step1__POST()
    {
        try {
            $w = BRequest::i()->post('w');
            BConfig::i()->add(array('db'=>$w['db']), true);
            BMigrate::i()->migrateModules('FCom_Admin');
            FCom_Core_Main::i()->writeDbConfig();
            $url = BApp::href('install/step2');
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'install');
            $url = BApp::href('install/step1');
        }
        BResponse::i()->redirect($url);
    }

    public function action_step2()
    {
        $this->layout('/step2');
        $sData =& BSession::i()->dataToUpdate();
        if (empty($sData['w']['admin'])) {
            $sData['w']['admin'] = array('username'=>'admin', 'password'=>'', 'email'=>'', 'firstname'=>'', 'lastname'=>'');
        }
        $this->messages('step2', 'install');
    }

    public function action_step2__POST()
    {
        try {
            $w = BRequest::i()->post('w');
            FCom_Admin_Model_User::i()
                ->create($w['admin'])
                ->set('is_superadmin', 1)
                ->save(
                ->login();
            $url = BApp::href('install/step3');
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'install');
            $url = BApp::href('install/step2');
        }
        BResponse::i()->redirect($url);
    }

    public function action_step3()
    {
        $this->layout('/step3');
        $this->messages('step3', 'install');
    }

    public function action_step3__POST()
    {
        BConfig::i()->add(array('install_status'=>'installed'), true);
        FCom_Core_Main::i()->writeLocalConfig();
        BResponse::i()->redirect(BApp::baseUrl());
    }
}
