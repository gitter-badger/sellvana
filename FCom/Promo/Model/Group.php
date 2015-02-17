<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Model_Group
 *
 * @property int $id
 * @property int $promo_id
 * @property string $group_type enum('buy','get')
 * @property string $group_name
 *
 * @deprecated
 *
 * DI
 * @property FCom_Promo_Model_Product $FCom_Promo_Model_Product
 */
class FCom_Promo_Model_Group extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_promo_group';

    /**
     * @return FCom_Promo_Model_Product[]|null
     */
    public function products()
    {
        return $this->FCom_Promo_Model_PromoProduct->orm()->where('group_id', $this->id())->find_many();
    }
}
