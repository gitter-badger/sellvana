<?php

class FCom_Admin_Model_User extends FCom_Core_Model_Abstract
{
    const
        STATUS_ACTIVE   = 'A',
        STATUS_INACTIVE = 'I',
        STATUS_DELETED  = 'D'
    ;

    protected static $_table = 'fcom_admin_user';

    protected static $_sessionUser;

    protected $_fieldOptions = array(
        'status' => array(
            'A' => 'Active',
            'I' => 'Inactive',
        ),
    );

    protected $_persModel;
    protected $_persData;

    public static function statusOptions()
    {
        return array(
            static::STATUS_ACTIVE => 'Active',
            static::STATUS_INACTIVE => 'Inactive',
        );
    }

    public function setPassword($password)
    {
        $this->password_hash = BUtil::fullSaltedHash($password);
        return $this;
    }

    public function getData()
    {
        $data = $this->as_array();
        unset($data['password_hash']);
        return $data;
    }

    public function validatePassword($password)
    {
        return BUtil::validateSaltedHash($password, $this->password_hash);
    }

    public static function has_role($orm, $role)
    {
        return $orm->where('role', $role);
    }

    public static function options()
    {
        $users = static::i()->orm()
            ->select('id')->select('firstname')->select('lastname')
            ->find_many();
        $options = array();
        foreach ($users as $u) {
            $options[$u->id] = $u->firstname.' '.$u->lastname;
        }
        return $options;
    }

    static public function sessionUser($reset=false)
    {
        if ($reset || !static::$_sessionUser) {
            $data = BSession::i()->data('admin_user');
            if (is_string($data)) {
                static::$_sessionUser = $data ? unserialize($data) : false;
            } else {
                return false;
            }
        }
        return static::$_sessionUser;
    }

    static public function sessionUserId()
    {
        $user = self::sessionUser();
        return !empty($user) ? $user['id'] : false;
    }

    static public function isLoggedIn()
    {
        return static::sessionUser() ? true : false;
    }

    static public function login($username, $password)
    {
        /** @var Denteva_Model_User */
        $user = static::i()->orm()->where_complex(array('OR'=>array('username'=>$username, 'email'=>$username)))->find_one();
        if (!$user || !$user->validatePassword($password)) {
            return false;
        }

        $user->set('last_login', BDb::now())->save();

        BSession::i()->data('admin_user', serialize($user));
        static::$_sessionUser = $user;

        if ($user->locale) {
            setlocale(LC_ALL, $user->locale);
        }
        if ($user->timezone) {
            date_default_timezone_set($user->timezone);
        }
        BPubSub::i()->fire('FCom_Admin_Model_User::login.after', array('user'=>$user));

        return true;
    }

    static public function logout()
    {
        BSession::i()->data('admin_user', false);
        static::$_sessionUser = null;
    }

    public function tzOffset()
    {
        return BLocale::i()->tzOffset($this->tz);
    }

    public function config()
    {
        return $this->relatedModel('Denteva_Model_Config', array('user_id'=>$this->id), true);
    }

    public function fullname()
    {
        return $this->firstname.' '.$this->lastname;
    }

    /**
    * Personalize user preferences (grids, dashboard, etc)
    * - grid
    *   - {grid-name}
    *     - colModel
    *
    * @param array|null $data
    * @return FCom_Admin_Model_User|array
    */
    public function personalize($data=null)
    {
        if (!$this->orm) {
            return $this->sessionUser()->personalize($data);
        }
        if (!$this->_persModel) {
            $this->_persModel = FCom_Admin_Model_Personalize::i()->load($this->id, 'user_id');
            if (!$this->_persModel) {
                $this->_persModel = FCom_Admin_Model_Personalize::i()->create(array('user_id'=>$this->id));
            }
        }
        if (!$this->_persData) {
            $this->_persData = $this->_persModel->data_json ? BUtil::fromJson($this->_persModel->data_json) : array();
        }
        if (is_null($data)) {
            return $this->_persData;
        }
        $this->_persData = BUtil::arrayMerge($this->_persData, $data);
        $this->_persModel->set('data_json', BUtil::toJson($this->_persData))->save();
        return $this;
    }
}