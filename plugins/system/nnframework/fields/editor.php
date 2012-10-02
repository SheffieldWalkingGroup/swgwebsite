<?php
/**
 * Element: Editor
 * Displays an HTML editor text field
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

class JFormFieldNN_Editor extends JFormField
{
	public $type = 'Editor';

	protected function getLabel()
	{
		return;
	}

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$width = $this->def('width', '100%');
		$height = $this->def('height', 400);

		$this->value = htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8');

		// Get an editor object.
		$editor = JFactory::getEditor();
		$html = $editor->display($this->name, $this->value, $width, $height, true, $this->id);
		$html .= '<br clear="all" />';

		return $html;
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}