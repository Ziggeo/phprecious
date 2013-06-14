<ul>
	<? foreach ($files as $file) { ?>
		<li>
			<a href="<?= APP()->router()->path("read_upload_path", $file->id()) ?>">
				<?= $file->file_name ?>
			</a>
			<? if ($file->session()->id() == APP()->session()->id()) { ?>
				&mdash;
				<a href="<?= APP()->router()->path("destroy_upload_path", $file->id()) ?>">
					Delete?
				</a>
			<? } ?>
		</li>
	<? } ?>
</ul>
<hr />
<form method="POST" action="<?= APP()->router()->path("create_upload_path") ?>" enctype='multipart/form-data'>
	<input type="file" name="file" />
	<input type="submit" value="Add File" />
</form>