<?php
namespace WordpressBpi;

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
		if ($this->getBpiStatus()) {
			return 'Already pulled. <a href="' . get_admin_url(null,
				'post.php?action=edit&post=' . $this->_postId). '">Check</a>';
		}
		return '';
	}

	public function getPostsTableState()
	{
		if ($this->getBpiStatus()) {
			return 'Pulled at '.$this->getBpiDate('d.m.Y H:i');
		}
		return '';
	}

	/**
	 * @return null|\WP_Post
	 */
	public function getPostObject()
	{
		return $this->_postData;
	}

	public function renderPush()
	{
		if (empty($_GET['post'])) {
			return 'Cant\'t push new post';
		}
		$postId = intval($_GET['post']);
		if (get_post_meta($postId, 'bpi_push_status', true)) {
			$date = get_post_meta($postId, 'bpi_timestamp', true);
			return 'Pushed at ' . date('d.m.Y H:i', $date);
		}
		return '<a href="javascript:void(0);" id="push-to-bpi" data-post-id="' . $postId . '">Push to BPI</a>';
	}

	public function renderBpiStatus()
	{
		if ( ! empty($_GET['post'])) {
			$postId = intval($_GET['post']);
			if (get_post_meta($postId, 'bpi_push_status', true)) {
				return 'Pushed to BPI';
			}
			if (get_post_meta($postId, 'bpi', true)) {
				return 'Imported from BPI';
			}
		}
		return 'Not in BPI yet';
	}

	public function renderBpiDate()
	{
		if ( ! empty($_GET['post'])) {
			$postId = intval($_GET['post']);
			if ($timestamp = get_post_meta($postId, 'bpi_timestamp', true)) {
				return date('d.m.Y H:i', $timestamp);
			}
		}
		return ' None';
	}

	public function getBpiStatus()
	{
		if (get_post_meta($this->_postId, 'bpi_push_status', true) || get_post_meta($this->_postId, 'bpi', true)) {
			return true;
		}
		return false;
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
	public function prepareToBpi($category, $audience, $with_images = false, $authorship = false, $editable = 1, $with_refs = false) {
		$bpi_content = array();

		$bpi_content['agency_id'] = \wpJediOptions::get_option('bpi_options', 'agency_id');
		$bpi_content['local_id']  = $this->_postId;
		$bpi_content['bpi_id']    = get_post_meta($this->_postId, 'bpi_id', true);

		$user                     = get_user_by('id', $this->_postData->post_author);
		$bpi_content['firstname'] = $user->display_name;
		$bpi_content['lastname']  = '';

		$bpi_content['title'] = $this->_postData->post_title;


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
		if ($with_refs) {
			$materials_map = field_view_field('node', $node, variable_get('bpi_field_materials', ''));
			if (isset($materials_map['#items'])) {
				foreach ($materials_map['#items'] as $key => $value) {
					if ( ! empty($materials_map[$key]['#object'])) {
						$ting_entity = $materials_map[$key]['#object'];
						$id          = $ting_entity->ding_entity_id;

						// Filter out id's with "katalog" PID, as they only makes sens on
						// current site.
						if ( ! preg_match('/katalog/', $id)) {
							$materials_drupal[] = $id;
						}
					}
				}
			}
		}

		/*		if ( ! empty($body_field) && isset($body_field['#items'][0]['safe_value'])) {
					$body = $body_field['#items'][0]['safe_value'];
				}*/
		$teaser = $body = apply_filters('the_content', $this->_postData->post_content);

		// Empty the teaser, if body and teaser are mapped to same fields
		// and the values are identical.
		if ($teaser === $body) {
			$teaser = '';
		}

		$bpi_content['body']   = html_entity_decode($body);
		$bpi_content['teaser'] = html_entity_decode($teaser);
		$dt                    = new \DateTime();
		$dt->setTimestamp(strtotime($this->_postData->post_date));
		$bpi_content['creation']          = $dt->format(\DateTime::W3C);
		$bpi_content['type']              = 'post';
		$bpi_content['category']          = $category;
		$bpi_content['audience']          = $audience;
		$bpi_content['related_materials'] = $materials_drupal;
		$bpi_content['editable']          = (int) $editable;
		$bpi_content['authorship']        = ($authorship) ? false : true;
		$bpi_content['images']            = array();

		if ($with_images) {
			$image_fields = bpi_fetch_image_fields($node->type);

			if ( ! empty($image_fields)) {
				foreach ($image_fields as $field_name) {
					$field_value = field_view_field('node', $node, $field_name);

					if ( ! empty($field_value['#items'][0]['uri'])) {
						$file_url = file_create_url($field_value['#items'][0]['uri']);
						// Image pseudo-check.
						if (@getimagesize($file_url)) {
							$bpi_content['images'][] = array(
								'path'  => $file_url,
								'alt'   => '',
								'title' => '',
							);
						}
					}
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
}