<?php
/**
 * Created by PhpStorm.
 * User: keriat
 * Date: 3/25/15
 * Time: 8:17 AM
 */
namespace WordpressBpi;

class Pull extends \Fruitframe\Pattern_Singleton
{
	/**
	 * @todo: добавить мэппинг
	 * @param $bpiNodeId
	 *
	 * @return bool
	 */
	public function insertPost($bpiNodeId)
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
			'post_date'    => $bpiProperties['date'],
			'post_status'  => 'draft',
		));
		if (!$postId)
		{
			return FALSE;
		}
		add_post_meta($postId, 'bpi', 1, TRUE);
		add_post_meta($postId, 'bpi_id', $bpiNodeId, TRUE);
		add_post_meta($postId, 'bpi_timestamp', time(), TRUE);
		return $postId;
	}
}