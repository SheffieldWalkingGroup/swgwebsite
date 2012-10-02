<?php
/**
 * Element: Custom Field Key
 * Displays a custom key field (use in combination with customfieldvalue)
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

class JFormFieldNN_CustomFieldKey extends JFormField
{
	public $type = 'CustomFieldKey';
	private $_version = '12.9.7';

	protected function getLabel()
	{
		return;
	}

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$size = ($this->def('size') ? 'size="' . $this->def('size') . '"' : '');
		$class = ($this->def('class') ? 'class="' . $this->def('class') . '"' : 'class="text_area"');
		$this->value = htmlspecialchars(html_entity_decode($this->value, ENT_QUOTES), ENT_QUOTES);

		JHtml::_('behavior.mootools');
		$document = JFactory::getDocument();
		$document->addScript(JURI::root(true) . '/plugins/system/nnframework/js/script.js?v=' . $this->_version);

		$html = '<input type="text" name="' . $this->name . '" id="' . $this->id . '" value="' . $this->value . '" ' . $class . ' ' . $size . ' />';
		$html = '<label for="' . $this->id . '" style="margin: 0;">' . $html . '</label>';

		return $html;
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}