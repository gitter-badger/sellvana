<?php

class FCom_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function authenticate($args=array())
    {
        if (in_array($this->_action, array('login', 'password_recover', 'password_reset'))) {
            return true;
        }
        return parent::authenticate($args);
    }

    public function action_test()
    {
        FCom_Admin_Model_User::i()->sessionUser()->recoverPassword();
        echo "DONE"; exit;
    }

    public function action_index()
    {
        $this->layout('/');
        //BLayout::i()->layout('/');
    }

    public function action_static()
    {
        $this->viewProxy('static', 'index', 'main', 'base');
    }

    public function action_blank()
    {
        exit;
    }

    public function action_noroute()
    {
        $this->layout('404');
        BResponse::i()->status(404);
    }

    public function action_login__POST()
    {
        try {
            $r = BRequest::i()->post('login');
            if (!empty($r['username']) && !empty($r['password'])) {
                $user = FCom_Admin_Model_User::i()->authenticate($r['username'], $r['password']);
                if ($user) {
                    $user->login();
                } else {
                    BSession::i()->addMessage('Invalid user name or password.', 'error', 'admin');
                }
            } else {
                BSession::i()->addMessage('Username and password cannot be blank.', 'error', 'admin');
            }
            $url = BSession::i()->data('admin_login_orig_url');
        } catch (Exception $e) {
            BDebug::logException($e);
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }
        BResponse::i()->redirect(!empty($url) ? $url : BApp::href());
    }

    public function action_password_recover()
    {
        $this->layout('/password/recover');
    }

    public function action_password_recover__POST()
    {
        $user = FCom_Admin_Model_User::i()->load(BRequest::i()->request('email'), 'email');
        if ($user) {
            $user->recoverPassword();
        }
        BSession::i()->addMessage('If the email address was correct, you should receive an email shortly with password recovery instructions.', 'success', 'admin');
        BResponse::i()->redirect(BApp::href());
    }

    public function action_password_reset()
    {
        $token = BRequest::i()->request('token');
        if ($token && ($user = FCom_Admin_Model_User::i()->load($token, 'token'))
            && ($user->get('token') === $token)
        ) {
            $this->layout('/password/reset');
        } else {
            BSession::i()->addMessage('Invalid link. It is possible your recovery link has expired.', 'error', 'admin');
            BResponse::i()->redirect(BApp::href());
        }
    }

    public function action_password_reset__POST()
    {
        $r = BRequest::i();
        $token = $r->request('token');
        $password = $r->post('password');
        $confirm = $r->post('password_confirm');
        if ($token && $password && $confirm && $password === $confirm
            && ($user = FCom_Admin_Model_User::i()->load($token, 'token'))
            && $user->get('token') === $token
        ) {
            $user->resetPassword($password);
            BSession::i()->addMessage('Password has been reset', 'success', 'admin');
            BResponse::i()->redirect(BApp::href());
        } else {
            BSession::i()->addMessage('Invalid form data', 'error', 'admin');
            BResponse::i()->redirect(BRequest::i()->currentUrl());
        }
    }

    public function action_logout()
    {
        FCom_Admin_Model_User::i()->logout();
        BResponse::i()->redirect(BApp::href());
    }

    public function action_dashboard()
    {
        if (!BRequest::i()->xhr()) {
            BResponse::i()->redirect('');
        }
        $widgets = FCom_Admin_View_Dashboard::i()->getWidgets();
        $widgetKeys = explode(',', BRequest::i()->get('widgets'));
        $result = array();
        foreach ($widgetKeys as $wKey) {
            if (empty($widgets[$wKey])) {
                continue;
            }
            if (!empty($widgets[$wKey]['view'])) {
                $html = (string)$this->view($widgets[$wKey]['view']);
            } else {
                $html = $widgets[$wKey]['content'];
            }
            $result['widgets'][] = array('key' => $wKey, 'html' => $html);
        }
        BResponse::i()->json($result);
    }

    public function action_my_account()
    {
        $model = FCom_Admin_Model_User::i()->sessionUser();
        BLayout::i()->view('my_account')->set('model', $model);
        $this->layout('/my_account');
    }

    public function action_my_account__POST()
    {
        $model = FCom_Admin_Model_User::i()->sessionUser();
        $r = BRequest::i();
        $data = $r->post('model');
        if (empty($data['password_current']) || !$model->validatePassword($data['password_current'])) {
            BSession::i()->addMessage('Missing or invalid current password');
            BResponse::i()->redirect('my_account');
        }
        try {
            if (!empty($data['password'])) {
                if (empty($data['password_confirm']) || $data['password'] !== $data['password_confirm']) {
                    BSession::i()->addMessage('Missing or not matching password confirmation');
                    BResponse::i()->redirect('my_account');
                }
            }
            $model->set($data)->save();
            BSession::i()->addMessage('Changes have been saved', 'success', 'admin');
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'admin');
        }

        BResponse::i()->redirect('my_account');
    }

    public function action_reports()
    {
        //TODO add code for reports
        // $model = FCom_Admin_Model_User::i()->sessionUser();
        //BLayout::i()->view('my_account')->set('model', $model);
        $this->layout('/reports');
    }

    public function action_personalize()
    {
        $r = BRequest::i()->request();
        $data = array();
        switch ($r['do']) {
        case 'grid.col.width':
            if (empty($r['grid']) || empty($r['width'])) {
                break;
            }
            $columns = array($r['col']=>array('width'=>$r['width']));
            $data = array('grid'=>array($r['grid']=>array('columns'=>$columns)));

            break;
        case 'grid.col.widths':
            $cols = $r['cols'];
            $columns = array();
            foreach($cols as $col) {
                if (empty($col['name']) || $col['name']==='cb') {
                    continue;
                }
                $columns[$col['name']] = array('width'=>$col['width']);
            }
            $data = array('grid'=>array($r['grid']=>array('columns'=>$columns)));

            break;
        case 'grid.col.hidden':
            if (empty($r['grid']) || empty($r['col']) || empty($r['hidden'])) {
                break;
            }
            $columns = array($r['col'] =>array('hidden'=>$r['hidden']));
            $data = array('grid'=>array($r['grid']=>array('columns'=>$columns)));

            break;
        case 'grid.filter.hidden':
            if (empty($r['grid']) || empty($r['col']) || empty($r['hidden'])) {
                break;
            }
            $filters = array($r['col'] =>array('hidden'=>$r['hidden']));
            $data = array('grid'=>array($r['grid']=>array('filters'=>$filters)));

            break;
        case 'grid.col.order':
            if (is_array($r['cols'])) {
                $cols = $r['cols'];
            } else {
                $cols = BUtil::fromJson($r['cols']);
            }

            $columns = array();
            foreach ($cols as $i=>$col) {
                if (empty($col['name']) || $col['name']==='cb') {
                    continue;
                }
                $columns[$col['name']] = array('position'=>$i, 'hidden'=>!empty($col['hidden']));
            }
            $data = array('grid'=>array($r['grid']=>array('columns'=>$columns)));

            break;
        case 'grid.filter.orders':
            if (is_array($r['cols'])) {
                $cols = $r['cols'];
            } else {
                $cols = BUtil::fromJson($r['cols']);
            }
            $filters = array();
            foreach ($cols as $i=>$col) {
                if (empty($col['field'])) {
                    continue;
                }
                $filters[$col['field']] = array('position'=>$i, 'hidden'=>!empty($col['hidden']));
            }
            $data = array('grid'=>array($r['grid']=>array('filters'=>$filters)));

            break;
        case 'grid.col.orders':
            if (is_array($r['cols'])) {
                $cols = $r['cols'];
            } else {
                $cols = BUtil::fromJson($r['cols']);
            }

            $columns = array();
            foreach ($cols as $i=>$col) {
                if (empty($col['name']) || $col['name']==='cb') {
                    continue;
                }
                $columns[$col['name']] = array('position'=>$col['position'], 'hidden'=>empty($col['hidden'])?false:$col['hidden']);
            }
            $data = array('grid'=>array($r['grid']=>array('columns'=>$columns)));

            break;
        case 'grid.state':
            if (empty($r['grid'])) {
                break;
            }
            if (!empty($r['s']) && empty($r['sd'])) {
                $r['sd'] = 'asc';
            }

            /*if ($r['sd']==='ascending') {
                $r['sd'] = 'asc';
            } elseif ($r['sd']==='descending') {
                $r['sd'] = 'desc';
            }*/

            $data = array('grid' => array($r['grid'] => array('state' => BUtil::arrayMask($r, 'p,ps,s,sd,q'))));
            break;

        case 'settings.tabs.order':
            break;

        case 'settings.sections.order':
            break;

        case 'nav.collapse':
            $data['nav']['collapsed'] = !empty($r['collapsed']);
            break;

        case 'dashboard.widget.pos':
            if (empty($r['widgets'])) {
                break;
            }
            foreach ($r['widgets'] as $i => $wKey) {
                $data['dashboard']['widgets'][$wKey]['pos'] = $i+1;
            }
            break;

        case 'dashboard.widget.close': case 'dashboard.widget.collapse':
            if (empty($r['key'])) {
                break;
            }
            $data = array();
            if ($r['do'] == 'dashboard.widget.close') {
                $data['closed'] = true;
            }
            if ($r['do'] == 'dashboard.widget.collapse') {
                $data['collapsed'] = !empty($r['collapsed']) && $r['collapsed']!=='0' && $r['collapsed']!=='false';
            }
            $data = array('dashboard' => array('widgets' => array($r['key'] => $data)));
            break;
        }
        BEvents::i()->fire(__METHOD__, array('request' => $r, 'data' => &$data));

        FCom_Admin_Model_User::i()->personalize($data);
        BResponse::i()->json(array('success'=>true, 'data' => $data, 'r' => $r));
    }
}
