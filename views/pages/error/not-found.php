<?php

header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");

?>
<?=view('page-header', ['title' => '404: Not Found']) ?>

<section class="group">
	<p><?=o('common.error.not-found.body') ?></p>
</section>

<?=view('page-footer') ?>
