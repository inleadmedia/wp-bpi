<?php
/**
 * Popup with Push information (in order to select options)
 *
 * @var PostStatus $postStatus
 * @var            $categories
 */
?>
<div id="info-popup">
	<div>
		<?php _("You're about to push content", 'wp-ae-plugin') ?>
		 “<?php echo $postStatus->getPostObject()->post_title ?>”
	</div>
	<h3><?php _("AE VOCABULARIES", 'wp-ae-plugin') ?></h3>

	<p><?php _("Article Exchange vocabularies is used to categorize pushed content, so searching for content later is easier and related content can be found.", 'wp-ae-plugin') ?></p>

	<p>
		<label for="category"><?php _("Select category", 'wp-ae-plugin') ?></label>
		<select name="category" id="category" class="push-options">
			<?php foreach ($categories as $category): ?>
				<option value="<?php echo $category ?>"><?php echo $category ?></option>
			<?php endforeach ?>
		</select>
	</p>
	<p>
		<label for="category"><?php _("Select audience", 'wp-ae-plugin') ?></label>
		<select name="audience" id="audience" class="push-options">
			<?php foreach ($audience as $item): ?>
				<option value="<?php echo $item ?>"><?php echo $item ?></option>
			<?php endforeach ?>
		</select>
	</p>
	<h3><?php _("Article Exchange OPTIONS", 'wp-ae-plugin') ?></h3>

	<p><input type="checkbox" name="images" id="images" value="1" class="push-options"/>
		<label for="images"><?php _("Push with images", 'wp-ae-plugin') ?></label></p>

	<p>
		<small><?php _("You should have permission to publish the images before selecting this option.", 'wp-ae-plugin') ?>
		</small>
	</p>
	<br/>

	<p><input type="checkbox" name="anonymous" id="anonymous" value="1" class="push-options"/>
		<label for="anonymous"><?php _("I want be anonymous", 'wp-ae-plugin') ?></label>
	</p>

	<p>
		<small><?php _("If checked the content will be pushed as anonymous to Article Exchange.", 'wp-ae-plugin') ?></small>
	</p>
	<br/>

	<p><input type="checkbox" name="references" id="references" value="1" class="push-options"/>
		<label for="references"><?php _("Push with references", 'wp-ae-plugin') ?></label>
	</p>

	<p>
		<small>
			<?php _("If checked the content will be pushed with material reference to the data well. Note that posts with the katalog keyword will not be pushed.", 'wp-ae-plugin') ?>
		</small>
	</p>
	<br/>

	<p><input type="checkbox" name="editable" id="editable" value="1" class="push-options"/>
		<label for="editable"><?php _("Editable", 'wp-ae-plugin') ?></label>
	</p>

	<p>
		<small><?php _("If checked the content will be marked as not editable (It is not enforced but only recommanded that the content is not changed after syndication).", 'wp-ae-plugin') ?>
		</small>
	</p>
	<br/>
</div>
