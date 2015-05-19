<?php
namespace WordpressAe;

class Pull extends \Fruitframe\Pattern_Singleton
{
	public function getNodeInfo($aeNodeId)
	{
		$node = ArticleExchange::init()->getNode($aeNodeId);
		if ( ! $node) {
			return false;
		}
		return array(
			'properties' => $node->getProperties(),
			'assets'     => $node->getAssets(),
		);
	}

	/**
	 * @todo: add errors handling
	 *
	 * @param       $aeNodeId
	 * @param array $images
	 *
	 * @return bool|PostStatus
	 */
	public function insertPost($aeNodeId, array $images = null)
	{
		$node = ArticleExchange::init()->getNode($aeNodeId);
		if ( ! $node) {
			return false;
		}
		$aeProperties = $node->getProperties();
		$postId        = wp_insert_post(array(
			'post_title'   => $aeProperties['title'],
			'post_content' => $aeProperties['body'],
			'post_excerpt' => $aeProperties['teaser'],
			'post_date'    => $aeProperties['creation'],
			'post_status'  => 'draft',
		));
		if ( ! $postId) {
			return false;
		}
		$postStatus = PostStatus::init($postId);
		$postStatus->pullFromAe($aeNodeId, $images, $aeProperties);

		return $postStatus;
	}
}