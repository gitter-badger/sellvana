<?php
/*
- id
- status
- num_participants
- create_at // when the session was created
- update_at // when the session had last message
*/
class FCom_AdminChat_Model_Chat extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_adminchat_chat';
    static protected $_origClass = __CLASS__;

    public function getChannel()
    {
        return FCom_PushServer_Model_Channel::i()->getChannel('adminchat:' . $this->id, true);
    }

    static public function start($remoteUser)
    {
        // get local user
        $user = FCom_Admin_Model_User::i()->sessionUser();
        // check if there's existing chat with only 2 users
        $chats = static::orm('c')->where('num_participants', 2)
            ->join('FCom_AdminChat_Model_Participant', array('p1.chat_id','=','c.id'), 'p1')
            ->join('FCom_AdminChat_Model_Participant', array('p2.chat_id','=','c.id'), 'p2')
            ->where('p1.user_id', $user->id)
            ->where('p2.user_id', $remoteUser->id)
            ->find_many_assoc();
        if ($chats) {
            foreach ($chats as $chat) {
                //$chat->delete();
                return $chat;
            }
        }
        $chat = static::create(array(
            'owner_user_id' => $user->id,
        ))->save();

        $chat->addParticipant($user);
        $chat->addParticipant($remoteUser);
        $chat->save();

        return $chat;
    }

    public function addParticipant($user)
    {
        $clients = FCom_PushServer_Model_Client::i()->findByAdminUser($user);
        $channel = $this->getChannel();

        foreach ($clients as $client) {
            $client->subscribe($channel);
        }

        $hlp = FCom_AdminChat_Model_Participant::i();
        $data = array('chat_id' => $this->id, 'user_id' => $user->id);
        $participant = $hlp->load($data);
        if (!$participant) {
            $data['status'] = 'online';
            $participant = $hlp->create($data)->save();
            $this->add('num_participants');
        }

        return $this;
    }

    public function removeParticipant($user)
    {
        $clients = FCom_PushServer_Model_Client::i()->findByAdminUser($user);
        $channel = $this->getChannel();
        foreach ($clients as $client) {
            $client->unsubscribe($channel);
        }

        FCom_AdminChat_Model_Participant::i()->delete_many(array(
            'chat_id' => $this->id,
            'user_id' => $user->id,
        ));

        $this->add('num_participants', -1);

        return $this;
    }

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $this->set('status', 'active', null);
        $this->set('create_at', BDb::now(), null);
        $this->set('update_at', BDb::now());

        return true;
    }

    public function onBeforeDelete()
    {
        $this->getChannel()->delete();
    }
}
