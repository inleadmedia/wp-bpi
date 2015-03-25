<?php

namespace Fruitframe;

/**
 * Класс отрисовки разных управляющих элементов с возможностью темизации
 */
class Control
{
	protected $_config;
	protected $_field;

	protected $_theme = array(
		'headerBefore' => '<div class="misc-pub-section misc-pub-post-status"><label for="post_status">',
		'headerAfter'  => ':</label>',
		'footer'       => '</div>',
		'fieldBefore'  => '<span id="post-status-display" style="vertical-align: middle;"> ',
		'fieldAfter'   => '</span>',
	);

	protected $_value;
	protected static $_instance = null;
	public $after;

	/**
	 * Get object instance
	 *
	 * @return object
	 */
	static public function getInstance()
	{
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Установка внешнего вида отрисовываемых элементов
	 * @param string $headerBefore
	 * @param string $headerAfter
	 * @param string $footer
	 * @param string $fieldBefore
	 * @param string $fieldAfter
	 */
	public function setTheme($headerBefore = '', $headerAfter = '', $footer = '', $fieldBefore = '', $fieldAfter = '')
	{
		$this->_theme = array(
			'headerBefore' => $headerBefore,
			'headerAfter'  => $headerAfter,
			'footer'       => $footer,
			'fieldBefore'  => $fieldBefore,
			'fieldAfter'   => $fieldAfter,
		);
	}

	public function renderAll($data, $values, $prefix)
	{
		$result = '';
		foreach($data as $field => $config)
			$result .= $this->render($field, $config, empty($values[$field]) ? NULL : $values[$field], $prefix);
		return $result;
	}

	public function render($field, $config = null, $value = null, $fieldPrefix = 'fruitframe')
	{
		$this->_fieldPrefix = $fieldPrefix;
		$this->_config = $config;
		$this->_field = $field;
		$this->_value = $value;
		$html = $this->_renderHeader();
		$html .= $this->_theme['fieldBefore'];
		switch ($this->_config['type']) {
			case 'text':
				$html .= $this->_renderText();
				break;
			case 'image':
				$html .= $this->_renderImage();
				break;
			case 'textarea':
				$html .= $this->_renderTextarea();
				break;
			case 'wysiwyg':
				$html .= $this->_renderWysiwyg();
				break;
			case 'select':
				$html .= $this->_renderSelectbox();
				break;
			case 'taxonomy':
				$html .= $this->_renderSelectboxTaxonomy();
				break;
			case 'checkbox':
				$html .= $this->_renderCheckbox();
				break;
			case 'view':
				$html .= $this->_renderView();
				break;
			case 'custom':
				$html .= $this->_renderCustom();
				break;
			default:
				break;
		}
		$html .= $this->_theme['fieldAfter'];
		$html .= $this->_renderDescription();
		$html .= $this->_renderFooter();
		return $html;
	}


	protected function _renderHeader()
	{
		return $this->_theme['headerBefore'] . $this->_config['label'] . $this->_theme['headerAfter'];
	}

	protected function _renderFooter()
	{
		return $this->_theme['footer'];
	}

	protected function _renderDescription($additional = '')
	{
		if (!is_array($this->_config))
			return;
		if (array_key_exists('description', $this->_config))
			return '<div class="description">'. $this->_config['description'] . $additional . '</div>';
	}

	protected function _renderText()
	{
		$value = null !== $this->_value ? $this->_value : $this->_config['default'];
		return  '<input type="text" class="text" name="'.$this->_fieldPrefix.'[' . $this->_field . ']" id="fruitframes_option_' . $this->_field . '" value="' . $value . '" />';
	}

	protected function _renderView()
	{
		if (!empty($this->_config['options']) && null !== $this->_value && array_key_exists($this->_value, $this->_config['options']))
		{
			return $this->_config['options'][$this->_value];
		}

		if (!empty($this->_config['format']))
		{
			switch($this->_config['format'])
			{
				case 'datetime':
					return date('d.m.Y H:i', $this->_value);
					break;
			}
		}
		return $value = null !== $this->_value ? $this->_value : (array_key_exists('default', $this->_config) ? $this->_config['default'] : '');
	}

	protected function _renderCustom()
	{
		if (array_key_exists('handler', $this->_config) && is_callable($this->_config['handler']))
		{
			return call_user_func($this->_config['handler'], array($this->_value));
		}
	}


	protected function _renderImage()
	{
		if ($this->_value) {
			$html = '<img src="' . $this->_value . '" alt="" /> <a href="#" class="delete" rel="' . $this->_field . '">delete</a>';
		} else {
			$html = '<input type="file" title="' . $this->_field . '" name="'.$this->_fieldPrefix.'[' . $this->_field . ']" id="fruitframes_option_' . $this->_field . '" />';
		}
//		$html .= $this->_renderDescription('<br/><strong>Image dimensions: ' . $this->_config['width'] . 'x' . $this->_config['height'] . '</strong>');
		return $html;
	}


	protected function _renderTextarea()
	{
		$value = null !== $this->_value ? $this->_value : $this->_config['default'];
		return '<textarea name="'.$this->_fieldPrefix.'[' . $this->_field . ']" id="fruitframes_option_' . $this->_field . '" cols="40" rows="5">' . stripslashes($value) . '</textarea>';
	}

	protected function _renderWysiwyg()
	{
		$value = null !== $this->_value ? $this->_value : $this->_config['default'];
		ob_start();
		wp_editor($value, $this->_fieldPrefix.'[' . $this->_field . ']', array('media_buttons' => FALSE));
		return ob_get_clean();
	}

	protected function _renderCheckbox()
	{
		$value = null !== $this->_value ? $this->_value : @$this->_config['default'];
		$html = '<select name="'.$this->_fieldPrefix.'[' . $this->_field . ']" id="fruitframes_option_' . $this->_field . '">';
		$html .= '<option value="0">No</option>';
		$html .= '<option value="1" ' . ($value ? 'selected="selected"' : '') . '>Yes</option>';
		$html .= '</select>';
		//$html .= '<input type="checkbox" name="'.$this->_fieldPrefix.'[' . $this->_field . ']" id="fruitframes_option_' . $this->_field . '" value="1" ' . ($value ? 'checked="checked"' : '') . ' />';
		return $html;
	}

	protected function _renderSelectbox()
	{
		if (is_array($this->_value))
		{
			$isMultiple = TRUE;
			$value = $this->_value;
		}
		else
		{
			$value = null !== $this->_value ? $this->_value : $this->_config['default'];
			$isMultiple = !empty($this->_config['multiple']);
		}
		$html = '<select name="'.$this->_fieldPrefix.'[' . $this->_field . ']'.($isMultiple ? '[]' : '').'" id="fruitframes_option_' . $this->_field . '"'.($isMultiple ? ' multiple="multiple" class="multiple-select"' : '').'>';
		foreach ($this->_config['options'] as $opt => $label) {
			$html .= '<option value="' . $opt . '" ' . (($isMultiple && is_array($value) && in_array($opt, $value)) ||  $opt == $value ? 'selected="selected"' : '') . '>' . $label . '</option>';
		}
		$html .= '</select>';
		return $html;
	}

	protected function _renderSelectboxTaxonomy()
	{
		if (is_array($this->_value))
		{
			$isMultiple = TRUE;
			$value = $this->_value;
		}
		else
		{
			$value = null !== $this->_value ? $this->_value : $this->_config['default'];
			$isMultiple = !empty($this->_config['multiple']);
		}
		$html = '<select name="'.$this->_fieldPrefix.'[' . $this->_field . ']'.($isMultiple ? '[]' : '').'" id="fruitframes_option_' . $this->_field . '"'.($isMultiple ? ' multiple="multiple" class="multiple-select"' : '').'>';
		if (array_key_exists('first', $this->_config))
		{
			foreach($this->_config['first'] as $key => $value)
			{
				$html .= '<option value="' . $key. '">' . $value . '</option>';
				}
		}
		foreach ($this->_config['options'] as $term) {
			$html .= '<option value="' . $term->term_id . '" ' . (($isMultiple && is_array($value) && in_array($term->term_id, $value)) ||  $term->term_id == $value ? 'selected="selected"' : '') . '>' . $term->name . '</option>';
		}
		$html .= '</select>';
		return $html;
	}
}