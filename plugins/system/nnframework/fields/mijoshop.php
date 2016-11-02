<?php
/**
 * Element: MijoShop
 * Displays a multiselectbox of available MijoShop categories / products
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

class JFormFieldNN_MijoShop extends JFormField
{
	public $type = 'MijoShop';
	private $params = null;
	private $db = null;
	public $store_id = 0;
	public $language_id = 1;
	private $max_list_count = 0;

	protected function getInput()
	{
		if (!NNFrameworkFunctions::extensionInstalled('mijoshop'))
		{
			return '<fieldset class="alert alert-danger">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_FILES_NOT_FOUND', JText::_('NN_MIJOSHOP')) . '</fieldset>';
		}

		$this->params = $this->element->attributes();
		$this->db = JFactory::getDBO();

		$group = $this->get('group', 'categories');

		$tables = $this->db->getTableList();
		if (!in_array($this->db->getPrefix() . 'mijoshop_' . ($group == 'products' ? 'product' : 'category'), $tables))
		{
			return '<fieldset class="alert alert-danger">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_TABLE_NOT_FOUND', JText::_('NN_MIJOSHOP')) . '</fieldset>';
		}

		$parameters = NNParameters::getInstance();
		$params = $parameters->getPluginParams('nnframework');
		$this->max_list_count = $params->max_list_count;

		require_once(JPATH_ROOT . '/components/com_mijoshop/mijoshop/mijoshop.php');
		$this->store_id = (int) MijoShop::get('opencart')->get('config')->get('config_store_id');
		$this->language_id = (int) MijoShop::get('opencart')->get('config')->get('config_language_id');

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
			->from('#__mijoshop_category AS c')
			->join('INNER', '#__mijoshop_category_description AS cd ON c.category_id = cd.category_id')
			->join('INNER', '#__mijoshop_category_to_store AS cts ON c.category_id = cts.category_id')
			->where('c.status = 1')
			->where('cd.language_id = ' . $this->language_id)
			->where('cts.store_id = ' . $this->store_id);
		$this->db->setQuery($query);
		$total = $this->db->loadResult();

		if ($total > $this->max_list_count)
		{
			return -1;
		}

		$show_ignore = $this->get('show_ignore');

		$query->clear()
			->select('c.category_id AS id, c.parent_id, cd.name AS title, c.status AS published')
			->from('#__mijoshop_category AS c')
			->join('INNER', '#__mijoshop_category_description AS cd ON c.category_id = cd.category_id')
			->join('INNER', '#__mijoshop_category_to_store AS cts ON c.category_id = cts.category_id')
			->where('c.status = 1')
			->where('cd.language_id = ' . $this->language_id)
			->where('cts.store_id = ' . $this->store_id)
			->order('c.sort_order, cd.name');
		$this->db->setQuery($query);
		$items = $this->db->loadObjectList();

		// establish the hierarchy of the menu
		// TODO: use node model
		$children = array();

		// first pass - collect children
		foreach ($items as $v)
		{
			$pt = $v->parent_id;
			$list = @$children[$pt] ? $children[$pt] : array();
			array_push($list, $v);
			$children[$pt] = $list;
		}

		// second pass - get an indent list of the items
		$list = JHtml::_('menu.treerecurse', (int) $root, '', array(), $children, 9999, 0, 0);

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
			->select('p.product_id as id, pd.name, p.model as number, cd.name AS cat, p.status AS published')
			->from('#__mijoshop_product AS p')
			->join('INNER', '#__mijoshop_product_description AS pd ON p.product_id = pd.product_id')
			->join('INNER', '#__mijoshop_product_to_store AS pts ON p.product_id = pts.product_id')
			->join('LEFT', '#__mijoshop_product_to_category AS ptc ON p.product_id = ptc.product_id')
			->join('LEFT', '#__mijoshop_category_description AS cd ON ptc.category_id = cd.category_id')
			->join('LEFT', '#__mijoshop_category_to_store AS cts ON ptc.category_id = cts.category_id')
			->where('p.status = 1')
			->where('p.date_available <= NOW()')
			->where('pd.language_id = ' . $this->language_id)
			->where('cts.store_id = ' . $this->store_id)
			->where('cd.language_id = ' . $this->language_id)
			->where('cts.store_id = ' . $this->store_id)
			->order('pd.name, p.model');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		// assemble items to the array
		$options = array();
		foreach ($list as $item)
		{
			$item->name = $item->name . ' [' . $item->id . ']' . ($item->cat ? ' [' . $item->cat . ']' : '');
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
