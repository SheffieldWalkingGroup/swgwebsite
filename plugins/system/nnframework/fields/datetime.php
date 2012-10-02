<?php
/**
 * Element: DateTime
 * Element to display the date and time
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

class JFormFieldNN_DateTime extends JFormField
{
	public $type = 'DateTime';

	protected function getLabel()
	{
		return;
	}

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$label = $this->def('label');
		$format = $this->def('format');

		$config = JFactory::getConfig();
		$date = JFactory::getDate();
		$date->setTimeZone(new DateTimeZone($config->getValue('config.offset')));

		if ($format) {
			$html = $date->toFormat($format, 1);
		} else {
			$html = $date->toFormat('', 1);
		}

		if ($label) {
			$html = JText::sprintf($label, $html);
		}

		return $html;
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}