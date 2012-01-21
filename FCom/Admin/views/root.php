<?php
    $user = FCom_Admin_Model_User::sessionUser();
?>
<!DOCTYPE html>
<html>
<head>
    <?php echo $this->hook('head') ?>
    <script>
window.appConfig = {
    baseHref: '<?php echo BApp::m('FCom_Admin')->baseHref() ?>'
}
    </script>
</head>
<body class="<?php echo $this->bodyClass ?>">
	<div class="adm-wrapper">
<?php if (FCom_Admin_Model_User::i()->isLoggedIn()): ?>
		<header class="adm-topbar">
			<span class="adm-logo">Denteva Admin</span>
			<nav class="sup-links">
				<ul>
					<li class="sup-quicksearch"><a href="#"><span class="icon"></span><span class="title">Quicksearch</span></a>
						<form action="#" method="post" class="sub-section">
							<fieldset>
								<ul class="form-list">
									<li>
										<select>
											<option value="#">Customers</option>
											<option value="#">Products</option>
											<option value="#">Orders</option>
										</select>
									</li>
									<li><input type="text" name=""/></li>
								</ul>
								<input type="submit" value="Search" class="btn st2 sz2"/>
							</fieldset>
						</form>
					</li>
					<li class="sup-shortcuts"><a href="#"><span class="icon"></span><span class="title">Shortcuts</span></a>
                        <ul class="sub-section">
                            <li><a href="">New Product</a></li>
                            <li><a href="">New Company</a></li>
                            <li><a href="<?php echo BApp::m('FCom_Admin')->baseHref()?>/logout">New User</a></li>
                        </ul>
                    </li>
					<li class="sup-updates"><a href="#"><span class="icon"></span><span class="title">Updates &nbsp;<em class="count">10</em></span></a></li>
					<li class="sup-account"><a href="#"><span class="icon"></span><span class="title"><?php echo $this->q($user->fullname()) ?></span></a>
						<ul class="sub-section">
							<li><a href="">My Account</a></li>
							<li><a href="">My Reports</a></li>
							<li><a href="<?php echo BApp::m('FCom_Admin')->baseHref()?>/logout">Log Out</a></li>
						</ul>
					</li>
				</ul>
			</nav>
			<strong class="adm-group-title">Catalog</strong>
		</header>
	    <section class="adm-nav-bg"></section>
        <nav class="adm-nav">
		    <?=$this->renderNodes() ?>
	    </nav>
        <div class="adm-middle"><?php echo $this->hook('main') ?></div>
<?php else: ?>
        <?php echo $this->hook('main') ?>
<?php endif ?>
	</div>
</body>
</html>
