<section class="adm-login-form">
	<h3 class="app-logo">Denteva</h3>
	<form method="post" action="<?=BApp::m('FCom_Admin')->baseHref()?>/login">
	    <fieldset>
	    	<header class="section-title">Log into Account</header>
    		<ul class="msgs">
    			<li class="notice-msg">Something seriously went wrong...</li>
    		</ul>
	        <ul class="form-list">
	        	<li class="label-l">
	        		<label for="#">Email/Username</label>
	        		<input type="text" name="login[username]" class="sz1"/>
	        	</li>
	        	<li class="label-l">
	        		<label for="#">Password</label>
	        		<input type="password" name="login[password]" class="sz1"/>
	        	</li>
	        </ul>
	        <input class="btn st1 sz1 "type="submit" name="Login"/>
	        <a href="#">Recover your password</a>
	    </fieldset>
	</form>
	<p class="copyright">&copy; <?php echo date("Y")?> Denteva LLC. All rights reserved.</p>
</section>