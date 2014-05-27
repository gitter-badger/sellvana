<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Model class for table 'fcom_customer'
 * The followings are the available columns in table 'fcom_customer':
 * @property string $id
 * @property string $email
 * @property string $firstname
 * @property string $lastname
 * @property string $password_hash
 * @property string $default_shipping_id
 * @property string $default_billing_id
 * @property string $create_at
 * @property string $update_at
 * @property string $last_login
 * @property string $token
 * @property string $payment_method
 * @property string $payment_details
 * @property string $customer_group
 * @property string $status
 *
 * relations
 * @property FCom_Customer_Model_Address $default_billing
 * @property FCom_Customer_Model_Address $default_shipping
 */
class FCom_Customer_Model_Customer extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_customer';
    protected static $_origClass = __CLASS__;

    protected static $_fieldOptions = [
        'status' => [
            'review'   => 'Review',
            'active'   => 'Active',
            'disabled' => 'Disabled',
        ],
    ];

    protected static $_sessionUser;
    protected $defaultShipping = null;
    protected $defaultBilling = null;

    private static $lastImportedCustomer = 0;

    protected static $_validationRules = [
        ['email', '@required'],
        ['firstname', '@required'],
        ['lastname', '@required'],
        //array('password', '@required'),
        ['password_confirm', '@password_confirm'],
        /*array('payment_method', '@required'),
        array('payment_details', '@required'),*/

        ['email', '@email'],

        ['default_shipping_id', '@integer'],
        ['default_billing_id', '@integer'],
        ['password', 'FCom_Customer_Model_Customer::validatePasswordSecurity'],
        /*array('customer_group', '@integer'),*/
    ];
    //todo: set rules password minimum length

    /**
     * @param bool  $new
     * @param array $args
     * @return FCom_Customer_Model_Customer
     */
    public static function i($new = false, array $args = [])
    {
        return parent::i($new, $args);
    }

    /**
     * override default rules for login form
     */
    public function setLoginRules()
    {
        static::$_validationRules =  [
            ['email', '@required'],
            ['password', '@required'],
            ['email', '@email'],
        ];
    }

    /**
     * override default rules for password recover form
     */
    public function setPasswordRecoverRules()
    {
        static::$_validationRules =  [
            ['email', '@required'],
            ['email', '@email'],
        ];
    }

    public function setAccountEditRules($incChangePassword = false)
    {
        static::$_validationRules = [
            ['email', '@required'],
            ['firstname', '@required'],
            ['lastname', '@required'],
        ];

        if ($incChangePassword) {
            static::$_validationRules[] = ['password', '@required'];
            static::$_validationRules[] = ['password_confirm', '@password_confirm'];
        }
    }

    public function setChangePasswordRules()
    {
        static::$_validationRules = [
            ['current_password', '@required'],
            ['password', '@required'],
            ['password_confirm', '@password_confirm'],
        ];
    }

    public function setSimpleRegisterRules()
    {
        static::$_validationRules = [
            ['email', '@required'],
            ['password', '@required'],
            ['password_confirm', '@password_confirm'],
        ];
    }

    public function setPassword($password)
    {
        $token = BUtil::randomString(16);
        $this->set([
            'password_hash' => BUtil::fullSaltedHash($password),
            'password_session_token' => $token,
        ]);
        if ($this->id() === static::sessionUserId()) {
            BSession::i()->set('admin_user_password_token', $token);
        }
        return $this;
    }

    public function recoverPassword()
    {
        $this->set(['token' => BUtil::randomString(20)])->save();
        BLayout::i()->view('email/customer-password-recover')->set('customer', $this)->email();
        return $this;
    }

    public function resetPassword($password)
    {
        $this->set(['token' => null])->setPassword($password)->save();
        BLayout::i()->view('email/customer-password-reset')->set('customer', $this)->email();
        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;
        if ($this->get('password')) {
            $this->setPassword($this->get('password'));
        }
        $this->set('create_at', BDb::now(), 'IFNULL');
        $this->set('update_at', BDb::now());
        return true;
    }

    public function onAfterSave()
    {
        parent::onAfterSave();

        if (static::sessionUserId() === $this->id()) {
            BSession::i()->set('customer_user', serialize($this));
            static::$_sessionUser = $this;
        }

        if ($this->_newRecord) {
            FCom_PushServer_Model_Channel::i()->getChannel('customers_feed', true)->send([
                'signal' => 'new_customer',
                'customer' => [
                    'id' => $this->id(),
                    'email' => $this->email,
                    'name' => $this->firstname . ' ' . $this->lastname,
                ],
            ]);
        }
    }

    public function prepareApiData($customers)
    {
        $result = [];
        foreach ($customers as $customer) {
            $result[] = [
                'id'                => $customer->id,
                'email'             => $customer->email,
                'firstname'         => $customer->firstname,
                'lastname'          => $customer->lastname,
                'shipping_address_id'  => $customer->default_shipping_id,
                'billing_address_id'   => $customer->default_billing_id
            ];
        }
        return $result;
    }

    public function formatApiPost($post)
    {
        $data = [];

        if (!empty($post['email'])) {
            $data['email'] = $post['email'];
        }
        if (!empty($post['password'])) {
            $data['password'] = $post['password'];
        }
        if (!empty($post['firstname'])) {
            $data['firstname'] = $post['firstname'];
        }
        if (!empty($post['lastname'])) {
            $data['lastname'] = $post['lastname'];
        }
        if (!empty($post['shipping_address'])) {
            $data['shipping_address_id'] = $post['shipping_address'];
        }
        if (!empty($post['billing_address_id'])) {
            $data['billing_address_id'] = $post['billing_address_id'];
        }
        return $data;
    }

    public function as_array(array $objHashes = [])
    {
        $data = parent::as_array();
        unset($data['password_hash']);
        return $data;
    }

    public static function validatePasswordSecurity($data, $args)
    {
        if (!BConfig::i()->get('modules/FCom_Customer/password_strength')) {
            return true;
        }
        $password = $data[$args['field']];
        if (strlen($password) > 0 && !preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[~!@#$%^&*()_+=}{><;:\]\[?]).{7,}/', $password)) {
            return 'Password must be at least 7 characters in length and must include at least one letter, one capital letter, one number, and one special character.';
        }
        return true;
    }

    public function validatePassword($password)
    {
        return BUtil::validateSaltedHash($password, $this->password_hash);
    }

    static public function sessionUserId()
    {
        return BSession::i()->get('customer_id');
    }

    /**
     * @param bool $reset
     * @return bool|FCom_Customer_Model_Customer
     */
    static public function sessionUser($reset = false)
    {
        if ($reset || !static::$_sessionUser) {
            $sessData =& BSession::i()->dataToUpdate();
            if (empty($sessData['customer_id'])) {
                return false;
            }
            $userId = $sessData['customer_id'];
            $user = static::$_sessionUser = static::load($userId);
            $token = $user->get('password_session_token');
            if (!$token) {
                $token = BUtil::randomString(16);
                $user->set('password_session_token', $token)->save();
            }
            if (empty($sessData['customer_password_token'])) {
                $sessData['customer_password_token'] = $token;
            } elseif ($sessData['customer_password_token'] !== $token) {
                $user->logout();
                BResponse::i()->cookie('remember_me', 0);
                BResponse::i()->redirect('');
                return;
            }
        }
        return static::$_sessionUser;
    }

    static public function isLoggedIn()
    {
        return static::sessionUserId() ? true : false;
    }

    static public function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        if (!BLoginThrottle::i()->init('FCom_Customer_Model_Customer', $username)) {
            return false;
        }
        /** @var FCom_Admin_Model_User */
        $user = static::i()->orm()->where('email', $username)->find_one();
        if (!$user || !$user->validatePassword($password)) {
            BLoginThrottle::i()->failure();
            return false;
        }
        BLoginThrottle::i()->success();
        return $user;
    }

    public function login()
    {
        $this->set('last_login', BDb::now())->save();

        BSession::i()->set('customer_id', $this->id());
        static::$_sessionUser = $this;
        if ($this->locale) {
            setlocale(LC_ALL, $this->locale);
        }
        if ($this->timezone) {
            date_default_timezone_set($this->timezone);
        }
        BEvents::i()->fire(__METHOD__ . ':after', ['user' => $this]);
        return $this;
    }

    static public function logout()
    {
        BEvents::i()->fire(__METHOD__ . ':before', ['user' => static::sessionUser()]);

        $sessData =& BSession::i()->dataToUpdate();
        $sessData = [];
        static::$_sessionUser = null;

        BSession::i()->regenerateId();
    }

    static public function register($r)
    {
        if (empty($r['email'])
            || empty($r['password']) || empty($r['password_confirm'])
            || $r['password'] != $r['password_confirm']
        ) {
            throw new Exception('Incomplete or invalid form data.');
        }

        unset($r['id']);
        $customer = static::i()->create($r)->save();
        BLayout::i()->view('email/new-customer')->set('customer', $customer)->email();
        BLayout::i()->view('email/new-admin')->set('customer', $customer)->email();
        return $customer;
    }



    public static function import($data)
    {
        BEvents::i()->fire(__METHOD__ . ':before', ['data' => &$data]);

        if (!empty($data['customer']['id'])) {
            $cust = static::load($data['customer']['id']);
        }
        $result['status'] = '';
        if (empty($cust)) {
            if (empty($data['customer']['email'])) {
                if (static::$lastImportedCustomer) {
                    $cust = static::$lastImportedCustomer;
                    $result['status'] = 'updated';
                } else {
                    $result = ['status' => 'error', 'message' => 'Missing email address'];
                    return $result;
                }
            } else {
                $cust = static::load($data['customer']['email'], 'email');
            }
        }
        if (!$cust) {
            $cust = static::create();
            $result['status'] = 'created';
        }
        $result['model'] = $cust;
        if (!empty($data['customer']['email'])) {
            $cust->set($data['customer']);
            if ($cust->is_dirty()) {
                if (!$result['status']) $result['status'] = 'updated';
                $cust->save();
            }
        }

        static::$lastImportedCustomer = $cust;

        $result['addr'] = FCom_Customer_Model_Address::i()->import($data, $cust);

        BEvents::i()->fire(__METHOD__ . ':after', ['data' => $data, 'result' => &$result]);

        return $result;
    }

    public function defaultBilling()
    {
        if ($this->default_billing_id && !$this->default_billing) {
            $this->default_billing = FCom_Customer_Model_Address::i()->load($this->default_billing_id);
        }
        return $this->default_billing;
    }

    public function defaultShipping()
    {
        if ($this->default_shipping_id && !$this->default_billing) {
            $this->default_billing = FCom_Customer_Model_Address::i()->load($this->default_shipping_id);
        }
        return $this->default_billing;
    }

    public function addresses()
    {
        return FCom_Customer_Model_Address::i()->orm('a')->where('customer_id', $this->id)->find_many();
    }

    public function getPaymentMethod()
    {
        return static::i()->load($this->id)->payment_method;
    }

    public function getPaymentDetails()
    {
        return static::i()->load($this->id)->payment_details;
    }

    public function setPaymentDetails($data)
    {
        $this->payment_details = Butil::toJson($data);
        $this->save();
    }

    static public function onAddProductToCart($args)
    {
        $cart = $args['model'];

        $user = static::sessionUser();
        if ($user) {
            $user->session_cart_id = $cart->id();
            $user->save();
        }
    }

    /**
     * get options data to create options html in select
     * @param $labelIncId
     * @return array
     */
    public function getOptionsData($labelIncId = false)
    {
        $results = $this->orm('p')->find_many();
        $data = [];
        if (count($results)) {
            foreach ($results as $r) {
                $fullname = $r->firstname . ' ' . $r->lastname;
                $data[$r->id] = $labelIncId ? $r->id . ' - ' . $fullname : $fullname;
            }
        }
        return $data;
    }

    /**
     * rule email unique
     * @param $data
     * @param $args
     * @return bool
     */
    public static function ruleEmailUnique($data, $args)
    {
        if (empty($data[$args['field']])) {
            return true;
        }
        $orm = static::i()->orm()->where('email', $data[$args['field']]);
        if (!empty($data['id'])) {
            $orm->where_not_equal('id', $data['id']);
        }
        if ($orm->find_one()) {
            return false;
        }
        return true;
    }

    /**
     * calc sales statistics
     * @return array
     */
    public function saleStatistics()
    {
        $statistics = [
            'lifetime' => 0,
            'avg'      => 0,
        ];
        if (BModuleRegistry::i()->isLoaded('FCom_Sales')) {
            $orders = FCom_Sales_Model_Order::i()->orm()->where('customer_id', $this->id)->find_many();
            if ($orders) {
                $cntOrders = count($orders);
                foreach ($orders as $order) {
                    $statistics['lifetime'] += $order->grandtotal;
                }
                $statistics['avg'] = $statistics['lifetime'] / $cntOrders;
            }
        }
        return $statistics;
    }
}
