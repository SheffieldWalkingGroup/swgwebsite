<?php
/**
 * Element: IsSaved
 * Displays a hidden value of 1
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

class JFormFieldNN_IsSaved extends JFormField
{
	public $type = 'IsSaved';

	protected function getLabel()
	{
		return;
	}

	protected function getInput()
	{
		$html = '<input type="hidden" id="' . $this->id . '" name="' . $this->name . '" value="1" />';
		if (!$this->value) {
			$label = $this->element['label'];
			if ($label) {
				$html .= '<div class="nn_panel"><div class="nn_block nn_title">' . JText::_($this->element['label']) . '</div></div>';
			}
		}
		return $html;
	}
}