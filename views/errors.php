<?php foreach ($errors as $messages): ?>
	<?php foreach ($messages as $message): ?>
		<div class="error-notice"><?=$message->raw ?></div>
	<?php endforeach; ?>
<?php endforeach; ?>
