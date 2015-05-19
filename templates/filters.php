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
<h2>Article Exchange Library</h2>
<div class="alignleft actions">
	<form>
		<?php foreach ($additionalParams as $paramName => $paramValue): ?>
			<input type="hidden" name="<?php echo $paramName ?>" value="<?php echo $paramValue ?>"/>
		<?php endforeach ?>

		<label for="filter-by-category" class="screen-reader-text">Filter by category</label>

		<select name="filter-category" id="filter-by-category">
			<option value="0">All categories</option>
			<?php foreach ($categories as $item): ?>
				<option <?php echo($item == $selectedCategory ? 'selected="selected"' : '') ?>
					value="<?php echo $item ?>"><?php echo $item ?></option>
			<?php endforeach ?>
		</select>
		<label for="filter-by-audience" class="screen-reader-text">Filter by date</label>
		<select name="filter-audience" id="filter-by-audience">
			<option selected="selected" value="0">Any audience</option>
			<?php foreach ($audience as $item): ?>
				<option
					<?php echo($item == $selectedAudience ? 'selected="selected"' : '') ?>value="<?php echo $item ?>"><?php echo $item ?></option>
			<?php endforeach ?>
		</select>

		<p class="search-box ae-style">
			<label class="screen-reader-text" for="post-search-input">Search:</label>
			<input type="search" id="post-search-input" name="filter-search" value="<?php echo $searchText ?>">
		</p>

		<?php
		submit_button(__('Filter'), 'secondary', false, false, array('id' => 'post-query-submit'));
		?>

		<?php if ($links): foreach ($links as $link): ?>
			<input type="hidden" name="page" value="ae-syndication"/>
			<a href="<?php echo $link['link'] ?>" class="ae-text-panel-block"><strong><?php echo $link['param'] ?>
					:</strong> <?php echo $link['value'] ?><span class="dashicons dashicons-no-alt"></span></a>
		<?php endforeach;endif; ?>
	</form>
</div>
