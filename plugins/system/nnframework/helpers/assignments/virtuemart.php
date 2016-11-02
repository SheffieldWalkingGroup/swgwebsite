<?php
/**
 * NoNumber Framework Helper File: Assignments: VirtueMart
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
 * Assignments: VirtueMart
 */
class NNFrameworkAssignmentsVirtueMart
{
	function init(&$parent)
	{
		$parent->params->item_id = JFactory::getApplication()->input->getInt('virtuemart_product_id', 0);
		$parent->params->category_id = JFactory::getApplication()->input->getString('virtuemart_category_id', '');
		$parent->params->id = ($parent->params->item_id) ? $parent->params->item_id : $parent->params->category_id;
	}

	function passPageTypes(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		return $parent->passPageTypes('com_virtuemart', $selection, $assignment, 1);
	}

	function passCategories(&$parent, &$params, $selection = array(), $assignment = 'all', $article = 0)
	{
		if ($parent->params->option != 'com_virtuemart')
		{
			return $parent->pass(0, $assignment);
		}

		$pass = (($params->inc_categories && in_array($parent->params->view, array('categories', 'category')))
			|| ($params->inc_items && $parent->params->view == 'productdetails')
		);

		if (!$pass)
		{
			return $parent->pass(0, $assignment);
		}

		$cats = array();
		if ($parent->params->view == 'productdetails' && $parent->params->item_id)
		{
			$parent->q->clear()
				->select('x.virtuemart_category_id')
				->from('#__virtuemart_product_categories AS x')
				->where('x.virtuemart_product_id = ' . (int) $parent->params->item_id);
			$parent->db->setQuery($parent->q);
			$cats = $parent->db->loadColumn();
		}
		else if ($parent->params->category_id)
		{
			$cats = $parent->params->category_id;
			if (!is_numeric($cats))
			{
				$parent->q->clear()
					->select('config')
					->from('#__virtuemart_configs')
					->where('virtuemart_config_id = 1');
				$parent->db->setQuery($parent->q);
				$config = $parent->db->loadResult();
				$lang = substr($config, strpos($config, 'vmlang='));
				$lang = substr($lang, 0, strpos($lang, '|'));
				if (preg_match('#"([^"]*_[^"]*)"#', $lang, $lang))
				{
					$lang = $lang['1'];
				}
				else
				{
					$lang = 'en_gb';
				}

				$parent->q->clear()
					->select('l.virtuemart_category_id')
					->from('#__virtuemart_categories_' . $lang . ' AS l')
					->where('l.slug = ' . $parent->db->quote($cats));
				$parent->db->setQuery($parent->q);
				$cats = $parent->db->loadResult();
			}
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
		if (!$parent->params->id || $parent->params->option != 'com_virtuemart' || $parent->params->view != 'productdetails')
		{
			return $parent->pass(0, $assignment);
		}

		return $parent->passSimple($parent->params->id, $selection, $assignment);
	}

	function getCatParentIds(&$parent, $id = 0)
	{
		return $parent->getParentIds($id, 'virtuemart_category_categories', 'category_parent_id', 'category_child_id');
	}
}
