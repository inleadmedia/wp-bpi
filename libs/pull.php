<?php
namespace WordpressBpi;

class Pull extends \Fruitframe\Pattern_Singleton
{
	public function getNodeInfo($bpiNodeId)
	{
		$node = Bpi::init()->getNode($bpiNodeId);
		if (!$node)
		{
			return FALSE;
		}
		return array(
			'properties' => $node->getProperties(),
			'assets'     => $node->getAssets()
		);
	}

	/**
	 * @todo: добавить мэппинг
	 * @param $bpiNodeId
	 * @param array $images
	 *
	 * @return bool|PostStatus
	 */
	public function insertPost($bpiNodeId, array $images = NULL)
	{
		$node = Bpi::init()->getNode($bpiNodeId);
		if (!$node)
		{
			return FALSE;
		}
		$bpiProperties = $node->getProperties();
		$postId = wp_insert_post(array(
			'post_title'  => $bpiProperties['title'],
			'post_content' => $bpiProperties['body'],
			'post_excerpt' => $bpiProperties['teaser'],
			'post_date'    => $bpiProperties['creation'],
			'post_status'  => 'draft',
		));
		if (!$postId)
		{
			return FALSE;
		}
		add_post_meta($postId, 'bpi', 1, TRUE);
		add_post_meta($postId, 'bpi_id', $bpiNodeId, TRUE);
		add_post_meta($postId, 'bpi_timestamp', time(), TRUE);
		return PostStatus::init($postId);
	}
}