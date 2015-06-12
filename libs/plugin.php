<?php
namespace WordpressAe;

use Fruitframe\Pattern_Singleton;
use Fruitframe\AddMetaBox;
use Fruitframe\Renderer;

class Plugin extends Pattern_Singleton
{
	/**
	 * @var null|wpJediOptions;
	 */
	protected $_pageOptions;

	protected $_table;

	protected function __construct()
	{
		$this->_page_options = new \wpJediOptions(array(
				"parent_page_slug" => 'ae-syndication',
				"page_title"       => "Settings",
				"page_menu_title"  => "Settings",
				"page_slug"        => "ae-options",
				"page_note"        => "",
				"options_name"     => "ae_options",
				"options_group"    => "ae_options_group",
				"options"          => array(
					"agency_id"        => array(
						"label" => "Agency ID",
						"name"  => "agency_id",
						"type"  => "text",
						//						"description" => "Agency ID which",
					),
					"secret_key"       => array(
						"label" => "Secret Key",
						"type"  => "text",
						"name"  => "secret_key",
					),
					"public_key"       => array(
						"label" => "Public Key",
						"type"  => "text",
						"name"  => "public_key",
					),
					"url"              => array(
						"label" => "URL",
						"type"  => "text",
						"name"  => "url",
					),
					"content_per_page" => array(
						"label" => "Content per page in Syndication",
						"type"  => "text",
						"name"  => "content_per_page",
					)
				)
			)
		);
		//$postStatus = new PostStatus();

		AddMetaBox::create('ae', array(
			'ae'           => array(
				'label'   => 'Status',
				'type'    => 'custom',
				'default' => 0,
				'handler' => array(
					$this,
					'renderAeStatus'
				),
			),
			'ae_id'        => array(
				'label'   => 'Item ID',
				'type'    => 'view',
				'default' => 'None'
			),
			'ae_timestamp' => array(
				'label'   => 'Import Date',
				'type'    => 'custom',
				'handler' => array(
					$this,
					'renderAeDate'
				),
			),
			'custom'       => array(
				'label'   => 'Export',
				'type'    => 'custom',
				'handler' => array(
					$this,
					'renderPush'
				),
			)
		), 'post', 'Article Exchange info', 'side');

		if (is_admin()) {
			add_action('admin_menu', array($this, 'addPages'), 3);
			add_action('current_screen', array($this, 'initTable'));
			add_action('admin_enqueue_scripts', array($this, 'scriptsEnqueue'));

			add_filter('manage_posts_columns', array($this, 'addColumnHead'));
			add_action('manage_posts_custom_column', array($this, 'addColumnContent'), 10, 2);

			add_action('wp_ajax_pull_from_ae', array($this, 'ajaxPullAction'));
			add_action('wp_ajax_check_pull_from_ae', array($this, 'ajaxCheckPullAction'));
			add_action('wp_ajax_check_push_to_ae', array($this, 'ajaxCheckPushAction'));
			add_action('wp_ajax_push_to_ae', array($this, 'ajaxPushAction'));

			add_action('add_meta_boxes', array($this, 'addMeta'));
		}

		$obRole = get_role('administrator');
		$obRole->add_cap('ae');
	}

	public function addMeta()
	{
		add_meta_box('ae', 'Article Exchange status', array($this, 'renderMeta'), 'post', 'side');
	}

	public function renderMeta()
	{
		echo Renderer::render_template('meta', array(
			'params' => PostStatus::init($GLOBALS['post']->ID)->getMetaParams(),
		));
	}

	public function renderPush()
	{
		if (empty($_GET['post'])) {
			return 'Cant\'t push new post';
		}
		$postId = intval($_GET['post']);
		if (get_post_meta($postId, 'ae_push_status', true)) {
			$date = get_post_meta($postId, 'ae_timestamp', true);
			return 'Pushed at ' . date('d.m.Y H:i', $date);
		}
		return '<a href="javascript:void(0);" id="push-to-ae" data-post-id="' . $postId . '">Push to AE</a>';
	}

	public function renderAeStatus()
	{
		if ( ! empty($_GET['post'])) {
			$postId = intval($_GET['post']);
			if (get_post_meta($postId, 'ae_push_status', true)) {
				return 'Pushed to AE';
			}
			if (get_post_meta($postId, 'ae', true)) {
				return 'Imported from AE';
			}
		}
		return 'Not in AE yet';
	}

