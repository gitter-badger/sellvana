<script>
    require(['jquery'], function($) {
        $.get("<?=BApp::href('marketclient/site/request_nonce')?>", function(response) {
            $('#market-remote-container').html('<iframe width="100%" height="100%" src="' + response.setup_url + '"></iframe>');
        });
    })
</script>
<div id="market-remote-container" style="height:100%"></div>
