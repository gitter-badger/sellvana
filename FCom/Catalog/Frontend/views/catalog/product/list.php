<?=$this->view('catalog/product/pager')->set('state', $this->products_data['state'])?>
<?php if (!$this->products_data['state']['c']): ?>
    <p class="note-msg"><?= BLocale::_("There are no products matching the selection") ?>.</p>

<?php else: ?>
	<?=$this->view('catalog/compare/block')?>
	<div class="product-listing">
	    <table>
	        <col width="60"/>
	        <col/>
	        <col width="100"/>
	        <tbody>
	            <?=$this->view('catalog/product/rows')
	                ->set('products', $this->products_data['rows'])
	                ->set('category', $this->category) ?>
	        </tbody>
	    </table>
    </div>
<?php endif ?>
