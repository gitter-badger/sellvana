<?php

class FCom_Cms_Model_Page extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_page';
    protected static $_origClass = __CLASS__;

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        if (!$this->get('create_dt')) {
            $this->set('create_dt', BDb::now());
        }
        $this->set('update_dt', BDb::now());
        $this->add('version');
        return true;
    }

    public function afterSave()
    {
        $user = FCom_Admin_Model_User::i()->sessionUser();
        $hist = FCom_Cms_Model_PageHistory::i()->create(array(
            'page_id' => $this->id,
            'user_id' => $user ? $user->id : null,
            'username' => $user ? $user->username : null,
            'version' => $this->version,
            'comments' => $this->version_comments,
            'ts' => BDb::now(),
            'data' => BUtil::toJson(BUtil::arrayMask($this->as_array(),
                'handle,title,content,layout_update,meta_title,meta_description,meta_keywords')),
        ));
    }
}
