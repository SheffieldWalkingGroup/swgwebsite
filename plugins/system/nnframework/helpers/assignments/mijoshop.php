<?php
/**
 * NoNumber Framework Helper File: Assignments: MijoShop
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
 * Assignments: MijoShop
 */
class NNFrameworkAssignmentsMijoShop
{
	function init(&$parent)
	{
		$input = JFactory::getApplication()->input;

		$category_id = $input->getCmd('path', 0);
		if (strpos($category_id, '_'))
		{
			$category_id = end(explode('_', $category_id));
		}

		$parent->params->item_id = $input->getInt('product_id', 0);
		$parent->params->category_id = $category_id;
		$parent->params->id = ($parent->params->item_id) ? $parent->params->item_id : $parent->params->category_id;

		$view = $input->getCmd('view', '');
		if (empty($view))
		{
			$mijoshop = JPATH_ROOT . '/components/com_mijoshop/mijoshop/mijoshop.php';
			if (!file_exists($mijoshop))
			{
				return;
			}

			require_once($mijoshop);

			$route = $input->getString('route', '');
			$view = MijoShop::get('router')->getView($route);
		}

		$parent->params->view = $view;
	}

	function passPageTypes(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		return $parent->passPageTypes('com_mijoshop', $selection, $assignment, 1);
	}

	function passCategories(&$parent, &$params, $selection = array(), $assignment = 'all', $article = 0)
	{
		if ($parent->params->option != 'com_mijoshop')
		{
			return $parent->pass(0, $assignment);
		}

		$pass = (
			($params->inc_categories
				&& ($parent->params->view == 'category')
			)
			|| ($params->inc_items && $parent->params->view == 'product')
		);

		if (!$pass)
		{
			return $parent->pass(0, $assignment);
		}

		$cats = array();
		if ($parent->params->category_id)
		{
			$cats = $parent->params->category_id;
		}
		else if ($parent->params->item_id)
		{
			$parent->q->clear()
				->select('c.category_id')
				->from('#__mijoshop_product_to_category AS c')
				->where('c.product_id = ' . (int) $parent->params->id);
			$parent->db->setQuery($parent->q);
			$cats = $parent->db->loadColumn();
		}

		$cats = $parent->makeArray($cats);

		$pass = $parent->passSimple($cats, $selection, 'include');

		if ($pass && $params->inc_children == 2)
		{
			return $parent->pass(0, $assignment);
		}
		else if (!$pass && $params->inc_children)
		{
			foreach ($cats as $cat)
			{
				$cats = array_merge($cats, self::getCatParentIds($parent, $cat));
			}
		}

		return $parent->passSimple($cats, $selection, $assignment);
	}

	function passProducts(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		if (!$parent->params->id || $parent->params->option != 'com_mijoshop' || $parent->params->view != 'product')
		{
			return $parent->pass(0, $assignment);
		}

		return $parent->passSimple($parent->params->id, $selection, $assignment);
	}

	function getCatParentIds(&$parent, $id = 0)
	{
		return $parent->getParentIds($id, 'mijoshop_category', 'parent_id', 'category_id');
	}
}
