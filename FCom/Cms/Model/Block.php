<?php

class FCom_Cms_Model_Block extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_cms_block';
    protected static $_origClass = __CLASS__;

    public function render()
    {
        $layout = BLayout::i();
        $viewName = 'cms_block_'.$this->handle.'_'.strtotime($this->update_dt);
        $layout->addView($viewName, array(
            'renderer'    => 'BPHPTAL::renderer',
            'source'      => $this->content,
            'source_name' => $viewName,
        ));
        return $layout->view($viewName)->render();
    }

    public function __toString()
    {
        return $this->render();
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        if (!$this->get('create_dt')) {
            $this->set('create_dt', BDb::now());
        }
        $this->set('update_dt', BDb::now());
        return true;
    }

    public function afterSave()
    {
        parent::afterSave();

        $user = FCom_Admin_Model_User::i()->sessionUser();
        $hist = FCom_Cms_Model_BlockHistory::i()->create(array(
            'block_id' => $this->id,
            'user_id' => $user ? $user->id : null,
            'username' => $user ? $user->username : null,
            'version' => $this->version,
            'comments' => $this->version_comments,
            'ts' => BDb::now(),
            'data' => BUtil::toJson(BUtil::arrayMask($this->as_array(),
                'handle,description,content')),
        ))->save();
    }

    public static function install()
    {
        $tBlock = static::table();
        BDb::run("

CREATE TABLE IF NOT EXISTS {$tBlock} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `content` text COLLATE utf8_unicode_ci,
  `layout_update` text COLLATE utf8_unicode_ci,
  `version` int(11) NOT NULL,
  `create_dt` datetime DEFAULT NULL,
  `update_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        ");
    }
}