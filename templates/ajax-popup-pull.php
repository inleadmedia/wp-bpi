<?php
/**
 * Popup with Pull information (in order to select images)
 *
 * @var $properties
 * @var $assets
 */
?>
<div id="info-popup">
	<div>
		<?php _( "You're about to syndicate content", 'wp-ae-plugin')?> “<?php echo $properties['title'] ?>” <?php _( "av", 'wp-ae-plugin')?>
		<strong><?php echo $properties['author'] ?></strong>, <?php _( "from category", 'wp-ae-plugin')?> “<?php echo $properties['category'] ?>” and
		<?php _( "audience", 'wp-ae-plugin')?> “<?php echo $properties['audience'] ?>”
	</div>
	<?php if ($assets): ?>
		<br/><div><strong><?php _( "Select images to syndicate with content:", 'wp-ae-plugin')?></strong></div>
		<ul>
			<?php foreach ($assets as $num => $image): ?>
				<li><input type="checkbox" class="selected-images" id="image-<?php echo $num ?>" name="image-<?php echo $num ?>" value="<?php echo $image ?>"/>
					<label for="image-<?php echo $num ?>"><img src="<?php echo $image ?>.jpg" width="100" height="100" alt="" align="middle"/></label>
				</li>
			<?php endforeach ?>
		</ul>
	<?php else: ?>
		<div><strong><?php _( "There is no images", 'wp-ae-plugin')?></strong></div>
	<?php endif ?>
</div>
