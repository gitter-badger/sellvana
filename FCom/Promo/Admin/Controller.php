<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * @property FCom_Promo_Model_Promo $FCom_Promo_Model_Promo
 * @property FCom_Promo_Model_Media $FCom_Promo_Model_Media
 * @property FCom_Promo_Model_Product $FCom_Promo_Model_Product
 * @property FCom_Promo_Model_Group $FCom_Promo_Model_Group
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Admin_View_Grid $FCom_Admin_View_Grid
 * @property FCom_Promo_Model_Coupon $FCom_Promo_Model_Coupon
 */
class FCom_Promo_Admin_Controller extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'promo';
    protected $_modelClass = 'FCom_Promo_Model_Promo';
    protected $_gridLayoutName = '/promo';
    protected $_gridHref = 'promo';
    protected $_gridTitle = 'Promotions';
    protected $_recordName = 'Promotion';
    protected $_mainTableAlias = 'p';
    protected $_navPath = 'catalog/promo';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'id', 'width' => 55, 'sorttype' => 'number'],
            ['name' => 'description', 'label' => 'Description', 'index' => 'description', 'width' => 250],
            ['name' => 'from_date', 'label' => 'Start Date', 'index' => 'from_date', 'formatter' => 'date'],
            ['name' => 'to_date', 'label' => 'End Date', 'index' => 'to_date', 'formatter' => 'date'],
            ['type' => 'input', 'name' => 'status', 'label' => 'Status', 'index' => 'p.status',
                'editable' => true, 'multirow_edit' => true, 'editor' => 'select',
                'options' => $this->FCom_Promo_Model_Promo->fieldOptions('status')
            ],
            ['name' => 'details', 'label' => 'Details', 'index' => 'details', 'hidden' => true],
            ['type' => 'btn_group', 'buttons' => [
                ['name' => 'edit'],
                ['name' => 'delete'],
            ]],
        ];
        $config['actions'] = [
            'edit' => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'from_date', 'type' => 'date-range'],
            ['field' => 'to_date', 'type' => 'date-range'],
            ['field' => 'status', 'type' => 'multiselect'],
            ['field' => 'description', 'type' => 'text'],
        ];
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        //load attachments
        $orm->select("(select group_concat(a.file_name separator ', ') from " .
            $this->FCom_Promo_Model_Media->table() .
            " pa inner join fcom_media_library a on a.id=pa.file_id where pa.promo_id=p.id)",
            'attachments')
        ;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->title = $m->id ? 'Edit Promo: ' . $m->description : 'Create New Promo';
    }

    public function formPostBefore($args)
    {
        parent::formPostBefore($args);
        if (!empty($args['data']['save_as'])) {
            switch ($args['data']['save_as']) {
                case 'copy': $args['model'] = $args['model']->createClone(); $id = $args['model']->id(); break;
                case 'template': $args['data']['model']['status'] = 'template'; break;
            }
        }
        if (!empty($args['data']['model'])) {
            $args['data']['model'] = $this->BLocale->parseRequestDates($args['data']['model'], 'from_date,to_date');
            $args['model']->set($args['data']['model']);
        }
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        $this->processGroupsPost($args['model'], $_POST);
        $this->processMediaPost($args['model'], $_POST);
    }

    public function processGroupsPost($model, $data)
    {
        $groups     = $model->groups();
        $groupData  = [];
        $groupProds = $this->FCom_Promo_Model_Product->orm()->where('promo_id', $model->id())->find_many();
        foreach ($groupProds as $gp) {
            $groupData[$gp->group_id][$gp->product_id] = 1;
        }
        if (!empty($data['_del_group_ids'])) {
            $deleteGroups = explode(',', trim($data['_del_group_ids'], ','));
            $this->FCom_Promo_Model_Group->delete_many([
                  'id'       => $deleteGroups,
                  'promo_id' => $model->id,
                ]
            );
            foreach ($deleteGroups as $gId) {
                unset($groups[$gId], $groupData[$gId]);
            }
        }
        $gIdMap = [];
        if (!empty($data['group'])) {
            foreach ($data['group'] as $gId => $g) {
                if ($gId < 0) {
                    $group  = $this->FCom_Promo_Model_Group->create([
                        'promo_id'   => $model->id,
                        'group_type' => $g['group_type'],
                        'group_name' => $g['group_name'],
                    ])->save();
                    $gIdMap[$gId]       = $group->id;
                    $groups[$group->id] = $group;
                } elseif (!empty($groups[$gId])) {
                    $groups[$gId]->set('group_name', $g['group_name'])->save();
                }

                if (!empty($g['product_ids_add'])) {
                    foreach (explode(',', $g['product_ids_add']) as $pId) {
                        if (!$pId) {
                            continue;
                        }
                        //list($gId, $pId) = explode(':', $gp);
                        if (!empty($groupData[$gId][$pId])) {
                            continue;
                        }
                        $this->FCom_Promo_Model_Product->create([
                            'promo_id'   => $model->id,
                            'group_id'   => $gId,
                            'product_id' => $pId,
                        ])->save();
                        $groupData[$gId][$pId] = 1;
                    }
                }

                if (!empty($g['product_ids_remove'])) {
                    $pIds = [];
                    foreach (explode(',', $g['product_ids_remove']) as $pId) {
                        if (!empty($groupData[$gId][$pId])) {
                            $pIds[] = $pId;
                            unset($groupData[$gId][$pId]);
                        }
                    }
                    if ($pIds) {
                        $this->FCom_Promo_Model_Product->delete_many([
                            'promo_id'   => $model->id,
                            'group_id'   => $gId,
                            'product_id' => $pIds,
                        ]);
                    }
                }

            }
        }

        return $this;
    }

    public function processMediaPost($model, $data)
    {
        $hlp = $this->FCom_Promo_Model_Media;
        if (!empty($data['grid']['promo_attachments']['del'])) {
            $hlp->delete_many([
                'promo_id' => $model->id,
                'file_id' => explode(',', $data['grid']['promo_attachments']['del']),
            ]);
        }
        if (!empty($data['grid']['promo_attachments']['add'])) {
            $oldAtt = $hlp->orm()->where('promo_id', $model->id)->find_many_assoc('file_id');
            foreach (explode(',', $data['grid']['promo_attachments']['add']) as $attId) {
                if ($attId && empty($oldAtt[$attId])) {
                    $m = $hlp->create([
                        'promo_id' => $model->id,
                        'file_id' => $attId,
                    ])->save();
                }
            }
        }
        return $this;
    }

    public function productGridConfig($model, $type, $groupId = null)
    {
        static $groups = [], $groupData = [];

        if ($model && $model->id && empty($groups[$model->id])) {
            $groups[$model->id] = $this->FCom_Promo_Model_Promo->load($model->id)->groups();
            $data = $this->FCom_Promo_Model_Product->orm()->table_alias('pp')
                ->join('FCom_Catalog_Model_Product', ['p.id', '=', 'pp.product_id'], 'p')
                ->select('pp.group_id')
                ->select('p.id')->select('p.product_name')->select('p.product_sku')
                ->where('promo_id', $model->id)->find_many();
            foreach ($data as $p) {
                $groupData[$p->group_id][] = $p->as_array();
            }

        }

        $groupName = $model ? htmlspecialchars($groups[$model->id][$groupId]->group_name)
                            : 'Group ' . abs($groupId);
        $gridId    = 'promo_products_' . $type . '_' . $groupId;
        $config    = parent::gridConfig();
        unset($config['orm']);
        $config['id']        = $gridId;
        $config['data']      = !empty($groupData[$groupId]) ? $groupData[$groupId] : [];
        $config['data_mode'] = 'local';
        $config['columns']   = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 40, 'hidden' => true],
            ['name' => 'product_name', 'label' => 'Name', 'index' => 'product_name',
                'width' => 450, 'addable' => true],
            ['name' => 'product_sku', 'label' => 'SKU', 'index' => 'product_sku', 'width' => 70],
        ];
        $actions = [
            'add'    => ['caption' => 'Add products'],
            'delete' => ['caption' => 'Remove products'],
        ];
        $config['actions'] = $actions;
        $config['filters'] = [
            ['field' => 'product_name', 'type' => 'text']
        ];
        $config['grid_before_create'] = $gridId . '_register';

