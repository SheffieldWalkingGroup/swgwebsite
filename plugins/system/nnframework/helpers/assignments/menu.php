<?php
/**
 * NoNumber Framework Helper File: Assignments: Menu
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
 * Assignments: Menu
 */
class NNFrameworkAssignmentsMenu
{
	function passMenu(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$pass = 0;

		if ($parent->params->Itemid) {
			$selection = $parent->makeArray($selection);
			$pass = in_array($parent->params->Itemid, $selection);
			if ($pass && $params->inc_children == 2) {
				$pass = 0;
			} else if (!$pass && $params->inc_children) {
				$parentids = NNFrameworkAssignmentsMenu::getParentIds($parent, $parent->params->Itemid);
				$parentids = array_diff($parentids, array('1'));
				foreach ($parentids as $id) {
					if (in_array($id, $selection)) {
						$pass = 1;
						break;
					}
				}
				unset($parentids);
			}
		} else if ($params->inc_noItemid) {
			$pass = 1;
		}

		return $parent->pass($pass, $assignment);
	}

	function getParentIds(&$parent, $id = 0)
	{
		return $parent->getParentIds($id, 'menu');
	}
}