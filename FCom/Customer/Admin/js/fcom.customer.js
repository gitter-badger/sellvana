define(['jquery', 'fcom.pushclient', 'jquery.bootstrap-growl'], function ($, PushClient) {

    PushClient.listen({channel: 'customers_feed', callback: channel_customers_feed});

    function channel_customers_feed(msg) {
        switch (msg.signal) {
            case 'new_customer':
                var c = msg.customer;
                var href = FCom.base_href + 'customers/form/?id=' + c.id;
                var cLink = '<a href="' + href + '">' + c.name + ' (' + c.email + ')</a>';
                $.bootstrapGrowl(cLink + ' ' + c.mes, {type: 'success', align: 'center', width: 'auto'});
                break;
            case 'new_order':
                var o = msg.order;
                var href = FCom.base_href + 'orders/form/?id=' + o.id;
                var cLink = '<a href="' + href + '">#' + o.id + '</a>';
                $.bootstrapGrowl(o.mes_be + ' ' + cLink + ' ' + o.mes_af + ' ' + o.name, {type: 'success', align: 'center', width: 'auto'});
                break;
        }
    }

})
