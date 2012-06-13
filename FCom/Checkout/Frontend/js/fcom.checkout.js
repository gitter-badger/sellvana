function FulleronCart(opt) {
    function add(id, qty) {
        $.post(opt.apiUrl, {action:'add', id:id, qty:qty||1}, function(data) {
            //console.log(data);
            $.pnotify({pnotify_title:data.title,
                pnotify_text:'<div class="ui-pnotify ui-widget ui-helper-clearfix" style="min-height: 56px; width: 300px; opacity: 1; display: block; right: 15px; top: 15px;">'+data.html+'</div>'});
            $('.cart-num-items').html(data.cnt);
        });
    }
    return {add:add};
}

function add_cart(id, qty){
    var fc = new FulleronCart({"apiUrl":"/cart"});
    fc.add(id, qty);
}