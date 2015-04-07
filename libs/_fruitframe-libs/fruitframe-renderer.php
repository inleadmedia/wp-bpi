<?php
namespace Fruitframe;
class Renderer
{
	/**
	 * Static method to render simple template
	 *
	 * @param string $filename
	 * @param array $args
	 * @return string
	 */
	public static function render($filename, $args = array())
	{
		if (is_file($filename)) {
			ob_start();
			extract($args);
			include($filename);
			return ob_get_clean();
		}
		trigger_error('Invalid filepath [' . $filename . ']');
		return false;
	}

	/**
	 * Render admin template
	 *
	 * @param string $template
	 * @param array $args
	 * @return string
	 */
	public static function render_template($template, $args)
	{
		return self::render(FRUITFRAME_TEMPLATES . '/' . $template . '.php', $args);
	}

	/**
	 * Показ блока из папки _parts
	 *
	 * @param string $slug
	 * @param string $name
	 * @param array $args
	 * @return string
	 */
	public static function render_template_part($slug, $name, $args)
	{
		if (!is_file($template = FRUITFRAME_PATH . '/_parts/'.$slug.'-'.$name.'.php'))
		{
			$template = FRUITFRAME_PATH . '/_parts/'.$slug.'.php';
		}
		return self::render($template, $args);
	}
}