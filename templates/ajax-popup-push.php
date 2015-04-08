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
		You're about to push content “<?php echo $postStatus->getPostObject()->post_title ?>”
	</div>
	<h3>BPI VOCABULARIES</h3>

	<p>BPI vocabularies is used to categories the pushed content, so searching for content later is easier and related
	   content can be found.</p>

	<p>
		<select name="category" id="category" class="push-options">
<!--			<option>Select category</option>-->
			<?php foreach ($categories as $category): ?>
				<option value="<?php echo $category ?>"><?php echo $category ?></option>
			<?php endforeach ?>
		</select>
	</p>
	<p>
		<select name="audience" id="audience" class="push-options">
<!--			<option>Select audience:</option>-->
			<?php foreach ($audience as $item): ?>
				<option value="<?php echo $item ?>"><?php echo $item ?></option>
			<?php endforeach ?>
		</select>
	</p>
	<h3>BPI OPTIONS</h3>

	<p><input type="checkbox" name="images" id="images" value="1" class="push-options"/> <label for="images">Push with
	                                                                                               images</label></p>

	<p>
		<small>You should have permission to publish the images before selecting this option.</small>
	</p>
	<br/>

	<p><input type="checkbox" name="anonymous" id="anonymous" value="1" class="push-options"/> <label for="anonymous">I want be
	                                                                                                     anonymous</label>
	</p>

	<p>
		<small>If checked the content will be pushed as anonymous to BPI.</small>
	</p>
	<br/>

	<p><input type="checkbox" name="references" id="references" value="1" class="push-options"/> <label for="references">Push with
	                                                                                                       references</label>
	</p>

	<p>
		<small>If checked the content will be pushed with material reference to the data well. Note that posts with the
		       katalog keyword will not be pushed.
		</small>
	</p>
	<br/>

	<p><input type="checkbox" name="editable" id="editable" value="1" class="push-options"/> <label for="editable">Editable</label>
	</p>

	<p>
		<small>If checked the content will be marked as not editable (It is not enforced but only recommanded that the
		       content is not changed after syndication).
		</small>
	</p>
	<br/>
</div>
