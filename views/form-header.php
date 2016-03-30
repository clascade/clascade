<div class="form-c">
	<form action="<?=(isset ($action) ? $action : request_path()) ?>" method="post"<?=(isset ($id) ? " id=\"{$id}\"" : '') ?><?=(isset ($class) ? " class=\"{$class}\"" : '') ?>>