//        $config = array(
//            'grid' => array(
//                'id'            => $gridId,
//                'data'          => !empty($groupData[$groupId]) ? $groupData[$groupId] : array(),
//                'datatype'      => 'local',
//                'caption'       =>
//                    "<input type='text' name='group[$groupId][group_name]' value='$groupName'>"
//                    ."<input type='hidden' name='group[$groupId][group_type]' value='$type'>"
//                    ."<input type='hidden' name='_group_id' value='$groupId'>"
//                    .($type==='buy' && !empty($model) && $model->buy_group!=='one'?" <button type='button' class='sz2 st2 btn' onclick=\"return removeGroup(this);\">Remove</button>":''),
//                'colModel'      => array(
//                    array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>40, 'hidden'=>true),
//                    array('name'=>'product_name', 'label'=>'Name', 'index'=>'product_name', 'width'=>250),
//                    array('name'=>'product_sku', 'label'=>'Mfr Part #', 'index'=>'product_sku', 'width'=>70),
//                ),
//                'rowNum'        => 10,
//                'sortname'      => 'p.product_name',
//                'sortorder'     => 'asc',
//                'autowidth'     => false,
//                'multiselect'   => true,
//                'multiselectWidth' => 30,
//                'shrinkToFit' => true,
//                'forceFit' => true,
//            ),
//            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>false),
//            array('navButtonAdd', 'caption' => '', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Products'),
//            array('navButtonAdd', 'caption' => '', 'buttonicon'=>'ui-icon-trash', 'title' => 'Remove Products'),
//            'js' => array(
//                "if (typeof productLibrary !== 'undefined'){ productLibrary.initTargetGrid('#$gridId'); }",
//            ),
//        );


        return [
            'config'  => $config,
            "group_name" => $groupName,
            'js' => [
                "if (typeof productLibrary !== 'undefined'){ productLibrary.initTargetGrid('#$gridId'); }",
            ]
        ];
    }

    public function action_form_group()
    {
        $this->BResponse->nocache();
        $r = $this->BRequest;
        $this->view('jqgrid')->set('config', $this->productGridConfig(false, $r->get('type'), $r->get('group_id')));
        $this->BLayout->setRootView('jqgrid');
    }

    public function action_form_products()
    {
        $orm = $this->FCom_Catalog_Model_Product->orm()->table_alias('p')->select('p.*')
            ->join('FCom_Promo_Model_Product', ['pp.product_id', '=', 'p.id'], 'pp')
            ->select('pp.qty')
            ->join('FCom_Promo_Model_Promo', ['promo.id', '=', 'pp.promo_id'], 'promo')
        ;
        $data = $this->FCom_Admin_View_Grid->processORM($orm, 'FCom_Promo_Admin_Controller::action_form_products');
        $this->BResponse->json($data);
    }

    public function onAttachmentsGridConfig($args)
    {
        array_splice($args['config']['grid']['colModel'], -1, 0, [
            ['name'          => 'promo_status',
                'label'         => 'Status',
                'width'         => 80,
                'options'       => ['' => 'All', 'A' => 'Active', 'I' => 'Inactive'],
                'editable'      => true,
                'edittype'      => 'select',
                'searchoptions' => ['defaultValue' => 'A']
            ],
        ]);
    }

    public function onAttachmentsGridGetORM($args)
    {
        $args['orm']->join('FCom_Promo_Model_Media', ['pa.file_id', '=', 'a.id',  ], 'pa')
            ->where_null('pa.promo_id')
            ->select(['pa.promo_status']);
    }

    public function onAttachmentsGridUpload($args)
    {
        $hlp = $this->FCom_Promo_Model_Media;
        $id = $args['model']->id;
        if (!$hlp->loadWhere(['promo_id' => null, 'file_id' => $id])) {
            $hlp->create(['file_id' => $id])->save();
        }
    }

    public function onAttachmentsGridEdit($args)
    {
        $r = $this->BRequest;
        $this->FCom_Promo_Model_Media
            ->loadWhere(['promo_id' => null, 'file_id' => $args['model']->id])
            ->set([
                'promo_status' => $r->post('promo_status'),
            ])
            ->save();
    }

    public function attachmentGridConfig($model)
    {
        return [
            'grid' => [
                'id' => 'promo_attachments',
                'caption' => 'Promotion Attachments',
                'datatype' => 'local',
                'data' => $this->BDb->many_as_array($model->mediaORM('a')->select('a.id')->select('a.file_name')->find_many()),
                'colModel' => [
                    ['name' => 'id', 'label' => 'ID', 'width' => 400, 'hidden' => true],
                    ['name' => 'file_name', 'label' => 'File Name', 'width' => 400],
                ],
                'multiselect' => true,
                'multiselectWidth' => 30,
                'shrinkToFit' => true,
                'forceFit' => true,
            ],
            'navGrid' => ['add' => false, 'edit' => false, 'search' => false, 'del' => false, 'refresh' => false],
            ['navButtonAdd', 'caption' => 'Add', 'buttonicon' => 'ui-icon-plus', 'title' => 'Add Attachments to Promotion', 'cursor' => 'pointer'],
            ['navButtonAdd', 'caption' => 'Remove', 'buttonicon' => 'ui-icon-trash', 'title' => 'Remove Attachments From Promotion', 'cursor' => 'pointer'],
        ];
    }

    public function action_coupons_grid()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        if(!$id){
            $html = $this->_("Promotion id not found");
            $status = 'error';
            $this->BResponse->status(400, $html, false);
        } else {
            $status = "success";
            $gridDataUrl = $this->BApp->href($this->_gridHref . '/coupons_grid_data');
            $config = [
                'id' => $this->FCom_Promo_Model_Coupon->origClass(),
                'orm' => $this->FCom_Promo_Model_Coupon->orm(),
                'data_url' => $gridDataUrl,
                'edit_url' => $gridDataUrl,
                'grid_url' => null,
                'form_url' => null,
                'columns' => [
                    ['type' => 'row_select'],
                    ['name' => 'id', 'label' => 'ID', 'hidden' => true],
                    ['name' => 'code', 'label' => 'Code', 'index' => 'code', 'width' => 100,
                        'editable' => 'inline', 'addable' => true, 'validation' => ['required' => true]],
                    ['name' => 'uses_per_customer', 'label' => 'Uses Per Customer', 'index' => 'uses_per_customer',
                                            'editable' => 'inline', 'addable' => true],
                    ['name' => 'uses_total', 'label' => 'Uses total', 'index' => 'uses_total', 'sorttype' => 'number',
                                            'editable' => 'inline', 'addable' => true],
                    //['name' => 'total_used', 'label' => 'Used', 'index' => 'total_used', 'sorttype' => 'number'],
                    ['type' => 'btn_group', 'buttons' => [['name' => 'delete']]],
                ],
                'actions' => [
                    'delete' => true,
                    'new' => ['caption' => 'Add New Coupon'],
                    'edit' => true,
                ],
                'data_mode' => 'local',
                'filters' => [
                    ['field' => 'code', 'type' => 'text'],
                    ['field' => 'uses_per_customer', 'type' => 'number-range'],
                    ['field' => 'uses_total', 'type' => 'number-range'],
                    //['field' => 'total_used', 'type' => 'number-range'],
                ]
            ];
            $html = $this->view('promo/coupons/grid')->set('grid',['config' => $config])->render();
        }
        $this->BResponse->json(['status' => $status, 'html'=>$html]);
    }
    public function action_coupons_generate__POST()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        if (!$id) {
            $html = $this->_("Promotion id not found");
            $status = 'error';
            $this->BResponse->status(400, $html, false);
        } else {
            $data = $r->post('model');
            $pattern = $data['code_pattern'];
            $length = $data['code_length'];
            $usesPerCustomer = $data['code_uses_per_customer'];
            $usesTotal = $data['code_uses_total'];
            $couponCount = $data['coupon_count'];
            $model = $this->FCom_Promo_Model_Coupon;
            $model->generateCoupons([
                'promo_id' => $id,
                'pattern' => $pattern,
                'length' => $length,
                'uses_per_customer' => $usesPerCustomer,
                'uses_total' => $usesTotal,
                'count' => $couponCount
            ]);
        }
    }
    public function action_coupons_generate()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        if (!$id) {
            $html = $this->_("Promotion id not found");
            $status = 'error';
            $this->BResponse->status(400, $html, false);
        } else {
            $status = "success";
            $html = $this->view('promo/coupons/generate')->render();
        }
        $this->BResponse->json(['status' => $status, 'html' => $html]);
    }

    public function action_coupons_import()
    {
        $r = $this->BRequest;
        $id = $r->get('id');
        if (!$id) {
            $html = $this->_("Promotion id not found");
            $status = 'error';
            $this->BResponse->status(400, $html, false);
        } else {
            $status = "success";
            $html = $this->view('promo/coupons/import')->render();
        }
        $this->BResponse->json(['status' => $status, 'html' => $html]);
    }
}
