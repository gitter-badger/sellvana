<?php

class FCom_ShippingUps_Ups extends FCom_Checkout_Model_Shipping_Abstract
{
    protected $name = 'Universal post service';
    protected $code = 'ShippingUps';
    protected $rate;

    public static function bootstrap()
    {
    }

    public function rateApiCall($shipNumber, $tozip, $service, $length, $width, $height, $weight)
    {
        $config = BConfig::i()->get('modules/FCom_ShippingUps');
        $password = !empty($config['password']) ? $config['password'] : '';
        $account = !empty($config['account']) ? $config['account'] : '';
        $accessKey = !empty($config['access_key']) ? $config['access_key'] : '';
        $rateApiUrl = !empty($config['rate_api_url']) ? $config['rate_api_url'] : '';

        //todo: notify if fromzip is not set
        $fromzip = BConfig::i()->get('modules/FCom_Checkout/store_zip');

        if (empty($accessKey) || empty($account) || empty($password)) {
            return false;
        }

        $this->rate = new UpsRate($rateApiUrl);
        $this->rate->setUpsParams($accessKey,$account, $password, $shipNumber);
        $this->rate->getRate($fromzip, $tozip, $service, $length, $width, $height, $weight);
        return true;
    }

    public function getEstimate()
    {
        if (!$this->rate) {
            $cart = FCom_Checkout_Model_Cart::sessionCart();
            $this->getRateCallback($cart);
            if (!$this->rate) {
                return 'Unable to calculate';
            }
        }
        $estimate = $this->rate->getEstimate();
        if (!$estimate) {
            return 'Unable to calculate';
        }
        $days = ($estimate == 1) ? ' day' : ' days';
        return $estimate . $days;
    }

    /**
     * UPS services
     * @return array
     */
    public function getServices()
    {
        return array(
            '01' => 'UPS Next Day Air',
            '02' => 'UPS Second Day Air',
            '03' => 'UPS Ground',
            '07' => 'UPS Worldwide Express',
            '08' => 'UPS Worldwide Expedited',
            '11' => 'UPS Standard',
            '12' => 'UPS Three-Day Select',
            '13' => 'Next Day Air Saver',
            '14' => 'UPS Next Day Air Early AM',
            '54' => 'UPS Worldwide Express Plus',
            '59' => 'UPS Second Day Air AM',
            '65' => 'UPS Saver'
        );
    }

    public function getDefaultService()
    {
        return array('03' => 'UPS Ground');
    }

    public function getServicesSelected()
    {
        $c = BConfig::i();
        $selected = array();
        foreach($this->getServices() as $sId => $sName) {
            if ($c->get('modules/FCom_ShippingUps/services/s'.$sId) == 1) {
                $selected[$sId] = $sName;
            }
        }
        if (empty($selected)) {
            $selected = $this->getDefaultService();
        }
        return $selected;
    }

    public function getRateCallback($cart)
    {
        //address
        $user = FCom_Customer_Model_Customer::sessionUser();
        $shippingAddress = FCom_Checkout_Model_Address::i()->findByCartType($cart->id(), 'shipping');
        if ($user && !$shippingAddress) {
            $shippingAddress = $user->defaultShipping();
        }
        $tozip = $shippingAddress->postcode;
        //service
        if ($cart->shipping_method == $this->code) {
            $service = $cart->shipping_service;
        } else {
            $service = '01';
        }
        //package dimension
        $items = $cart->items();
        $length = $width = $height = 10;
        $packages = array();
        $packageId = 0;
        $groupPackageId = 0;
        foreach($items as $item) {
            if ( $item->getWeight() > 250 ||  $item->getWeight() == 0 ) {
                continue;
            }
            for ($i = 0; $i < $item->getQty(); $i++) {
                if ($item->isGroupAble()){
                    if (!empty($packages[$groupPackageId]) && $item->getWeight() + $packages[$groupPackageId] >= 150) {
                        $packageId++;
                        $groupPackageId = $packageId;
                    }
                    if (!empty($packages[$groupPackageId])) {
                        $packages[$groupPackageId] += $item->getWeight();
                    } else {
                        $packages[$groupPackageId] = $item->getWeight();
                    }

                } else {
                    $packageId++;
                    $packages[$packageId] = $item->getWeight();
                }
            }
        }
        //package weight
        $total = 0;
        foreach($packages as $pack) {
            $this->rateApiCall($cart->id(), $tozip, $service, $length, $width, $height, $pack);
            if ($this->rate->isError()) {
                 continue;
            }
            $total += $this->rate->getTotal();
        }
        return $total;
    }

    public function getError()
    {
        return $this->rate->getError();
    }

    public function getDescription()
    {
        return $this->name;
    }
}
