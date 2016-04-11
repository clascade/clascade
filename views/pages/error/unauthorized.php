<?php

header("{$_SERVER['SERVER_PROTOCOL']} 401 Unauthorized");
header("WWW-Authenticate: Basic realm=\"{$realm->raw}\"");

?>
<?=view('page-header', ['title' => '401: Unauthorized']) ?>

<section class="group">
	<p><?=o('common.error.unauthorized.body') ?></p>
</section>

<?=view('page-footer') ?>
