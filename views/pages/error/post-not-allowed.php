<?php

header("{$_SERVER['SERVER_PROTOCOL']} 405 Method Not Allowed");
header('Allow: '.implode(', ', $methods->raw));

?>
<?=view('page-header', ['title' => '405: Method Not Allowed']) ?>

<section class="group">
	<p><?=o('common.error.post-not-allowed.body') ?></p>
</section>

<?=view('page-footer') ?>
