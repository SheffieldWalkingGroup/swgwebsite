<?php
/**
 * Element: VirtueMart
 * Displays a multiselectbox of available VirtueMart categories / products
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

require_once JPATH_PLUGINS . '/system/nnframework/helpers/functions.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/parameters.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';

class JFormFieldNN_VirtueMart extends JFormField
{
	public $type = 'VirtueMart';
	private $params = null;
	private $db = null;
	private $max_list_count = 0;

	protected function getInput()
	{
		if (!NNFrameworkFunctions::extensionInstalled('virtuemart'))
		{
			return '<fieldset class="alert alert-danger">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_FILES_NOT_FOUND', JText::_('NN_VIRTUEMART')) . '</fieldset>';
		}

		$this->params = $this->element->attributes();
		$this->db = JFactory::getDBO();

		$group = $this->get('group', 'categories');

		$tables = $this->db->getTableList();
		if (!in_array($this->db->getPrefix() . 'virtuemart_' . $group, $tables))
		{
			return '<fieldset class="alert alert-danger">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_TABLE_NOT_FOUND', JText::_('NN_VIRTUEMART')) . '</fieldset>';
		}

		$parameters = NNParameters::getInstance();
		$params = $parameters->getPluginParams('nnframework');
		$this->max_list_count = $params->max_list_count;

		$query = $this->db->getQuery(true)
			->select('config')
			->from('#__virtuemart_configs')
			->where('virtuemart_config_id = 1');
		$this->db->setQuery($query);
		$config = $this->db->loadResult();
		$lang = substr($config, strpos($config, 'vmlang='));
		$lang = substr($lang, 0, strpos($lang, '|'));
		if (preg_match('#"([^"]*_[^"]*)"#', $lang, $lang))
		{
			$this->lang = $lang['1'];
		}
		else
		{
			$this->lang = 'en_gb';
		}

		if (!is_array($this->value))
		{
			$this->value = explode(',', $this->value);
		}

		$options = $this->{'get' . $group}();

		$size = (int) $this->get('size');
		$multiple = $this->get('multiple');

		if ($group == 'categories')
		{
			require_once JPATH_PLUGINS . '/system/nnframework/helpers/html.php';
			return nnHtml::selectlist($options, $this->name, $this->value, $this->id, $size, $multiple);
		}

		$attr = '';
		$attr .= ' size="' . (int) $size . '"';
		$attr .= $multiple ? ' multiple="multiple"' : '';

		return JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
	}

	function getCategories()
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from('#__virtuemart_categories AS c')
			->where('c.published > -1');
		$this->db->setQuery($query);
		$total = $this->db->loadResult();

		if ($total > $this->max_list_count)
		{
			return -1;
		}

		$show_ignore = $this->get('show_ignore');

		$query->clear()
			->select('c.virtuemart_category_id as id, cc.category_parent_id AS parent_id, l.category_name AS title, c.published')
			->from('#__virtuemart_categories_' . $this->lang . ' AS l')
			->join('', '#__virtuemart_categories AS c using (virtuemart_category_id)')
			->join('LEFT', '#__virtuemart_category_categories AS cc ON l.virtuemart_category_id = cc.category_child_id')
			->where('c.published > -1')
			->order('c.ordering, l.category_name');
		$this->db->setQuery($query);
		$items = $this->db->loadObjectList();

		// establish the hierarchy of the menu
		// TODO: use node model
		$children = array();

		if ($items)
		{
			// first pass - collect children
			foreach ($items as $v)
			{
				$pt = $v->parent_id;
				$list = @$children[$pt] ? $children[$pt] : array();
				array_push($list, $v);
				$children[$pt] = $list;
			}
		}

		// second pass - get an indent list of the items
		$list = JHtml::_('menu.treerecurse', 0, '', array(), $children, 9999, 0, 0);

		// assemble items to the array
		$options = array();
		if ($show_ignore)
		{
			if (in_array('-1', $this->value))
			{
				$this->value = array('-1');
			}
			$options[] = JHtml::_('select.option', '-1', '- ' . JText::_('NN_IGNORE') . ' -', 'value', 'text', 0);
			$options[] = JHtml::_('select.option', '-', '&nbsp;', 'value', 'text', 1);
		}
		foreach ($list as $item)
		{
			$item->treename = NNText::prepareSelectItem($item->treename, $item->published, '', 1);
			$options[] = JHtml::_('select.option', $item->id, $item->treename, 'value', 'text', 0);
		}

		return $options;
	}

	function getProducts()
	{
		$query = $this->db->getQuery(true)
			->select('p.virtuemart_product_id as id, l.product_name AS name, p.product_sku as sku, cl.category_name AS cat, p.published')
			->from('#__virtuemart_products AS p')
			->join('LEFT', '#__virtuemart_products_' . $this->lang . ' AS l ON l.virtuemart_product_id = p.virtuemart_product_id')
			->join('LEFT', '#__virtuemart_product_categories AS x ON x.virtuemart_product_id = p.virtuemart_product_id')
			->join('LEFT', '#__virtuemart_categories AS c ON c.virtuemart_category_id = x.virtuemart_category_id')
			->join('LEFT', '#__virtuemart_categories_' . $this->lang . ' AS cl ON cl.virtuemart_category_id = c.virtuemart_category_id')
			->where('p.published > -1')
			->order('l.product_name, p.product_sku');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		// assemble items to the array
		$options = array();
		foreach ($list as $item)
		{
			$item->name = $item->name . ' [' . $item->sku . ']' . ($item->cat ? ' [' . $item->cat . ']' : '');
			$item->name = NNText::prepareSelectItem($item->name, $item->published);
			$options[] = JHtml::_('select.option', $item->id, $item->name, 'value', 'text', 0);
		}

		return $options;
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
