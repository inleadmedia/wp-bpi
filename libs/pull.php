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
		$postStatus = PostStatus::init($postId);
		$postStatus->pullFromBpi($bpiNodeId, $images);

		return $postStatus;
	}
}