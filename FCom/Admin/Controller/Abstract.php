<?php

class FCom_Admin_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    public function messages($viewName, $namespace='admin')
    {
        $this->view($viewName)->messages = BSession::i()->messages($namespace);
        return $this;
    }

    public function authorize($args=array())
    {
        return FCom_Admin_Model_User::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_unauthorized()
    {
        $r = BRequest::i();
        if ($r->xhr()) {
            BSession::i()->data('login_orig_url', $r->referrer());
            BResponse::i()->json(array('error'=>'login'));
        } else {
            BSession::i()->data('login_orig_url', $r->currentUrl());
            $this->messages('login')->layout('/login');
            BResponse::i()->status(401, 'Not authorized');
        }
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
            $tabView = $layout->view($tab['view']);
            if ($tabView) {
                $tabView->set(array(
                    'model' => $model,
                    'mode' => $mode,
                ));
            } else {
                BDebug::error('MISSING VIEW: '.$tab['view']);
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
}
