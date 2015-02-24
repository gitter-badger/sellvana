<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Main
 *
 * @property FCom_Promo_Model_Promo $FCom_Promo_Model_Promo
 * @property FCom_Promo_Model_PromoCart $FCom_Promo_Model_PromoCart
 */
class FCom_Promo_Main extends BClass
{
    public function onWorkflowCustomerAddsCouponCode($args)
    {
        $cart = $args['cart'];
        $couponCode = $args['coupon_code'];

        $promos = $this->FCom_Promo_Model_Promo->findByCouponCodes([$couponCode]);
        if (!$promos) {
            $result['error']['message'] = 'Coupon not found';
            return;
        }
        $hlp = $this->FCom_Promo_Model_PromoCart;
        foreach ($promos as $promo) {
            if (!$hlp->validateConditions($promo, $cart)) {
                $result['error']['message'] = "Coupon can't be applied to your cart";
                return;
            }
        }
        $result['success'] = true;
        unset($result['error']);
    }

    public function onCartDiscountCalculate($args)
    {
        /** @var FCom_Sales_Model_Cart $cart */
        $cart = $args['cart'];
        $validateResult = [];
        $result =& $args['result'];
        $stopFlag = 0;

        $hlp = $this->FCom_Promo_Model_PromoCart;
        $couponCodes = $cart->getCouponCodes();
        if ($couponCodes) {
            $couponPromos = $this->FCom_Promo_Model_Promo->findByCouponCodes($couponCodes);
            foreach ($couponPromos as $couponPromo) {
                $validateResult = $hlp->validateConditions($couponPromo, $cart);
                if (!empty($validateResult['match'])) {
                    $result['promos'][$couponPromo->id()]['coupon_code'] = $couponPromo->get('coupon_code');
                    $result['promos'][$couponPromo->id()]['coupon_id'] = $couponPromo->get('coupon_id');
                    $hlp->calculateActions($couponPromo, $cart, $validateResult, $result);
                    if ($couponPromo->get('stop_flag')) {
                        $stopFlag = 1;
                        break;
                    }
                }
            }
        }

        if (!$stopFlag) {
            /** @var FCom_Promo_Model_Promo[] $noCouponPromos */
            $noCouponPromos = $this->FCom_Promo_Model_Promo->findActiveOrm()
                ->where('promo_type', 'cart')->where('coupon_type', 0)->find_many();

            foreach ($noCouponPromos as $promo) {
                $validateResult = $hlp->validateConditions($promo, $cart);
                if (!empty($validateResult['match'])) {
                    $hlp->calculateActions($promo, $cart, $validateResult, $result);
                    if ($promo->get('stop_flag')) {
                        break;
                    }
                }
            }
        }

        $hlp->applyActions($cart, $result);
    }

    public function onCatalogDiscountCalculate($args)
    {
        /** @var FCom_Promo_Model_Promo[] $noCouponPromos */
        $promos = $this->FCom_Promo_Model_Promo->findActiveOrm()
            ->where('promo_type', 'catalog')->where('coupon_type', 0)->find_many();

        foreach ($promos as $promo) {

        }
    }


    public function compareValues($v1, $v2, $op)
    {
        switch ($op) {
            case 'gt':          return $v1 > $v2;
            case 'gte':         return $v1 >= $v2;
            case 'lt':          return $v1 < $v2;
            case 'lte':         return $v1 <= $v2;
            case 'eq':          return $v1 == $v2;
            case 'neq':         return $v1 != $v2;
            case 'is':          return in_array($v1, (array)$v2, false);
            case 'in':          return in_array($v1, (array)$v2, false);
            case 'is_not':      return !in_array($v1, (array)$v2, false);
            case 'not_in':      return !in_array($v1, (array)$v2, false);
            case 'empty':       return $v1 === null || $v1 === false || $v1 === '';
            case 'contains':    return strpos($v1, $v2) !== false;
            case 'between':     return $v1 >= $v2[0] && $v1 <= $v2[1];
            default:            throw new BException('Invalid operator: '. $op);
        }
    }

    public function validateProductConditionCombination(FCom_Catalog_Model_Product $product, array $condition)
    {
        $finalMatch = $condition['match'] === FCom_Promo_Model_Promo::MATCH_ALL ? true : false;
        foreach ($condition['fields'] as $fieldCond) {
            list($fieldSource, $fieldCode) = explode('.', $fieldCond['field']);

            switch ($fieldSource) {
                case 'field':
                case 'static':
                    $value = $product->get($fieldCode);
                    break;

                case 'stock':
                    $value = $product->getInventoryModel()->get($fieldCode);
                    break;

                default:
                    throw new BException('Invalid field source: ' . $fieldSource);
            }

            $match = $this->compareValues($value, $fieldCond['value'], $fieldCond['filter']);

            if ($condition['match'] === FCom_Promo_Model_Promo::MATCH_ANY && $match) {
                $finalMatch = true;
                break;
            } elseif ($condition['match'] === FCom_Promo_Model_Promo::MATCH_ALL && !$match) {
                $finalMatch = false;
                break;
            }
        }
        return $finalMatch;
    }
}