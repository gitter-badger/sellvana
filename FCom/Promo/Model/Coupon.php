<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * @property FCom_Promo_Model_Promo $FCom_Promo_Model_Promo
 * @project sellvana_core
 */
class FCom_Promo_Model_Coupon extends BModel
{
    protected        $_table     = 'fcom_promo_coupon';
    static protected $_origClass = __CLASS__;

    /**
     * Generate number fo coupon codes for a promotion
     * $params = [
     *  'promo_id' => $id,
     *  'pattern' => $pattern,
     *  'length' => $length,
     *  'uses_per_customer' => $usesPerCustomer,
     *  'uses_total' => $usesTotal,
     *  'count' => $couponCount
     * ]
     * @param array $params
     * @return null
     */
    public function generateCoupons($params)
    {
        if (empty($params['promo_id'])) {
            return null;
        }
        $promo = $this->FCom_Promo_Model_Promo->load($params['promo_id']);
        if(!$promo){
            throw new \InvalidArgumentException("Promotion not found.");
        }
        $data = [
            'promo_id' => $promo->id(),
            'uses_per_customer' => empty($params['uses_per_customer'])? null: (int)$params['uses_per_customer'],
            'uses_total' => empty($params['uses_total'])? null: (int)$params['uses_total'],
        ];

        $paramsCount = empty($params['count'])? 1: (int)$params['count'];
        if (empty($params['pattern'])) { // no pattern supplied, first generate a random pattern
            $length = empty($params['length'])? 8: (int)$params['length'];
            $pattern = '{UDL' . $length . '}';
        } else {
            $pattern = $params['pattern'];
        }
        $codes = $this->prepareCodes($pattern, $paramsCount);
        $count = 0;
        foreach ($codes as $code) {
            $coupon = array_merge($data, ['code' => $code]);
            static::create($coupon)->save();
            $count++;
        }
        return ['generated' => $count, 'failed' => ($paramsCount - $count), 'codes' => $codes];
    }

    /**
     * @param $pattern
     * @param $paramsCount
     * @return array
     */
    protected function prepareCodes($pattern, $paramsCount)
    {
        $done = false;
        $count = $paramsCount;
        $codes = [];
        $limit = 100; // 100 tries to generate the coupons
        while (!$done) {
            $count = $count - count($codes); // calculate how many more need to be generated
            for ($i = 0; $i < $count; $i++) {
                $code = $this->generateCouponCode($pattern);
                if (!isset($codes[$code])) { // if code repeats, don't add it
                    $codes[$code] = 1;
                }
            }
            $codes = $this->filterOutExistingCodes($codes); // codes now has just valid (unique) codes in it.
            $done = (count($codes) == $paramsCount); // if number of codes is equal to requested number of codes, we're done
            if ($limit-- == 0) { // if limit has reached 0, give up
                break;
            }
        }
        return array_keys($codes);
    }

    /**
     * @param $pattern
     * @return string
     */
    public function generateCouponCode($pattern)
    {
        $code = $this->BUtil->randomPattern($pattern);
        return $code;
    }

    /**
     * Check if any of the supplied codes already exists and if so remove it from results
     * @param $codes
     */
    protected function filterOutExistingCodes($codes)
    {
        $sql = "SELECT `code` FROM " . static::table() . " WHERE `code` IN "; // check if codes exist already
        $place_holders = implode(',', array_fill(0, count($codes), '?'));
        $sql .= "($place_holders)";
        $PDO = BORM::get_db();
        $res = $PDO->prepare($sql);
        $res->execute(array_keys($codes));
        while ($row = $res->fetchObject()) {
            unset($codes[$row->code]); // if code exists, remove it
        }
        return $codes;
    }
}
