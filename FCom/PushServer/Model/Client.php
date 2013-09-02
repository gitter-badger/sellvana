<?php

class FCom_PushServer_Model_Client extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_client';
    static protected $_origClass = __CLASS__;

    static protected $_clientCache = array();
    static protected $_pageId;
    static protected $_connId;

    protected $_messages = array();
    /**
     * - id
     * - session_id
     * - status
     * - handover
     * - admin_user_id
     * - customer_id
     * - create_at
     * - update_at
     */

    /**
     * Get or create client record for current browser session
     */
    static public function sessionClient()
    {
        $sessId = BSession::i()->sessionId();
        $client = static::load($sessId, 'session_id');
        if (!$client) {
            $client = static::create(array(
                'session_id' => $sessId,
                'remote_ip' => BRequest::i()->ip(),
            ))->save();
        }
        if (!$client->get('admin_user_id') && class_exists('FCom_Admin_Model_User')) {
            $userId = FCom_Admin_Model_User::i()->sessionUserId();
            if ($userId) {
                $client->set('admin_user_id', $userId);
            }
        }
        if (!$client->get('customer_id') && class_exists('FCom_Customer_Model_Customer')) {
            $custId = FCom_Customer_Model_Customer::i()->sessionUserId();
            if ($custId) {
                $client->set('customer_id', $custId);
            }
        }
        return $client;
    }

    /**
     * Get client by id or session_id
     */
    static public function getClient($clientId)
    {
        if (is_object($clientId) && $clientId instanceof FCom_PushServer_Model_Client) {
            return $clientId;
        }
        if (!empty(static::$_clientCache[$clientId])) {
            return static::$_clientCache[$clientId];
        }
        $client = false;
        if (is_numeric($clientId)) {
            $client = static::load($clientId);
        } elseif (is_string($clientId)) {
            $client = static::load($clientId, 'session_id');
        }
        static::$_clientCache[$clientId] = $client;
        return $client;
    }

    static public function getPageId()
    {
        return static::$_pageId;
    }

    static public function getConnId()
    {
        return static::$_connId;
    }

    static public function findByAdminUser($user)
    {
        if (is_object($user)) {
            $user = $user->id;
        }
        $result = static::orm()->where('admin_user_id', $user)->find_many_assoc('session_id');
        return $result;
    }

    static public function findByCustomer($customer)
    {
        if (is_object($customer)) {
            $customer = $customer->id;
        }
        return static::orm()->where('customer_id', $customer)->find_many_assoc('session_id');
    }

    public function processRequest($request)
    {
        $client = FCom_PushServer_Model_Client::i()->sessionClient();

        if (!isset($request['page_id']) || !isset($request['conn_id'])) {
            $client->send(array(
                'signal' => 'error',
                'description' => 'Missing page_id or conn_id',
            ));
            return;
        }
        static::$_pageId = $request['page_id'];
        static::$_connId = $request['conn_id'];

        if (empty($request['messages'])) {
            return $this;
        }
        $services = FCom_PushServer_Main::i()->getServices();
        foreach ($request['messages'] as $message) {
            try {
                foreach ($services as $service) {
                    if ($service['channel'] !== $message['channel']
                        && !($service['is_pattern'] && preg_match($service['channel'], $message['channel']))
                    ) {
                        continue;
                    }
                    if (is_callable($service['callback'])) {
                        call_user_func($service['callback'], $message);
                        continue;
                    }
                    if (!class_exists($service['callback'])) {
                        continue;
                    }
                    $class = $service['callback'];
                    $instance = $class::i();
                    if (!($instance instanceof FCom_PushServer_Service_Abstract)) {
                        //TODO: exception?
                        continue;
                    }

                    $instance->setMessage($message, $client);

                    if (!$instance->onBeforeDispatch()) {
                        continue;
                    }

                    if (!empty($message['signal'])) {
                        $method = 'signal_' . $message['signal'];
                        if (!method_exists($class, $method)) {
                            $method = 'onUnknownSignal';
                        }
                    } else {
                        $method = 'onUnknownSignal';
                    }

                    $instance->$method();

                    $instance->onAfterDispatch();
                }
            } catch (Exception $e) {
                $client->send(array(
                    'ref_seq' => !empty($message['seq']) ? $message['seq'] : null,
                    'ref_signal' => !empty($message['signal']) ? $message['signal'] : null,
                    'signal' => 'error',
                    'description' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                ));
            }
        }

        return $this;
    }

    /**
     * Check in the client
     *
     */
    public function checkIn()
    {
        $oldPages = $newPages = (array) $this->getData('pages');
        $oldConnections = !empty($oldPages[static::$_pageId]['connections'])
            ? $oldPages[static::$_pageId]['connections'] : array();

        foreach ($newPages as $pageId => $page) { // some cleanup
            if (empty($page['connections'])) {
                unset($newPages[$pageId]);
            }
        }

        foreach ($oldConnections as $connId => $conn) { // reset old connections
            $newPages[static::$_pageId]['connections'][$connId] = 0;
        }
        $newPages[static::$_pageId]['connections'][static::$_connId] = 1; // set new connection

        $this->setData('pages', $newPages)->save(); // save new state

        if (!$oldPages) { // is this first connection for the client
            $this->subscribe();
            $this->set('status', 'online')->save(); // set as connected
        } elseif ($oldConnections) { // are there already connections for this page
            $start = microtime(true);
            $connKey = 'pages/'.static::$_pageId.'/connections';
            while (true) {
                $this->fetchCustomData(); // update connections
                $newConnections = $this->getData($connKey);
                if (!$newConnections || sizeof($newConnections) === 1) { // only this connection left or no connections at all
                    break;
                }
                if (microtime(true) - $start > 1) { // timeout for waiting for other connections to reset
                    foreach ($newConnections as $connId => $conn) { // remove old connections
                        unset($newConnections[$connId]);
                    }
                    $this->setData($connKey, $newConnections)->save();
                    break;
                }
                usleep(300000);
            }
        }
//BDebug::dump($this->getData('pages'));
        return $this;
    }

    public function waitForMessages()
    {
        $delay = BConfig::i()->get('modules/FCom_PushServer/delay_microsec');
        $start = time();
        while (true) {
            // if (time() - $start > 60) {
            //     break;
            // }
            if (connection_aborted()) {
                break;
            }
            $this->_messages = $this->sync();
            if ($this->_messages) {
                break;
            } else {
                usleep($delay ? $delay : 300000);
            }
        }
        return $this;
    }

    /**
     * Get messages to be sent to browser
     */
    public function sync()
    {
        $messageModels = FCom_PushServer_Model_Message::i()->orm('m')
            ->where('client_id', $this->id)
            ->where('status', 'published')
            ->find_many_assoc();
        $messages = array();
        foreach ($messageModels as $model) {
            $model->set('status', 'sent')->save();
            $message = (array) BUtil::fromJson($model->get('data_serialized'));
            //$message['ts'] = $model->get('create_at');
            $messages[] = $message;
            if (empty($message['seq'])) {
                $model->delete();
            }
        }

        $this->fetchCustomData();
        $connKey = 'pages/'.static::$_pageId.'/connections';
        $connections = $this->getData($connKey);
        if (empty($connections[static::$_connId])) { // this connection was removed
            unset($connections[static::$_connId]);
            $this->setData($connKey, $connections);
            $messages[] = array('channel' => 'session', 'signal' => 'noop');
        }
        // foreach ($connections as $connId => $conn) {
        //     if ($connId > static::$_connId) { // a new connection was made
        //         $messages[] = array('channel' => 'session', 'signal' => 'noop');
        //         break;
        //     }
        // }

        return $messages;
    }

    /**
     * Check out the client
     */
    public function checkOut()
    {
        $pages = $this->getData('pages');
        unset($pages[static::$_pageId]['connections'][static::$_connId]);
        if (empty($pages[static::$_pageId]['connections'])) {
            unset($pages[static::$_pageId]);
        }
        $this->setData('pages', $pages)->save();
        return $this;
    }

    public function fetchCustomData()
    {
        $clientUpdate = static::orm()->select('data_serialized')->where('id', $this->id)->find_one();
        if ($clientUpdate) { // another connection just connected
            $data = (array) BUtil::fromJson($clientUpdate->get('data_serialized'));
            $this->set(static::$_dataCustomField, $data);
        }
        return $this;
    }

    /**
     * Subscribe the client to a channel
     */
    public function subscribe($channel = null)
    {
        if (is_null($channel)) {
            $channel = $this->getChannel();
        }
        if (!is_object($channel)) {
            $channel = FCom_PushServer_Model_Channel::i()->getChannel($channel, true);
        }
        $hlp = FCom_PushServer_Model_Subscriber::i();
        $data = array('client_id' => $this->id, 'channel_id' => $channel->id);
        $subscriber = $hlp->load($data);
        if (!$subscriber) {
            $subscriber = $hlp->create($data)->save();
        }
        return $this;
    }

    /**
     * Unsubscribe the client from the channel
     */
    public function unsubscribe($channel)
    {
        if (!is_object($channel)) {
            $channel = FCom_PushServer_Model_Channel::i()->getChannel($channel, true);
        }
        $data = array('client_id' => $this->id, 'channel_id' => $channel->id);
        FCom_PushServer_Model_Subscriber::i()->delete_many($data);
        return $this;
    }

    /**
     * Send a message to the client
     */
    public function send($message)
    {
        $this->getChannel()->send($message);
        return $this;
    }

    public function getChannel()
    {
        return FCom_PushServer_Model_Channel::i()->getChannel('session:' . $this->session_id, true);
    }

    public function getMessages ()
    {
        return $this->_messages;
    }
}