	public function renderAeDate()
	{
		if ( ! empty($_GET['post'])) {
			$postId = intval($_GET['post']);
			if ($timestamp = get_post_meta($postId, 'ae_timestamp', true)) {
				return date('d.m.Y H:i', $timestamp);
			}
		}
		return ' None';
	}

	public function addColumnHead($defaults)
	{
		$defaults['ae'] = 'AE Timestamp';
		return $defaults;
	}

	public function addColumnContent($column_name, $post_ID)
	{
		if ($column_name == 'ae') {
			echo PostStatus::init($post_ID)->getPostsTableState();
		}
	}

	/**
	 * Adds all needed pages
	 */
	public function addPages()
	{
		add_menu_page('Article Exchange', 'Article Exchange', 'ae', 'ae-syndication',
			array($this, 'renderSyndication'));
	}

	public function initTable()
	{
		$this->_table = new SyndicationTable();
	}

	public function renderSyndication()
	{
		if ( ! empty($_GET['action'])) {

		} else {
			$this->_table->prepare_items();
			$this->_table->display();
		}
	}

	public function scriptsEnqueue($hook)
	{
		wp_enqueue_script('ae-script', plugins_url(WP_AE_PLUGIN_NAME.'/assets/script.js'), array('jquery-ui-dialog'));
		wp_enqueue_style('wp-jquery-ui-dialog');

		/**
		 * Apply it only on post.php page
		 */
		if ('post.php' == $hook || 'ae-options_page_ae-syndication' == $hook) {
			wp_enqueue_style('ae-style', plugins_url(WP_AE_PLUGIN_NAME.'/assets/style.css'));
			return;
		}
	}


	public function ajaxCheckPullAction()
	{
		$nodeInfo = Pull::init()->getNodeInfo($_GET['ae-node-id']);

		die(json_encode(array(
			'state' => 1,
			'html'  => Renderer::render_template('ajax-popup-pull', array(
				'properties'    => $nodeInfo['properties'],
				'assets'        => $nodeInfo['assets'],
			)),
			'title' => $nodeInfo['properties']['title'],
			'body'  => $nodeInfo['properties']['body'],
		)));
	}

	/**
	 * @todo: add some params check
	 */
	public function ajaxPullAction()
	{
		$aeNodeId = $_GET['ae-node-id'];
		$images   = empty($_GET['images']) ? null : $_GET['images'];

		/**
		 * @todo: Check to confirm we have no such object which was pulled already.
		 */
		$postStatus = Pull::init()->insertPost($aeNodeId, $images);

		die(json_encode(array(
			'state'       => 1,
			'text'        => '<div>' . $postStatus->getTableState() . '</div>',
			'field'       => $postStatus->getTableState(),
			'id'          => 'record_' . $aeNodeId,
			'column_name' => '_actions'

		)));
	}

	public function ajaxPushAction()
	{
		try {
			if (empty($_REQUEST['post_id'])) {
				throw new \Exception('No post ID given');
			}

			PostStatus::init($_REQUEST['post_id'])->pushToAe($_REQUEST['category'], $_REQUEST['audience'],
				$_REQUEST['images'], ! $_REQUEST['anonymous'], $_REQUEST['editable'], $_REQUEST['references']);

			echo json_encode(array(
				'state' => 1,
				'text'  => Renderer::render_template('meta', array(
					'params' => PostStatus::init($_REQUEST['post_id'])->getMetaParams()
				))
			));

		} catch ( \Exception $e ) {
			echo json_encode(array(
				'state'   => 0,
				'message' => $e->getMessage()
			));
		}
		wp_die();
	}

	public function ajaxCheckPushAction()
	{
		try {
			if (empty($_REQUEST['post_id'])) {
				throw new \Exception('No post ID given');
			}
			$dictionaries = ArticleExchange::init()->getDictionaries();
			echo json_encode(array(
				'state' => 1,
				'html'  => Renderer::render_template('ajax-popup-push', array(
					'postStatus' => PostStatus::init($_REQUEST['post_id']),
					'categories' => empty($dictionaries['category']) ? array() : $dictionaries['category'],
					'audience'   => empty($dictionaries['audience']) ? array() : $dictionaries['audience'],
				))
			));
		} catch
		( \Exception $e ) {
			echo json_encode(array(
				'state'   => 0,
				'message' => $e->getMessage()
			));
		}
		wp_die();
	}
}