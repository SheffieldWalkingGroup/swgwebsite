<?php
/**
 * NoNumber Framework Helper File: Assignments: FlexiContent
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
		if ($parent->params->option != 'com_flexicontent')
		{
			return $parent->pass(0, $assignment);
		}

		$pass = (
			($params->inc_tags && $parent->params->view == 'tags')
			|| ($params->inc_items && in_array($parent->params->view, array('item', 'items')))
		);

		if (!$pass)
		{
			return $parent->pass(0, $assignment);
		}

		if ($params->inc_tags && $parent->params->view == 'tags')
		{
			$parent->q->clear()
				->select('t.name')
				->from('#__flexicontent_tags AS t')
				->where('t.id = ' . (int) trim(JFactory::getApplication()->input->getInt('id', 0)))
				->where('t.published = 1');
			$parent->db->setQuery($parent->q);
			$tag = $parent->db->loadResult();
			$tags = array($tag);
		}
		else
		{
			$parent->q->clear()
				->select('t.name')
				->from('#__flexicontent_tags_item_relations AS x')
				->join('LEFT', '#__flexicontent_tags AS t ON t.id = x.id')
				->where('x.itemid = ' . (int) $parent->params->id)
				->where('t.published = 1');
			$parent->db->setQuery($parent->q);
			$tags = $parent->db->loadColumn();
		}

		return $parent->passSimple($tags, $selection, $assignment, 1);
	}

	function passTypes(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		if ($parent->params->option != 'com_flexicontent')
		{
			return $parent->pass(0, $assignment);
		}

		$pass = in_array($parent->params->view, array('item', 'items'));

		if (!$pass)
		{
			return $parent->pass(0, $assignment);
		}

		$parent->q->clear()
			->select('x.type_id')
			->from('#__flexicontent_items_ext AS x')
			->where('x.itemid = ' . (int) $parent->params->id);
		$parent->db->setQuery($parent->q);
		$type = $parent->db->loadResult();

		$types = $parent->makeArray($type, 1);

		return $parent->passSimple($types, $selection, $assignment);
	}

	function getCatParentIds(&$parent, $id = 0)
	{
		return $parent->getParentIds($id, 'categories', 'parent_id');
	}
}
