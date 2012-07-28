<h2>Orders history</h2>

<?php if ($this->orders) :?>
    <table border="1">
        <tr>
            <th width="50px;">ID</th>
            <th width="200px;">Shipping</th>
            <th width="200px;">Payment</th>
            <th width="200px;">Status</th>
            <th width="200px;">Balance</th>
        </tr>
    <?php foreach($this->orders as $order) :?>
        <tr>
            <td align="center"><a href="<?=Bapp::href('customer/order/view')?>?id=<?=$order->id?>"><?=$order->id?></a> </td>
            <td align="center"><?=$order->shipping_method?> </td>
            <td align="center"><?=$order->payment_method?> </td>
            <td align="center"><?=$order->status?> </td>
            <td align="center"><?=$order->balance?> </td>
        </tr>
    <?php endforeach; ?>
    </table>
<?php endif; ?>
