<?php
/**
 * Element: ColorPicker
 * Displays a textfield with a color picker
 *
 * @package         NoNumber Framework
 * @version         14.2.6
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2014 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

jimport('joomla.form.formfield');

class JFormFieldNN_ColorPicker extends JFormField
{
	public $type = 'ColorPicker';

	protected function getInput()
	{
		$field = new nnFieldColorPicker;
		return $field->getInput($this->name, $this->id, $this->value, $this->element->attributes());
	}
}

class nnFieldColorPicker
{
	function getInput($name, $id, $value, $params)
	{
		$this->name = $name;
		$this->id = $id;
		$this->value = $value;
		$this->params = $params;
		$action = '';

		if ($this->get('inlist', 0) && $this->get('action'))
		{
			$this->name = $name . $id;
			$this->id = $name . $id;
			$action = ' onchange="' . $this->get('action') . '"';
		}

		JHtml::stylesheet('nnframework/colorpicker.min.css', false, true);
		JHtml::script('nnframework/colorpicker.min.js', false, true);

		$class = ' class="' . trim('nncolorpicker chzn-done ' . $this->get('class')) . '"';

		$color = strtolower($this->value);
		if (!$color || in_array($color, array('none', 'transparent')))
		{
			$color = 'none';
		}
		else if ($color['0'] != '#')
		{
			$color = '#' . $color;
		}

		$colors = $this->get('colors');
		if (empty($colors))
		{
			$colors = array(
				'none',
				'#049cdb',
				'#46a546',
				'#9d261d',
				'#ffc40d',
				'#f89406',
				'#c3325f',
				'#7a43b6',
				'#ffffff',
				'#999999',
				'#555555',
				'#000000'
			);
		}
		else
		{
			$colors = explode(',', $colors);
		}

		$split = (int) $this->get('split');
		if (!$split)
		{
			$count = count($colors);
			if ($count % 5 == 0)
			{
				$split = 5;
			}
			else if ($count % 4 == 0)
			{
				$split = 4;
			}
		}
		$split = $split ? $split : 3;

		$html = array();
		$html[] = '<select ' . $action . ' name="' . $this->name . '" id="' . $this->id . '"'
			. $class . ' style="visibility:hidden;width:22px;height:1px">';

		foreach ($colors as $i => $c)
		{
			$html[] = '<option' . ($c == $color ? ' selected="selected"' : '') . '>' . $c . '</option>';
			if (($i + 1) % $split == 0)
			{
				$html[] = '<option>-</option>';
			}
		}
		$html[] = '</select>';

		return implode('', $html);
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
