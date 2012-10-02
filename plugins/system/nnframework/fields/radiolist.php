<?php
/**
 * Element: Radio List
 * Displays a list of radio items with a break after each item
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

class JFormFieldNN_RadioList extends JFormField
{
	public $type = 'RadioList';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$html = array();

		$html[] = '<fieldset id="' . $this->id . '" class="radio">';

		$options = array();
		$i = 0;
		foreach ($this->element->children() as $option) {
			$i++;
			$checked = ((string) $option['value'] == (string) $this->value) ? ' checked="checked"' : '';
			$html[] = '<input type="radio" id="' . $this->id . $i . '" name="' . $this->name . '"' .
				' value="' . htmlspecialchars((string) $option['value'], ENT_COMPAT, 'UTF-8') . '"'
				. $checked . ' class="radio" style="clear:left;" />';

			$html[] = '<label for="' . $this->id . $i . '" class="radio" style="width:auto;min-width:none;">' . JText::_(trim((string) $option)) . '</label>';
		}

		$html[] = '</fieldset>';

		return implode('', $html);
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}