<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @property int id
 * @property enum status (new, recent, archived)
 * @property enum type (workflow, alert)
 * @property varchar code (order:new:123)
 * @property varchar permissions (orders, customers, modules)
 * @property int action_user_id
 * @property int customer_id
 * @property int order_id
 * @property datetime create_at
 * @property text data_serialized
 *     - message (?)
 *     - message_html
 *     - href
 *     - item_class
 *     - icon_class
 */
class FCom_Admin_Model_Activity extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_admin_activity';
    static protected $_origClass = __CLASS__;

    protected $_fieldOptions = [
        'status' => [
            'new' => 'New',
            'recent' => 'Recent',
            'archived' => 'Archived',
        ],
        'type' => [
            'workflow' => 'Workflow',
            'alert' => 'Alert',
        ],
    ];

    static protected $_availableFilters = [];

    static protected $_permissionsCache = [];

    static protected $_filtersCache = [];

    static public function registerFilter($filter)
    {
        static::$_availableFilters += (array)$filter;
    }

    static public function registerAllFilters()
    {
        BEvents::i()->fire(__METHOD__);
    }

    static public function fetchAllUsersRestrictions()
    {
        if (!static::$_usersRestrictionsCache) {
            $users = FCom_Admin_Model_User::i()->orm('u')
                ->left_outer_join('FCom_Admin_Model_Role', ['r.id', '=', 'u.role_id'], 'r')
                ->select('u.id')->select('u.is_superadmin')
                ->select('u.data_serialized')->select('r.permissions_data')
                ->find_many_assoc();

            foreach ($users as $uId => $u) {
                static::$_permissionsCache['*'][$uId] = $u->get('is_superadmin');
                if (!static::$_permissionsCache['*'][$uId]) {
                    $perms = $u->get('permissions_data');
                    if ($perms) {
                        foreach (array_flip(explode("\n", $perms)) as $p) {
                            static::$_permissionsCache[$p][$uId] = 1;
                        }
                    }
                }
                static::$_filtersCache[$uId] = '#^(' . join(':|', $u->getData('alert_filters')) . ':)#';
            }
        }
    }

    static public function addActivity($data)
    {
        $coreFields = 'status,type,code,permissions,action_user_id,customer_id,order_id,create_at';
        $coreData = BUtil::arrayMask($data, $coreFields);
        $customData = BUtil::arrayMask($data, $coreFields, true);
        $model = static::create($coreData)->setCustomData($customData)->save();
        $permissions = explode(',', $data['permissions']);

        $hlp = FCom_Admin_Model_ActivityUser::i();

        static::fetchAllUsersRestrictions();

        foreach (static::$_permissionsCache['*'] as $uId => $isSuperUser) {
            // check alert permissions
            if ($permissions[0] !== '*') { // allow for everybody
                if (!$isSuperUser) { // super user is allowed to receive everything
                    $skip = true; // assume not allowed
                    foreach ($permissions as $perm) { // iterate alert permissions
                        if (!empty(static::$_permissionsCache[$perm][$uId])) { // user has permission
                            $skip = false;
                            break;
                        }
                    }
                    if ($skip) {
                        continue;
                    }
                }
            }
            // check user filters
            $filters = static::$_filtersCache[$uId];
            if ($filters && !preg_match($filters, $data['code'])) {
                continue; // not in user filters
            }
            $hlp->create(['activity_id' => $model->id(), 'user_id' => $uId, 'alert_user_status' => 'new'])->save();
        }

        return $model;
    }

    static public function getRecentActivityByUser($type = null, $userId = null)
    {
        if (!$userId) {
            $userId = FCom_Admin_Model_User::i()->sessionUserId();
        }

        $orm = static::orm('a')
            ->join('FCom_Admin_Model_ActivityUser', ['au.activity_id', '=', 'a.id'], 'au')
            ->where('au.user_id', $userId);

        if ($type) {
            $orm->where('a.type', $type);
        }

        return $orm->find_many();
    }

    public function markAsRead($userId = null)
    {
        if (!$userId) {
            $userId = FCom_Admin_Model_User::i()->sessionUserId();
        }

        $hlp = FCom_Admin_Model_ActivityUser::i();
        $actUser = $hlp->loadWhere(['activity_id' => $this->id(), 'user_id' => $userId]);
        $actUser->set('alert_user_status', 'read')->save();

        return $this;
    }

    public function dismiss($userId = null)
    {
        if (!$userId) {
            $userId = FCom_Admin_Model_User::i()->sessionUserId();
        }

        $hlp = FCom_Admin_Model_ActivityUser::i();

        $hlp->delete_many(['activity_id' => $this->id(), 'user_id' => $userId]);

        /*
        $actUser = $hlp->load(array('activity_id' => $this->id(), 'user_id' => $userId));
        $actUser->set('alert_user_status', 'dismissed')->save();
        $left = $hlp->orm()->where('activity_id', $this->id())->where_in('alert_user_status', array('new','read'))
            ->select('(count(*))', 'cnt')->find_one();
        */

        /*
        if (!$left->get('cnt')) {
            $this->set('status', 'archive')->save();
        }
        */
        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('status', 'new', 'IFNULL');
        $this->set('type', 'workflow', 'IFNULL');
        $this->set('create_at', BDb::now(), 'IFNULL');

        if (($userId = FCom_Admin_Model_User::i()->sessionUserId())) {
            $this->set('action_user_id', $userId, 'IFNULL');
        }

        if (($custId = FCom_Customer_Model_Customer::i()->sessionUserId())) {
            $this->set('customer_id', $userId, 'IFNULL');
        }

        return true;
    }
}
