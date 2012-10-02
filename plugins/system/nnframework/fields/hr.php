<?php
/**
 * Element: HR
 * Displays a line
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

class JFormFieldNN_HR extends JFormField
{
	public $type = 'HR';
	private $_version = '12.9.7';

	protected function getLabel()
	{
		return;
	}

	protected function getInput()
	{
		$document = JFactory::getDocument();
		$document->addStyleSheet(JURI::root(true) . '/plugins/system/nnframework/css/style.css?v=' . $this->_version);

		return '<div class="nn_panel nn_hr"></div>';
	}
}