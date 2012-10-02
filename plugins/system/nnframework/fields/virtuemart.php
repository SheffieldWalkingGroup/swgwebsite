<?php
/**
 * Element: VirtueMart
 * Displays a multiselectbox of available VirtueMart categories / products
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

class JFormFieldNN_VirtueMart extends JFormField
{
	public $type = 'VirtueMart';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_virtuemart/admin.virtuemart.php')) {
			return '<fieldset class="radio">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_FILES_NOT_FOUND', JText::_('NN_VIRTUEMART')) . '</fieldset>';
		}

		$group = $this->def('group', 'categories');

		$this->db = JFactory::getDBO();
		$tables = $this->db->getTableList();
		if (!in_array($this->db->getPrefix() . 'virtuemart_' . $group, $tables)) {
			return '<fieldset class="radio">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_TABLE_NOT_FOUND', JText::_('NN_VIRTUEMART')) . '</fieldset>';
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
		$query->select('c.virtuemart_category_id as id, cc.category_parent_id AS parent_id, l.category_name AS title, c.published');
		$query->from('#__virtuemart_categories AS c');
		$query->join('LEFT', '#__virtuemart_categories_en_gb AS l ON l.virtuemart_category_id = c.virtuemart_category_id');
		$query->join('LEFT', '#__virtuemart_category_categories AS cc ON cc.category_child_id = c.virtuemart_category_id');
		$query->where('c.published > -1');
		$query->order('c.ordering, l.category_name');
		$this->db->setQuery($query);
		$items = $this->db->loadObjectList();

		// establish the hierarchy of the menu
		// TODO: use node model
		$children = array();

		if ($items) {
			// first pass - collect children
			foreach ($items as $v) {
				$pt = $v->parent_id;
				$list = @$children[$pt] ? $children[$pt] : array();
				array_push($list, $v);
				$children[$pt] = $list;
			}
		}

		// second pass - get an indent list of the items
		require_once JPATH_LIBRARIES . '/joomla/html/html/menu.php';
		$list = JHTMLMenu::treerecurse(0, '', array(), $children, 9999, 0, 0);

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
		$query->from('#__virtuemart_products AS p');
		$query->where('p.published > -1');
		$this->db->setQuery($query);
		$total = $this->db->loadResult();

		if ($total > 2500) {
			return -1;
		}

		$query = $this->db->getQuery(true);
		$query->select('p.virtuemart_product_id as id, l.product_name AS name, p.product_sku as sku, cl.category_name AS cat, p.published');
		$query->from('#__virtuemart_products AS p');
		$query->join('LEFT', '#__virtuemart_products_en_gb AS l ON l.virtuemart_product_id = p.virtuemart_product_id');
		$query->join('LEFT', '#__virtuemart_product_categories AS x ON x.virtuemart_product_id = p.virtuemart_product_id');
		$query->join('LEFT', '#__virtuemart_categories AS c ON c.virtuemart_category_id = x.virtuemart_category_id');
		$query->join('LEFT', '#__virtuemart_categories_en_gb AS cl ON cl.virtuemart_category_id = c.virtuemart_category_id');
		$query->where('p.published > -1');
		$query->order('l.product_name, p.product_sku');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		// assemble items to the array
		$options = array();
		foreach ($list as $item) {
			$item->name = $item->name . ' [' . $item->sku . ']' . ($item->cat ? ' [' . $item->cat . ']' : '');
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