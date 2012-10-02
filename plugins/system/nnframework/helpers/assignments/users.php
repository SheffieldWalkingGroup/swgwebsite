<?php
/**
 * NoNumber Framework Helper File: Assignments: Users
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
 * Assignments: Users
 */
class NNFrameworkAssignmentsUsers
{
	function passUserGroupLevels(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$user = JFactory::getUser();

		if (isset($user->groups) && !empty($user->groups)) {
			$groups = array_values($user->groups);
		} else {
			$groups = $user->getAuthorisedGroups();
		}

		return $parent->passSimple($groups, $selection, $assignment);
	}

	function passUsers(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$user = JFactory::getUser();

		return $parent->passSimple($user->get('id'), $selection, $assignment);
	}
}