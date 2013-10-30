<?php

class FCom_Blog_Model_Tag extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_blog_tag';
    static protected $_origClass = __CLASS__;

    public function getUrl()
    {
        return BApp::href('blog/tag/' . $this->get('tag_key'));
    }

    static public function getTagCounts()
    {
        return FCom_Blog_Model_Tag::i()->orm('t')
            ->join('FCom_Blog_Model_PostTag', array('pt.tag_id','=','t.id'), 'pt')
            ->join('FCom_Blog_Model_Post', array('p.id','=','pt.post_id'), 'p')
            ->where_in('p.status', array('published'))
            ->group_by('t.id')
            ->select('t.id')->select('tag_name')->select('(count(*))', 'cnt')
            ->find_many_assoc('id');
    }

}
