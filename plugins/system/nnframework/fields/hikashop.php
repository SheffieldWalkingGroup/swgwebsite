<?php
/**
 * Element: HikaShop
 * Displays a multiselectbox of available HikaShop categories / products
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

require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';

class JFormFieldNN_HikaShop extends JFormField
{
	public $type = 'HikaShop';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_hikashop/admin.hikashop.php')) {
			return '<fieldset class="radio">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_FILES_NOT_FOUND', JText::_('NN_HIKASHOP')) . '</fieldset>';
		}

		$group = $this->def('group', 'categories');

		$this->db = JFactory::getDBO();
		$tables = $this->db->getTableList();
		if (!in_array($this->db->getPrefix() . 'hikashop_' . ($group == 'products' ? 'product' : 'category'), $tables)) {
			return '<fieldset class="radio">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_TABLE_NOT_FOUND', JText::_('NN_HIKASHOP')) . '</fieldset>';
		}

		if (!is_array($this->value)) {
			$this->value = explode(',', $this->value);
		}

		$options = $this->{'get' . $group}();

		$size = (int) $this->def('size');
		$multiple = $this->def('multiple');

		require_once JPATH_PLUGINS . '/system/nnframework/helpers/html.php';
		return nnHTML::selectlist($options, $this->name, $this->value, $this->id, $size, $multiple);
	}

	function getCategories()
	{
		$show_ignore = $this->def('show_ignore');

		$query = $this->db->getQuery(true);
		$query->select('c.category_id');
		$query->from('#__hikashop_category AS c');
		$query->where('c.category_type = ' . $this->db->quote('root'));
		$this->db->setQuery($query);
		$root = $this->db->loadResult();

		$query = $this->db->getQuery(true);
		$query->select('c.category_id as id, c.category_parent_id AS parent_id, c.category_name AS title, c.category_published as published');
		$query->from('#__hikashop_category AS c');
		$query->where('c.category_published > -1');
		$query->where('c.category_type = ' . $this->db->quote('product'));
		$query->order('c.category_ordering, c.category_name');
		$this->db->setQuery($query);
		$items = $this->db->loadObjectList();

		// establish the hierarchy of the menu
		// TODO: use node model
		$children = array();

		// first pass - collect children
		foreach ($items as $v) {
			$pt = $v->parent_id;
			$list = @$children[$pt] ? $children[$pt] : array();
			array_push($list, $v);
			$children[$pt] = $list;
		}

		// second pass - get an indent list of the items
		require_once JPATH_LIBRARIES . '/joomla/html/html/menu.php';
		$list = JHTMLMenu::treerecurse((int) $root, '', array(), $children, 9999, 0, 0);

		// assemble items to the array
		$options = array();
		if ($show_ignore) {
			if (in_array('-1', $this->value)) {
				$this->value = array('-1');
			}
			$options[] = JHtml::_('select.option', '-1', '- ' . JText::_('NN_IGNORE') . ' -', 'value', 'text', 0);
			$options[] = JHtml::_('select.option', '-', '&nbsp;', 'value', 'text', 1);
		}
		foreach ($list as $item) {
			$item->treename = NNText::prepareSelectItem($item->treename, $item->published, '', 1);
			$options[] = JHtml::_('select.option', $item->id, $item->treename, 'value', 'text', 0);
		}

		return $options;
	}

	function getProducts()
	{
		$query = $this->db->getQuery(true);
		$query->select('COUNT(*)');
		$query->from('#__hikashop_product AS p');
		$query->where('p.product_published > -1');
		$this->db->setQuery($query);
		$total = $this->db->loadResult();

		if ($total > 2500) {
			return -1;
		}

		$query = $this->db->getQuery(true);
		$query->select('p.product_id as id, p.product_name AS name, c.category_name AS cat, p.product_published AS published');
		$query->from('#__hikashop_product AS p');
		$query->join('LEFT', '#__hikashop_product_category AS x ON x.product_id = p.product_id');
		$query->join('LEFT', '#__hikashop_category AS c ON c.category_id = x.category_id');
		$query->where('p.product_published > -1');
		$query->order('p.product_name, p.product_id');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		// assemble items to the array
		$options = array();
		foreach ($list as $item) {
			$item->name = $item->name . ' [' . $item->id . ']' . ($item->cat ? ' [' . $item->cat . ']' : '');
			$item->name = NNText::prepareSelectItem($item->name, $item->published);
			$options[] = JHtml::_('select.option', $item->id, $item->name, 'value', 'text', 0);
		}

		return $options;
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}