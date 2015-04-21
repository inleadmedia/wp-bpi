<?php
namespace WordpressBpi;

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
				"page_title"      => "BPI Options",
				"page_menu_title" => "BPI Options",
				"page_slug"       => "bpi-options",
				"page_note"       => "",
				"options_name"    => "bpi_options",
				"options_group"   => "bpi_options_group",
				"options"         => array(
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
						"label" => "Content per page in Sindication",
						"type"  => "text",
						"name"  => "content_per_page",
					)
				)
			)
		);

		//$postStatus = new PostStatus();

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

		if (is_admin()) {
			add_action('admin_menu', array($this, 'addPages'));
			add_action('current_screen', array($this, 'initTable'));
			add_action('admin_enqueue_scripts', array($this, 'scriptsEnqueue'));

			add_filter('manage_posts_columns', array($this, 'addColumnHead'));
			add_action('manage_posts_custom_column', array($this, 'addColumnContent'), 10, 2);

			add_action('wp_ajax_pull_from_bpi', array($this, 'ajaxPullAction'));
			add_action('wp_ajax_check_pull_from_bpi', array($this, 'ajaxCheckPullAction'));
			add_action('wp_ajax_check_push_to_bpi', array($this, 'ajaxCheckPushAction'));
			add_action('wp_ajax_push_to_bpi', array($this, 'ajaxPushAction'));

			add_action('add_meta_boxes', array($this, 'addMeta'));
		}

		$obRole = get_role('administrator');
		$obRole->add_cap('bpi');
	}

	public function addMeta()
	{
		add_meta_box('bpi', 'BPI status', array($this, 'renderMeta'), 'post', 'side');
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

	public function addColumnHead($defaults)
	{
		$defaults['bpi'] = 'BPI Timestamp';
		return $defaults;
	}

	public function addColumnContent($column_name, $post_ID)
	{
		if ($column_name == 'bpi') {
			echo PostStatus::init($post_ID)->getPostsTableState();
		}
	}

	/**
	 * Adds all needed pages
	 */
	public function addPages()
	{
		add_submenu_page('bpi-options', 'Syndication', 'Syndication', 'bpi', 'bpi-syndication',
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
		wp_enqueue_script('bpi-script', plugins_url('wp-bpi-plugin/assets/script.js'), array('jquery-ui-dialog'));
		wp_enqueue_style('wp-jquery-ui-dialog');

		/**
		 * Apply it only on post.php page
		 */
		if ('post.php' == $hook || 'bpi-options_page_bpi-syndication' == $hook) {
			wp_enqueue_style('bpi-style', plugins_url('wp-bpi-plugin/assets/style.css'));
			return;
		}
	}


	public function ajaxCheckPullAction()
	{
		$nodeInfo = Pull::init()->getNodeInfo($_GET['bpi-node-id']);

		die(json_encode(array(
			'state' => 1,
			'html'  => Renderer::render_template('ajax-popup-pull', array(
				'properties' => $nodeInfo['properties'],
				'assets'     => $nodeInfo['assets']
			))
		)));
	}

	/**
	 * @todo: add some params check
	 */
	public function ajaxPullAction()
	{
		$bpiNodeId = $_GET['bpi-node-id'];
		$images    = empty($_GET['images']) ? null : $_GET['images'];

		/**
		 * @todo: Check to confirm we have no such object which was pulled already.
		 */
		$postStatus = Pull::init()->insertPost($bpiNodeId, $images);

		die(json_encode(array(
			'state'       => 1,
			'text'        => '<div>' . $postStatus->getTableState() . '</div>',
			'field'       => $postStatus->getTableState(),
			'id'          => 'record_' . $bpiNodeId,
			'column_name' => '_actions'

		)));
	}

	public function ajaxPushAction()
	{
		try {
			if (empty($_REQUEST['post_id'])) {
				throw new \Exception('No post ID given');
			}

			PostStatus::init($_REQUEST['post_id'])->pushToBpi($_REQUEST['category'],$_REQUEST['audience'],$_REQUEST['images'], $_REQUEST['anonymous'], $_REQUEST['editable'], $_REQUEST['references']);

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
			$dictionaries = Bpi::init()->getDictionaries();
			echo json_encode(array(
				'state' => 1,
				'html'  => Renderer::render_template('ajax-popup-push', array(
					'postStatus' => PostStatus::init($_REQUEST['post_id']),
					'categories' => $dictionaries['category'],
					'audience'   => $dictionaries['audience']
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