<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Cart_Total_Shipping
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 */
class FCom_Sales_Model_Cart_Total_Shipping extends FCom_Sales_Model_Cart_Total_Abstract
{
    protected $_code = 'shipping';
    protected $_label = 'Shipping & Handling';
    protected $_sortOrder = 40;

    public function calculate()
    {
        $cart = $this->_cart;
        if ($cart->get('recalc_shipping_rates')) {
            $methods = $this->FCom_Sales_Main->getShippingMethods();
            $weight = 0;
            $rates = [];
            foreach ($methods as $methodCode => $method) {
                $rates[$methodCode] = $method->fetchCartRates($cart);
                if (!empty($rates[$methodCode]['weight'])) {
                    $weight = $rates[$methodCode]['weight'];
                }
            }
            $cart->setData('shipping_rates', $rates);
        } else {
            $weight = $cart->get('shipping_weight');
            $rates = $cart->getData('shipping_rates');
        }

        list($selMethod, $selService) = $this->_findAndSetMethodService($rates);

        $this->_value = $rates[$selMethod][$selService]['price'];
        $cart->set([
            'shipping_method' => $selMethod,
            'shipping_service' => $selService,
            'shipping_price' => $this->_value,
            'shipping_weight' => $weight,
            'recalc_shipping_rates' => 0,
            'grand_total' => $cart->get('grand_total') + $this->_value,
        ]);

        return $this;
    }

    protected function _findAndSetMethodService($rates)
    {
        $minRates = [];
        foreach ($rates as $methodCode => $methodRates) {
            if (!empty($rates[$methodCode]['error'])) {
                continue;
            }
            $minPrice = 99999;
            foreach ($methodRates as $serviceCode => $serviceRate) {
                if ($serviceRate['price'] < $minPrice) {
                    $minService = $serviceCode;
                    $minPrice = $serviceRate['price'];
                }
            }
            $minRates[$methodCode] = ['service' => $minService, 'price' => $minPrice];
        }

        $defMethod = $this->BConfig->get('modules/FCom_Sales/default_shipping_method');
        $selMethod = $this->_cart->get('shipping_method');
        $selService = $this->_cart->get('shipping_service');

        if (!$selMethod && !$selService) { // if not set at all

            if (empty($minRates[$defMethod])) { // if no rate for default method
                $minPrice = 99999;
                foreach ($minRates as $methodCode => $minService) { // find cheapest method
                    if ($minService['price'] < $minPrice) {
                        $minPrice = $minService['price'];
                        $selMethod = $methodCode;
                    }
                }
            } else { // or set it as selected
                $selMethod = $defMethod;
            }
            $selService = null; // set cheapest method service as selected

        } elseif (empty($rates[$selMethod][$selService])) { // if selected service is not available

            $selService = null;

        }

        if (!$selService) { // if service is not set or was reset, set to cheapest
            $selService = $minRates[$selMethod]['service'];
        }

        return [$selMethod, $selService];
    }
}