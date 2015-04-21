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
		You're about to syndicate content “<?php echo $properties['title'] ?>” av
		<strong><?php echo $properties['author'] ?></strong>, from category “<?php echo $properties['category'] ?>” and
		audience “<?php echo $properties['audience'] ?>”
	</div>
	<?php if ($assets): ?>
		<br/><div><strong>Select images to syndicate with content:</strong></div>
		<ul>
			<?php foreach ($assets as $num => $image): ?>
				<li><input type="checkbox" class="selected-images" id="image-<?php echo $num ?>" name="image-<?php echo $num ?>" value="<?php echo $image ?>"/>
					<label for="image-<?php echo $num ?>"><img src="<?php echo $image ?>.jpg" width="100" height="100" alt="" align="middle"/></label>
				</li>
			<?php endforeach ?>
		</ul>
	<?php else: ?>
		<div><strong>There is no images</strong></div>
	<?php endif ?>
</div>
