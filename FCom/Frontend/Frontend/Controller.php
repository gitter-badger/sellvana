<?php

class FCom_Frontend_Frontend_Controller extends FCom_Frontend_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/');
    }

    public function action_static()
    {
        $this->viewProxy('static', 'index', 'main', 'base');
    }

    public function action_noroute()
    {
        $this->layout('404');
        BResponse::i()->status(404);
    }
}
