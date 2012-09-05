<?php

class FCom_Admin_Controller_ApiServer_Abstract extends FCom_Admin_Controller_Abstract
{
    protected static $_origClass;
    protected $_permission;
    protected $_authorizeActions = array();

    public function __construct() {
        parent::__construct();
        BResponse::i()->contentType('application/json');
    }

    public function authenticate($args=array())
    {
        $res = FCom_Admin_Model_User::i()->isLoggedIn();
        if (!$res) {
            return $this->authorize($args);
        }
        return $res;
    }


    public function authorize($args=array())
    {
        $authorizeActions = $this->_authorizeActions;
        if (false == $authorizeActions) {
            return true;
        }

        if (!is_array($authorizeActions)) {
            $authorizeActions = array($authorizeActions);
        }

        if (false == in_array($this->getAction(), $authorizeActions)) {
            return true;
        }

        $password = BRequest::i()->headers('PHP_AUTH_PW');
        $username = BRequest::i()->headers('PHP_AUTH_USER');
        $user = FCom_Admin_Model_User::i()->sessionUser();
        if ($user) {
            return true;
        }
        $user = FCom_Admin_Model_User::i()->authenticateApi($username, $password);
        if ($user) {
            $user->login();
            return true;
        }
        BResponse::i()->status(403, null, BUtil::toJson("Authorization required"));
    }
}