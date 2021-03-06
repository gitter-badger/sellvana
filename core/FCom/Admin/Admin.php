<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_Admin
 *
 * @property FCom_Frontend_Main $FCom_Frontend_Main
 * @property FCom_Admin_Main $FCom_Admin_Main
 * @property FCom_Admin_Controller_MediaLibrary $FCom_Admin_Controller_MediaLibrary
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 */
class FCom_Admin_Admin extends BClass
{
    public function onBeforeBootstrap()
    {
        $defaultTheme = $this->BConfig->get('modules/FCom_Admin/theme');
        $this->BLayout
            ->setDefaultTheme($defaultTheme ? $defaultTheme : 'FCom_Admin_DefaultTheme')
            ->setDefaultViewClass('FCom_Admin_View_Default')
        ;
    }

    public function bootstrap()
    {
        $this->FCom_Admin_Main->bootstrap();

        if ($this->BRequest->https() && $this->BConfig->get('web/hsts_enable')) {
            $this->BResponse->httpSTS();
        }

        $this->BResponse->nocache();
        //$this->BConfig->set('web/hide_script_name', 0);

        $this->FCom_Admin_Controller_MediaLibrary
            ->allowFolder('media/images') // for wysiwyg uploads
        ;
    }

    public function layout()
    {
        /** @var FCom_Core_View_Head $head */
        $head = $this->BLayout->view('head');
        /** @var FCom_Core_View_Text $script */
        $script = $this->BLayout->view('head_script');
        /** @var FCom_Core_View_Text $css */
        $css = $this->BLayout->view('head_css');

        $text = '
FCom.Admin = {};
FCom.Admin.base_url = "' . rtrim($this->BConfig->get('web/base_src'), '/') . '/' . '";
FCom.Admin.code_mirror_base_url = "' . $this->BApp->src('@FCom_Admin/Admin/js/codemirror') . '";
FCom.Admin.upload_href = "' . $this->BApp->href('upload') . '";
FCom.Admin.personalize_href = "' . $this->BApp->href('my_account/personalize') . '";
FCom.Admin.current_mode = "'.$this->BDebug->mode().'";
        ';
        $head->js_raw('admin_init', $text);
        $script->addText('FCom_Admin:init', $text);

        if (!empty($config['add_js_code'])) {
            $head->js_raw('add_css_code', $config['add_js_code']);
            $script->addText('FCom_Admin:add_js', $config['add_js_code']);
        }

        if (!empty($config['add_css_style'])) {
            $head->css_raw('add_css_style', $config['add_css_style']);
            $css->addText('FCom_Admin:add_css', $config['add_css_style']);
        }

        $config = $this->BConfig->get('modules/FCom_Admin');
        if (!empty($config['add_js_files'])) {
            foreach (explode("\n", $config['add_js_files']) as $js) {
                $head->js(trim($js));
            }
        }
        if (!empty($config['add_css_files'])) {
            foreach (explode("\n", $config['add_css_files']) as $css) {
                $head->css(trim($css));
            }
        }

        $pers = $this->FCom_Admin_Model_User->personalize();
        if (!empty($pers['nav']['collapsed'])) {
            $this->BLayout->view('root')->addBodyClass('main-nav-closed');
        }
    }

    public function onSettingsPost($args)
    {
        if (!empty($args['post']['config']['db'])) {
            $db =& $args['post']['config']['db'];
            if (empty($db['password']) || $db['password'] === '*****') {
                unset($db['password']);
            }
        }

        $ip = $this->BRequest->ip();
        foreach (['Frontend', 'Admin'] as $area) {
            if (!empty($args['post']['config']['mode_by_ip']['FCom_' . $area])) {
                $modes =& $args['post']['config']['mode_by_ip']['FCom_' . $area];
                $modes = str_replace('@', $ip, $modes);
                unset($modes);
            }
        }
    }

    public function onGetDashboardWidgets($args)
    {
        $view = $args['view'];
        /** @var FCom_Admin_View_Dashboard $view */
        $view->addWidget('visitors-totals', [
            'title' => 'Visitors',
            'icon' => 'group',
            'view' => 'dashboard/visitors-totals',
            'cols' => 2,
            'async' => true,
        ]);
    }
}
