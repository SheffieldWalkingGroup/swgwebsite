<?php
/**
 * NoNumber Framework Helper File: Assignments: Menu
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

/**
 * Assignments: Menu
 */
class NNFrameworkAssignmentsMenu
{
	function passMenu(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$pass = 0;

		if ($parent->params->Itemid && !empty($selection))
		{
			$menutype = 'type.' . self::getMenuType($parent);
			$pass = in_array($menutype, $selection);
			if (!$pass)
			{
				$selection = $parent->makeArray($selection);
				$pass = in_array($parent->params->Itemid, $selection);
				if ($pass && $params->inc_children == 2)
				{
					$pass = 0;
				}
				else if (!$pass && $params->inc_children)
				{
					$parentids = self::getParentIds($parent, $parent->params->Itemid);
					$parentids = array_diff($parentids, array('1'));
					foreach ($parentids as $id)
					{
						if (in_array($id, $selection))
						{
							$pass = 1;
							break;
						}
					}
					unset($parentids);
				}
			}
		}
		else if ($params->inc_noItemid)
		{
			$pass = 1;
		}

		return $parent->pass($pass, $assignment);
	}

	function getParentIds(&$parent, $id = 0)
	{
		return $parent->getParentIds($id, 'menu');
	}

	function getMenuType(&$parent)
	{
		if (!isset($parent->params->menutype))
		{
			$parent->q->clear()
				->select('m.menutype')
				->from('#__menu AS m')
				->where('m.id = ' . (int) $parent->params->Itemid);
			$parent->db->setQuery($parent->q);
			$parent->params->menutype = $parent->db->loadResult();
		}
		return $parent->params->menutype;
	}
}
