<?php

class FCom_Sales_Model_Cart extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_cart';
    protected static $_origClass = __CLASS__;

    protected static $_sessionCart;
    protected $shippingMethods = array();
    protected $shippingClasses = array();
    protected $paymentMethods = array();
    protected $paymentClasses = array();

    public $items;


    static public function sessionCartId($id=BNULL)
    {
        if (BNULL===$id) {
            return BSession::i()->data('cart_id');
        }
        BSession::i()->data('cart_id', $id);
        return $id;
    }

    static public function sessionCart($reset = true)
    {
        if ($reset || !static::$_sessionCart) {
            if ($reset instanceof FCom_Sales_Model_Cart) {
                static::$_sessionCart = $reset;
                static::sessionCartId($reset->id);
            } else {
                if (($cartId = static::sessionCartId()) && ($cart = static::load($cartId))) {
                    static::$_sessionCart = $cart;
                } elseif (($cart = static::i()->orm()->where('session_id',BSession::i()->sessionId())->where('status', 'new')->find_one() )) {
                    static::$_sessionCart = $cart;
                    static::sessionCartId($cart->id);
                } else {
                    static::$_sessionCart = static::i()->create();
                    static::sessionCartId();
                }
            }
        }
        return static::$_sessionCart;
    }

    static public function userLogin()
    {
        if (false == BModuleRegistry::isLoaded('FCom_Customer')) {
            return false;
        }

        $user = FCom_Customer_Model_Customer::sessionUser();
        if(!$user){
            return;
        }
        $sessCartId = static::sessionCartId();
        if ($user->session_cart_id) {
            $cart = static::i()->load($user->session_cart_id);
            if(!$cart){
                $user->session_cart_id = $sessCartId;
                $user->save();
            } elseif ($user->session_cart_id != $sessCartId) {
                if ($sessCartId) {
                    $cart->merge($sessCartId)->save();
                }
            }
        } elseif ($sessCartId) {
            $user->set('session_cart_id', $sessCartId)->save();
        }

        static::sessionCartId($user->session_cart_id);
    }

    static public function userLogout()
    {
        static::sessionCartId(false);
        static::$_sessionCart = null;
    }

    public function merge($cartId)
    {
        $cart = static::i()->load($cartId);
        foreach ($cart->items() as $item) {
            $this->addProduct($item->product_id, array('qty'=>$item->qty, 'price'=>$item->price));
        }
        $cart->delete();
        $this->calcTotals()->save();
        return $this;
    }

    public static function carts($flags=array())
    {
        $sessCartId = 1*static::sessionCartId();
        $carts = array();
        if (!empty($flags['default'])) {
            $carts[] = array('id'=>$sessCartId, 'description'=>$sessCartId ? static::sessionCart()->description : 'Unsaved Cart');
        }
        $orm = static::orm();
        if (!empty($flags['user'])) {
            $orm->filter('by_user', $flags['user']);
        }
        if ($sessCartId) {
            $orm->where_not_equal('id', $sessCartId);
        }
        $orm->order_by_asc('sort_order');
        foreach ($orm->find_many() as $cart) $carts[] = $cart->as_array();
        if (!empty($flags['full'])) {
            static::loadCartsData($carts);
        }
        return $carts;
    }

    public static function newDescription()
    {
        return 'New Cart';
    }

    public static function addFromList()
    {
        $carts = static::carts(array('user'=>true, 'default'=>true));
        return $carts;
    }

    public static function sendToList()
    {
        $carts = static::carts(array('user'=>true, 'default'=>true));
        $carts[] = array('id'=>-1, 'description'=>'['.self::newDescription().']');
        return $carts;
    }

    /**
     * Return total UNIQUE number of items in the cart
     * @param boolean $assoc
     * @return array
     */
    public function items($assoc=true)
    {
        $this->items = FCom_Sales_Model_CartItem::i()->orm()->where('cart_id', $this->id)->find_many_assoc();
        return $assoc ? $this->items : array_values($this->items);
    }

    public function recentItems($limit=3)
    {
        $orm = FCom_Sales_Model_CartItem::i()->orm('ci')->where('ci.cart_id', $this->id)
            ->order_by_desc('ci.update_dt')->limit($limit);
        BEvents::i()->fire(__METHOD__.'.orm', array('orm'=>$orm));
        $items = $orm->find_many();
        BEvents::i()->fire(__METHOD__.'.data', array('items'=>&$items));
        return $items;
    }

    public function loadProducts($items = null)
    {
        if (is_null($items)) {
            $items = $this->items();
        }
        $productIds = array();
        foreach ($items as $item) {
            if ($item->product) continue;
            if (($cached = FCom_Catalog_Model_Product::i()->cacheFetch('id', $item->product_id))) {
                $item->product = $cached;
            } else {
                $productIds[$item->product_id] = $item->id;
            }
        }
        if($productIds){
            //todo: fix bug for ambigious field ID
            //FCom_Catalog_Model_Product::i()->cachePreloadFrom(array_keys($productIds));
        }
        foreach ($items as $item) {
            $item->product = FCom_Catalog_Model_Product::i()->load($item->product_id);
        }
        return $this;
    }

    public static function cartItems($cartId)
    {
        $tProduct = FCom_Catalog_Model_Product::table();
        $tCartItem = FCom_Sales_Model_CartItem::table();
        return BDb::many_as_array(FCom_Catalog_Model_Product::i()->orm()
            ->join($tCartItem, array($tCartItem.'.product_id','=',$tProduct.'.id'))
            ->select($tProduct.'.*')
            ->select($tCartItem.'.*')
            ->where($tCartItem.'.cart_id', $cartId)
            ->find_many());
    }

    /**
     * Return total number of items in the cart
     * @return integer
     */
    public function itemQty()
    {
        return $this->item_qty*1;
    }

    public static function by_user($orm, $userId)
    {
        if (is_null($userId)) {
            if (BModuleRegistry::isLoaded('FCom_Customer')) {
                $userId = FCom_Customer_Model_Customer::sessionUserId();
            } else {
                return;
            }
        }
        return $orm->where('user_id', $userId);
    }

    public static function sendProducts($request)
    {
        if (true===$request->multirow_ids) {
            $request->multirow_ids = array();
            $items = FCom_Sales_Model_CartItem::i()->orm()->select('product_id')->select('qty')->select('price')
                ->where('cart_id', $request->source_id)
                ->find_many();
            foreach ($items as $item) {
                $request->multirow_ids[] = $item->product_id;
            }
        }
        if ($request->target!=='catalog') {
            $productIds = !empty($request->multirow_ids) ? $request->multirow_ids : (array)$request->row_id;
            $request->qtys = array();
            if (empty($items)) {
                $items = FCom_Sales_Model_CartItem::i()->orm()->select('product_id')->select('qty')->select('price')
                    ->where('cart_id', $request->source_id)->where_in('product_id', $productIds)
                    ->find_many();
            }
            foreach ($items as $item) {
                $request->qtys[$item->product_id] = $item->qty;
            }
        }
    }

    public function addProduct($productId, $options=array())
    {
        //save cart to DB on add first product
        if (!$this->id) {
            $this->item_qty = 1;
            $this->save();
        }

        if (empty($options['qty']) || !is_numeric($options['qty'])) {
            $options['qty'] = 1;
        }
        if (empty($options['price']) || !is_numeric($options['price'])) {
            $options['price'] = 0;
        } else {
            $options['price'] = $options['price']; //$options['price'] * $options['qty']; // ??
        }
        $item = FCom_Sales_Model_CartItem::load(array('cart_id'=>$this->id, 'product_id'=>$productId));
        if ($item && $item->promo_id_get == 0) {
            $item->add('qty', $options['qty']);
            $item->set('price', $options['price']);
        } else {
            $item = FCom_Sales_Model_CartItem::create(array('cart_id'=>$this->id, 'product_id'=>$productId,
                'qty'=>$options['qty'], 'price' => $options['price']));
        }
        $item->save();
        if (empty($options['no_calc_totals'])) {
            $this->calcTotals()->save();
        }

        BEvents::i()->fire(__METHOD__, array('model'=>$this, 'item' => $item));

        static::sessionCartId($this->id);
        return $this;
    }

    public function removeItem($item)
    {
        if (is_numeric($item)) {
            $this->items();
            $item = $this->childById('items', $item);
        }
        if ($item) {
            unset($this->items[$item->id]);
            $item->delete();
            $this->calcTotals()->save();
        }
        return $this;
    }

    public function removeProduct($productId)
    {
        $this->items();
        $this->removeItem($this->childById('items', $productId, 'product_id'));
        BEvents::i()->fire(__METHOD__, array('model'=>$this));
        return $this;
    }

    public static function updateCarts($request)
    {
        try {
            static::writeDb()->beginTransaction();

            $oldCarts = static::orm()->filter('by_user')->find_many_assoc();
            $newCarts = array();
            if (!empty($request->carts)) {
                foreach ($request->carts as $c) {
                    $newCarts[$c->id] = (array)$c;
                }
            }
            if (!empty($request->deleted)) {
                foreach ($request->deleted as $cId) {
                    if (!empty($oldCarts[$cId])) {
                        $oldCarts[$cId]->delete();
                    }
                }
            }
            foreach ($newCarts as $cId=>$c) {
                unset($c->id);
                if ($cId<0) {
                    unset($c['id']);
                    $cart = static::create($c)->save();
                    $cId = $cart->id;
                } else {
                    if (empty($oldCarts[$cId])) {
throw new Exception("Invalid cart_id: ".$cId);
                        continue;
                    }
                    $cart = $oldCarts[$cId]->set($c)->save();
                }
                $cart->updateUsers(!empty($c['users']) ? $c['users'] : array(), !empty($c['deleted_users']) ? $c['deleted_users'] : null);
            }
            static::writeDb()->commit();
        } catch (Exception $e) {
            static::writeDb()->rollback();
            throw $e;
        }
    }

    public function updateItemsQty($request)
    {
        $items = $this->items();
        foreach ($request as $data) {
            if (!empty($items[$data->id])) {
                $items[$data->id]->set('qty', $data->qty)->save();
            }
        }
        $this->calcTotals()->save();
        return $this;
    }

    public function calcTotals()
    {
        $this->loadProducts();
        $this->item_num = 0;
        $this->item_qty = 0;
        $this->subtotal = 0;
        foreach ($this->items() as $item) {
            if (!$item->product()) {
                $this->removeProduct($item->product_id);
            }
            $this->item_num++;
            $this->item_qty += $item->qty;
            $this->subtotal += $item->price*$item->qty;
        }
        BEvents::i()->fire(__METHOD__, array('model'=>$this));
        return $this;
    }
