<?php
/**
 * NoNumber Framework Helper File: Assignments: Templates
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

/**
 * Assignments: Templates
 */
class NNFrameworkAssignmentsTemplates
{
	function passTemplates(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$app = JFactory::getApplication();
		$template = $app->getTemplate();

		return $parent->passSimple($template, $selection, $assignment, 1);
	}
}