<?php
namespace WordpressBpi;

use Fruitframe\Pattern_Singleton;

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
				"page_title"          => "BPI Options",
				"page_menu_title"     => "BPI Options",
				"page_slug"           => "bpi-options",
				"page_note"           => "",
				"options_name"        => "bpi_options",
				"options_group"       => "bpi_options_group",
				"options"             => array(
					"agency_id"        => array(
						"label"       => "Agency ID",
						"name"        => "agency_id",
						"type"        => "text",
						//						"description" => "Agency ID which",
					),
					"secret_key" => array(
						"label"       => "Secret Key",
						"type"        => "text",
						"name"        => "secret_key",
					),
					"public_key" => array(
						"label"       => "Public Key",
						"type"        => "text",
						"name"        => "public_key",
					),
					"url" => array(
						"label"       => "URL",
						"type"        => "text",
						"name"        => "url",
					),
					"content_per_page" => array(
						"label"       => "Content per page in Sindication",
						"type"        => "text",
						"name"        => "content_per_page",
					)
				)
			)
		);

		if (is_admin()) {
			add_action('admin_menu', array($this, 'addPages'));
			add_action('current_screen', array($this, 'initTable'));
		}

		$obRole = get_role('administrator');
		$obRole->add_cap('bpi');

		PostStatus::init();
	}

	/**
	 * Adds all needed pages
	 */
	public function addPages()
	{
		add_submenu_page('bpi-options','Syndication', 'Syndication', 'bpi', 'bpi-syndication', array($this, 'renderSyndication'));
	}

	public  function initTable()
	{
		$this->_table = new SyndicationTable();
	}

	public function renderSyndication()
	{
		if (!empty($_GET['action']))
		{
			if ($_GET['action'] == 'pull')
			{
				$bpiNodeId = $_GET['bpi-node-id'];
				/**
				 * Add check to confirm we have no such object which was pulled already.
				 */
				$postId = Pull::init()->insertPost($bpiNodeId);
				echo 'Record added as a draft. Please <a href="'.get_admin_url(NULL,'post.php?action=edit&post='.$postId).'">check & post it</a>';
			}
		}
		else {
			$this->_table->prepare_items();
			$this->_table->display();
		}
	}
}