/*
    public function totalAsHtml()
    {
        $subtotal = $this->subtotal;
        $shipping = 0;
        if ($this->shipping_method) {
            $shipping = $this->shipping_price;
        }
        $discount = 0;
        if ($this->discount_code) {
            $discount = 10;
        }
        //if tax
        $beforeTax = $subtotal + $shipping - $discount;
        $estimatedTax = 0;
        if (1) {
            $estimatedTax = $beforeTax*0.2;
        }
        $total = $beforeTax + $estimatedTax;
        $html = '
Items: $'.$subtotal.'<br>
Shipping and handling: $'.$shipping.'<br>
Discount: -$'.$discount.'<br/>
Total before tax: $'.$beforeTax.'<br>
Estimated tax: $'.$estimatedTax.'<br>
<b>Order total: $'.$total.'</b>';
        return $html;
    }
*/
    public function addShippingMethod($method, $class)
    {
        $this->shippingMethods[$method] = $class;
    }

    /**
     *
     * @return Array of Shipping Method objects
     */
    public function getShippingMethods()
    {
        if (!$this->shippingMethods) {
            return false;
        }
        if (empty($this->shippingClasses)) {
            foreach($this->shippingMethods as $method => $class) {
                $this->shippingClasses[$method] = $class::i();
            }
        }
        return $this->shippingClasses;
    }

    public function getShippingClassName($method)
    {
        return $this->shippingMethods[$method];
    }

    public function getShippingMethod($method)
    {
        $this->getShippingMethods();
        if (!empty($this->shippingClasses[$method])){
            return $this->shippingClasses[$method];
        } else {
            return false;
        }
    }

    public function addPaymentMethod($method, $class)
    {
        $this->paymentMethods[$method] = $class;
    }

    /**
     *
     * @return Array of Payment Methods
     */
    public function getPaymentMethods()
    {
        if (!$this->paymentMethods) {
            return false;
        }
        if (empty($this->paymentClasses)) {
            foreach($this->paymentMethods as $method => $class) {
                $this->paymentClasses[$method] = $class::i();
            }
        }
        return $this->paymentClasses;
    }

    public function addTotalRow($name, $options)
    {
        $cart = self::sessionCart();
        $totals = BUtil::fromJson($cart->totals_json);
        $totals[$name] = array('name' => $name, 'options' => $options);
        $cart->totals_json = BUtil::toJson($totals);
        $cart->save();
    }

    public function calculateTotals()
    {
        $this->calc_balance = 0;
        $totals = BUtil::fromJson($this->totals_json);
        if (!$totals) {
            return;
        }
        $sorted = $this->sortTotals($totals);
        if (!$sorted) {
            return;
        }

        foreach ($sorted as $key => $totalMethod) {
            if (empty($totalMethod['options']['callback'])) {
                continue;
            }
            $callback = BUtil::extCallback($totalMethod['options']['callback']);
            if (!is_callable($callback)) {
                BDebug::warning('Invalid cart total callback: '.$key);
                continue;
            }
            try {
                $totals[$key]['total'] = call_user_func($callback, $this);
            } catch (FCom_Checkout_Exception_CartTotal $e) {
                $totals[$key]['total'] = 0;
                $totals[$key]['error'] = $e->getMessage();
            }

            $this->calc_balance += $totals[$key]['total'];
        }
        $this->totals_json = BUtil::toJson($totals);
        $this->save();
    }

    public function getTotals()
    {
        return BUtil::fromJson($this->totals_json);
    }


    public function sortTotals($totals)
    {
        $totalObjects = $totals;
        // take care of 'load_after' option
        foreach ($totalObjects as $index => $data) {
            if (!empty($data['options']['after'])) {
                $totalObjects[$index]['parents'][] = $data['options']['after'];
                $totalObjects[$data['options']['after']]['children'][] = $data['name'];
            }
        }

        // get modules without dependencies
        $rootObjects = array();
        foreach ($totalObjects as $data) {
            if (empty($data['parents'])) {
                $rootObjects[] = $data;
            }
        }

        $sorted = array();
        while($totalObjects) {
            // check for circular reference
            if (!$rootObjects) return false;
            // remove this node from root modules and add it to the output
            $n = array_pop($rootObjects);

            $sorted[$n['name']] = $n;

            if (empty($n['children'])) {
                unset($totalObjects[$n['name']]);
                continue;
            }
            // for each of its children: queue the new node, finally remove the original
            for ($i = count($n['children'])-1; $i>=0; $i--) {
                // get child module
                $childObject = $totalObjects[$n['children'][$i]];
                // remove child modules from parent
                unset($n['children'][$i]);
                // remove parent from child module
                unset($childObject['parents'][array_search($n['name'], $childObject['parents'])]);
                // check if this child has other parents. if not, add it to the root modules list
                if (empty($childObject['parents'])) array_push($rootObjects, $childObject);
            }
            // remove processed module from list
            unset($totalObjects[$n['name']]);
        }

        $sortedTotals = array();
        foreach($sorted as $key => $data){
            $sortedTotals[$key] = $totals[$key];
        }
        return $sortedTotals;
    }

    //todo: rename to subtotalCallback
    public function subtotalCallback()
    {
        $cart = self::sessionCart();
        return $cart->subtotal;
    }

    //this is example
    //todo: move this to discout module when it will be ready
    public function discountCallback()
    {
        $cart = self::sessionCart();
        if ($cart->discount_code) {
            return -10;
        }
        return 0;
    }

    public function urlHash($id)
    {
        return '/carts/items/'.$id;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->create_dt) $this->create_dt = BDb::now();
        $this->update_dt = BDb::now();
        return true;
    }
}
