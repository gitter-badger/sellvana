<?php $c = $this->model ?>
<fieldset>
<div class="settings-container">
    <div class="group">
        <h3><a href="#">Area Settings</a></h3>
        <div>
            <table>
                <tr><td>IP: Mode</td><td><textarea name="config[modules][FCom_Admin][mode_by_ip]" style="width:400px; height:100px"><?php echo $this->q($c->get('modules/FCom_Admin/mode_by_ip')) ?></textarea></td></tr>
                <tr><td>Modules to run in RECOVERY mode</td><td><input type="text" name="config[modules][FCom_Admin][recovery_modules]" value="<?php echo $this->q($c->get('modules/FCom_Admin/recovery_modules'))?>"/></td></tr>
            </table>
        </div>
    </div>
</div>
</fieldset>