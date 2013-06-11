<form action="<?=BApp::href('customer/address')?>" method="post">
    <?php if (!empty($this->address->id)): ?>
        <input type="hidden" name="id" value="<?=$this->address->id?>">
    <?php endif; ?>
    <?= BLocale::_("First name") ?>: <input type="text" name="firstname" value="<?=$this->address->firstname?>"><br/>
    <?= BLocale::_("Last name") ?>: <input type="text" name="lastname" value="<?=$this->address->firstname?>"><br/>
    <?= BLocale::_("Street 1") ?>: <input type="text" name="street1" value="<?=$this->address->street1?>"><br/>
    <?= BLocale::_("Street 2") ?>: <input type="text" name="street2" value="<?=$this->address->street2?>"><br/>
    <?= BLocale::_("City") ?>: <input type="text" name="city" value="<?=$this->address->city?>"><br/>

    <?=$this->view('geo/embed')?>
    <script>
        $(function() {
            $('.geo-country').geoCountryRegion({country:'<?=$this->address->country?>', region:'<?=$this->address->region?>'});
        })
    </script>
    <label for="#"><?= BLocale::_("Country") ?><em class="required">*</em></label>
    <select class="geo-country" name="country" id="country">
        <option value=""><?= BLocale::_("Select an option") ?></option>
    </select>

    <select class="geo-region required" name="region" >
        <option value=""><?= BLocale::_("Select an option") ?></option>
    </select>
    <input type="text" class="geo-region" name="region" />

    <br/>
    <?= BLocale::_("Zip") ?>: <input type="text" name="postcode" value="<?=$this->address->postcode?>"><br/>


    <input type="checkbox" name="address_default_shipping" value="1"
           <?=$this->default_shipping == 1?'checked':''?>>
    <?= BLocale::_("Set as default shipping address") ?> <br/>
    <input type="checkbox" name="address_default_billing" value="1"
           <?=$this->default_billing == 1?'checked':''?>>
    <?= BLocale::_("Set as default billing address") ?>
        <br/>

    <input type="submit" value="<?= BLocale::_("Save address") ?>">

</form>