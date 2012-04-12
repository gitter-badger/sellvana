<?php

class FCom_Catalog_Model_Product extends BModel
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product';

    public static function stockStatusOptions($onlyAvailable=false)
    {
        $options = array(
            'in_stock' => 'In Stock',
            'backorder' => 'On Backorder',
            'special_order' => 'Special Order',
        );
        if (!$onlyAvailable) {
            $options += array(
                'do_not_carry' => 'Do Not Carry',
                'temp_unavail' => 'Temporarily Unavailable',
                'vendor_disc' => 'Supplier Discontinued',
                'manuf_disc' => 'MFR Discontinued',
            );
        }
        return $options;
    }

    public function url($category=null)
    {
        $url = BApp::href('p/');
        if ($category) {
            $url .= $category->url_path.'/';
        }
        $url .= $this->url_key;
        return $url;
    }

    public function imageUrl($full=false)
    {
        $url = $full ? BApp::src('FCom_Catalog').'/' : '';
        return $url.'media/'.($this->image_url ? $this->image_url : 'DC642702.jpg');
    }

    public function thumbUrl($w, $h=null)
    {
        return FCom_Core::i()->resizeUrl().'?f='.urlencode($this->imageUrl()).'&s='.$w.'x'.$h;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        if (!$this->get('url_key')) $this->generateUrlKey();

        return true;
    }

    public function generateUrlKey()
    {
        //$key = $this->manuf()->manuf_name.'-'.$this->manuf_sku.'-'.$this->product_name;
        $key = $this->product_name;
        $this->set('url_key', BLocale::transliterate($key));
        return $this;
    }

    public function onAssociateCategory($args)
    {
        $catId = $args['id'];
        $prodIds = $args['ref'];
        if (!$copy) {

        }
    }

    public function getPriceRangeText()
    {
        if ($this->base_price < 100){
            return "$0 to $99";
        } elseif ($this->base_price < 300) {
            return "$100 to $299";
        } else {
            return "$300 and above";
        }
    }

    public function getBrandName()
    {
        return (rand(0, 100) % 2 == 0) ? "Brand 1": "Brand 2";
    }

    public function rating()
    {
        return rand(0, 100);
    }

    public function categories($pId)
    {
        return FCom_Catalog_Model_CategoryProduct::i()->orm('cp')->where('cp.product_id', $pId)->find_many();
    }

    public function customFields($product)
    {
        return FCom_CustomField_Model_ProductField::i()->productFields($product);
    }
}

