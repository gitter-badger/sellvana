<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Promo_Frontend_Controller_Cart
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 * @property FCom_Promo_Model_PromoCart $FCom_Promo_Model_PromoCart
 */
class FCom_Promo_Frontend_Controller_Cart extends FCom_Frontend_Controller_Abstract
{

    public function action_add_free_item()
    {
        $post = $this->BRequest->request();

        if (!$this->BSession->validateCsrfToken($post['token'])) {
            $this->BResponse->redirect('cart');
            return;
        }

        $result = [];

        try {
            $cart = $this->FCom_Sales_Model_Cart->sessionCart();
            $this->FCom_Promo_Model_PromoCart->addFreeItem($post, $cart);
            $cart->calculateTotals()->saveAllDetails();

            $this->FCom_Sales_Main->workflowAction('customerAddsFreeItemsToCart', [
                'post' => $post,
                'result' => &$result,
            ]);
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        if (!empty($result['error'])) {
            $this->message($result['message'], 'error');
        } else {
            $this->message('Free item has been added to cart');
        }
        $this->BResponse->redirect('cart');
    }
}