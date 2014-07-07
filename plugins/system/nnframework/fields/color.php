<?php
/**
 * Element: Color
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

class JFormFieldNN_Color extends JFormField
{
	public $type = 'Color';

	protected function getInput()
	{
		$field = new nnFieldColor;
		return $field->getInput($this->name, $this->id, $this->value, $this->element->attributes());
	}
}

class nnFieldColor
{
	function getInput($name, $id, $value, $params)
	{
		$this->name = $name;
		$this->id = $id;
		$this->value = $value;
		$this->params = $params;

		$class = trim('nn_color minicolors ' . $this->get('class'));
		$disabled = $this->get('disabled') ? ' disabled="disabled"' : '';

		JHtml::stylesheet('nnframework/color.min.css', false, true);
		JHtml::script('nnframework/color.min.js', false, true);

		$this->value = strtolower(strtoupper(preg_replace('#[^a-z0-9]#si', '', $this->value)));

		return '<input type="text" name="' . $this->name . '" id="' . $this->id . '" class="' . $class . '" value="' . $this->value . '"' . $disabled . '>';
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
