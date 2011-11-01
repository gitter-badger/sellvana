<?
$navRoot = FCom_Nav::load(1);
$navRoot->descendants(); // cache preload
?>
<div class="site-nav-2">
    <ul id="nav">
        <li>
            <a href="#">Browse All Categories</a>
            <ul>
<? foreach (ACategory::load(1)->children() as $c): ?>
                <li class="level0 level-top">
                    <a href="<?=FCom_Catalog::url('c', $c->url_path)?>"><?=$this->q($c->node_name)?></a>
                </li>
<? endforeach ?>
            </ul>
        </li>
<? foreach ($navRoot->children() as $topNav): ?>
        <li>
            <a href="#"><?=$this->q($topNav->node_name)?></a>
            <ul>
<? foreach ($topNav->children('node_name') as $c): ?>
                <li class="level0 level-top">
                    <a href="<?=FCom_Catalog::url('c', $c->url_href)?>"><?=$this->q($c->node_name)?></a>
                </li>
<? endforeach ?>
            </ul>
        </li>
<? endforeach ?>
    </ol>
</div>