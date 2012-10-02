<?php
/**
 * NoNumber Framework Helper File: Assignments: HikaShop
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
 * Assignments: HikaShop
 */
class NNFrameworkAssignmentsHikaShop
{
	function passPageTypes(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		function passPageTypes(&$parent, &$params, $selection = array(), $assignment = 'all')
		{
			if ($parent->params->option != 'com_hikashop') {
				return $parent->pass(0, $assignment);
			}

			$type = $parent->params->view;
			if (
				($type == 'product' && in_array($parent->params->layout, array('contact', 'show')))
				|| ($type == 'user' && in_array($parent->params->layout, array('cpanel')))
			) {
				$type .= '_' . $parent->params->layout;
			}

			return $parent->passSimple($type, $selection, $assignment);
		}
	}

	function passCategories(&$parent, &$params, $selection = array(), $assignment = 'all', $article = 0)
	{
		if ($parent->params->option != 'com_hikashop') {
			return $parent->pass(0, $assignment);
		}

		$pass = (
			($params->inc_categories
				&& ($parent->params->view == 'category')
			)
				|| ($params->inc_items && $parent->params->view == 'product')
		);

		if (!$pass) {
			return $parent->pass(0, $assignment);
		}

		$cats = array();
		if ($parent->params->view == 'category') {
			$cats = $parent->params->id;
		} else if ($parent->params->id) {
			$query = $parent->db->getQuery(true);
			$query->select('c.category_id');
			$query->from('#__hikashop_product_category AS c');
			$query->where('c.product_id = ' . (int) $parent->params->id);
			$parent->db->setQuery($query);
			$cats = $parent->db->loadResultArray();
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

	function passProducts(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		if (!$parent->params->id || $parent->params->option != 'com_hikashop' || $parent->params->view != 'product') {
			return $parent->pass(0, $assignment);
		}

		return $parent->passSimple($parent->params->id, $selection, $assignment);
	}

	function getCatParentIds(&$parent, $id = 0)
	{
		return $parent->getParentIds($id, 'hikashop_category', 'category_parent_id', 'category_id');
	}
}