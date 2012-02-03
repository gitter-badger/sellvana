<?php
$m = $this->model;
$mediaCtrl = FCom_Admin_Controller_MediaLibrary::i();
$vAutocompleteUrl = BApp::url('Denteva_Admin', '/vendors/autocomplete');
?>
<div id="images-layout">
    <div class="ui-layout-west">

        <input type="hidden" name="_add_images" value=""/>
        <input type="hidden" name="_del_images" value=""/>
        <?=$this->view('jqgrid')->set('config', array(
            'grid' => array(
                'id' => 'product_images',
                'caption' => 'Product Images',
                'datatype' => 'local',
                'data' => BDb::many_as_array($m->mediaORM('A')->select('a.id')->select('a.file_name')->find_many()),
                'colModel' => array(
                    array('name'=>'id', 'label'=>'ID', 'width'=>400, 'hidden'=>true),
                    array('name'=>'file_name', 'label'=>'File Name', 'width'=>400, 'formatter'=>"function(val,opt,obj) {
                        return val+'<input type=\"hidden\" name=\"images[]\" value=\"'+obj.id+'\"/>';
                    }"),
                ),
                'multiselect' => true,
                'multiselectWidth' => 30,
            ),
            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>false),
            array('navButtonAdd', 'caption' => 'Add', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Images to Product', 'cursor'=>'pointer'),
            array('navButtonAdd', 'caption' => 'Remove', 'buttonicon'=>'ui-icon-trash', 'title' => 'Remove Images From Product', 'cursor'=>'pointer'),
        )) ?>
    </div>

    <div class="ui-layout-center">
        <?=$this->view('jqgrid')->set('config', $mediaCtrl->gridConfig(array('id'=>'all_images', 'folder'=>'media/product/image'))) ?>
    </div>
</div>
<script>

var imagesLayout = $('#images-layout').height($('.adm-wrapper').height()).layout({
    useStateCookie: true,
    west__minWidth:400,
    west__spacing_open:20,
    west__closable:false,
    triggerEventsOnLoad: true,
    onresize:function(pane, $Pane, paneState) {
        $('.ui-jqgrid-btable:visible', $Pane).each(function(index) {
            $(this).setGridWidth(paneState.innerWidth - 20);
        });
    }
});

var attachmentsGrid = new FCom_Admin.MediaLibrary({
    grid:'#all_images',
    url:'<?=BApp::url('FCom_Admin', '/media/grid')?>',
    folder:'media/product/image',
    oneditfunc:function(tr) { $('input[name=manuf_vendor_name]', tr).fcom_autocomplete({url:'<?=$vAutocompleteUrl?>'}); }
});
$('#all_images #gs_manuf_vendor_name').fcom_autocomplete({url:'<?=$vAutocompleteUrl?>'});

attachmentsGrid.initTargetGrid('#product_images');
</script>