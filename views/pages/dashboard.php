<?=$this->view('page-header', ['title' => 'Dashboard']) ?>

<section class="group">
	<h1>Welcome, <?=$display_name ?>!</h1>
	
	<?=$this->view('form-header', ['action' => '/logout', 'method' => 'post']) ?>
	<?=$this->view('form-footer', ['submit-text' => 'Log out']) ?>
</section>

<?=$this->view('page-footer') ?>
