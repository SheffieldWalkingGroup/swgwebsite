<?php
/**
 * Element: Text Area Plus
 * Displays a text area with extra options
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

require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';

class JFormFieldNN_TextAreaPlus extends JFormField
{
	public $type = 'TextAreaPlus';
	private $_version = '12.9.7';

	protected function getLabel()
	{
		$this->params = $this->element->attributes();

		$label = NNText::html_entity_decoder(JText::_($this->def('label')));

		$html = '<label id="' . $this->id . '-lbl" for="' . $this->id . '"';
		if ($this->description) {
			$html .= ' class="hasTip" title="' . $label . '::' . JText::_($this->description) . '">';
		} else {
			$html .= '>';
		}
		$html .= $label . '</label>';

		return $html;
	}

	protected function getInput()
	{
		$resize = $this->def('resize', 1);
		$width = $this->def('width', 400);
		$minwidth = $this->def('minwidth', 200);
		$minwidth = min($width, $minwidth);
		$maxwidth = $this->def('maxwidth', 1200);
		$maxwidth = max($width, $maxwidth);
		$height = $this->def('height', 80);
		$minheight = $this->def('minheight', 40);
		$minheight = min($height, $minheight);
		$maxheight = $this->def('maxheight', 600);
		$maxheight = max($height, $maxheight);
		$class = $this->def('class', 'text_area');
		$class = 'class="' . $class . '"';
		$type = $this->def('texttype');

		if ($resize) {
			$document = JFactory::getDocument();
			$document->addScript(JURI::root(true) . '/plugins/system/nnframework/fields/textareaplus/textareaplus.js?v=' . $this->_version);
			$document->addStyleSheet(JURI::root(true) . '/plugins/system/nnframework/fields/textareaplus/textareaplus.css?v=' . $this->_version);
			// not for Safari (and other webkit browsers) because it has its own resize option
			$script = 'window.addEvent( \'domready\', function() {'
				. ' if ( !window.webkit ) {'
				. ' new TextAreaResizer( \'' . $this->id . '\', { \'min_x\':' . $minwidth . ', \'max_x\':' . $maxwidth . ', \'min_y\':' . $minheight . ', \'max_y\':' . $maxheight . ' } );'
				. " }"
				. " });";
			$document->addScriptDeclaration($script);
		}

		if ($type == 'html') {
			// Convert <br /> tags so they are not visible when editing
			$this->value = str_replace('<br />', "\n", $this->value);
		} else if ($type == 'regex') {
			// Protects the special characters
			$this->value = str_replace('[:REGEX_ENTER:]', '\n', $this->value);
		}

		$this->value = htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8');

		return '<textarea name="' . $this->name . '" cols="' . (round($width / 7.5)) . '" rows="' . (round($height / 15)) . '" style="width:' . $width . 'px;height:' . $height . 'px" ' . $class . ' id="' . $this->id . '" >' . $this->value . '</textarea>';
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}