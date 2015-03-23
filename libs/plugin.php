<?php
/**
 * Created by PhpStorm.
 * User: keriat
 * Date: 3/21/15
 * Time: 8:01 PM
 */

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
						"label"       => "Content per page in Sindicate",
						"type"        => "text",
						"name"        => "url",
					)
				)
			)
		);
		$this->_initSindicationPage();
		//		echo 'asdads';
	}

	protected function _initSindicationPage()
	{
		if (is_admin()) {
			add_action('admin_menu', array($this, 'adminAddPage'));
			add_action('current_screen', array($this, 'initTable'));
		}
		$obRole = get_role('administrator');
		$obRole->add_cap('bpi-control');
	}

	/**
	 * Callback to add page
	 */
	public function adminAddPage()
	{
		add_submenu_page('bpi-options','Item database', 'BPI-list', 'bpi-control', 'bpi-list', array($this, 'renderSindication'));
	}

	public  function initTable()
	{
		$this->_table = new \Control_List_Table();
	}

	public function renderSindication()
	{
		$this->_table->prepare_items();
		$this->_table->display();
	}
}