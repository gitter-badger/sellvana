<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * model class for table "fcom_sales_cart"
 *
 * The followings are the available columns in table 'fcom_sales_cart':
 * @property string $id
 * @property string $item_qty
 * @property integer $item_num
 * @property string $subtotal
 * @property string $tax_amount
 * @property string $discount_amount
 * @property string $grand_total
 * @property string $session_id
 * @property string $customer_id
 * @property string $customer_email
 * @property string $shipping_method
 * @property string $shipping_price
 * @property string $shipping_service
 * @property string $payment_method
 * @property string $payment_details
 * @property string $coupon_code
 * @property string $status
 * @property string $create_at
 * @property string $update_at
 * @property string $data_serialized
 * @property string $last_calc_at
 * @property string $admin_id
 *
 * other property
 * @property int $same_address flag to know shipping is same as billing
 * @property array $data from json_decode data_serialized
 *
 * DI
 * @property FCom_Sales_Model_Cart_Item $FCom_Sales_Model_Cart_Item
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Catalog_Model_InventorySku $FCom_Catalog_Model_InventorySku
 * @property FCom_Sales_Model_Cart_Address $FCom_Sales_Model_Cart_Address
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 */
class FCom_Sales_Model_Cart extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Model_Trait_Address;

    protected static $_table = 'fcom_sales_cart';
    protected static $_origClass = __CLASS__;

    protected static $_sessionCart;
    protected static $_totalRowHandlers = [];

    protected static $_fieldOptions = [
        'state_overall' => [
            'active'  => 'Active',
            'ordered' => 'Ordered',
            'abandoned' => 'Abandoned',
            'archived' => 'Archived',
        ],
    ];

    protected $_addresses;
    public $items;
    public $totals;

    /**
     * @param null $id
     * @return int
     */
    public function sessionCartId($id = null)
    {
        return $this->sessionCart()->id();
    }

    /**
     * @param bool $createAnonymousIfNeeded
     * @param bool|FCom_Sales_Model_Cart $reset
     * @return FCom_Sales_Model_Cart
     */
    public function sessionCart($createAnonymousIfNeeded = false, $reset = false)
    {
        // if there's already session cart and no reset requested, return existin session cart
        if (static::$_sessionCart && !$reset) {
            return static::$_sessionCart;
        }

        // get cookie token ttl from config
        $ttl = $this->BConfig->get('modules/FCom_Sales/cart_cookie_token_ttl_days') * 86400;

        // if reset to a specific cart model is requested, set it and return
        if ($reset instanceof FCom_Sales_Model_Cart) {
            static::$_sessionCart = $cart = $reset;
            $cookieToken = $cart->get('cookie_token');
            $this->BResponse->cookie('cart', $cookieToken, $ttl);
            return $reset;
        }

        // get unique cart token from cookie
        $cookieToken = $this->BRequest->cookie('cart');

        // get session user
        $customer = $this->FCom_Customer_Model_Customer->sessionUser();

        // if cookie cart token is set, try to load it
        if ($cookieToken) {

            static::$_sessionCart = $this->loadWhere(['cookie_token' => (string)$cookieToken, 'state_overall' => 'active']);

            // if a cart with this token is not found and no need to create a new one, remove cookie cart token and return
            if (!static::$_sessionCart && !$createAnonymousIfNeeded) {
                $this->BResponse->cookie('cart', false);
                return false;
            }

        } elseif ($customer) { // if no cookie cart token and customer is logged in, try to find customer cart

            $data = ['customer_id' => $customer->id(), 'state_overall' => 'active'];
            static::$_sessionCart = $this->loadWhere($data);

            if (!static::$_sessionCart) {
                // generate token
                $data['cookie_token'] = $this->BUtil->randomString(32);
                static::$_sessionCart = $this->create($data)->save();
            }

            if (!static::$_sessionCart->hasCompleteAddress('shipping')) {
                static::$_sessionCart->importAddressesFromCustomer($customer)->save();
            }

            // set cookie cart token
            $this->BResponse->cookie('cart', static::$_sessionCart->get('cookie_token'), $ttl);
        }

        // if no cart is found and new cart creation is requested, create one and set cookie cart token
        if (!static::$_sessionCart && $createAnonymousIfNeeded) {
            // generate token
            $cookieToken = $this->BUtil->randomString(32);
            // create cart record
            static::$_sessionCart = $this->create([
                'cookie_token' => (string)$cookieToken,
                'state_overall' => 'active',
                'customer_id' => $customer ? $customer->id() : null,
            ])->save();
            // set cookie cart token
            $this->BResponse->cookie('cart', $cookieToken, $ttl);
        }

        return static::$_sessionCart;
    }

    /**
     * @return FCom_Sales_Model_Cart
     */
    public function resetSessionCart()
    {
        static::$_sessionCart = null;
        return $this;
    }

    /**
     * @param FCom_Sales_Model_Cart $cart
     * @return FCom_Sales_Model_Cart
     * @throws BException
     */
    public function merge($cart)
    {
        if (is_numeric($cart)) {
            $cart = $this->load($cart);
        }
        foreach ($cart->items() as $item) {
            $this->addProduct($item->product_id, ['qty' => $item->qty, 'price' => $item->price]);
        }
        $cart->delete();
        $this->calculateTotals()->saveAllDetails();
        return $this;
    }

    /**
     * Return total UNIQUE number of items in the cart
     * @param boolean $assoc
     * @return FCom_Sales_Model_Cart_Item[]
     */
    public function items($assoc = true)
    {
        if (!$this->items) {
            $this->items = $this->FCom_Sales_Model_Cart_Item->orm()->where('cart_id', $this->id())->find_many_assoc();
        }
        return $assoc ? $this->items : array_values($this->items);
    }

    /**
     * Save cart with items and other details
     *
     * @param array $options
     * @return static
     */
    public function saveAllDetails($options = [])
    {
        $this->save();
        foreach ($this->items() as $item) {
            $item->save();
        }
        return $this;
    }

    /**
     * @param int $limit
     * @return array
     */
    public function recentItems($limit = 3)
    {
        if (!$this->id()) {
            return [];
        }
        $orm = $this->FCom_Sales_Model_Cart_Item->orm('ci')->where('ci.cart_id', $this->id())
            ->order_by_desc('ci.update_at')->limit($limit);
        $this->BEvents->fire(__METHOD__ . ':orm', ['orm' => $orm]);
        $items = $orm->find_many();
        $this->BEvents->fire(__METHOD__ . ':data', ['items' => &$items]);
        return $items;
    }

    /**
     * @param null $items
     * @return FCom_Sales_Model_Cart
     */
    public function loadProducts($items = null)
    {
        if (is_null($items)) {
            $items = $this->items();
        }
        $productIds = [];
        foreach ($items as $item) {
            if ($item->product) continue;
            if (($cached = $this->FCom_Catalog_Model_Product->cacheFetch('id', $item->product_id))) {
                $item->product = $cached;
            } else {
                $productIds[$item->product_id] = $item->id;
            }
        }
        if ($productIds) {
            //todo: fix bug for ambigious field ID
            //$this->FCom_Catalog_Model_Product->cachePreloadFrom(array_keys($productIds));
        }
        foreach ($items as $item) {
            $item->product = $this->FCom_Catalog_Model_Product->load($item->product_id);
        }
        return $this;
    }

    /**
     * @param $cartId
     * @return array
     */
    public function cartItems($cartId)
    {
        $tProduct = $this->FCom_Catalog_Model_Product->table();
        $tCartItem = $this->FCom_Sales_Model_Cart_Item->table();
        return $this->BDb->many_as_array($this->FCom_Catalog_Model_Product->orm()
            ->join($tCartItem, [$tCartItem . '.product_id', '=', $tProduct . '.id'])
            ->select($tProduct . '.*')
            ->select($tCartItem . '.*')
            ->where($tCartItem . '.cart_id', $cartId)
            ->find_many());
    }

    /**
     * Return total number of items in the cart
     * @return integer
     */
    public function itemQty()
    {
        return $this->get('item_qty') * 1;
    }

    public function findItemToMerge($params)
    {
        if (!empty($params['show_separate'])) {
            return false;
        }
        $items = $this->items();
        foreach ($items as $item) {
            if ($item->get('show_separate') || $item->get('product_id') !== $params['product_id']) {
                continue;
            }

        }
        return false;
    }

    public function calcItemSignatureHash($signature)
    {
        $s = $this->BUtil->toJson($signature);
        $hash = crc32($s);
        return $hash;
    }

    /**
     * @todo combine variants and shopper fields into structure grouped differently, i.e. all output in the same array
     * @todo move variants to FCom_CustomField
     *
     * @param FCom_Catalog_Model_Product|int $product
     * @param array $params
     *      - qty
     *      - price
     *      - is_separate
     * @return FCom_Sales_Model_Cart
     */
    public function addProduct($product, $params = [])
    {
        //save cart to DB on add first product
        if (!$this->id()) {
            $this->save();
        }

        if (is_numeric($product)) {
            $productId = $product;
            $product = $this->FCom_Catalog_Model_Product->load($productId);
        } else {
            $productId = $product->id();
        }

        if (empty($params['qty']) || !is_numeric($params['qty'])) {
            $params['qty'] = 1;
        }
        $params['qty'] = intval($params['qty']);

        if (empty($params['price']) || !is_numeric($params['price'])) {
            $params['price'] = 0;
        }

        $hash = !empty($params['signature']) ? $this->calcItemSignatureHash($params['signature']) : null;

        /** @var FCom_Sales_Model_Cart_Item $item */
        $item = null;
        if (empty($params['show_separate'])) {
            $where = [
                'cart_id' => $this->id(),
                'product_id' => $productId,
                'show_separate' => 0,
            ];
            if (!empty($params['signature'])) {
                $where['unique_hash'] = $hash;
            }
            $item = $this->FCom_Sales_Model_Cart_Item->loadWhere($where);
            if ($item) {
                $item->add('qty', $params['qty']);
                $item->set('price', $params['price']);
            }
        }
        $skuModel = $this->FCom_Catalog_Model_InventorySku->load($params['inventory_id']);
        if (!$item) {
            $item = $this->FCom_Sales_Model_Cart_Item->create([
                'cart_id' => $this->id(),
                'product_id' => $productId,
                'product_name' => $product->get('product_name'),
                'product_sku' => !empty($params['product_sku']) ? $params['product_sku'] : null,
                'inventory_id' => !empty($params['inventory_id']) ? $params['inventory_id'] : null,
                'inventory_sku' => !empty($params['inventory_sku']) ? $params['inventory_sku'] : null,
                'show_separate' => !empty($params['show_separate']) ? $params['show_separate'] : false,
                'pack_separate' => $skuModel->get('pack_separate'),
                'shipping_weight' => $skuModel->get('shipping_weight'),
                'shipping_size' => $skuModel->get('shipping_size'),
                'cost' => $skuModel->get('unit_cost'),
                'qty' => $params['qty'],
                'price' => $params['price'],
                'unique_hash' => $hash,
            ]);
        }
        if (!empty($params['data'])) {
            foreach ($params['data'] as $key => $val) {
                $item->setData($key, $val);
            }
        }

        $item->save();

        $this->BEvents->fire(__METHOD__, ['model' => $this, 'item' => $item]);

        return $this;
    }

    /**
     * @param $item
     * @return $this
     */
    public function removeItem($item)
    {
        if (is_numeric($item)) {
            $this->items();
            $item = $this->childById('items', $item);
        }
        if ($item) {
            unset($this->items[$item->id]);
            $item->delete();
            $this->calculateTotals()->save();
        }
        return $this;
    }

    /**
     * @param $productId
     * @return $this
     */
    public function removeProduct($productId)
    {
        $this->items();
        $this->removeItem($this->childById('items', $productId, 'product_id'));
        $this->BEvents->fire(__METHOD__, ['model' => $this]);
        return $this;
    }

    /**
     * @param $request
     * @return $this
     * @throws BException
     */
    public function updateItemsQty($request)
    {
        $items = $this->items();
        foreach ($request as $data) {
            if (!empty($items[$data->id])) {
                $data->qty = intval($data->qty);
                $items[$data->id]->set('qty', $data->qty)->save();
            }
        }
        $this->calculateTotals()->save();
        return $this;
    }

    /**
     * @param $name
     * @param null $class
     * @return $this
     */
    public function registerTotalRowHandler($name, $class = null)
    {
        if (is_null($class)) $class = $name;
        static::$_totalRowHandlers[$name] = $class;
        return $this;
    }

    /**
     * @return array
     */
    public function getTotalRowInstances()
    {
        if (!$this->totals) {
            $this->totals = [];
            foreach (static::$_totalRowHandlers as $name => $class) {
                $inst = $this->BClassRegistry->instance($class)->init($this);
                $this->totals[$inst->getCode()] = $inst;
            }
            uasort($this->totals, function($a, $b) { return $a->getSortOrder() - $b->getSortOrder(); });
        }
        return $this->totals;
    }

    /**
     * @return $this
     */
    public function calculateTotals()
    {
        $this->loadProducts();
        $totals = [];
        foreach ($this->getTotalRowInstances() as $total) {
            $total->init($this)->calculate();
            $totals[$total->getCode()] = $total->asArray();
        }
        $this->set('last_calc_at', time())->setData('totals', $totals);
        return $this;
    }

    /**
     * @return array
     */
    public function getTotals()
    {
        //TODO: price invalidate
        if (!$this->getData('totals') || !$this->get('last_calc_at') || $this->get('last_calc_at') < time() - 86400) {
            $this->calculateTotals()->save();
        }

        return $this->getTotalRowInstances();
    }

    /**
     * @return bool
     */
    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) return false;

        $customerId = $this->FCom_Customer_Model_Customer->sessionUserId();

        if (!$this->get('customer_id') && $customerId) {
            $this->set('customer_id', $customerId);
        }

        return true;
    }

    public function onAfterCreate()
    {
        parent::onAfterCreate();

        $this->set('same_address', 1);
        $this->setPaymentMethod(true);

        return $this;
    }

    /**
     * @return null
     */
    public function getBillingAddress()
    {
        return $this->addressAsObject('billing');
    }

    /**
     * @return null
     */
    public function getShippingAddress()
    {
        return $this->addressAsObject('shipping');
    }

    public function importAddressesFromCustomer(FCom_Customer_Model_Customer $customer)
    {
        $defBilling = $customer->getDefaultBillingAddress();
        if ($defBilling) {
            $this->importAddressFromObject($defBilling, 'billing');
        }
        $defShipping = $customer->getDefaultShippingAddress();
        if ($defShipping) {
            $this->importAddressFromObject($defShipping, 'shipping');
        }

        $this->set('same_address', $defBilling && $defShipping && $defBilling->id() == $defShipping->id());

        return $this;
    }

    public function importPaymentMethodFromCustomer(FCom_Customer_Model_Customer $customer)
    {
        $this->set('payment_method', $customer->getPaymentMethod());
        $this->setData('payment_details', $customer->getPaymentDetails());
        return $this;
    }

    public function isShippable()
    {
        foreach ($this->items() as $item) {
            if ($item->isShippable()) {
                return true;
            }
        }
        return false;
    }

    public function hasShippingMethod()
    {
        return $this->get('shipping_method') ? true : false;
    }

    /**
     * @return null
     */
    public function getShippingMethod()
    {
        if (!$this->shipping_method) {
            $shippingMethod = $this->BConfig->get('modules/FCom_Sales/default_shipping_method');
            if (!$shippingMethod) {
                return null;
            }
            $this->shipping_method = $shippingMethod;
        }
        $methods = $this->FCom_Sales_Main->getShippingMethods();
        return $methods[$this->shipping_method];
    }

    /**
     * Set shipping method
     *
     * Check if provided code is valid shipping method and apply it
     * @throws BException
     * @param string $shippingMethod
     * @return $this
     */
    public function setShippingMethod($method, $service)
    {
        $methods = $this->FCom_Sales_Main->getShippingMethods();
        if (empty($methods[$method])) {
            throw new BException('Invalid shipping method: '. $method);
        }
        $services = $methods[$method]->getServices();
        if (empty($services[$service])) {
            throw new BException('Invalid shipping service: ' . $service . '(' . $method . ')');
        }
        $this->set('shipping_method', $method)->set('shipping_service', $service);
        return $this;
    }
    /**
     * @return null|FCom_Sales_Method_Payment_Interface
     */
    public function getPaymentMethod()
    {
        if (!$this->payment_method) {
            return null;
        }
        $methods = $this->FCom_Sales_Main->getPaymentMethods();
        return $methods[$this->payment_method];
    }

    /**
     * Set payment method
     *
     * Check if provided code is valid payment method and apply it
     * @throws BException
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($method)
    {
        $methods = $this->FCom_Sales_Main->getPaymentMethods();
        if (true === $method) {
            $method = $this->BConfig->get('modules/FCom_Sales/default_payment_method');
        } elseif (empty($methods[$method])) {
            throw new BException('Invalid payment method: ' . $method);
        }
        $this->set('payment_method', $method);
        return $this;
    }

    public function setStatus($state)
    {
        if ($this->get('state_overall') !== $state) {
            $this->set('state_overall', $state);
            $this->BEvents->fire(__METHOD__, ['cart' => $this, 'state_overall' => $state]);
        }
        return $this;
    }

    public function setStatusActive()
    {
        $this->setStatus('active');
        return $this;
    }

    public function setStatusOrdered()
    {
        $this->setStatus('ordered');
        return $this;
    }

    public function setStatusAbandoned()
    {
        $this->setStatus('abandoned');
        return $this;
    }

    public function setStatusArchived()
    {
        $this->setStatus('archived');
        return $this;
    }

    public function setPaymentDetails($data = [])
    {
        if (!empty($data)) {
            $paymentMethod = $this->getPaymentMethod();
            if ($paymentMethod) {
                $paymentMethod->setDetails($data);
                $this->payment_details = $this->BUtil->toJson($paymentMethod->getPublicData());
            }
        }
        return $this;
    }

    /**
     * @param $post
     */
    public function setPaymentToUser($post)
    {
        /** @var FCom_Customer_Model_Customer $user */
        $user = $this->FCom_Customer_Model_Customer->sessionUser();
        if ($user && isset($post['payment'])) {
            $user->setPaymentDetails($post['payment']);
        }
    }

    /**
     * Verify if the cart has a complete billing or shipping address
     *
     * @throws BException
     * @param string $type 'billing' or 'shipping'
     * @return boolean
     */
    public function hasCompleteAddress($type)
    {
        if ('billing' !== $type && 'shipping' !== $type) {
            throw new BException('Invalid address type: ' . $type);
        }
        $country = $this->get($type . '_country');
        if (!$country) {
            return false;
        }
        $fields = ['firstname', 'lastname', 'street1', 'city'];
        if ($this->BLocale->postcodeRequired($country)) {
            $fields[] = 'postcode';
        }
        if ($this->BLocale->regionRequired($country)) {
            $fields[] = 'region';
        }
        foreach ($fields as $field) {
            $val = $this->get($type . '_' . $field);
            if (null === $val || '' === $val) {
                return false;
            }
        }
        return true;
    }

    public function getShippingRates()
    {
        $ratesArr = $this->getData('shipping_rates');
        if (!$ratesArr) {
            return false;
        }
        $result = [];
        $selMethod = $this->get('shipping_method');
        $selService = $this->get('shipping_service');

        $allMethods = $this->FCom_Sales_Main->getShippingMethods();
        foreach ($allMethods as $methodCode => $method) {
            if (empty($ratesArr[$methodCode])) {
                continue;
            }
            $servicesArr = $ratesArr[$methodCode];
            if (!empty($servicesArr['error'])) {
                continue;
            }
            $allServices = $method->getServices();
            $services = [];
            foreach ($servicesArr as $serviceCode => $serviceRate) {
                $serviceTitle = $allServices[$serviceCode];
                $services[$serviceCode] = [
                    'value' => $methodCode . ':' . $serviceCode,
                    'title' => $serviceTitle,
                    'price' => $serviceRate['price'],
                    'max_days' => $serviceRate['max_days'],
                    'selected' => $selMethod == $methodCode && $selService == $serviceCode,
                ];
            }
            if ($services) {
                $result[$methodCode] = [
                    'title' => $method->getDescription(),
                    'services' => $services,
                ];
            }
        }

        return $result;
    }

    public function __destruct()
    {
        unset($this->_addresses, $this->items, $this->totals);
    }

}
