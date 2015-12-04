<?php
/**
 * Remove links aroung images
 *
 * @param string $content
 *
 * @return string
 */
function fruitframe_clear_images($content)
{
	return preg_replace('/<a[^>]*>(<img[^>]*>)<\/a>/iu', '$1', $content);
}

/**
 * Get first post image. Used in filters.
 *
 * @param string $content
 *
 * @return string
 */
function fruitframe_parse_first_image($content = NULL)
{
	if ( ! $content )
	{
		$content = get_the_content();
	}

	return fruitframe_parse_tag_img($content);
}

/**
 * Search for image in transmitted text
 *
 * @param string $content
 *
 * @return string
 */
function fruitframe_parse_tag_img($content = NULL)
{
	preg_match('~(<img[^>]+>)~sim', trim($content), $matches);

	return $matches[1];
}

/**
 * Remove first image from post. Used in filters.
 *
 * @param string $content
 *
 * @return string
 */
function fruitframe_remove_first_image($content = NULL)
{
	if ( ! $content )
	{
		$content = get_the_content();
	}
	$content = trim(preg_replace('~(<img[^>]+>)~sim', '', $content, 1));

	return $content;
}

/**
 * Remove all post images.
 *
 * @param string $content
 *
 * @return string
 */
function fruitframe_remove_images($content = NULL)
{
	if ( ! $content )
	{
		$content = get_the_content();
	}
	$content = trim(preg_replace('~(<a[^>]+>)?\s*(<img[^>]+>)\s*(</a>)?~sim', '', $content));

	return $content;
}

function fruitframe_get_attachments($id = NULL, $excludeIds = array())
{
	if ( ! $id )
	{
		$id = get_the_ID();
	}

	return get_posts(array(
		'post_parent'    => $id,
		'post_status'    => 'inherit',
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'order'          => 'ASC',
		'numberposts'    => - 1,
		'orderby'        => 'menu_order',
		'post__not_in'   => $excludeIds
	));
}


/**
 * Wrap all images with div
 *
 * @param string $content
 *
 * @return string
 */
function fruitframe_wrap_images($content = NULL)
{
	if ( ! $content )
	{
		$content = get_the_content();
	}
	$content = preg_replace('~(<img[^>]+>)~sim', '<div class="image">$1</div>', $content);

	return $content;
}

/**
 * Get all post images
 *
 * @param string $content
 *
 * @return string
 */
function fruitframe_get_all_images($content = NULL)
{
	if ( ! $content )
	{
		$content = get_the_content();
	}
	preg_match_all('~(<img[^>]+>)~sim', $content, $matches);

	return $matches[1];
}

function fruitframe_get_attachment_image_src($attachment_id, $size)
{
	$image = wp_get_attachment_image_src($attachment_id, $size);
	if ( ! $image )
	{
		return FALSE;
	}
	list($src, $width, $height) = $image;

	return $src;
}

function fruitframe_attachment_image_src($attachment_id, $size)
{
	echo fruitframe_get_attachment_image_src($attachment_id, $size);
}

function fruitware_get_image_tag_src($imageTag)
{
	preg_match('/src="([^"]*)"/', $imageTag, $matches);

	return $matches[1];
}