<?php

define('FRUITFRAME_TEMPLATES', plugin_dir_path(__FILE__).'templates/');
define('WP_AE_PLUGIN_NAME', basename(plugin_dir_path(__FILE__)));

require_once 'vendor/autoload.php';
require_once 'vendor/bpi/sdk/Bpi/Sdk/Bpi.php';
if ( ! class_exists('WP_List_Table'))
{
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}


/**
 * Recursive directory require functions
 * @param $path File Path
 * @param bool $recursive Should we check each dir and require its files
 */
function requireFiles($path, $recursive = FALSE)
{
	if (is_array($filePathArray = glob($path.'/*'.($recursive ? '' : '.php'))))
	{
		foreach($filePathArray as $filePath)
		{
			if (is_dir($filePath))
			{
				requireFiles($filePath, TRUE);
				continue;
			}
			if (!$recursive || (($pathInfo = pathinfo($filePath)) && strtolower(@$pathInfo['extension']) == 'php'))
			{
				require_once($filePath);
			}
		}
	}
}

requireFiles(__DIR__.'/libs', true);
