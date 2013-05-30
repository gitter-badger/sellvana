<?php

class FCom_Frontend extends BClass
{
    static public function bootstrap()
    {
        if (BRequest::i()->https()) {
            BResponse::i()->httpSTS();
        }

        if (BApp::i()->get('area')==='FCom_Frontend') {
            static::i()->bootstrapUI();
        }

        if (BDebug::is('RECOVERY,MIGRATION')) {
            BLayout::i()->setRootView('under_construction');
            BResponse::i()->render();
        }
    }

    public function bootstrapUI()
    {
        BRouting::i()
            ->route('_ /noroute', 'FCom_Frontend_Controller.noroute', array(), null, false)
            ->route('GET /', 'FCom_Frontend_Controller.index')
        ;

        $defaultTheme = BConfig::i()->get('modules/FCom_Frontend/theme');

        BLayout::i()
            ->view('root', array('view_class'=>'FCom_Frontend_View_Root'))
            ->view('breadcrumbs', array('view_class'=>'FCom_Frontend_View_Breadcrumbs'))
            //->view('head', array('view_class'=>'BViewHead'))

            ->addAllViews('views')

            ->defaultTheme($defaultTheme ? $defaultTheme : 'FCom_Frontend_DefaultTheme')
            ->afterTheme('FCom_Frontend::layout')
        ;

        return $this;
    }

    public static function layout($args)
    {
        if (($head = BLayout::i()->view('head'))) {
            $config = BConfig::i()->get('modules/FCom_Frontend');
            if (!empty($config['add_js'])) {
                foreach (explode("\n", $config['add_js']) as $js) {
                    $head->js($js);
                }
            }
            if (!empty($config['add_css'])) {
                foreach (explode("\n", $config['add_css']) as $css) {
                    $head->css($css);
                }
            }
        }
    }

    public static function adminHref($url='')
    {
        $href = BConfig::i()->get('web/base_admin');
        if (!$href) {
            $href = BApp::baseUrl(true) . '/admin';
        }
        return trim($href.'/'.ltrim($url, '/'), '/');
    }

    public static function href($url='')
    {
        $r = BRequest::i();
        $href = $r->scheme().'://'.$r->httpHost().BConfig::i()->get('web/base_store');
        return trim(rtrim($href, '/').'/'.ltrim($url, '/'), '/');
    }
}

class FCom_Frontend_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    public function messages($viewName, $namespace='frontend')
    {
        $this->view($viewName)->messages = BSession::i()->messages($namespace);
        return $this;
    }

    public function action_unauthenticated()
    {
        $r = BRequest::i();
        if ($r->xhr()) {
            BSession::i()->data('login_orig_url', $r->referrer());
            BResponse::i()->json(array('error'=>'login'));
        } else {
            BSession::i()->data('login_orig_url', $r->currentUrl());
            $this->layout('/customer/login');
            BResponse::i()->status(401, 'Unauthorized'); // HTTP sic
        }
    }

    public function action_unauthorized()
    {
        $r = BRequest::i();
        if ($r->xhr()) {
            BSession::i()->data('login_orig_url', $r->referrer());
            BResponse::i()->json(array('error'=>'denied'));
        } else {
            BSession::i()->data('login_orig_url', $r->currentUrl());
            $this->layout('/denied');
            BResponse::i()->status(403, 'Forbidden');
        }
    }
}

class FCom_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/');
    }

    public function action_noroute()
    {
        $this->layout('404');
        BResponse::i()->status(404);
    }
}

class FCom_Frontend_View_Root extends FCom_Core_View_Root
{
    public function setLayoutClass($layout)
    {
        $this->layout_class = $layout;
        $this->show_left_col = $layout=='col2-layout-left' || $layout=='col3-layout';
        $this->show_right_col = $layout=='col2-layout-right' || $layout=='col3-layout';
        return $this;
    }

}

class FCom_Frontend_View_Breadcrumbs extends BView
{
    public function getCrumbs()
    {
        if (!$this->crumbs_formatted) {
            if ($this->crumbs) {
                $crumbs = $this->crumbs;
            } elseif ($this->navNode) {
                $crumbs = array('home');
                if (($asc = $this->navNode->ascendants())) {
                    foreach ($asc as $a) {
                        if (!$a->node_name) continue;
                        $crumbs[] = array(
                            'href'=>$a->url_href ? BApp::baseUrl().trim('/'.$a->url_href, '/') : null,
                            'title'=>$a->node_name,
                            'label'=>$a->node_name,
                        );
                    }
                }
                $crumbs[] = array('label'=>$this->navNode->node_name, 'active'=>true);
            }

            if (!empty($crumbs)) {
                foreach ($crumbs as $i=>&$c) {
                    if ($c=='home') $c = array('href'=>BApp::href(), 'label'=>'Home', 'li_class'=>'home');
                    if (!isset($c['title'])) $c['title'] = $c['label'];
                }
                unset($c);
            }
            $this->crumbs_formatted = $crumbs;
        }
        return $this->crumbs_formatted;
    }
}