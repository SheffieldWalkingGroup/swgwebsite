<?php
/**
 * Element: Checkbox
 * Displays options as checkboxes
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

class JFormFieldNN_Checkbox extends JFormField
{
	public $type = 'Checkbox';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$newlines = $this->def('newlines', 0);
		$showcheckall = $this->def('showcheckall', 0);

		$checkall = ($this->value == '*');

		if (!$checkall) {
			if (!is_array($this->value)) {
				$this->value = explode(',', $this->value);
			}
		}

		$options = array();
		foreach ($this->element->children() as $option) {
			$text = $option->data();
			$hasval = 0;
			if (isset($option['value'])) {
				$val = (string) $option['value'];
				$disabled = (int) $option['disabled'];
				$hasval = 1;
			}
			if ($hasval) {
				$option = '<input type="checkbox" class="nn_' . $this->id . '" id="' . $this->id . $val . '" name="' . $this->name . '[]" value="' . $val . '"';
				if ($checkall || in_array($val, $this->value)) {
					$option .= ' checked="checked"';
				}
				if ($disabled) {
					$option .= ' disabled="disabled"';
				}
				$option .= ' /> <label for="' . $this->id . $val . '" class="checkboxes">' . JText::_($text) . '</label>';
			} else {
				$option = '<label style="clear:both;"><strong>' . JText::_($text) . '</strong></label>';
			}
			$options[] = $option;
		}

		$options = implode('', $options);

		if ($showcheckall) {
			$checkers = array();
			if ($showcheckall) {
				$checkers[] = '<input id="nn_checkall_' . $this->id . '" type="checkbox" onclick="NNFrameworkCheckAll( this, \'nn_' . $this->id . '\' );" /> ' . JText::_('JALL');

				$document = JFactory::getDocument();
				$js = "
					window.addEvent('domready', function() {
						$('nn_checkall_" . $this->id . "').checked = NNFrameworkAllChecked( 'nn_" . $this->id . "' );
					});
				";
				$document->addScriptDeclaration($js);
			}
			$options = implode('&nbsp;&nbsp;&nbsp;', $checkers) . '<br />' . $options;
		}
		$options .= '<input type="hidden" id="' . $this->id . 'x" name="' . $this->name . '' . '[]" value="x" checked="checked" />';

		$html = array();
		$html[] = '<fieldset id="' . $this->id . '" class="' . ($newlines ? 'checkboxes' : 'radio') . '">';
		$html[] = $options;
		$html[] = '</fieldset>';
		return implode('', $html);
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}