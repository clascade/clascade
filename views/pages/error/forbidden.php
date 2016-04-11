<?php

header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");

?>
<?=view('page-header', ['title' => '403: Forbidden']) ?>

<section class="group">
	<p><?=o('common.error.forbidden.body') ?></p>
</section>

<?=view('page-footer') ?>
