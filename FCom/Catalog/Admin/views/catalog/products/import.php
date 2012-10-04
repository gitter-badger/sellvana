<header class="adm-page-title">
    <span class="title">Upload and Import Products</span>
    <div class="btns-set">
    </div>
</header>
<div id="import-accordion">
    <h3><a href="#">Step 1: Upload or select file</a></h3>
    <div>
        <form method="POST">
<?=$this->view('jqgrid')->set('config', FCom_Admin_Controller_MediaLibrary::i()->gridConfig(array(
    'id' => 'import_files',
    'folder' => 'storage/import/products',
    'config' => array(
        'grid' => array(
            'multiselect'=>false,
            'autowidth'=>false,
            'width'=>600,
            'height'=>300,
        ),
    ),
))) ?>
            <br/><button type="button" class="btn st1 sz2" id="step1-next">Select file and go to next step</button>
        </form>
    </div>
    <h3><a href="#">Step 2: Configure columns and other options</a></h3>
    <div>
        <div id="import-config">
        </div>
    </div>
    <h3><a href="#">Step 3: Proceed with Import</a></h3>
    <div>
        <div id="import-status">
        </div>
    </div>
    <!--
    <h3><a href="#">Step 4: Review Import results</a></h3>
    <div>

    </div>
    -->
</div>
<script>
head(function() {
    $('#import-accordion').accordion({});

    importsGrid = new FCom.Admin.MediaLibrary({
        grid:'#import_files',
        url:'<?=BApp::href('media/grid')?>',
        folder:'storage/import/products'
    });

    $('#step1-next').click(function(ev) {
        var sel = importsGrid.getSelectedRows();
        if (!sel.length) {
            alert('Please select one file');
            return;
        }
        var row = $('#import_files').jqGrid('getRowData', sel[0]);
        $('#import-accordion').accordion('activate', 1);
        $('#import-config').html('Please wait loading file configuration...');
        $.getJSON('<?=BApp::href('catalog/products/import/config')?>?file='+encodeURIComponent(row.file_name), function(data, status, xhr) {
            $('#import-config').html(data.html);
        });
    });

    $('#step2-next').live('click', function(ev) {
        $('#import-accordion').accordion('activate', 2);
        $('#import-status').html('Please wait starting import...');
        $.post('<?=BApp::href('catalog/products/import/config')?>', $('#import-columns-form').serialize(), function(data, status, xhr) {
            $('#import-status').html(data);
        });
    });

    $('#step3-start').live('click', function(ev) {
        $('#import-status').load('<?=BApp::href('catalog/products/import/status')?>?start=true');
    });

    $('#step3-stop').live('click', function(ev) {
        $.post('<?=BApp::href('catalog/products/import/stop')?>', $('#import-columns-form').serialize(), function(data, status, xhr) {
            $('#import-status').html(data);
        });
    });
});
</script>