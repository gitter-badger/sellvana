<?php

class FCom_PushServer_Model_Channel extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_pushserver_channel';
    static protected $_origClass = __CLASS__;

    static protected $_channelCache = array();

    /**
     * - id
     * - channel_name
     * - channel_out
     * - create_at
     * - update_at
     * - data_serialized
     *   - permissions
     *     - can_subscribe
     *       - everyone
     *       - admin_user
     *       - customer
     *       - none
     *     - can_publish
     *       - everyone
     *       - admin_user
     *       - customer
     *       - none
     *   - subscribers
     *   - message_queue
     */

    public function getChannel($channel, $create=false)
    {
        if (is_object($channel) && ($channel instanceof FCom_PushServer_Model_Channel)) {
            return $channel;
        }
        if (!empty(static::$_channelCache[$channel])) {
            return static::$_channelCache[$channel];
        }
        if (is_string($channel)) {
            $channelName = $channel;
            $channel = static::load($channel, 'channel_name');
            if (!$channel) {
                $channel = static::create(array('channel_name' => $channelName))->save();
            }
            static::$_channelCache[$channelName] = $channel;
        }
        return $channel;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('create_at', BDb::now(), null);
        $this->set('update_at', BDb::now());

        return true;
    }

    public function onBeforeDelete()
    {
        if (!parent::onBeforeDelete()) return false;

        $this->send(array('signal' => 'delete'));

        return true;
    }

    public function listen($callback)
    {
        $channelName = $this->channel_name;
        BEvents::i()->on('FCom_PushServer_Model_Channel::send:' . $channelName, $callback);
        return $this;
    }

    public function subscribe($client)
    {
        FCom_PushServer_Model_Client::i()->getClient($client)->subscribe($this);
        return $this;
    }

    public function unsubscribe($client)
    {
        FCom_PushServer_Model_Client::i()->getClient($client)->unsubscribe($this);
        return $this;
    }

    public function send($message, $client = null)
    {
        if (empty($message['channel'])) {
            $message['channel'] = $this->channel_name;
        }

        BEvents::i()->fire(__METHOD__ . ':' . $this->channel_name, array(
            'channel' => $this,
            'message' => $message,
            'client'  => $client,
        ));

        $clientHlp = FCom_PushServer_Model_Client::i();
        $msgHlp = FCom_PushServer_Model_Message::i();

        $subscribers = FCom_PushServer_Model_Subscriber::i()->orm()->where('channel_id', $this->id)->find_many();
        foreach ($subscribers as $sub) {
            if ($client && $client->id === $sub->client_id) {
                continue;
            }
            $msg = $msgHlp->create(array(
                'seq' => !empty($message['seq']) ? $message['seq'] : null,
                'channel_id' => $this->id,
                'subscriber_id' => $sub->id,
                'client_id' => $sub->client_id,
                'page_id' => $clientHlp->getPageId(),
                'conn_id' => $clientHlp->getConnId(),
                'status' => 'published',
            ))->setData($message)->save();
        }
        return $this;
    }
}