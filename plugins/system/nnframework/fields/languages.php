<?php
/**
 * Element: Languages
 * Displays a select box of languages
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

class JFormFieldNN_Languages extends JFormField
{
	public $type = 'Languages';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$size = (int) $this->def('size');
		$multiple = $this->def('multiple');
		$client = $this->def('client', 'SITE');

		jimport('joomla.language.helper');
		$options = JLanguageHelper::createLanguageList($this->value, constant('JPATH_' . strtoupper($client)), true);
		foreach ($options as $i => $option) {
			if ($option['value']) {
				$options[$i]['text'] = $option['text'] . ' [' . $option['value'] . ']';
			}
		}

		require_once JPATH_PLUGINS . '/system/nnframework/helpers/html.php';
		return nnHTML::selectlist($options, $this->name, $this->value, $this->id, $size, $multiple);
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}