<?php
/**
 * @var $categories
 * @var $audience
 * @var $selectedAudience
 * @var $selectedCategory
 * @var $searchText
 * @var $links
 * @var $additionalParams
 */
?>
<h2><?php _("Article Exchange Library", 'wp-ae-plugin') ?></h2>
<div class="alignleft actions">
	<form>
		<?php foreach ($additionalParams as $paramName => $paramValue): ?>
			<input type="hidden" name="<?php echo $paramName ?>" value="<?php echo $paramValue ?>"/>
		<?php endforeach ?>

		<label for="filter-by-category" class="screen-reader-text"><?php _("Filter by category", 'wp-ae-plugin') ?></label>

		<select name="filter-category" id="filter-by-category">
			<option value="0"><?php _("All categories", 'wp-ae-plugin') ?></option>
			<?php foreach ($categories as $item): ?>
				<option <?php echo($item == $selectedCategory ? 'selected="selected"' : '') ?>
					value="<?php echo $item ?>"><?php echo $item ?></option>
			<?php endforeach ?>
		</select>
		<label for="filter-by-audience" class="screen-reader-text"><?php _("Filter by date", 'wp-ae-plugin') ?></label>
		<select name="filter-audience" id="filter-by-audience">
			<option selected="selected" value="0"><?php _("Any audience", 'wp-ae-plugin') ?></option>
			<?php foreach ($audience as $item): ?>
				<option
					<?php echo($item == $selectedAudience ? 'selected="selected"' : '') ?>value="<?php echo $item ?>"><?php echo $item ?></option>
			<?php endforeach ?>
		</select>

		<p class="search-box ae-style">
			<label class="screen-reader-text" for="post-search-input"><?php _("Search", 'wp-ae-plugin') ?>:</label>
			<input type="search" id="post-search-input" name="filter-search" value="<?php echo $searchText ?>">
		</p>

		<?php
		submit_button(_('Filter', 'wp-ae-plugin'), 'secondary', false, false, array('id' => 'post-query-submit'));
		?>

		<?php if ($links): foreach ($links as $link): ?>
			<input type="hidden" name="page" value="ae-syndication"/>
			<a href="<?php echo $link['link'] ?>" class="ae-text-panel-block"><strong><?php echo $link['param'] ?>
					:</strong> <?php echo $link['value'] ?><span class="dashicons dashicons-no-alt"></span></a>
		<?php endforeach;endif; ?>
	</form>
</div>
