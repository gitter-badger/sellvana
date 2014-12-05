<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_AuthorizeNet_PaymentMethod_Aim
 *
 * @property FCom_AuthorizeNet_AimApi $FCom_AuthorizeNet_AimApi
 * @property FCom_AuthorizeNet_Model_Settings $FCom_AuthorizeNet_Model_Settings
 * @property FCom_Sales_Model_Order_Payment $FCom_Sales_Model_Order_Payment
 */

class FCom_AuthorizeNet_PaymentMethod_Aim extends FCom_Sales_Method_Payment_Abstract
{

    const PAYMENT_METHOD_KEY = "authorizenet_aim";

    function __construct()
    {
        $this->_name = 'Authorize.net';
        $this->_capabilities['pay_online'] = 1;
        $this->_capabilities['void_online'] = 1;
        $this->_capabilities['refund_online'] = 1;
    }

    public function getCheckoutFormView()
    {
        return $this->BLayout->view('authorizenet/aim')->set('key', static::PAYMENT_METHOD_KEY);
    }

    public function payOnCheckout()
    {
        $config = $this->config();
        if (!$config['active']) {
            // log this and eventually show a message
            return null;
        }
        $action = $config['payment_action'];

        /* @var $api FCom_AuthorizeNet_AimApi */
        $api = $this->FCom_AuthorizeNet_AimApi;

        switch ($action) {
            case 'AUTH_ONLY':
                $response = $api->authorize($this);
                break;
            case 'AUTH_CAPTURE':
                $response = $api->sale($this);
                break;
            default :
                // log and show message
                return null;
                break;
        }
        $success = $response['response_code'] == 1;
        if ($success) {
            $this->setDetail($response['transaction_id'], $response);
            $this->setDetail('transaction_id', $response['transaction_id']);
            $status = $action == 'AUTH_ONLY' ? 'authorized' : 'paid';
        } else {
            $status = 'error';
        }
        $paymentData = [
            'method'           => static::PAYMENT_METHOD_KEY,
            'parent_id'        => $response['transaction_id'],
            'order_id'         => $this->getOrder()->id(),
            'amount'           => $this->getDetail('amount_due'),
            'transaction_status' => $status,
            'transaction_id'   => $response['transaction_id'],
            'transaction_type' => $action == 'AUTH_ONLY' ? 'authorize' : 'sale',
            'online'           => 1,
        ];
        $this->clear();
        $paymentModel = $this->FCom_Sales_Model_Order_Payment->create($paymentData)->save();
        $paymentModel->setData('response', $response);
        $paymentModel->save();
        return $response;
    }

    public function getOrder()
    {
        return $this->_order;
    }

    public function getCardNumber()
    {
        if (isset($this->_details['cc_num'])) {
            return $this->_details['cc_num'];
        }
        return null;
    }

    public function getDetail($key)
    {
        if (isset($this->_details[$key])) {
            return $this->_details[$key];
        }
        return null;
    }

    public function setDetail($key, $value)
    {
        $this->_details[$key] = $value;
    }

    /**
     * @return array
     */
    public function cardTypes()
    {
        return $this->FCom_AuthorizeNet_Model_Settings->cardTypes();
    }

    /**
     * @return array|null
     */
    public function config()
    {
        $config = $this->BConfig;
        return $config->get('modules/FCom_AuthorizeNet/aim');
    }

    public function setDetails($details)
    {
        $details = isset($details[static::PAYMENT_METHOD_KEY]) ? $details[static::PAYMENT_METHOD_KEY] : [];

        if (isset($details['expire'], $details['expire']['month'], $details['expire']['year'])) {
            $details['card_exp_date'] = $details['expire']['month'] . '/' . $details['expire']['year'];
        }

        return parent::setDetails($details);
    }

    public function setSalesEntity($order, $options)
    {
        $this->setDetail('amount_due', $order->amount_due);
        return parent::setSalesEntity($order, $options);
    }

    public function getPublicData()
    {
        $data = $this->_details;
        if (!empty($data) && isset($data['cc_num'])) {
            $data['last_four'] = $this->lastFour();
        }
        return $data;
    }

    protected function lastFour()
    {
        $lastFour = $this->getDetail('last_four');
        $ccNum    = $this->getDetail('cc_num');
        if (!$lastFour && $ccNum) {
            $this->setDetail('last_four', substr($ccNum, -4));
        }
        return $this->getDetail('last_four');
    }
    protected function clear()
    {
        $this->lastFour();
        unset($this->_details['cc_num']);
    }

    public function asArray()
    {
        $data = parent::asArray();
        $data = $this->BUtil->arrayMerge($data, $this->getPublicData());
        return [static::PAYMENT_METHOD_KEY => $data];
    }

}
