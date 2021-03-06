<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Blog_Model_Tag
 *
 * @property int $id
 * @property int $tag_key
 * @property int $tag_name
 *
 * DI
 * @property Sellvana_Blog_Model_Tag $Sellvana_Blog_Model_Tag
 */
class Sellvana_Blog_Model_Tag extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_blog_tag';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['tag_key'],
    ];
    public function getUrl()
    {
        return $this->BApp->href('blog/tag/' . $this->get('tag_key'));
    }

    public function getTagCounts()
    {
        return $this->Sellvana_Blog_Model_Tag->orm('t')
            ->join('Sellvana_Blog_Model_PostTag', ['pt.tag_id', '=', 't.id'], 'pt')
            ->join('Sellvana_Blog_Model_Post', ['p.id', '=', 'pt.post_id'], 'p')
            ->where_in('p.status', ['published'])
            ->group_by('t.id')
            ->select('t.id')->select('tag_name')->select('tag_key')->select('(count(*))', 'cnt')
            ->find_many_assoc('id');
    }

}
