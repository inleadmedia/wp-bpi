<?php
/**
 * Created by PhpStorm.
 * User: keriat
 * Date: 3/25/15
 * Time: 9:07 AM
 */

namespace WordpressBpi;

use Fruitframe\Pattern_Singleton;
use Fruitframe\AddMetaBox;

class PostStatus extends Pattern_Singleton
{
	protected function __construct()
	{

		AddMetaBox::create('bpi', array(
			'bpi'           => array(
				'label'   => 'Status',
				'type'    => 'custom',
				'default' => 0,
				'handler' => array(
					$this,
					'renderBpiStatus'
				),
			),
			'bpi_id'        => array(
				'label'   => 'Item ID',
				'type'    => 'view',
				'default' => 'None'
			),
			'bpi_timestamp' => array(
				'label'   => 'Import Date',
				'type'    => 'custom',
				'handler' => array(
					$this,
					'renderBpiDate'
				),
			),
			'custom'        => array(
				'label'   => 'Export',
				'type'    => 'custom',
				'handler' => array(
					$this,
					'renderPush'
				),
			)
		), 'post', 'BPI info', 'side');

		add_action('wp_ajax_push_to_bpi', array($this, 'pushToBpi'));

		add_action('admin_enqueue_scripts', array($this, 'scriptsEnqueue'));
	}

	public function scriptsEnqueue($hook)
	{
		/**
		 * Apply it only on post.php page
		 */
		if ('post.php' != $hook) {
			return;
		}
		wp_enqueue_script('ajax-script', plugins_url('wp-bpi-plugin/assets/push.js'), array('jquery'));
		//wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 ) );
	}

	/**
	 * Convert node object to array structure, suitable for pushing to the well.
	 *
	 * @param stdClass $node
	 *   Node object being processed.
	 * @param string   $category
	 *   Selected category at the BPI side.
	 * @param string   $audience
	 *   Selected audience at the BPI side.
	 * @param bool     $with_images
	 *   Include images or not.
	 * @param bool     $authorship
	 *   Include author name or not.
	 * @param int      $editable
	 *   1 - to mark as editable, 0 - not editable.
	 * @param bool     $with_refs
	 *   If TRUE ting material reference are extracted.
	 *
	 * @return array
	 *   An array of node values, used by the BPI web service.
	 *
	 * @todo Add a hook allowing changing the values before they are sent to BPI.
	 * @todo Split this function into smaller parts (ex: images, texts).
	 */
	protected function _convertWordpressPostToBpiNode(
		$wpPost,
		$category,
		$audience,
		$with_images = false,
		$authorship = false,
		$editable = 1,
		$with_refs = false
	) {
		$bpi_content = array();

		$bpi_content['agency_id'] = \wpJediOptions::get_option('bpi_options', 'agency_id');
		$bpi_content['local_id']  = $wpPost->ID;
		$bpi_content['bpi_id']    = get_post_meta($wpPost->ID, 'bpi_id', true);

		$user                     = get_user_by('id', $wpPost->post_author);
		$bpi_content['firstname'] = $user->display_name;
		$bpi_content['lastname']  = '';

		$bpi_content['title'] = $wpPost->post_title;


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
		$teaser = $body = apply_filters('the_content', $wpPost->post_content);

		// Empty the teaser, if body and teaser are mapped to same fields
		// and the values are identical.
		if ($teaser === $body) {
			$teaser = '';
		}

		$bpi_content['body']   = html_entity_decode($body);
		$bpi_content['teaser'] = html_entity_decode($teaser);
		$dt                    = new \DateTime();
		$dt->setTimestamp(strtotime($wpPost->post_date));
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


	public function pushToBpi()
	{
		try {
			if (empty($_REQUEST['post_id'])) {
				throw new \Exception('No post ID given');
			}
			//			var_dump()
			if ( ! is_object($wpPost = get_post($_REQUEST['post_id']))) {
				throw new \Exception('No post with ID=' . $_REQUEST['post_id'] . ' found');
			}
			$bpiNode = $this->_convertWordpressPostToBpiNode($wpPost, 'Review', 'All');
			Bpi::init()->pushNode($bpiNode);
			$date = get_post_meta($_REQUEST['post_id'], 'bpi_timestamp', true);


			echo json_encode(array(
				'state' => 1,
				'text'  => 'Pushed at ' . date('d.m.Y H:i', $date),
			));

		} catch ( \Exception $e ) {
			echo json_encode(array(
				'state'   => 0,
				'message' => $e->getMessage()
			));
		}
		wp_die();
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
				return date('d.m.Y H:i', $timestamp );
			}
		}
		return ' None';
	}
}