<?php
/**
 * Element: License
 * Displays the License state
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

class JFormFieldNN_License extends JFormField
{
	public $type = 'License';
	private $params = null;

	protected function getLabel()
	{
		return '';
	}

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$extension = $this->get('extension');

		if (!strlen($extension))
		{
			return '';
		}

		require_once JPATH_PLUGINS . '/system/nnframework/helpers/licenses.php';
		return '</div><div class="hide">' . NNLicenses::getInstance()->getMessage($extension);
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
