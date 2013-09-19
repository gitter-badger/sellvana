<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_Payment_Frontend_Controller
    extends FCom_Frontend_Controller_Abstract
{
    public function action_report()
    {
        $transactionId = BRequest::get('transaction_id');
        if ($transactionId) {
            /* @var $paymentMethod FCom_PaymentIdeal_PaymentMethod */
            $paymentMethod = FCom_PaymentIdeal_PaymentMethod::i();
            $paymentMethod->checkPayment($transactionId);
            $paymentMethod->setOrderPaid($transactionId);
        }
    }

    public function action_return()
    {
        $transactionId = BRequest::get('transaction_id');
        if($transactionId){
            /* @var $paymentMethod FCom_PaymentIdeal_PaymentMethod */
            $paymentMethod = FCom_PaymentIdeal_PaymentMethod::i();
            $order = $paymentMethod->loadOrderByTransactionId($transactionId);
            $sData =& BSession::i()->dataToUpdate();
            $sData['last_order']['id'] = $order ? $order->id : null;
        }
        return $this->forward('success', 'FCom_Checkout_Frontend_Controller_Checkout'); // forward to success page
    }
}