<script>
require(['jquery'], function($) {
    $(function(){
        var geoCountries = <?php echo BUtil::toJson(FCom_Geo_Model_Country::i()->options($this->countries)) ?>;
        var geoRegions = <?php echo BUtil::toJson(FCom_Geo_Model_Region::i()->allOptions()) ?>;

        $.fn.geoCountryRegion = function(opt) {
            opt = opt || {};
            var $country = this;
            var $regionSelect = $(opt.regionSelectEl || 'select.geo-region');
            var $regionSelect2 = $('#s2id_' + $regionSelect.attr('id') );
            console.log($regionSelect2);
            var regionLeave = $('option', $regionSelect).length;
            var $regionInput = $(opt.regionInputEl || 'input.geo-region');
            var country = opt.country || '';
            var region = opt.region || '';
            var regionDefHtml = $regionSelect.html();
            for (i in geoCountries) {
                $country.append($('<option>').val(i).text(geoCountries[i]));
            }
            $country.val(country);

            $country.change(function(ev) {
                country = $country.val();
                var regions = country ? geoRegions[country] : null;
                if (regions) {
                    $regionSelect.html(regionDefHtml);
                    for (i in regions) {
                        $regionSelect.append($('<option>').val(i).text(regions[i]));
                    }
                    $regionSelect.val(region);
                    $regionSelect.show().removeAttr('disabled');
                    $regionSelect2.show();
                    $regionInput.hide().attr('disabled', 'disabled');
                } else {
                    $regionSelect2.hide();
                    $regionSelect.hide().attr('disabled', 'disabled');
                    $regionInput.show().removeAttr('disabled');
                }
            });
            $country.trigger('change');

            $regionSelect.change(function(ev) { region = $regionSelect.val(); });
            $regionInput.change(function(ev) { region = $regionInput.val(); });
            return this;
        }

    })
})
</script>
