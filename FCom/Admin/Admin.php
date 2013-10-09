<?php

class FCom_Admin_Admin extends BClass
{
    public static function beforeBootstrap()
    {
        $defaultTheme = BConfig::i()->get('modules/FCom_Admin/theme');
        BLayout::i()
            ->setDefaultTheme($defaultTheme ? $defaultTheme : 'FCom_Admin_DefaultTheme')
            ->defaultViewClass('FCom_Admin_View_Default')
        ;
    }

    public static function bootstrap()
    {
        FCom_Admin_Main::bootstrap();

        if (BRequest::i()->https()) {
            BResponse::i()->httpSTS();
        }
    }

    public static function layout()
    {
        if (($head = BLayout::i()->view('head'))) {
            $head->js_raw('admin_init', '
FCom.Admin = {}
FCom.Admin.codemirrorBaseUrl = "'.BApp::src('@FCom_Admin/js/codemirror').'"
            ');

            $config = BConfig::i()->get('modules/FCom_Admin');
            if (!empty($config['add_js_files'])) {
                foreach (explode("\n", $config['add_js_files']) as $js) {
                    $head->js(trim($js));
                }
            }
            if (!empty($config['add_js_code'])) {
                $head->js_raw('add_js_code', $config['add_js_code']);
            }
            if (!empty($config['add_css_files'])) {
                foreach (explode("\n", $config['add_css_files']) as $css) {
                    $head->css(trim($css));
                }
            }
            if (!empty($config['add_css_style'])) {
                $head->css_raw('add_css_code', $config['add_css_style']);
            }
        }
    }
}
