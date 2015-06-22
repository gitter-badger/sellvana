<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_StoreCredit_Model_Balance
 *
 * @property Sellvana_StoreCredit_Model_Transaction $Sellvana_StoreCredit_Model_Transaction
 */
class Sellvana_StoreCredit_Model_Balance extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_storecredit_balance';
    protected static $_origClass = __CLASS__;

    public function deposit($amount)
    {
        if (!$this->id()) {
            $this->save();
        }
        $this->add('amount', $amount);
        $trans = $this->Sellvana_StoreCredit_Model_Transaction->create([
            'balance_id' => $this->id(),
            'event' => 'deposit',
            'amount' => $amount,
        ])->save();
        $this->BEvents->fire(__METHOD__, ['balance' => $this, 'transaction' => $trans]);
        return $this;
    }

    public function withdraw($amount)
    {
        if (!$this->id()) {
            $this->save();
        }
        $this->add('amount', -$amount);
        $trans = $this->Sellvana_StoreCredit_Model_Transaction->create([
            'balance_id' => $this->id(),
            'event' => 'withdraw',
            'amount' => -$amount,
        ])->save();
        $this->BEvents->fire(__METHOD__, ['balance' => $this, 'transaction' => $trans]);
        return $this;
    }
}