<?php
namespace WordpressAe;

/**
 * Using Singleton Pattern for the list of posts
 * Helper-class to extend Wordpress Post with all AE-staff.
 *
 * Class PostStatus
 * @package WordpressAe
 */
class PostStatus
{
	static $_posts = array();

	protected $_postId;
	protected $_postData;

	protected $_aeMeta = array(
		'ae'                => null,
		'ae_id'             => null,
		'ae_push_status'    => null,
		'ae_push_timestamp' => null,
		'ae_pull_status'    => null,
		'ae_pull_timestamp' => null,
		'ae_category'       => null,
		'ae_audience'       => null
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
	 * @param $aeId
	 *
	 * @throws \Exception
	 *
	 * @return bool|PostStatus
	 */
	public static function findByAeId($aeId)
	{
		if (count($results = get_posts(array(
			'meta_key'    => 'ae_id',
			'meta_value'  => $aeId,
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
		if ($this->_checkAe()) {
			return 'Already pulled. <a href="' . get_admin_url(null, 'post.php?action=edit&post=' . $this->_postId) . '">Check</a>';
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
			'export' => $this->_postId
		);
		if ( ! $this->_checkAe()) {
			$params['AE Status'] = 'Not in AE';

			return $params;
		}
		if ($this->_aeMeta['ae_push_status']) {
			$params['delete'] = $this->_postId;
		}
		$params['AE Status'] = 'In AE';
		$params['AE ID']     = $this->_aeMeta['ae_id'];
		if (($params['Pull status'] = (int) $this->_aeMeta['ae_pull_status'])) {
			$params['Pull date'] = date('d.m.Y H:i', $this->_aeMeta['ae_pull_timestamp']);
		}
		if (($params['Push status'] = (int) $this->_aeMeta['ae_push_status'])) {
			$params['Push date'] = date('d.m.Y H:i', $this->_aeMeta['ae_push_timestamp']);
			unset($params['export']);
		}
		$params['AE Category'] = $this->_aeMeta['ae_category'];
		$params['AE Audience'] = $this->_aeMeta['ae_audience'];

		return $params;
	}

	/**
	 * Return status for the posts table
	 * @return string|void
	 */
	public function getPostsTableState()
	{
		if ( ! $this->_checkAe()) {
			return;
		}
		$response = '';
		if ($this->_aeMeta['ae_pull_status']) {
			$response .= 'Pulled at ' . date('d.m.Y H:i', $this->_aeMeta['ae_pull_timestamp']);
		}
		if ($this->_aeMeta['ae_push_status']) {
			$response .= ($response ? '<br/>' : '') . 'Pushed at ' . date('d.m.Y H:i', $this->_aeMeta['ae_push_timestamp']);
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

	public function getAeDate($format = false)
	{
		if ( ! ($date = get_post_meta($this->_postId, 'ae_timestamp', true))) {
			return false;
		}

		return $format ? date($format, $date) : $date;
	}

	/**
	 * Pushing current post to AE and saving all needed params right here
	 *
	 * @param $category
	 * @param $audience
	 * @param $images
	 * @param $anonymous
	 * @param $editable
	 * @param $references
	 */
	public function pushToAe($category, $audience, $images, $anonymous, $editable, $references)
	{
		$aeData     = $this->_prepareToAe($category, $audience, $images, $anonymous, $editable, $references);
		$pushStatus = ArticleExchange::init()->pushNode($aeData);
		if ($pushStatus) {
			add_post_meta($this->_postId, 'ae', 1, true);
			add_post_meta($this->_postId, 'ae_push_status', 1, true);
			add_post_meta($this->_postId, 'ae_id', $pushStatus['id'], true);
			add_post_meta($this->_postId, 'ae_push_timestamp', time(), true);
			add_post_meta($this->_postId, 'ae_category', $category, true);
			add_post_meta($this->_postId, 'ae_audience', $audience, true);

		}
	}

	/**
	 * Method to delete record from AE
	 *
	 * @return bool
	 */
	public function deleteFromAe()
	{
		if ( ! $this->_checkAe()) {
			return false;
		}
		if ( ! ArticleExchange::init()->deleteNode($this->_aeMeta['ae_id'])) {
			return false;
		}
		foreach($this->_aeMeta as $key => $value)
		{
			delete_post_meta($this->_postId, $key, $value);
			$this->_aeMeta[$key] = null;
		}
		return true;
	}

	/**
	 * Pulling current post from AE (not actually pulling but still saving params)
	 *
	 * @param       $externalId
	 * @param array $images
	 */
	public function pullFromAe($externalId, $images = array(), $nodeProperties)
	{
		add_post_meta($this->_postId, 'ae', 1, true);
		add_post_meta($this->_postId, 'ae_id', $externalId, true);
		add_post_meta($this->_postId, 'ae_pull_timestamp', time(), true);
		add_post_meta($this->_postId, 'ae_pull_status', 1, true);
		add_post_meta($this->_postId, 'ae_category', $nodeProperties['category'], true);
		add_post_meta($this->_postId, 'ae_audience', $nodeProperties['audience'], true);

		if (tagAuthorFunctional::isIndexdataActive()) {
			if (is_array($tags = tagAuthorFunctional::parseTags($nodeProperties['body']))) {
				wp_set_post_tags($this->_postId, $tags);
			}
			if ($author = tagAuthorFunctional::parseAuthor($nodeProperties['body'])) {
				update_post_meta($this->_postId, 'indexdata_artist', $author);
			}
		}


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
	 *   Selected category at the AE side.
	 * @param string $audience
	 *   Selected audience at the AE side.
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
	 *   An array of node values, used by the AE web service.
	 *
	 * @todo Add a hook allowing changing the values before they are sent to AE.
	 * @todo Split this function into smaller parts (ex: images, texts).
	 */
	public function _prepareToAe($category, $audience, $with_images = false, $authorship = false, $editable = 1, $with_refs = false)
	{
		$ae_content = array();

		$ae_content['agency_id'] = \wpJediOptions::get_option('ae_options', 'agency_id');
		$ae_content['local_id']  = $this->_postId;
		$ae_content['ae_id']     = get_post_meta($this->_postId, 'ae_id', true);

		$ae_content['firstname'] = $ae_content['lastname'] = '';

		if ($authorship) {
			$user                    = get_user_by('id', $this->_postData->post_author);
			$ae_content['firstname'] = $user->display_name;
		}

		$ae_content['title'] = $this->_postData->post_title;
		$teaser              = $this->_postData->post_excerpt ? $this->_postData->post_excerpt : fruitframe_truncate($this->_postData->post_content);


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
		 * $materials_map = field_view_field('node', $node, variable_get('ae_field_materials', ''));
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
		$body = html_entity_decode(apply_filters('the_content', $this->_postData->post_content));

		if (tagAuthorFunctional::isIndexdataActive()) {
			if (is_array($tags = wp_get_post_tags($this->_postId)) && count($tags)) {
				$resultTags = array();
				foreach ($tags as $tag) {
					$resultTags[] = $tag->name;
				}
				$body = tagAuthorFunctional::includeTags($body, $resultTags);
			}
			if ($author = get_post_meta($this->_postId, 'indexdata_artist', true)) {
				$body = tagAuthorFunctional::includeAuthor($body, $author);
			}
		}


		// Some articles are not right encoded.
		// Forcing to UTF-8 encode.
		$current_encoding = mb_detect_encoding($body, 'auto');
		$body             = iconv($current_encoding, 'UTF-8', $body);

		$ae_content['body']   = $body;
		$ae_content['teaser'] = html_entity_decode($teaser);
		$dt                   = new \DateTime();
		$dt->setTimestamp(strtotime($this->_postData->post_date));
		$ae_content['creation']          = $dt->format(\DateTime::W3C);
		$ae_content['type']              = 'post';
		$ae_content['category']          = $category;
		$ae_content['audience']          = $audience;
		$ae_content['related_materials'] = $materials_drupal;
		$ae_content['editable']          = $editable;
		$ae_content['authorship']        = $authorship;
		$ae_content['images']            = array();

		if ( ! $with_images) {
			$ae_content['body'] = preg_replace('~(<p>)?<img.+?/>(</p>)?~is', '', $ae_content['body']);
		}

		return $ae_content;
	}

	protected function _checkAe()
	{
		foreach ($this->_aeMeta as $key => $value) {
			$this->_aeMeta[$key] = get_post_meta($this->_postId, $key, true);
		}

		return $this->_aeMeta['ae'];
	}
}
