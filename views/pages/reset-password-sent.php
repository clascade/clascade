<?=$this->view('page-header', ['title' => 'Reset password']) ?>

<div class="reset-sent">
	<div>
		<section class="group reset-sent">
			<h1>Next step: Check your inbox.</h1>
			
			<p>If the email address you typed matches the one in our system, you should soon receive an email containing instructions for resetting your password.</p>
			
			<p><a href="<?=$app_base ?>/login">Back to login</a></p>
		</section>
	</div>
</div>

<?=$this->view('page-footer') ?>
