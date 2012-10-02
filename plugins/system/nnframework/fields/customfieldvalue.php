<?php
/**
 * Element: Custom Field Value
 * Displays a custom key field (use in combination with customfieldkey)
 *
 * @package         NoNumber Framework
 * @version         12.9.7
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2012 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

class JFormFieldNN_CustomFieldValue extends JFormField
{
	public $type = 'CustomFieldValue';

	protected function getLabel()
	{
		return '<span id="span_' . $this->id . '"></span>';
	}

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$size = ($this->def('size') ? 'size="' . $this->def('size') . '"' : '');
		$class = ($this->def('class') ? 'class="' . $this->def('class') . '"' : 'class="text_area"');
		$this->value = htmlspecialchars(html_entity_decode($this->value, ENT_QUOTES), ENT_QUOTES);

		return '<input type="text" name="' . $this->name . '" id="' . $this->id . '" value="' . $this->value . '" ' . $class . ' ' . $size . ' />';
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}