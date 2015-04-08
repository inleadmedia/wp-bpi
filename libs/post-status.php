<?php
namespace WordpressBpi;

use Fruitframe\Renderer;

/**
 * Using Singleton Pattern for the list of posts
 * Helper-class to extend Wordpress Post with all bpi-staff.
 *
 * Class PostStatus
 * @package WordpressBpi
 */
class PostStatus
{
	static $_posts = array();

	protected $_postId;
	protected $_postData;

	protected $_bpiMeta = array(
		'bpi'                => null,
		'bpi_id'             => null,
		'bpi_push_status'    => null,
		'bpi_push_timestamp' => null,
		'bpi_pull_status'    => null,
		'bpi_pull_timestamp' => null,
	);

	/**
	 * @param $postId
	 *
	 * @throws \Exception
	 */
	protected function __construct($postId)
	{
		if ( ! ($this->_postData = get_post($postId))) {
			throw new \Exception('No post with ID=' . $postId . ' found');
		}
		$this->_postId = $postId;
	}

	/**
	 * @param $postId
	 *
	 * @throws \Exception
	 *
	 * @return PostStatus
	 */
	public static function init($postId)
	{
		if ( ! array_key_exists($postId, self::$_posts)) {
			self::$_posts[$postId] = new self($postId);
		}
		return self::$_posts[$postId];
	}

	/**
	 * @param $bpiId
	 *
	 * @throws \Exception
	 *
	 * @return bool|PostStatus
	 */
	public static function findByBpiId($bpiId)
	{
		if (count($results = get_posts(array(
			'meta_key'    => 'bpi_id',
			'meta_value'  => $bpiId,
			'post_status' => 'any'
		)))) {
			return self::init($results[0]->ID);
		}
		return false;
	}

	/**
	 * For syndication
	 */
	public function getTableState()
	{
		if ($this->_checkBpi()) {
			return 'Already pulled. <a href="' . get_admin_url(null,
				'post.php?action=edit&post=' . $this->_postId) . '">Check</a>';
		}
		return '';
	}

	/**
	 * Get params for meta-block
	 * @return array
	 */
	public function getMetaParams()
	{
		$params = array(
			'export'     => $this->_postId
		);
		if ( ! $this->_checkBpi()) {
			$params['BPI Status'] = 'Not in BPI';
			return $params;
		}
		$params['BPI Status'] = 'In BPI';
		$params['BPI ID'] = $this->_bpiMeta['bpi_id'];
		if (($params['Pull status'] = (int)$this->_bpiMeta['bpi_pull_status'])) {
			$params['Pull date'] = date('d.m.Y H:i', $this->_bpiMeta['bpi_pull_timestamp']);
		}
		if (($params['Push status'] = (int)$this->_bpiMeta['bpi_push_status'])) {
			$params['Push date'] = date('d.m.Y H:i', $this->_bpiMeta['bpi_push_timestamp']);
			unset($params['export']);
		}
		return $params;
	}

	/**
	 * Return status for the posts table
	 * @return string|void
	 */
	public function getPostsTableState()
	{
		if ( ! $this->_checkBpi()) {
			return;
		}
		$response = '';
		if ($this->_bpiMeta['bpi_pull_status']) {
			$response .= 'Pulled at ' . date('d.m.Y H:i', $this->_bpiMeta['bpi_pull_timestamp']);
		}
		if ($this->_bpiMeta['bpi_push_status']) {
			$response .= ($response ? '<br/>' : '') . 'Pushed at ' . date('d.m.Y H:i',
					$this->_bpiMeta['bpi_push_timestamp']);
		}
		return $response;
	}

	/**
	 * @return null|\WP_Post
	 */
	public function getPostObject()
	{
		return $this->_postData;
	}

	public function getBpiDate(
		$format = false
	) {
		if ( ! ($date = get_post_meta($this->_postId, 'bpi_timestamp', true))) {
			return false;
		}
		return $format ? date($format, $date) : $date;
	}

	/**
	 * Pushing current post to BPI and saving all needed params right here
	 *
	 * @param $category
	 * @param $audience
	 * @param $images
	 * @param $anonymous
	 * @param $editable
	 * @param $references
	 */
	public function pushToBpi($category, $audience, $images, $anonymous, $editable, $references)
	{
		$bpiData = $this->_prepareToBpi($category, $audience, $images, ! $anonymous, $editable, $references);
		$pushStatus = Bpi::init()->pushNode($bpiData);
		if ($pushStatus) {
			add_post_meta($this->_postId, 'bpi', 1, true);
			add_post_meta($this->_postId, 'bpi_push_status', 1, true);
			add_post_meta($this->_postId, 'bpi_id', $pushStatus['id'], true);
			add_post_meta($this->_postId, 'bpi_push_timestamp', time(), true);
		}
	}

