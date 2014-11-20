<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_CustomField_Admin
 *
 * @property FCom_CustomField_Model_Field $FCom_CustomField_Model_Field
 * @property FCom_CustomField_Model_FieldOption $FCom_CustomField_Model_FieldOption
 * @property FCom_CustomField_Model_ProductField $FCom_CustomField_Model_ProductField
 * @property FCom_CustomField_Model_ProductVarfield $FCom_CustomField_Model_ProductVarfield
 * @property FCom_CustomField_Model_ProductVariant $FCom_CustomField_Model_ProductVariant
 * @property FCom_CustomField_Model_ProductVariantField $FCom_CustomField_Model_ProductVariantField
 * @property FCom_CustomField_Model_ProductVariantImage $FCom_CustomField_Model_ProductVariantImage
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 */
class FCom_CustomField_Admin extends BClass
{
/*
    public function productAfterSave($args)
    {
        $p = $args['model'];
        $data = $p->as_array();
        $fields = $this->FCom_CustomField_Model_Field->fieldsInfo('product', true);
        if (array_intersect($fields, array_keys($data))) {
            $custom = $this->FCom_CustomField_Model_ProductField->load($p->id, 'product_id');
            if (!$custom) {
                $custom = $this->FCom_CustomField_Model_ProductField->create();
            }
            $custom->set('product_id', $p->id)->set($data)->save();
        }
        // not deleting to preserve meta info about fields
    }
*/
    public function onProductGridColumns($args)
    {
        /** @var FCom_CustomField_Model_Field[] $fields */
        $fields = $this->FCom_CustomField_Model_Field->orm('f')->find_many();
        foreach ($fields as $f) {
            $col = ['label' => $f->field_name, 'index' => 'pcf.' . $f->field_name, 'hidden' => true];
            if ($f->admin_input_type == 'select') {
                $col['options'] = $this->FCom_CustomField_Model_FieldOption->orm()
                    ->where('field_id', $f->id)
                    ->find_many_assoc(stripos($f->table_field_type, 'varchar') === 0 ? 'label' : 'id', 'label');
            }
            $args['columns'][$f->field_code] = $col;
        }
    }

    public function onProductsFormViewBefore()
    {
        $id = $this->BRequest->param('id', true);
        $p = $this->BLayout->view('admin/form')->get('model');
        #$p = $this->FCom_Catalog_Model_Product->load($id);

        if (!$p) {
            return;//$p = $this->FCom_Catalog_Model_Product->create();
        }

        $fieldsOptions = [];
        $fields = $this->FCom_CustomField_Model_ProductField->productFields($p);
        if ($fields) {
            $fieldIds = $this->BUtil->arrayToOptions($fields, 'id');
            $fieldOptionsAll = $this->FCom_CustomField_Model_FieldOption->orm()->where_in("field_id", $fieldIds)
                ->order_by_asc('field_id')->order_by_asc('label')->find_many();
            foreach ($fieldOptionsAll as $option) {
                $fieldsOptions[$option->get('field_id')][] = $option;
            }
        }
        $view = $this->BLayout->view('customfields/products/fields-partial');
        $view->set('model', $p)->set('fields', $fields)->set('fields_options', $fieldsOptions);
    }

