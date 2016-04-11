<?php

header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");

?>
<?=view('page-header', ['title' => 'Session Locked']) ?>

<section class="group">
	<p><?=o('common.error.locked.body') ?></p>
</section>

<?=view('page-footer') ?>
