<h2>My account</h2>

Hello <b><?=$this->customer->firstname?></b><br/>
E-mail: <b><?=$this->customer->email?></b><br/>
<a href="<?=BApp::href('customer/myaccount/edit')?>">Edit</a><br/>
<a href="<?=BApp::href('customer/myaccount/editpassword')?>">Edit password</a>

<br/>
<br/>
<a href="<?=BApp::href('customer/order')?>">Orders history</a>

<br/>
<br/>
<a href="<?=BApp::href('customer/address/shipping')?>">Shipping Address</a><br/>
<a href="<?=BApp::href('customer/address/billing')?>">Billing Address</a><br/>