    public function onProductFormPostAfterValidate($args)
    {
        $model = $args['model'];
        $data = $args['data'];

        if (!empty($data['custom_fields'])) {
            $model->setData('custom_fields', $data['custom_fields']);
        }

        // get new variant fields data from form
        $varFieldsData = [];
        if (!empty($data['vfields'])) {
            $varFieldsPost = $this->BUtil->fromJson($data['vfields']);
            foreach ($varFieldsPost as $vf) {
                $varFieldsData[(int)$vf['id']] = $vf;
            }
        }
        $variantsData = $this->BUtil->fromJson($data['variants']);
        //$variantsDataIds = $this->BUtil->arrayToOptions($variantsData, 'id');

        #echo "<pre>"; var_dump($data, '<hr>', $varFieldsData, '<hr>', $variantsData); exit;

        $pId = $model->id();
        
        // retrieve variant fields already associated to product
        $prodVarfieldHlp = $this->FCom_CustomField_Model_ProductVarfield;
        $prodVarfieldModels = $prodVarfieldHlp->orm()->where('product_id', $pId)->find_many_assoc('field_id');

        // retrieve product variant models
        $prodVariantHlp = $this->FCom_CustomField_Model_ProductVariant;
        $prodVariantModels = $prodVariantHlp->orm()->where('product_id', $pId)->find_many_assoc();

        // delete removed fields
        if ($prodVarfieldModels) {
            $fieldIdsToDelete = array_diff(array_keys($prodVarfieldModels), array_keys($varFieldsData));
            if ($fieldIdsToDelete) {
                $prodVarfieldHlp->delete_many(['product_id' => $pId, 'field_id' => $fieldIdsToDelete]);

                foreach ($prodVarfieldModels as $fId => $m) {
                    if (in_array($fId, $fieldIdsToDelete)) {
                        unset($prodVarfieldModels[$fId]);
                    }
                }
            }
        }
        if ($varFieldsData) {
            // retrieve custom fields
            $fieldHlp = $this->FCom_CustomField_Model_Field;
            $fieldModels = $fieldHlp->orm()->where_in('id', array_keys($varFieldsData))->find_many_assoc();
            $fieldsByCode = [];
            foreach ($fieldModels as $m) {
                $fieldsByCode[$m->get('field_code')] = $m->id();
            }

            // update or create product variant fields
            $pos = 0;
            foreach ($varFieldsData as $vfId => $vf) {
                if (empty($prodVarfieldModels[$vfId])) {
                    $prodVarfieldModels[$vfId] = $prodVarfieldHlp->create(['product_id' => $pId, 'field_id' => $vfId]);
                }
                $prodVarfieldModels[$vfId]->set(['field_label' => $vf['name'], 'position' => $pos])->save();
                $pos++;
            }
        }

        if ($variantsData) {
            // retrieve related custom fields options
            $fieldOptionHlp = $this->FCom_CustomField_Model_FieldOption;
            $fieldOptionsModels = $fieldOptionHlp->orm()->where_in('field_id', array_keys($varFieldsData))->find_many();
            $fieldOptionsById = [];
            $fieldOptionsByLabel = [];
            foreach ($fieldOptionsModels as $m) {
                $fieldOptionsById[$m->get('field_id')][$m->id()] = $m->get('label');
                $fieldOptionsByLabel[$m->get('field_id')][$m->get('label')] = $m->id();
            }
            
            // retrieve related product variants field values and associate with variants
            $prodVariantFieldHlp = $this->FCom_CustomField_Model_ProductVariantField;
            $prodVariantFieldModels = $prodVariantFieldHlp->orm()->where('product_id', $pId)->find_many_assoc('id');
            $prodVariantFieldsArr = [];
            foreach ($prodVariantFieldModels as $m) {
                $f = $fieldModels[$m->get('field_id')];
                $v = $fieldOptionsById[$m->get('field_id')][$m->get('option_id')];
                // TODO: implement locates for field option labels
                $prodVariantFieldsArr[$m->get('variant_id')][$f->get('field_code')] = ['label' => $v, 'id' => $m->id()];
            }
#echo "<pre>"; var_dump($prodVariantFieldsArr); echo "</pre>";
            // match variants from form data to already existing variants by key fields values
            $matchedVariants = [];
            foreach ($variantsData as $i => &$vd) {
                foreach ($prodVariantModels as $vId => $vm) {
                    if (empty($prodVariantFieldsArr[$vId])) {
                        continue;
                    }
                    $match = true;
                    foreach ($vd['field_values'] as $f => $fv) {
                        if ($prodVariantFieldsArr[$vId][$f]['label'] !== $fv) {
                            $match = false;
                            break;
                        }
                    }
                    if ($match) {
                        $matchedVariants[$i] = $vId;
                        break;
                    }
                }
            }
            unset($vd);

            // delete unmatched variant models
            $where = ['product_id' => $pId];
            if (!empty($matchedVariants)) {
                $where['NOT'] = ['id' => $matchedVariants];
            }
            $prodVariantHlp->delete_many($where);

            // update matched variant models and create new variants
            foreach ($variantsData as $i => $vd) {
                if (!empty($matchedVariants[$i])) {
                    $m = $prodVariantModels[$matchedVariants[$i]];
                } else {
                    $m = $prodVariantHlp->create(['product_id' => $pId]);
                }
                $m->set([
                    'field_values'  => $this->BUtil->toJson($vd['field_values']),
                    'variant_sku'   => $vd['variant_sku']   !== '' ? $vd['variant_sku']   : null,
                    'variant_price' => $vd['variant_price'] !== '' ? $vd['variant_price'] : null,
                    'variant_qty'   => $vd['variant_qty']   !== '' ? $vd['variant_qty']   : null,
                ])->save();
                if (empty($matchedVariants[$i])) {
                    $prodVariantModels[$m->id()] = $m;
                    $matchedVariants[$i] = $m->id();
                    foreach ($vd['field_values'] as $f => $fv) {
                        $fId = $fieldsByCode[$f];
                        $prodVariantFieldHlp->create([
                            'product_id'  => $pId,
                            'variant_id'  => $m->id(),
                            'field_id'    => $fId,
                            'varfield_id' => $prodVarfieldModels[$fId]->id(),
                            'option_id'   => $fieldOptionsByLabel[$fId][$fv],
                        ])->save();
                    }
                } else {
                    foreach ($vd['field_values'] as $f => $fv) {
                        $prodVariantField = $prodVariantFieldModels[$prodVariantFieldsArr[$vId][$f]['id']];
                        $fId = $fieldsByCode[$f];
                        $prodVariantField->set([
                            'option_id' => $fieldOptionsByLabel[$fId][$fv],
                        ])->save();
                    }
                }
            }

            // retrieve related product variants images
            $prodVariantImageHlp = $this->FCom_CustomField_Model_ProductVariantImage;
            $prodVariantImageModels = $prodVariantImageHlp->orm()->where('product_id', $pId)->find_many();
            $prodVariantImages = [];
            foreach ($prodVariantImageModels as $m) {
                $prodVariantImages[$m->get('variant_id')][$m->get('file_id')] = $m;
            }

            // update and create variant images
            $fileIdsToDelete = [];
            foreach ($variantsData as $i => $vd) {
                $dataFileIds = !empty($vd['variant_file_id']) ? explode(',', $vd['variant_file_id']) : [];
                $vId = $matchedVariants[$i];
                if (!empty($prodVariantImages[$vId])) {
                    foreach ($prodVariantImages[$vId] as $fileId => $m) {
                        if (!in_array($fileId, $dataFileIds)) {
                            $fileIdsToDelete[] = $m->id();
                        }
                    }
                }
                foreach ($dataFileIds as $pos => $fileId) {
                    if (!empty($prodVariantImages[$vId][$fileId])) {
                        $prodVariantImages[$vId][$fileId]->set('position', $pos)->save();
                    } else {
                        $prodVariantImageHlp->create([
                            'product_id' => $pId,
                            'variant_id' => $vId,
                            'file_id'    => $fileId,
                            'position'   => $pos,
                        ])->save();
                    }
                }
            }
            // delete unused variant images
            if ($fileIdsToDelete) {
                $prodVariantImageHlp->delete_many(['product_id' => $pId, 'id' => $fileIdsToDelete]);
            }
        }
    }
}
