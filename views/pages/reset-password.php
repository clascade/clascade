<?=$this->view('page-header', ['title' => 'Reset password']) ?>
<?=$this->view('form-header') ?>

<section class="group">
	<h1>Please choose a new password.</h1>
	
	<div class="fields">
		<input type="hidden" name="reset-key" value="<?=$reset_key ?>">
		<div class="field-group">
			<span class="field-label"><label for="field-new-password-0">New Password:</label></span>
			<span class="field-input"><input type="password" name="new-password" id="field-new-password-0" value=""></span>
		</div>
		<div class="field-group">
			<span class="field-label"><label for="field-confirm-0">Retype New Password:</label></span>
			<span class="field-input"><input type="password" name="confirm" id="field-confirm-0" value=""></span>
		</div>
	</div>
</section>

<?=$this->view('form-footer', ['subject-text' => 'Set password']) ?>
<?=$this->view('page-footer') ?>
