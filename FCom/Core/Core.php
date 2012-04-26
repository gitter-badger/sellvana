<?php

class FCom_Core extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()
            ->defaultViewClass('FCom_Core_View_Abstract')
            ->view('head', array('view_class'=>'FCom_Core_View_Head'))
        ;
    }

    public function writeDbConfig()
    {
        BConfig::i()->writeFile('db.php', array('db'=>BConfig::i()->get('db', true)));
        return $this;
    }

    public function writeLocalConfig()
    {
        $c = BConfig::i()->get(null, true);
        unset($c['db']);
        BConfig::i()->writeFile('local.php', $c);
        return $this;
    }

    public function resizeUrl()
    {
        static $url;
        if (!$url) {
            $url = BConfig::i()->get('web/base_store').'/resize.php';
        }
        return $url;
    }

    public function thumbSrc($module, $path, $size)
    {
        $url = BApp::src($module, $path);
        $path = str_replace(BApp::baseUrl(true), '', $url);
        return $this->resizeUrl().'?f='.urlencode($path).'&s='.$size;
    }

    public function dir($path, $autocreate=true, $mode=0777)
    {
        $dir = BConfig::i()->get('fs/root_dir').'/'.$path;
        if ($autocreate && !file_exists($dir)) {
            mkdir($dir, $mode, true);
        }
        return $dir;
    }

    /**
    * Run bootstrap depending on area
    *
    * @deprecated by declaring different bootstrap per area in manifest
    * @param mixed $class
    */
    public static function bootstrapByArea($class)
    {
        switch (BApp::i()->get('area')) {
            case 'FCom_Admin': $class .= '_Admin'; break;
            case 'FCom_Frontend': $class .= '_Frontend'; break;
        }
        $class::bootstrap();
    }

    /**
    * @deprecated
    *
    * @param mixed $str
    */
    static public function getUrlKey($str)
    {
        return BLocale::transliterate($str);
    }

    static public function url($type, $args)
    {
        if (is_string($args)) {
            return BApp::href(''.$type.'/'.$args);
        }
        return false;
    }

    static public function lastNav($save=false)
    {
        $s = BSession::i();
        $r = BRequest::i();
        if ($save) {
            $s->data('lastNav', array($r->rawPath(), $r->get()));
        } else {
            $d = $s->data('lastNav');
            return BApp::baseUrl().($d ? $d[0].'?'.http_build_query((array)$d[1]) : '');
        }
    }
}

class FCom_Core_Controller_Abstract extends BActionController
{
    public function beforeDispatch()
    {
        BLayout::i()->view('root')->bodyClass = BRequest::i()->path(0, 1);
        return parent::beforeDispatch();
    }

    public function afterDispatch()
    {
        BResponse::i()->render();
    }

    public function layout($name)
    {
        $theme = BConfig::i()->get('modules/'.BApp::i()->get('area').'/theme');
        $layout = BLayout::i();
        $layout->theme($theme);
        foreach ((array)$name as $l) {
            $layout->layout($l);
        }
        return $this;
    }

    public function messages($viewName, $namespace='frontend')
    {
        $this->view($viewName)->messages = BSession::i()->messages($namespace);
        return $this;
    }
}

class FCom_Core_Model_Abstract extends BModel
{

}

class FCom_Core_View_Abstract extends BView
{
    public function messagesHtml($namespace=null)
    {
        $messages = $this->messages;
        if (!$messages && $namespace) {
            $messages = BSession::i()->messages($namespace);
        }
        $html = '';
        if ($messages) {
            $html .= '<ul class="msgs">';
            foreach ($messages as $m) {
                $html .= '<li class="'.$m['type'].'-msg">'.$this->q($m['msg']).'</li>';
            }
            $html .= '</ul>';
        }
        return $html;
    }
}

class FCom_Core_View_Root extends FCom_Core_View_Abstract
{
    protected $_htmlAttr = array('lang'=>'en');

    public function addBodyClass($class)
    {
        $this->body_class = !$this->body_class ? (array)$class
            : array_merge($this->body_class, (array)$class);
        return $this;
    }

    public function getBodyClass()
    {
        return $this->body_class ? join(' ', $this->body_class) : '';
    }

    public function getHtmlAttributes()
    {
        $xmlns = array();
        foreach ($this->_htmlAttr as $a=>$v) {
            $xmlns[] = $a.'="'.$this->q($v).'"';
        }
        return join(' ', $xmlns);
    }

    public function xmlns($ns, $href)
    {
        $this->_htmlAttr['xmlns:'.$ns] = $href;
        return $this;
    }
}

class FCom_Core_View_Head extends BViewHead
{

}
