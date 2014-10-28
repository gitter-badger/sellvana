<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_CustomField_Frontend
 *
 * Uses:
 * @property FCom_Sales_Model_Cart                  $FCom_Sales_Model_Cart
 * @property FCom_CustomField_Model_ProductVariant  $FCom_CustomField_Model_ProductVariant
 */
class FCom_CustomField_Frontend extends BClass
{
    /**
     * Convert form data to cart item details
     *
     * @param $args
     *  - cart: (OPTIONAL) FCom_Sales_Model_Cart object
     *  - post: simple post data
     *      id: product_id
     *      qty:
     *      variant_select:
     *  - items: (SEQUENCE ARRAY, UPDATABLE) per item post data. if single, copied from `post`
     *    - id: product_id
     *      product: FCom_Catalog_Model_Product
     *      qty: item qty (optional, default: 1)
     *      details: resulting details data structure
     *          price: item price (optional, default: $product->getPrice() )
     *          product_sku: local catalog product unique id
     *          mfr_sku: manufacturer or wholesale sku (depends on variant)
     *          data:
     *              variants: (DEPRECATED)
     *                  product_id:
     *                  variant_qty:
     *                  variant_price:
     *                  field_values:
     *              display: (NEW)
     *                  - { label:"Color", text:"Blue" }
     *                  - { label:"Size, text:"Large" }
     *                  - { label:"Inscription", text:"For my love" }
     *                  - { type:img, src:preview.jpg }
     *                  - { label:"Extra", text:"Nice buttons" }
     *              variant: (NEW)
     *                  field_values: { color:blue, size:large }
     *              frontend_fields: (NEW)
     *                  - { label: "Label 1", val: "Val 1" }
     *      result:
     *          error:
     *
     * @return bool
     */
    public function onWorkflowCustomerAddsItemsCalcDetails($args)
    {
        $post = $args['post'];
        // TODO: Use for child items for bundles
        $cart = !empty($args['cart']) ? $args['cart'] : $this->FCom_Sales_Model_Cart->sessionCart();

        foreach ($args['items'] as &$item) {
            $p = $item['product'];
            if ($p->getData('variants_fields')) {
                $varValues = $item['variant_select'];
                /** @var FCom_CustomField_Model_ProductVariant $variantHlp */
                $variantHlp = $this->FCom_CustomField_Model_ProductVariant;

                if ($variantHlp->checkEmptyVariant($item['id'])) {
                    //TODO: validate when product empty variant
                    // is this situation common?
                    continue;
                }
                if (empty($item['variant_select'])) {
                    $item['error'] = $this->BLocale->_('Please specify the product variant');
                    continue;
                }
                $variant = $variantHlp->findByProductFieldValues($p, $varValues);
                if (!$variant) {
                    $item['error'] = $this->BLocale->_('Invalid variant');
                    continue;
                }
                $availQty = $variant->get('variant_qty');
                if (!$availQty) { //TODO: allow empty qty
                    $item['error'] = $this->BLocale->_('The variant is out of stock');
                    continue;
                }
                if ($availQty < $item['qty']) {
                    $item['error'] = $this->BLocale->_('The variant currently has only %s items in stock', $variant->variant_qty);
                    continue;
                }

                if ($variant->get('variant_price') > 0) { //TODO: allow free variants
                    $args['options']['price'] = $variant->variant_price;
                }
                $item['details']['variant'] = $variant->as_array();
            }
            $args['options']['data']['variants'] = $defaultVariant;

            if (isset($args['post']['shopper'])) {
                $options['shopper'] = $args['post']['shopper'];
                foreach ($options['shopper'] as $key => $value) {
                    if (!isset($value['val']) || $value['val'] == '') {
                        unset($options['shopper'][$key]);
                    }
                    if ($value['val'] == 'checkbox') {
                        unset($options['shopper'][$key]['val']);
                    }
                }
            }

            // FROM Cart::addProduct()
            if (isset($params['data'])) {
                $variants = $item->getData('variants');
                $flag = true;
                $params['data']['variants']['field_values'] = $this->BUtil->fromJson($params['data']['variants']['field_values']);
                if (null !== $variants) {
                    foreach ($variants as &$arr) {
                        if (in_array($params['data']['variants']['field_values'], $arr)) {
                            $flag = false;
                            $arr['variant_qty'] = $arr['variant_qty'] + $params['qty'];
                            if (isset($params['shopper'])) {
                                $arr['shopper'] = $params['shopper'];
                            }

                        }
                    }
                }
                if ($flag) {
                    if (!empty($params['data']['variants'])) {
                        $params['data']['variants']['variant_qty'] = $params['qty'];
                        $variants = (null !== $variants) ? $variants : [];
                        if (isset($params['shopper'])) {
                            $params['data']['variants']['shopper'] = $params['shopper'];
                        }
                        array_push($variants, $params['data']['variants']);
                    }
                }
                $item->setData('variants', $variants);
            }
        }
        unset($item);

        return true;
    }
}
