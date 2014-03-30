<?php
/**
 * Element: Custom Field Key
 * Displays a custom key field (use in combination with customfieldvalue)
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

class JFormFieldNN_CustomFieldKey extends JFormField
{
	public $type = 'CustomFieldKey';
	private $params = null;

	protected function getLabel()
	{
		$this->params = $this->element->attributes();

		$label = ($this->get('label') ? $this->get('label') : '');
		$size = ($this->get('size') ? 'size="' . $this->get('size') . '"' : '');
		$class = ($this->get('class') ? 'class="' . $this->get('class') . '"' : 'class="text_area"');
		$this->value = htmlspecialchars(html_entity_decode($this->value, ENT_QUOTES), ENT_QUOTES);

		$html = '<input type="text" name="' . $this->name . '" id="' . $this->id . '" value="' . $this->value
			. '" placeholder="' . JText::_($label) . '" title="' . JText::_($label) . '" ' . $class . ' ' . $size . ' />';
		$html = '<label for="' . $this->id . '" style="margin-top: -5px;">' . $html . '</label>';

		return $html;
	}

	protected function getInput()
	{
		return '<div style="display:none;"><div><div>';
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
