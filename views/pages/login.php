<?=view('page-header', ['title' => 'Log in']) ?>
<?=view('form-header') ?>

<section class="group">
	<h1>Please enter your login details</h1>
	
	<div class="fields">
		<div class="field-group <?=$email->fieldClass() ?>">
			<span class="field-label"><label for="field-email-0">Email:</label></span>
			<span class="field-input"><input type="text" name="email" id="field-email-0" value="<?=$email ?>"></span>
		</div>
		<div class="field-group <?=$password->fieldClass() ?>">
			<span class="field-label"><label for="field-password-0">Password:</label></span>
			<span class="field-input"><input type="password" name="password" id="field-password-0" value=""></span>
		</div>
	</div>
	
	<p><a href="<?=$reset_password_url ?>">Forgot your password?</a></p>
</section>

<div class="fields">
	<div class="field-group <?=$remember->fieldClass() ?>">
		<span class="field-input"><input type="checkbox" name="remember" id="field-remember-0" value="1"<?=$remember->checked('1') ?>></span>
		<span class="field-label"><label for="field-remember-0">Remember me</label></span>
	</div>
</div>

<input type="hidden" name="redirect-to" value="<?=$redirect_to ?>">

<?=view('form-footer') ?>
<?=view('page-footer') ?>
