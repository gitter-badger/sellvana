<header class="adm-page-title">
    <span class="title">Product Functions</span>
    <div class="btns-set">
        <button class="st1 sz2 btn" onclick="location.href='<?php echo BApp::href('indextank/product_functions/form/')?>'"><span>New Function</span></button>
    </div>
</header>

<h3>Index <?=$this->status['name']?> (created at <?=date("Y-m-d", strtotime($this->status['date']))?>)
        Status: <?=$this->status['status']?>
</h3>
<h3>Size: <?=$this->status['size']?> documents</h3>

<?php echo $this->view('jqgrid') ?>
