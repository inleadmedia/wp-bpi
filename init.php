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
 * Функция подключения файлов из директории, в том числе и рекурсивно
 * @param $path Путь, по которому будут подключаться файлы
 * @param bool $recursive Параметр рекурсивного прохода — с ним будут проверятся найденные в пути директории
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
