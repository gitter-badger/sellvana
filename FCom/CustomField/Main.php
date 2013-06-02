<?php

class FCom_CustomField_Main extends BClass
{
    protected $_types;
    protected $_disabled;

    public static function bootstrap()
    {
        BEvents::i()
            ->on('FCom_Catalog_Model_Product::find_one.orm', 'FCom_CustomField_Main.productFindORM')
            ->on('FCom_Catalog_Model_Product::find_many.orm', 'FCom_CustomField_Main.productFindORM')
            // is there save on frontend?
            //->on('FCom_Catalog_Model_Product::afterSave', 'FCom_CustomField_Main.productAfterSave')
            ->on('FCom_Catalog_Model_Product::afterSave', 'FCom_CustomField_Main.productAfterSave')
        ;
    }

    public function disable($flag)
    {
        $this->_disabled = $flag;
        return $this;
    }

    public function productFindORM($args)
    {
        if ($this->_disabled) {
            return;
        }
        $tP = $args['orm']->table_alias();
        $args['orm']
            ->select($tP.'.*')
            ->left_outer_join('FCom_CustomField_Model_ProductField', array('pcf.product_id','=',$tP.'.id'), 'pcf')
        ;
        $fields = FCom_CustomField_Model_Field::i()->fieldsInfo('product', true);
        $args['orm']->select($fields);
    }

    public function productAfterSave($args)
    {
        $p = $args['model'];
        $data = $p->as_array();
        $fields = FCom_CustomField_Model_Field::i()->fieldsInfo('product', true);
        if (array_intersect($fields, array_keys($data))) {
            $custom = FCom_CustomField_Model_ProductField::i()->load($p->id, 'product_id');
            if (!$custom) {
                $custom = FCom_CustomField_Model_ProductField::i()->create();
            }
            $dataCustomKeys = array_intersect($fields, array_keys($data));
            $dataCustom = array();
            foreach($dataCustomKeys as $key) {
                $dataCustom[$key] = $data[$key];
            }
            //print_r($dataCustom);exit;
            $custom->set('product_id', $p->id)->set($dataCustom)->save();
        }
        // not deleting to preserve meta info about fields
    }

    public function hookCustomFieldFilters($args)
    {
        $category = false;
        if (is_object($args['category'])) {
            $category = $args['category'];
        }

        $customFields = FCom_CustomField_Model_Field::orm()
                ->where_in('facet_select', array('Inclusive','Exclusive'))
                ->where('frontend_show', 1)
                ->order_by_asc('sort_order')
                ->find_many();
        if (!$customFields) {
            return;
        }

        $filter = BRequest::get('f');
        $currentFilter = array();
        $excludeFilters = array();
        if (!empty($filter)) {
            foreach($filter as $fkey => $fval) {
                $fkey = urldecode($fkey);
                $field = FCom_CustomField_Model_Field::orm()->where('field_code', $fkey)->find_one();
                $currentFilter[$field->frontend_label][] =
                        array(
                            'key' => $field->field_code,
                            'facet_select' => $field->facet_select,
                            'value' => $fval
                            );
                if (is_array($fval)) {
                    foreach($fval as $fvalsingle)
                    $excludeFilters[$field->frontend_label][] = $fvalsingle;
                } else {
                    $excludeFilters[$field->frontend_label][] = $fval;
                }
            }
        }


        $groups = array();
        foreach($customFields as $cf) {
            if ($category) {
                $productOrm = $category->productsORM();
            } else {
                $productOrm = FCom_Catalog_Model_Product::orm();
            }
            $products = $productOrm->where_not_equal($cf->field_code, '')->group_by($cf->field_code)->find_many();
            if (empty($products)) {
                continue;
            }
            $values = array();
            foreach($products as $p) {
                if (isset($excludeFilters[$cf->frontend_label]) &&
                        in_array($p->{$cf->field_code}, $excludeFilters[$cf->frontend_label])
                ) {
                    continue;
                }
                $values[] = $p->{$cf->field_code};
            }
            if (empty($values)) {
                continue;
            }
            $groups[$cf->frontend_label] = array(
                'key'=>$cf->field_code,
                'facet_select' => $cf->facet_select,
                'values' => $values
                );
        }


        if (empty($groups) && empty($currentFilter)) {
            return;
        }
        BLayout::i()->view('customfields/filters')->selected_filters = $currentFilter;
        BLayout::i()->view('customfields/filters')->groups = $groups;
        return BLayout::i()->view('customfields/filters')->render();
    }
}
