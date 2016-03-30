<?=$this->view('page-header', ['title' => '401: Unauthorized']) ?>

<section class="group">
	<p><?=o('common.error.unauthorized.body') ?></p>
</section>

<?=$this->view('page-footer') ?>
