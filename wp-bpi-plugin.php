<?php
/**
 * Plugin Name: BPI
 * Plugin URI: http://fruitware.ru
 * Description: BPI System Integration Plugin
 * Version: 0.0.1
 * Author: Fruitware
 * Author URI: http://fruitware.ru
 * Text Domain: bpi
 * Domain Path: /locale/
 * License: GPLv2 or later
 */
//register_activation_hook(__FILE__, '');
//register_deactivation_hook(__FILE__, 'fwds_slider_deactivation');
require_once 'init.php';

WordpressBpi\Plugin::init();

/*

add_action( 'admin_init', '' );
12
13	function tc_i18n() {
14	        load_plugin_textdomain( 'themecheck', false, 'theme-check/lang' );
15	}*/
