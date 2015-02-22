<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Cart_Item
 *
 * @property int $id
 * @property int $cart_id
 * @property int $product_id
 * @property string $product_sku
 * @property string $product_name
 * @property string $inventory_sku
 * @property string $inventory_id
 * @property float $qty
 * @property float $price
 * @property float $row_total
 * @property float $tax
 * @property float $discount
 * @property int $promo_id_buy //todo: ??? why varchar in db
 * @property int $promo_id_get
 * @property float $promo_qty_used
 * @property float $promo_amt_used
 * @property datetime $create_at
 * @property datetime $update_at
 * @property string $data_serialized
 *
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 */
class FCom_Sales_Model_Cart_Item extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_cart_item';

    /**
     * @var FCom_Catalog_Model_Product
     */
    protected $_product;

    /**
     * @var FCom_Sales_Model_Cart
     */
    protected $_cart;

    protected $_relatedItemsCache = [];

    /**
     * @param FCom_Catalog_Model_Product $product
     * @return $this
     */
    public function setProduct(FCom_Catalog_Model_Product $product)
    {
        $this->_product = $product;
        return $this;
    }

    /**
     * @return FCom_Catalog_Model_Product
     */
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = $this->relatedModel('FCom_Catalog_Model_Product', $this->get('product_id'));
        }
        return $this->_product;
    }

    /**
     * @param FCom_Sales_Model_Cart $cart
     * @return $this
     */
    public function setCart(FCom_Sales_Model_Cart $cart)
    {
        $this->_cart = $cart;
        return $this;
    }

    /**
     * @return FCom_Sales_Model_Cart
     */
    public function getCart()
    {
        if (!$this->_cart) {
            $this->_cart = $this->FCom_Sales_Model_Cart->load($this->get('cart_id'));
        }
        return $this->_cart;
    }

    /**
     * @param null $variantId
     * @return mixed
     */
    public function calcRowTotal()
    {
        return $this->get('price') * $this->get('qty');
    }

    /**
     * @return bool
     * @todo implement
     */
    public function isGroupable()
    {
        return true;
    }

    /**
     * @return bool
     * @todo implement
     */
    public function isShippable()
    {
        return true;
    }

    /**
     * @param bool $ship
     * @return bool
     */
    public function getItemWeight($ship = true)
    {
        $p = $this->getProduct();
        if (!$p) {
            return false;
        }
        return $p->get($ship ? 'ship_weight' : 'net_weight');
    }

    /**
     * @param bool $ship
     * @return bool|float
     */
    public function getRowWeight($ship = true)
    {
        $w = $this->getItemWeight($ship);
        if (false === $w) {
            return false;
        }
        return $this->getQty() * $w;
    }

    /**
     * @return float
     */
    public function getQty()
    {
        return $this->get('qty');
    }

    public function calcUniqueHash($signature)
    {

    }

    public function getCartTemplateViewName()
    {
        if ($this->get('auto_added')) {
            return 'cart/item/auto-added';
        }
        return 'cart/item/default';
    }

    public function __destruct()
    {
        unset($this->_product, $this->_cart, $this->_relatedSkuProductCache);
    }
}

