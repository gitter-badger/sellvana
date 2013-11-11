<?php

class FCom_ProductReviews_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'prodreviews';
    protected $_modelClass = 'FCom_ProductReviews_Model_Review';
    protected $_mainTableAlias = 'prr';
	protected $_gridTitle = 'Product Reviews';
    protected $_recordName = 'Product Review';

    public function gridConfig($productModel = false)
    {
        $formUrl = BApp::href("prodreviews/form");
        $config = parent::gridConfig();
        $columns = array(
            array('name'=>'id','label'=>'ID', 'width'=>55),
            array('name'=>'title', 'label'=>'Title', 'width'=>250, 'editable'=>true),
            array('name'=>'rating', 'label'=>'Rating', 'width'=>60, 'editable'=>true),
            array('name'=>'helpful','label'=>'Helpful', 'width'=>60, 'editable'=>true),
            array('name'=>'approved', 'label'=>'Approved', 'editable'=>true, 'options'=>array('1'=>'Yes','0'=>'No')),
            array('name'=>'_actions', 'label'=>'Actions', 'sortable'=>false, 'data'=>array('edit'=>array('href'=>BApp::href('/prodreviews/form?id='), 'col'=>'id'),'delete'=>true))
        );

        $config['filters'] = array(
            array('field'=>'title', 'type'=>'text'),
            '_quick'=>array('expr'=>'title like ? or id=?', 'args'=>array('%?%', '?'))
        );

        //$config['autowidth'] = false;
        $config['caption'] = 'All review';
        //$config['multiselect'] = false;
        //$config['height'] = '100%';
        $config['columns'] = $columns;
        //$config['navGrid'] = array('add'=>false, 'edit'=>true, 'del'=>true);

        if ($productModel) {
            $config['id'] = 'products_reviews';
            $config['columns']['product_name'] = array('label'=>'Product name', 'width'=>250, 'editable'=>false);
            $config['data_mode'] = 'local';
            $config['editurl'] = '';
            $config['url'] = '';
            $config['filters'][] = array('field'=>'product_name', 'type'=>'text');
            $config['custom'] = array('personalize'=>true);
            $orm = FCom_ProductReviews_Model_Review::orm('pr')->where('product_id', $productModel->id())
                ->join('FCom_Catalog_Model_Product', array('p.id','=','pr.product_id'), 'p')
                ->select('pr.*')->select('p.product_name');

            $data = BDb::many_as_array($orm->find_many());
            /*$columnKeys = array_keys($config['grid']['columns']);
            foreach($data as &$prod){
                foreach($prod as $k=>$p) {
                    if (!in_array($k, $columnKeys)) {
                        unset($prod[$k]);
                    }
                }
            }*/
            //print_r($data);
            $config['data'] = $data;
        } else {
            //$config['custom'] = array('personalize'=>true, 'autoresize'=>true, 'hashState'=>true, 'export'=>true, 'dblClickHref'=>$formUrl.'?id=');
        }

        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'title'=>$m->id ? 'Edit Product Review: '.$m->title : 'Create New Product Review',
            'actions'=>array(
                'back'=>'<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href("prodreviews").'\'"><span>' .  BLocale::_('Back to list') . '</span></button>',
                'delete'=>'<button type="submit" class="st2 sz2 btn" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>' .  BLocale::_('Delete') . '</span></button>',
                'save'=>'<button type="submit" class="st1 sz2 btn btn-primary" onclick="return adminForm.saveAll(this)"><span>' .  BLocale::_('Save') . '</span></button>',
            ),
        ));

    }

}
