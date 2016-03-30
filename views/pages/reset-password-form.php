<?=$this->view('page-header', ['title' => 'Reset password']) ?>
<?=$this->view('form-header') ?>

<section class="group">
	<h1>Please type your email address to request a password reset.</h1>
	
	<div class="fields">
		<div class="field-group">
			<span class="field-label"><label for="field-email-0">Email:</label></span>
			<span class="field-input"><input type="text" name="email" id="field-email-0" value=""></span>
		</div>
	</div>
</section>

<?=$this->view('form-footer', ['subject-text' => 'Send confirmation email']) ?>
<?=$this->view('page-footer') ?>
