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

		/*AddMetaBox::create('bpi', array(
			'bpi'           => array(
				'label'   => 'Status',
				'type'    => 'custom',
				'default' => 0,
				'handler' => array(
					$postStatus,
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
					$postStatus,
					'renderBpiDate'
				),
			),
			'custom'        => array(
				'label'   => 'Export',
				'type'    => 'custom',
				'handler' => array(
					$postStatus,
					'renderPush'
				),
			)
		), 'post', 'BPI info', 'side');*/

		if (is_admin()) {
			add_action('admin_menu', array($this, 'addPages'));
			add_action('current_screen', array($this, 'initTable'));
			add_action('admin_enqueue_scripts', array($this, 'scriptsEnqueue'));

			add_action('wp_ajax_pull_from_bpi', array($this, 'ajaxPullAction'));
			add_action('wp_ajax_check_pull_from_bpi', array($this, 'ajaxCheckPullAction'));
			add_action('wp_ajax_push_to_bpi', array($this, 'ajaxPushAction'));
		}

		$obRole = get_role('administrator');
		$obRole->add_cap('bpi');
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
		wp_enqueue_script('popup-script', plugins_url('wp-bpi-plugin/assets/popup.js'), array('jquery-ui-dialog'));

		/**
		 * Apply it only on post.php page
		 */
		if ('post.php' == $hook) {
			wp_enqueue_script('ajax-script', plugins_url('wp-bpi-plugin/assets/push.js'), array('jquery'));
			return;
		}
		if ('bpi-options_page_bpi-syndication' == $hook) {

			wp_enqueue_style('bpi-style', plugins_url('wp-bpi-plugin/assets/style.css'));
			wp_enqueue_style('wp-jquery-ui-dialog');
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
		 * Add check to confirm we have no such object which was pulled already.
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

			Bpi::init()->pushNode(PostStatus::init($_REQUEST['post_id'])->prepareToBpi('Review', 'All'));

			echo json_encode(array(
				'state' => 1,
				'text'  => 'Pushed at ' . PostStatus::init($_REQUEST['post_id'])->getBpiDate('d.m.Y H:i'),
			));

		} catch ( \Exception $e ) {
			echo json_encode(array(
				'state'   => 0,
				'message' => $e->getMessage()
			));
		}
		wp_die();
	}

}