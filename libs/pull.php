<?php
namespace WordpressBpi;

class Pull extends \Fruitframe\Pattern_Singleton
{
	public function getNodeInfo($bpiNodeId)
	{
		$node = Bpi::init()->getNode($bpiNodeId);
		if ( ! $node) {
			return false;
		}
		return array(
			'properties' => $node->getProperties(),
			'assets'     => $node->getAssets()
		);
	}

	/**
	 * @todo: add errors handling
	 *
	 * @param       $bpiNodeId
	 * @param array $images
	 *
	 * @return bool|PostStatus
	 */
	public function insertPost($bpiNodeId, array $images = null)
	{
		$node = Bpi::init()->getNode($bpiNodeId);
		if ( ! $node) {
			return false;
		}
		$bpiProperties = $node->getProperties();
		$postId        = wp_insert_post(array(
			'post_title'   => $bpiProperties['title'],
			'post_content' => $bpiProperties['body'],
			'post_excerpt' => $bpiProperties['teaser'],
			'post_date'    => $bpiProperties['creation'],
			'post_status'  => 'draft',
		));
		if ( ! $postId) {
			return false;
		}
		add_post_meta($postId, 'bpi', 1, true);
		add_post_meta($postId, 'bpi_id', $bpiNodeId, true);
		add_post_meta($postId, 'bpi_timestamp', time(), true);
		if ($images) {
			$thumbnailSet = false;
			foreach ($images as $image) {
				$image = $image . '.jpg';

				// Set variables for storage, fix file filename for query strings.
				preg_match('/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image, $matches);
				$file_array         = array();
				$file_array['name'] = basename($matches[0]);

				// Download file to temp location.
				$file_array['tmp_name'] = download_url($image);

				// If error storing temporarily, return the error.
				/*if (is_wp_error($file_array['tmp_name'])) {
					return $file_array['tmp_name'];
				}*/

				// Do the validation and storage stuff.
				$id = media_handle_sideload($file_array, $postId);

				// If error storing permanently, unlink.
				if (is_wp_error($id)) {
					@unlink($file_array['tmp_name']);
					//return $id;
				}
				if ( ! $thumbnailSet) {
					set_post_thumbnail($postId, $id);
					$thumbnailSet = true;
				}
			}
		}

		return PostStatus::init($postId);
	}
}