	/**
	 * Pulling current post from BPI (not actually pulling but still saving params)
	 *
	 * @param       $externalId
	 * @param array $images
	 */
	public function pullFromBpi($externalId, $images = array())
	{
		add_post_meta($this->_postId, 'bpi', 1, true);
		add_post_meta($this->_postId, 'bpi_id', $externalId, true);
		add_post_meta($this->_postId, 'bpi_pull_timestamp', time(), true);

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
				$id = media_handle_sideload($file_array, $this->_postId);

				// If error storing permanently, unlink.
				if (is_wp_error($id)) {
					@unlink($file_array['tmp_name']);
					//return $id;
				}
				if ( ! $thumbnailSet) {
					set_post_thumbnail($this->_postId, $id);
					$thumbnailSet = true;
				}
			}
		}

	}

	/**
	 * Convert node object to array structure, suitable for pushing to the well.
	 *
	 * @param string $category
	 *   Selected category at the BPI side.
	 * @param string $audience
	 *   Selected audience at the BPI side.
	 * @param bool   $with_images
	 *   Include images or not.
	 * @param bool   $authorship
	 *   Include author name or not.
	 * @param int    $editable
	 *   1 - to mark as editable, 0 - not editable.
	 * @param bool   $with_refs
	 *   If TRUE ting material reference are extracted.
	 *
	 * @return array
	 *   An array of node values, used by the BPI web service.
	 *
	 * @todo Add a hook allowing changing the values before they are sent to BPI.
	 * @todo Split this function into smaller parts (ex: images, texts).
	 */
	public function _prepareToBpi(
		$category,
		$audience,
		$with_images = false,
		$authorship = false,
		$editable = 1,
		$with_refs = false
	) {
		$bpi_content = array();

		$bpi_content['agency_id'] = \wpJediOptions::get_option('bpi_options', 'agency_id');
		$bpi_content['local_id']  = $this->_postId;
		$bpi_content['bpi_id']    = get_post_meta($this->_postId, 'bpi_id', true);

		$user                     = get_user_by('id', $this->_postData->post_author);
		$bpi_content['firstname'] = $user->display_name;
		$bpi_content['lastname']  = '';

		$bpi_content['title'] = $this->_postData->post_title;
		$teaser               = $this->_postData->post_excerpt ? $this->_postData->post_excerpt : fruitframe_truncate($this->_postData->post_content);


		/*		$teaser_field = $wpPost->post_excerpt;
				$body_field   = $wpPost->post_content;
				$teaser       = '';
				$body         = '';

				// Whether the field is a text area with summary, fetch the summary, if not -
				// fetch it's safe value.
				if ( ! empty($teaser_field) && isset($teaser_field['#items'][0]['safe_summary'])) {
					$teaser = $teaser_field['#items'][0]['safe_summary'];
				} elseif (isset($teaser_field['#items'][0]['safe_value'])) {
					$teaser = $teaser_field['#items'][0]['safe_value'];
				}*/

		// Find the references to the ting date well.
		$materials_drupal = array();
		/* @todo: understand what the hell is this references for.
		 * if ($with_refs) {
		 *
		 * $materials_map = field_view_field('node', $node, variable_get('bpi_field_materials', ''));
		 * if (isset($materials_map['#items'])) {
		 * foreach ($materials_map['#items'] as $key => $value) {
		 * if ( ! empty($materials_map[$key]['#object'])) {
		 * $ting_entity = $materials_map[$key]['#object'];
		 * $id          = $ting_entity->ding_entity_id;
		 *
		 * // Filter out id's with "katalog" PID, as they only makes sens on
		 * // current site.
		 * if ( ! preg_match('/katalog/', $id)) {
		 * $materials_drupal[] = $id;
		 * }
		 * }
		 * }
		 * }
		 * }*/

		/*		if ( ! empty($body_field) && isset($body_field['#items'][0]['safe_value'])) {
					$body = $body_field['#items'][0]['safe_value'];
				}*/
		$body = apply_filters('the_content', $this->_postData->post_content);

		$bpi_content['body']   = html_entity_decode($body);
		$bpi_content['teaser'] = html_entity_decode($teaser);
		$dt                    = new \DateTime();
		$dt->setTimestamp(strtotime($this->_postData->post_date));
		$bpi_content['creation']          = $dt->format(\DateTime::W3C);
		$bpi_content['type']              = 'post';
		$bpi_content['category']          = $category;
		$bpi_content['audience']          = $audience;
		$bpi_content['related_materials'] = $materials_drupal;
		$bpi_content['editable']          = $editable;
		$bpi_content['authorship']        = $authorship;
		$bpi_content['images']            = array();

		if ($with_images) {
			foreach (fruitframe_get_attachments($this->_postId) as $image) {
				$bpi_content['images'][] = array(
					'path'  => fruitframe_get_attachment_image_src($image->ID, 'full'),
					'alt'   => '',
					'title' => '',
				);
			}
			if (empty($bpi_content['images']))
			{
				if (($thumbId = get_post_thumbnail_id($this->_postId)))
				{
					$bpi_content['images'][] = array(
						'path'  => fruitframe_get_attachment_image_src($thumbId, 'full'),
						'alt'   => '',
						'title' => '',
					);
				}

			}
		} else {
			$bpi_content['body'] = preg_replace(
				'~(<p>)?<img.+?/>(</p>)?~is',
				'',
				$bpi_content['body']
			);
		}

		return $bpi_content;
	}

	protected function _checkBpi()
	{
		foreach ($this->_bpiMeta as $key => $value) {
			$this->_bpiMeta[$key] = get_post_meta($this->_postId, $key, true);
		}
		return $this->_bpiMeta['bpi'];
	}
}