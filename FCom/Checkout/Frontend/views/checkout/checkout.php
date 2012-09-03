
			<!--<button type="submit" name="update" class="button"><span><?= BLocale::_("Apply changes") ?></span></button>-->
<div class="page-checkout-review">
	<form action="<?=BApp::href('checkout')?>" method="post">
		<div class="col-checkout-review-left">
			<header class="page-title">
				<h1 class="title">Review and Place Order</h1>
			</header>
			<?php if ($this->messagesHtml()) :?>
				<p class="error"><?php echo $this->messagesHtml() ?></p>
			<?php endif; ?>
			<h2><?= BLocale::_('Review the information below then click "Place your order"') ?></h2>
			<div class="col2-set">
				<div class="col first">
					<h4><?= BLocale::_("Shipping to") ?></h4>
					<?=$this->shippingAddress?>
					<?php if($this->guest_checkout) :?>
					    <small><a href="<?=BApp::href('checkout/address?t=s')?>"><?= BLocale::_("Change") ?></a></small>
					<?php else :?>
					    <small><a href="<?=BApp::href('customer/address/choose?t=s')?>"><?= BLocale::_("Change") ?></a></small>
					<?php endif; ?>
				</div>
			<?php if (!empty($this->shippingMethods)) :?>
			    <div class="col last">
			    	<h4><?= BLocale::_("Shipping Options") ?>:</h4>
				    <ul>
				        <?php foreach($this->shippingMethods as $shippingMethod => $shippingClass): ?>
				        <li><?=$shippingClass->getDescription()?> (<?=$shippingClass->getEstimate()?>)
				            <ul>
				            <?php foreach($shippingClass->getServicesSelected() as $serviceKey => $service) :?>
				                <li style="margin-left: 20px;">
				                    <input type="radio" name="shipping" value="<?=$shippingMethod.':'.$serviceKey?>"
				                    <?= $shippingMethod == $this->cart->shipping_method &&
				                            $serviceKey == $this->cart->shipping_service ? 'checked' : '' ?>> <?=$service?></li>
				            <?php endforeach; ?>
				            </ul>
				        </li>
				        <?php endforeach; ?>
				    </ul>
				    <button type="submit" name="update" class="button btn-aux btn-sz1"><span><?= BLocale::_("Apply changes") ?></span></button>
				</div>
			<?php endif; ?>
			</div>
			<div class="divider"></div>
			<div class="col2-set">
			    <div class="col first">
			    	<h4><?= BLocale::_("Billing address") ?></h4>
				<?=$this->billingAddress?>
				<?php if($this->guest_checkout) :?>
				    <small><a href="<?=BApp::href('checkout/address?t=b')?>"><?= BLocale::_("Change") ?></a></small>
				<?php else :?>
				    <small><a href="<?=BApp::href('customer/address/choose?t=b')?>"><?= BLocale::_("Change") ?></a></small>
				<?php endif; ?>
				</div>
				<div class="col last">
					<h4><?= BLocale::_("Payment method") ?></h4>
			<?php if (!empty($this->paymentMethod)) :?>
			    	<small><a href="/checkout/payment"><?= BLocale::_("Change") ?></a></small><br/>

			    	<i><?=$this->paymentClass->getName()?></i><br/>
			    	<?= $this->view($this->paymentMethod.'/form')->set('paymentDetails', $this->paymentDetails);?>
			<?php else: ?>
			    <a href="/checkout/payment" style="color:red"><?= BLocale::_("Select payment method") ?></a><br/>
			<?php endif; ?>
				</div>
			</div>
			<div class="divider"></div>
			<section class="data-table">
				<table>
			        <col width="500"/>
			        <col width="70"/>
			        <col width="70"/>
			        <col width="70"/>
			        <thead>
			            <tr>
			                <th class="a-left"><?= BLocale::_("Product") ?></th>
			                <th class="a-right"><?= BLocale::_("Price") ?></th>
			                <th class="a-center"><?= BLocale::_("Qty") ?></th>
			                <th class="a-right"><?= BLocale::_("Subtotal") ?></th>
			            </tr>
			        </thead>
			        <tbody>
			<?php foreach ($this->cart->items() as $item): $p = $item->product() ?>
			            <tr id="tr-product-<?=$p->id?>">
			                <td class="a-left">
			                    <span class="product-name"><a href="<?=$this->q($p->url($this->category))?>"><?=$this->q($p->product_name)?></a></span>
			                </td>
			                <td class="a-right">
			                    <div class="price-box">
			                    	$<?=number_format($p->base_price, 2)?>
			                    </div>
			                </td>
			                <td class="a-center">
			                    <div class="price-box">
			                    	<?=number_format($item->qty, 0)?>
			                    </div>
			                </td>
			                <td class="a-right">
			                    <span class="price">$<?=number_format($item->rowTotal(), 2)?></span>
			                </td>
			            </tr>
			<?php endforeach ?>
			        </tbody>
			        <tfoot>
			            <td></td>
			            <td></td>
			            <td></td>
			            <td></td>
			        </tfoot>
			    </table>
			</section>
			<p><a href="/cart"><?= BLocale::_("Need to change quantities or delete") ?>?</a></p>

			<?php if ($this->guest_checkout) :?>
				<label for="#"><?= BLocale::_("Create an account") ?>?</label>
				<input type="checkbox" name="create_account" value="1" class="required"><br/>
				<label for="#">E-mail</label>
				<input type="text" name="account[email]" value="<?=$this->billingAddressObject->email?>" class="required"><br/>
				<label for="#"><?= BLocale::_("Password") ?></label>
				<input type="password" name="account[password]" class="required" id="model-password"/><br/>
				<label for="#"><?= BLocale::_("Confirm Password") ?> </label>
				<input type="password" name="account[password_confirm]" class="required" equalto="#model-password"/><br/>
			<?php endif; ?>
			<button type="submit" name="update" class="button btn-aux btn-sz1"><span><?= BLocale::_("Apply changes") ?></span></button>
		</div>
		<!-- .col-checkout-left ends -->
		<div class="col-checkout-review-right">
			<?php if (!empty($this->totals)) : ?>
		    <div class="cart-totals">
		    	<div class="block-promo">
					<header class="block-title"><?= BLocale::_("Coupon, discount or promo code") ?></header>
					<p><input type="text" name="discount_code"><button type="submit" name="coupon_submit" class="button btn-aux btn-sz1"><span><?= BLocale::_("Apply") ?></span></button></p>
				</div>
			    <table>
			    <?php foreach($this->totals as $totals) :?>
			        <tr>
			        	<td class="title"><?=$totals['options']['label']?></td>
			        	<td>$<?=number_format($totals['total'], 2)?><?php if (!empty($totals['error'])) :?><br/>(<span class="error"><?=$totals['error']?></span>)<?php endif;?></td>
			        </tr>
			    <?php endforeach; ?>
			        <tr class="grand-total">
			        	<td class="title"><?= BLocale::_("Grand total") ?></td>
			        	<td><?=$this->cart->calc_balance?></td>
			        </tr>
			    </table>
		    </div>
			<?php endif; ?>
           	<ul class="checkout-btns">
				<li><button type="submit" name="place_order" class="button btn-sz2"><span><?= BLocale::_("Place your order") ?></span></button></li>
			</ul>
		</div>
		<!-- .col-checkout-right ends -->
	</form>
</div>