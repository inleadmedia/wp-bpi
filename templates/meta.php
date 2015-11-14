<?php
/**
 * @var array $params
 */
?>
<?php foreach ($params as $title => $value): ?>
	<?php if ($title == 'export'): ?>
		<div class="misc-pub-section misc-pub-post-status">
			<label for="post_status">Export:</label>
			<span id="post-status-display" style="vertical-align: middle;">
				<a href="javascript:void(0);" id="push-to-ae" data-post-id="<?php echo $value ?>">Push to Article Exchange</a>
			</span>
		</div>
	<?php elseif ($title == 'delete'): ?>
		<div class="misc-pub-section misc-pub-post-status">
			<label for="post_status">Export:</label>
			<span id="post-status-display" style="vertical-align: middle;">
				<a href="javascript:void(0);" id="delete-from-ae" data-post-id="<?php echo $value ?>">Delete from Article Exchange</a>
			</span>
		</div>
	<?php else: ?>
		<div class="misc-pub-section misc-pub-post-status">
			<label for="post_status"><?php echo $title ?>:</label>
			<span id="post-status-display" style="vertical-align: middle;">
				<?php echo is_int($value) ? ($value ? 'Yes' : 'No') : $value ?>
			</span>
		</div>
	<?php endif; ?>
<?php endforeach ?>
