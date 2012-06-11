<?php

class FCom_Admin_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    protected static $_origClass;
    protected $_permission;

    public function messages($viewName, $namespace='admin')
    {
        $this->view($viewName)->messages = BSession::i()->messages($namespace);
        return $this;
    }

    public function authenticate($args=array())
    {
        return FCom_Admin_Model_User::i()->isLoggedIn();
    }

    public function authorize($args=array())
    {
        if (!parent::authorize($args)) {
            return false;
        }
        if (!empty($this->_permission)) {
            $user = FCom_Admin_Model_User::i()->sessionUser();
            if (!$user) {
                return false;
            }
            return $user->getPermission($this->_permission);
        }
        return true;
    }

    public function action_unauthenticated()
    {
        $r = BRequest::i();
        if ($r->xhr()) {
            BSession::i()->data('admin_login_orig_url', $r->referrer());
            BResponse::i()->json(array('error'=>'login'));
        } else {
            BSession::i()->data('admin_login_orig_url', $r->currentUrl());
            $this->layout('/login');
            BResponse::i()->status(401, 'Unauthorized'); // HTTP sic
        }
    }

    public function action_unauthorized()
    {
        $r = BRequest::i();
        if ($r->xhr()) {
            BSession::i()->data('admin_login_orig_url', $r->referrer());
            BResponse::i()->json(array('error'=>'denied'));
        } else {
            BSession::i()->data('admin_login_orig_url', $r->currentUrl());
            $this->layout('/denied');
            BResponse::i()->status(403, 'Forbidden');
        }
    }

    public function processFormTabs($view, $model=null, $mode='edit', $allowed=null)
    {
        $r = BRequest::i();
        if ($r->xhr() && !is_null($r->get('tabs'))) {
            $this->outFormTabsJson($view, $model, $mode);
        } else {
            $this->initFormTabs($view, $model, $mode, $allowed);
        }
        return $this;
    }

    public function initFormTabs($view, $model, $mode='view', $allowed=null)
    {
        $r = BRequest::i();
        $layout = BLayout::i();
        $curTab = $r->request('tab');
        if (is_string($allowed)) {
            $allowed = explode(',', $allowed);
        }
        $tabs = $view->tabs;
        foreach ($tabs as $k=>&$tab) {
            if (!is_null($allowed) && $allowed!=='ALL' && !in_array($k, $allowed)) {
                $tab['disabled'] = true;
                continue;
            }
            if (!$curTab) {
                $curTab = $k;
            }
            if ($curTab===$k) {
                $tab['async'] = false;
            }
            if (!empty($tab['view'])) {
                $tabView = $layout->view($tab['view']);
                if ($tabView) {
                    $tabView->set(array(
                        'model' => $model,
                        'mode' => $mode,
                    ));
                } else {
                    BDebug::warning('MISSING VIEW: '.$tab['view']);
                }
            }
        }
        unset($tab);
        $view->set(array(
            'tabs' => $tabs,
            'model' => $model,
            'mode' => $mode,
            'cur_tab' => $curTab,
        ));
        return $this;
    }

    public function outFormTabsJson($view, $model, $defMode='view')
    {
        $r = BRequest::i();
        $mode = $r->request('mode');
        if (!$mode) {
            $mode = $defMode;
        }
        $outTabs = $r->request('tabs');
        if ($outTabs && $outTabs!=='ALL' && is_string($outTabs)) {
            $outTabs = explode(',', $outTabs);
        }
        $out = array();
        if ($outTabs) {
            $layout = BLayout::i();
            $tabs = $view->tabs;
            foreach ($tabs as $k=>$tab) {
                if ($outTabs!=='ALL' && !in_array($k, $outTabs)) {
                    continue;
                }
                $view = $layout->view($tab['view']);
                if (!$view) {
                    BDebug::error('MISSING VIEW: '.$tabs[$k]['view']);
                    continue;
                }
                $out['tabs'][$k] = (string)$view->set(array(
                    'model' => $model,
                    'mode' => $mode,
                ));
            }
        }
        $out['messages'] = BSession::i()->messages('admin');
        BResponse::i()->json($out);
    }

    protected function _processGridDataPost($class, $defData=array())
    {
        $r = BRequest::i();
        $id = $r->post('id');
        $data = $defData + $r->post();
        $model = $class::i();
        unset($data['id'], $data['oper']);

        $args = array('data'=>&$data, 'model'=>&$model);
        $this->gridPostBefore(array('data'=>&$data, 'model'=>&$model));

        switch ($r->post('oper')) {
        case 'add':
            $set = $model->create($data)->save();
            $result = $set->as_array();
            break;
        case 'edit':
            $set = $model->load($id)->set($data)->save();
            $result = $set->as_array();
            break;
        case 'del':
            $model->load($id)->delete();
            $result = array('success'=>true);
            break;
        }

        $this->gridPostAfter(array('data'=>$data, 'model'=>$model, 'result'=>&$result));

        //BResponse::i()->redirect(BApp::href('fieldsets/grid_data'));
        BResponse::i()->json($result);
    }

    public function gridPostBefore($args)
    {
        BPubSub::i()->fire(static::$_origClass.'::gridPostBefore', $args);
    }

    public function gridPostAfter($args)
    {
        BPubSub::i()->fire(static::$_origClass.'::gridPostAfter', $args);
    }
}
