<?php
$m = $this->model;
$tabs = $this->sortedTabs();
$formUrl = BApp::href('cms/blocks/form/?id='.$m->id)
?>
<script>
head(function() {
    window.adminForm = FCom.Admin.form({
        tabs:     '.adm-tabs li',
        panes:    '.adm-tabs-content',
        url_get:  '<?php echo $formUrl ?>',
        url_post: '<?php echo $formUrl ?>'
    });
})
</script>
<form action="<?php echo $formUrl ?>" method="post">
    <input type="hidden" id="tab" name="tab" value="<?=$this->cur_tab?>"/>
    <header class="adm-page-title">
        <span class="title"><?php echo $m->id ? 'Edit CMS Block: '.$this->q($m->handle) : 'Create New CMS Block' ?></span>
        <div style="float:right">
            <button class="st1 sz2 btn" onclick="adminForm.saveAll()"><span><?php echo BLocale::_('Save')?></span></button>
        </div>
    </header>

    <section class="adm-content-box info-view-mode">
            <nav class="adm-tabs">
                <ul>
    <?php foreach ($tabs as $k=>$tab): ?>
                    <li <?php if ($k===$this->cur_tab): ?>class="active"<?php endif ?>>
                        <a href="#tab-<?php echo $this->q($k) ?>"><span class="icon"></span><?php echo $this->q($tab['label']) ?></a>
                    </li>
    <?php endforeach ?>
                </ul>
            </nav>
            <div class="adm-tabs-container">
    <?php foreach ($tabs as $k=>$tab): ?>
                <section id="tab-<?php echo $this->q($k) ?>" class="adm-tabs-content"
                    <?php if ($k!==$this->cur_tab): ?>hidden<?php endif ?>
                    <?php if (empty($tab['async'])): ?>data-loaded="true"<?php endif ?>
                >
    <?php if (empty($tab['async'])) echo $this->view($tab['view']) ?>
                </section>
    <?php endforeach ?>
            </div>
    </section>
</form>
