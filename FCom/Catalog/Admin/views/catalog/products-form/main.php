<?php
    $m = $this->model;
?>

<?php if (!$this->mode || $this->mode==='view'): ?>

    <div class="adm-section-group">
        <div class="btns-set">
        	<button class="btn st2 sz2 btn-edit" onclick="return adminForm.tabAction('edit', this);"><span>Edit</span></button>
       	</div>
        <ul class="form-list">
            <li>
                <h4 class="label">Product Name</h4>
                <?php echo $this->q($m->product_name) ?>
            </li>
            <li>
                <h4 class="label">Short Description</h4>
                <?php echo $this->q($m->short_description) ?>
            </li>
            <li>
                <h4 class="label">Long Description</h4>
                <?php echo $m->description ?>
            </li>
            <li>
                <h4 class="label">Price</h4>
                <?php echo $this->q($m->base_price) ?>
            </li>
            <li>
                <h4 class="label">Shipping Weight</h4>
                <?php echo $this->q($m->weight) ?>
            </li>
        </ul>
    </div>

<script>
head(function() {
    adminForm.wysiwygDestroy('general-info-description');
});
</script>

<?php elseif ($this->mode==='create' || $this->mode==='edit'): ?>

    <fieldset class="adm-section-group">

        <ul class="form-list">
            <li>
                <h4 class="label">Product Name</h4>
                <input type="text" name="model[product_name]" value="<?php echo $this->q($m->product_name) ?>"/>
            </li>
            <li>
                <h4 class="label">Short Description</h4>
                <input type="text" name="model[short_description]" value="<?php echo $this->q($m->short_description) ?>"/>
            </li>
            <li>
                <h4 class="label">Long Description</h4>

                <textarea id="general-info-description" name="model[description]"><?php echo $this->q($m->description) ?></textarea>
            </li>
            <li>
                <h4 class="label">Price</h4>
                <input type="text" name="model[base_price]" value="<?php echo $this->q($m->base_price) ?>"/>
            </li>
            <li>
                <h4 class="label">Shipping Weight</h4>
                <input type="text" name="model[weight]" value="<?php echo $this->q($m->weight) ?>"/>
            </li>
            <li>
                <h4 class="label">Hide product</h4>
                <input type="hidden" name="model[is_hidden]" value="0"/>
                <input type="checkbox" name="model[is_hidden]" value="1" <?= 1 == $m->is_hidden ? 'checked' : '' ?>/>
            </li>
        </ul>
    </fieldset>
    <script>
head(function() {
    adminForm.wysiwygCreate('general-info-description');
});
    </script>

<?php endif ?>

<?php echo $this->hook('catalog/products/tab/main', array('model'=>$this->model)) ?>