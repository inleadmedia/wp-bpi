<?php
/**
 * Plugin Name: Article Exchange
 * Plugin URI: http://fruitware.ru
 * Description: AE System Integration Plugin
 * Version: 1.0.1
 * Author: Fruitware
 * Author URI: http://fruitware.ru
 * Text Domain: ae
 * Domain Path: /locale/
 * License: GPLv2 or later
 */

require_once 'init.php';

WordpressAe\Plugin::init();

/*

add_action( 'admin_init', '' );
12
13	function tc_i18n() {
14	        load_plugin_textdomain( 'themecheck', false, 'theme-check/lang' );
15	}*/
