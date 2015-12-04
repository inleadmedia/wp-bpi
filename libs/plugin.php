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
			"page_title"       => __("Settings", 'wp-ae-plugin'),
			"page_menu_title"  => __("Settings", 'wp-ae-plugin'),
			"page_slug"        => "ae-options",
			"page_note"        => "",
			"options_name"     => "ae_options",
			"options_group"    => "ae_options_group",
			"options"          => array(
				"agency_id"              => array(
					"label" => __("Agency ID", 'wp-ae-plugin'),
					"name"  => "agency_id",
					"type"  => "text",
				),
				"secret_key"             => array(
					"label" => __("Secret Key", 'wp-ae-plugin'),
					"type"  => "text",
					"name"  => "secret_key",
				),
				"public_key"             => array(
					"label" => __("Public Key", 'wp-ae-plugin'),
					"type"  => "text",
					"name"  => "public_key",
				),
				"url"                    => array(
					"label" => __("URL", 'wp-ae-plugin'),
					"type"  => "text",
					"name"  => "url",
				),
				"content_per_page"       => array(
					"label" => __("Content per page in Syndication", 'wp-ae-plugin'),
					"type"  => "text",
					"name"  => "content_per_page",
				),
				"html_strip_empty"       => array(
					'label' => __("HTML | Strip empty tags", 'wp-ae-plugin'),
					'type'  => "checkbox",
					"name"  => 'strip_empty'
				),
				"html_remove_attributes" => array(
					'label' => __("HTML | Remove attributes", 'wp-ae-plugin'),
					'type'  => "checkbox",
					"name"  => 'html_remove_attributes'
				),
				"html_strip_tags"        => array(
					'label' => __("HTML | Strip Tags", 'wp-ae-plugin'),
					'type'  => "text",
					"name"  => 'html_strip_tags'
				),
				"html_skip_iframes"      => array(
					'label' => __("HTML | Skip Iframes", 'wp-ae-plugin'),
					'type'  => "checkbox",
					"name"  => 'html_skip_iframes'
				),
			)
		));
		//$postStatus = new PostStatus();

		AddMetaBox::create('ae', array(
			'ae'           => array(
				'label'   => __("Status", 'wp-ae-plugin'),
				'type'    => 'custom',
				'default' => 0,
				'handler' => array(
					$this,
					'renderAeStatus'
				),
			),
			'ae_id'        => array(
				'label'   => __("Item ID", 'wp-ae-plugin'),
				'type'    => 'view',
				'default' => 'None'
			),
			'ae_timestamp' => array(
				'label'   => __("Import Date", 'wp-ae-plugin'),
				'type'    => 'custom',
				'handler' => array(
					$this,
					'renderAeDate'
				),
			),
			'custom'       => array(
				'label'   => __("Export", 'wp-ae-plugin'),
				'type'    => 'custom',
				'handler' => array(
					$this,
					'renderPush'
				),
			)
		), 'post', __("Article Exchange info", 'wp-ae-plugin'), 'side');

		if (is_admin())
		{
			add_action('admin_menu', array($this, 'addPages'), 3);
			add_action('current_screen', array($this, 'initTable'));
			add_action('admin_enqueue_scripts', array($this, 'scriptsEnqueue'));

			add_filter('manage_posts_columns', array($this, 'addColumnHead'));
			add_action('manage_posts_custom_column', array($this, 'addColumnContent'), 10, 2);

			add_action('wp_ajax_pull_from_ae', array($this, 'ajaxPullAction'));
			add_action('wp_ajax_check_pull_from_ae', array($this, 'ajaxCheckPullAction'));
			add_action('wp_ajax_check_push_to_ae', array($this, 'ajaxCheckPushAction'));
			add_action('wp_ajax_delete_from_ae', array($this, 'ajaxDeleteAction'));
			add_action('wp_ajax_push_to_ae', array($this, 'ajaxPushAction'));

			add_action('add_meta_boxes', array($this, 'addMeta'));

			/**
			 * Load multilinguar support
			 */
			add_action('plugins_loaded', 'load_textdomain');
		}

		$obRole = get_role('administrator');
		$obRole->add_cap('ae');
	}

	public function loadTextDomain()
	{
		load_plugin_textdomain('wp-ae-plugin', false, plugin_basename(dirname(__FILE__)) . '/languages');
	}

	public function addMeta()
	{
		add_meta_box('ae', __("Article Exchange status", 'wp-ae-plugin'), array($this, 'renderMeta'), 'post', 'side');
	}

	public function renderMeta()
	{
		echo Renderer::render_template('meta', array(
			'params' => PostStatus::init($GLOBALS['post']->ID)->getMetaParams(),
		));
	}

	public function renderPush()
	{
		if (empty($_GET['post']))
		{
			return 'Cant\'t push new post';
		}
		$postId = intval($_GET['post']);
		if (get_post_meta($postId, 'ae_push_status', true))
		{
			$date = get_post_meta($postId, 'ae_timestamp', true);

			return __("Pushed at", 'wp-ae-plugin') .' '. date('d.m.Y H:i', $date);
		}

		return '<a href="javascript:void(0);" id="push-to-ae" data-post-id="' . $postId . '">Push to AE</a>';
	}

	public function renderAeStatus()
	{
		if ( ! empty($_GET['post']))
		{
			$postId = intval($_GET['post']);
			if (get_post_meta($postId, 'ae_push_status', true))
			{
				return __("Pushed to AE", 'wp-ae-plugin');
			}
			if (get_post_meta($postId, 'ae', true))
			{
				return __("Imported from AE", 'wp-ae-plugin');
			}
		}

		return __("Not in AE yet", 'wp-ae-plugin');
	}

	public function renderAeDate()
	{
		if ( ! empty($_GET['post']))
		{
			$postId = intval($_GET['post']);
			if ($timestamp = get_post_meta($postId, 'ae_timestamp', true))
			{
				return date('d.m.Y H:i', $timestamp);
			}
		}

		return ' '.__("None", 'wp-ae-plugin');
	}

	public function addColumnHead($defaults)
	{
		$defaults['ae'] = __("AE Timestamp", 'wp-ae-plugin');

		return $defaults;
	}

	public function addColumnContent($column_name, $post_ID)
	{
		if ($column_name == 'ae')
		{
			echo PostStatus::init($post_ID)->getPostsTableState();
		}
	}

	/**
	 * Adds all needed pages
	 */
	public function addPages()
	{
		add_menu_page(__("Article Exchange", 'wp-ae-plugin'), __("Article Exchange", 'wp-ae-plugin'), 'ae', 'ae-syndication', array(
			$this,
			'renderSyndication'
		));
	}

	public function initTable()
	{
		if (empty($_REQUEST['page']) || $_REQUEST['page'] != 'ae-syndication')
		{
			return;
		}
		$this->_table = new SyndicationTable();
	}

	public function renderSyndication()
	{
		if ( ! empty($_GET['action']))
		{

		} else
		{
			$this->_table->prepare_items();
			$this->_table->display();
		}
	}

	public function scriptsEnqueue($hook)
	{
		wp_enqueue_script('ae-script', plugins_url(WP_AE_PLUGIN_NAME . '/assets/script.js'), array('jquery-ui-dialog'));
		wp_enqueue_style('wp-jquery-ui-dialog');

		/**
		 * Apply it only on post.php page
		 */
		if ('post.php' == $hook || 'ae-options_page_ae-syndication' == $hook)
		{
			wp_enqueue_style('ae-style', plugins_url(WP_AE_PLUGIN_NAME . '/assets/style.css'));

			return;
		}
	}


	public function ajaxCheckPullAction()
	{
		$nodeInfo = Pull::init()->getNodeInfo($_GET['ae-node-id']);

		wp_send_json(array(
			'state' => 1,
			'html'  => Renderer::render_template('ajax-popup-pull', array(
				'properties' => $nodeInfo['properties'],
				'assets'     => $nodeInfo['assets'],
			)),
			'title' => $nodeInfo['properties']['title'],
			'body'  => tagAuthorFunctional::clearMatches($nodeInfo['properties']['body']),
		));
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

		wp_send_json(array(
			'state'       => 1,
			'text'        => '<div>' . $postStatus->getTableState() . '</div>',
			'field'       => $postStatus->getTableState(),
			'id'          => 'record_' . $aeNodeId,
			'column_name' => '_actions'

		));
	}

	public function ajaxPushAction()
	{
		try
		{
			if (empty($_REQUEST['post_id']))
			{
				throw new \Exception('No post ID given');
			}

			PostStatus::init($_REQUEST['post_id'])->pushToAe($_REQUEST['category'], $_REQUEST['audience'], $_REQUEST['images'], ! $_REQUEST['anonymous'], $_REQUEST['editable'], $_REQUEST['references']);

			wp_send_json(array(
				'state' => 1,
				'text'  => Renderer::render_template('meta', array(
					'params' => PostStatus::init($_REQUEST['post_id'])->getMetaParams()
				))
			));

		} catch ( \Exception $e )
		{
			wp_send_json(array(
				'state'   => 0,
				'message' => $e->getMessage()
			));
		}
	}

	public function ajaxCheckPushAction()
	{
		try
		{
			if (empty($_REQUEST['post_id']))
			{
				throw new \Exception(__("No post ID given", 'wp-ae-plugin'));
			}
			$dictionaries = ArticleExchange::init()->getDictionaries();
			wp_send_json(array(
				'state' => 1,
				'html'  => Renderer::render_template('ajax-popup-push', array(
					'postStatus' => PostStatus::init($_REQUEST['post_id']),
					'categories' => empty($dictionaries['category']) ? array() : $dictionaries['category'],
					'audience'   => empty($dictionaries['audience']) ? array() : $dictionaries['audience'],
				))
			));
		} catch ( \Exception $e )
		{
			wp_send_json(array(
				'state'   => 0,
				'message' => $e->getMessage()
			));
		}
	}

	public function ajaxDeleteAction()
	{
		try
		{
			if (empty($_REQUEST['post_id']))
			{
				throw new \Exception(__("No post ID given", 'wp-ae-plugin'));
			}

			if ( ! PostStatus::init($_REQUEST['post_id'])->deleteFromAe())
			{
				throw new \Exception(__("Removing article from A-Exchange failed", 'wp-ae-plugin'));
			}
			wp_send_json(array(
				'state' => 1,
				'text'  => Renderer::render_template('meta', array(
					'params' => PostStatus::init($_REQUEST['post_id'])->getMetaParams()
				))
			));


		} catch ( \Exception $e )
		{
			wp_send_json(array(
				'state'   => 0,
				'message' => $e->getMessage()
			));
		}
	}
}