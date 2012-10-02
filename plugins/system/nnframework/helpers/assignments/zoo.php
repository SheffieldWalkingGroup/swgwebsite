<?php
/**
 * NoNumber Framework Helper File: Assignments: ZOO
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
 * Assignments: ZOO
 */
class NNFrameworkAssignmentsZOO
{
	function init(&$parent)
	{
		if (!$parent->params->view) {
			$parent->params->view = $parent->params->task;
		}
		switch ($parent->params->view) {
			case 'item':
				$parent->params->idname = 'item_id';
				break;
			case 'category':
				$parent->params->idname = 'category_id';
				break;
		}
		$parent->params->id = JRequest::getInt($parent->params->idname);
	}

	function passPageTypes(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		return $parent->passPageTypes('com_zoo', $selection, $assignment);
	}

	function passCategories(&$parent, &$params, $selection = array(), $assignment = 'all', $article = 0)
	{
		if ($parent->params->option != 'com_zoo') {
			return $parent->pass(0, $assignment);
		}

		$pass = (
			($params->inc_apps && $parent->params->view == 'frontpage')
				|| ($params->inc_categories && $parent->params->view == 'category')
				|| ($params->inc_items && $parent->params->view == 'item')
		);

		if (!$pass) {
			return $parent->pass(0, $assignment);
		}

		$cats = array();
		if ($article && isset($article->catid)) {
			$cats = $article->catid;
		} else {
			switch ($parent->params->view) {
				case 'frontpage':
					if ($parent->params->id) {
						$cats[] = $parent->params->id;
					} else {
						$menuparams = $parent->getMenuItemParams($parent->params->Itemid);
						if (isset($menuparams->application)) {
							$cats[] = 'app' . $menuparams->application;
						}
					}
					break;
				case 'category':
					if ($parent->params->id) {
						$cats[] = $parent->params->id;
					} else {
						$menuparams = $parent->getMenuItemParams($parent->params->Itemid);
						if (isset($menuparams->category)) {
							$cats[] = $menuparams->category;
						}
					}
					if ($cats['0']) {
						$query = $parent->db->getQuery(true);
						$query->select('c.application_id');
						$query->from('#__zoo_category AS c');
						$query->where('c.id = ' . (int) $cats['0']);
						$parent->db->setQuery($query);
						if ($parent->db->loadResult()) {
							$cats[] = 'app' . $parent->db->loadResult();
						}
					}
					break;
				case 'item':
					$id = $parent->params->id;
					if (!$id) {
						$menuparams = $parent->getMenuItemParams($parent->params->Itemid);
						$id = isset($menuparams->item_id) ? $menuparams->item_id : '';
					}
					if ($id) {
						$query = $parent->db->getQuery(true);
						$query->select('c.category_id');
						$query->from('#__zoo_category_item AS c');
						$query->where('c.item_id = ' . (int) $id);
						$query->where('c.category_id != 0');
						$parent->db->setQuery($query);
						$cats = $parent->db->loadResultArray();

						$query = $parent->db->getQuery(true);
						$query->select('i.application_id');
						$query->from('#__zoo_item AS i');
						$query->where('i.id = ' . (int) $id);
						$parent->db->setQuery($query);
						$cats[] = 'app' . $parent->db->loadResult();
					}
					break;
				default:
					return $parent->pass(0, $assignment);
					break;
			}
		}

		$cats = $parent->makeArray($cats);

		$pass = $parent->passSimple($cats, $selection, 'include');

		if ($pass && $params->inc_children == 2) {
			return $parent->pass(0, $assignment);
		} else if (!$pass && $params->inc_children) {
			foreach ($cats as $cat) {
				$cats = array_merge($cats, self::getCatParentIds($parent, $cat));
			}
		}

		return $parent->passSimple($cats, $selection, $assignment);
	}

	function passItems(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		if (!$parent->params->id || $parent->params->option != 'com_zoo' || $parent->params->view != 'item') {
			return $parent->pass(0, $assignment);
		}

		return $parent->passSimple($parent->params->id, $selection, $assignment);
	}

	function getCatParentIds(&$parent, $id = 0)
	{
		$parent_ids = array();

		if (!$id) {
			return $parent_ids;
		}

		while ($id) {
			if (substr($id, 0, 3) == 'app') {
				$parent_ids[] = $id;
				break;
			} else {
				$query = $parent->db->getQuery(true);
				$query->select('c.parent');
				$query->from('#__zoo_category AS c');
				$query->where('c.id = ' . (int) $id);
				$parent->db->setQuery($query);
				$pid = $parent->db->loadResult();
				if ($pid) {
					$parent_ids[] = $pid;
				} else {
					$query = $parent->db->getQuery(true);
					$query->select('c.application_id');
					$query->from('#__zoo_category AS c');
					$query->where('c.id = ' . (int) $id);
					$parent->db->setQuery($query);
					$app = $parent->db->loadResult();
					if ($app) {
						$parent_ids[] = 'app' . $app;
					}
					break;
				}
				$id = $pid;
			}
		}
		return $parent_ids;
	}
}