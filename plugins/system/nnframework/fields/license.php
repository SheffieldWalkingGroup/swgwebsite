<?php
/**
 * Element: License
 * Displays the License state
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

class JFormFieldNN_License extends JFormField
{
	public $type = 'License';

	protected function getLabel()
	{
		return;
	}

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$extension = $this->def('extension');

		if (!strlen($extension)) {
			return;
		}

		// Import library dependencies
		require_once JPATH_PLUGINS . '/system/nnframework/helpers/licenses.php';
		$licenses = NNLicenses::getInstance();

		return $licenses->getMessage($extension);
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}

/* For backward compatibility */
if (!function_exists('NoNumber_License_outputState')) {
	function NoNumber_License_outputState($extension)
	{
		require_once JPATH_PLUGINS . '/system/nnframework/helpers/licenses.php';
		$licenses = NNLicenses::getInstance();

		return $licenses->getMessage($extension);
	}
}
if (!function_exists('NoNumber_License_getState')) {
	function NoNumber_License_getState($extension)
	{
		require_once JPATH_PLUGINS . '/system/nnframework/helpers/licenses.php';
		$licenses = NNLicenses::getInstance();

		return $licenses->getState($extension);
	}
}