<?php
/**
 * NoNumber Framework Helper File: Assignments: Tags
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
 * Assignments: Tags
 */
class NNFrameworkAssignmentsTags
{
	function passTags(&$parent, &$params, $selection = array(), $assignment = 'all', $article = 0)
	{
		$is_content = in_array($parent->params->option, array('com_content', 'com_flexicontent'));

		if (!$is_content)
		{
			return $parent->pass(0, $assignment);
		}

		$is_item = in_array($parent->params->view, array('', 'article', 'item'));
		$is_category = in_array($parent->params->view, array('category'));

		if ($is_item)
		{
			$prefix = 'com_content.article';
		}
		else if ($is_category)
		{
			$prefix = 'com_content.category';
		}
		else
		{
			return $parent->pass(0, $assignment);
		}

		// Load the tags.
		$parent->q->clear()
			->select($parent->db->quoteName('t.id'))
			->from('#__tags AS t')
			->join(
				'INNER', '#__contentitem_tag_map AS m'
				. ' ON m.tag_id = t.id'
				. ' AND m.type_alias = ' . $parent->db->quote($prefix)
				. ' AND m.content_item_id IN ( ' . $parent->params->id . ')'
			);
		$parent->db->setQuery($parent->q);
		$tags = $parent->db->loadColumn();

		if (empty($tags))
		{
			return $parent->pass(0, $assignment);
		}

		$pass = 0;

		foreach ($tags as $tag)
		{
			$pass = in_array($tag, $selection);
			if ($pass && $params->inc_children == 2)
			{
				$pass = 0;
			}
			else if (!$pass && $params->inc_children)
			{
				$parentids = self::getParentIds($parent, $tag);
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

		return $parent->pass($pass, $assignment);
	}

	function getParentIds(&$parent, $id = 0)
	{
		return $parent->getParentIds($id, 'tags');
	}
}
