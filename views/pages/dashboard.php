<?=view('page-header', ['title' => 'Dashboard']) ?>

<section class="group">
	<h1>Welcome, <?=$display_name ?>!</h1>
	
	<?=view('form-header', ['action' => '/logout', 'method' => 'post']) ?>
	<?=view('form-footer', ['submit-text' => 'Log out']) ?>
</section>

<?=view('page-footer') ?>
