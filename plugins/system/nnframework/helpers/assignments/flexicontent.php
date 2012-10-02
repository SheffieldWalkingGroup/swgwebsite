<?php
/**
 * NoNumber Framework Helper File: Assignments: FlexiContent
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
 * Assignments: FlexiContent
 */
class NNFrameworkAssignmentsFlexiContent
{
	function passPageTypes(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		return $parent->passPageTypes('com_flexicontent', $selection, $assignment);
	}

	function passTags(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		if ($parent->params->option != 'com_flexicontent') {
			return $parent->pass(0, $assignment);
		}

		$pass = (
			($params->inc_tags && $parent->params->view == 'tags')
				|| ($params->inc_items && in_array($parent->params->view, array('item', 'items')))
		);

		if (!$pass) {
			return $parent->pass(0, $assignment);
		}

		if ($params->inc_tags && $parent->params->view == 'tags') {
			$query = $parent->db->getQuery(true);
			$query->select('t.name');
			$query->from('#__flexicontent_tags AS t');
			$query->where('t.id = ' . (int) trim(JRequest::getInt('id')));
			$query->where('t.published = 1');
			$parent->db->setQuery($query);
			$tag = $parent->db->loadResult();
			$tags = array($tag);
		} else {
			$query = $parent->db->getQuery(true);
			$query->select('t.name');
			$query->from('#__flexicontent_tags_item_relations AS x');
			$query->leftJoin('#__flexicontent_tags AS t ON t.id = x.id');
			$query->where('x.itemid = ' . (int) $parent->params->id);
			$query->where('t.published = 1');
			$parent->db->setQuery($query);
			$tags = $parent->db->loadResultArray();
		}

		return $parent->passSimple($tags, $selection, $assignment, 1);
	}

	function passTypes(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		if ($parent->params->option != 'com_flexicontent') {
			return $parent->pass(0, $assignment);
		}

		$pass = in_array($parent->params->view, array('item', 'items'));

		if (!$pass) {
			return $parent->pass(0, $assignment);
		}

		$query = $parent->db->getQuery(true);
		$query->select('x.type_id');
		$query->from('#__flexicontent_items_ext AS x');
		$query->where('x.itemid = ' . (int) $parent->params->id);
		$parent->db->setQuery($query);
		$type = $parent->db->loadResult();

		$types = $parent->makeArray($type, 1);

		return $parent->passSimple($types, $selection, $assignment);
	}

	function getCatParentIds(&$parent, $id = 0)
	{
		return $parent->getParentIds($id, 'categories', 'parent_id');
	}
}