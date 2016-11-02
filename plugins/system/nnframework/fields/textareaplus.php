<?php
/**
 * Element: Text Area Plus
 * Displays a text area with extra options
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

require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';

class JFormFieldNN_TextAreaPlus extends JFormField
{
	public $type = 'TextAreaPlus';
	private $params = null;

	protected function getLabel()
	{
		$this->params = $this->element->attributes();

		$label = NNText::html_entity_decoder(JText::_($this->get('label')));

		$html = '<label id="' . $this->id . '-lbl" for="' . $this->id . '"';
		if ($this->description)
		{
			$html .= ' class="hasTooltip" title="<strong>' . $label . '</strong><br />' . JText::_($this->description) . '">';
		}
		else
		{
			$html .= '>';
		}
		$html .= $label . '</label>';

		return $html;
	}

	protected function getInput()
	{
		$width = $this->get('width', 600);
		$height = $this->get('height', 80);
		$class = trim('nn_textarea ' . $this->get('class'));
		$class = 'class="' . $class . '"';
		$type = $this->get('texttype');

		if(is_array($this->value)) {
			$this->value = trim(implode("\n", $this->value));
		}

		if ($type == 'html')
		{
			// Convert <br /> tags so they are not visible when editing
			$this->value = str_replace('<br />', "\n", $this->value);
		}
		else if ($type == 'regex')
		{
			// Protects the special characters
			$this->value = str_replace('[:REGEX_ENTER:]', '\n', $this->value);
		}

		$this->value = htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8');

		return '<textarea name="' . $this->name . '" cols="' . (round($width / 7.5)) . '" rows="' . (round($height / 15)) . '" style="width:' . (($width == '600') ? '100%' : $width . 'px') . ';height:' . $height . 'px" ' . $class . ' id="' . $this->id . '" >' . $this->value . '</textarea>';
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
