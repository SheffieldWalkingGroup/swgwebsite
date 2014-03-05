<?php
/**
 * Element: ZOO
 * Displays a multiselectbox of available ZOO categories
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

class JFormFieldNN_ZOO extends JFormField
{
	public $type = 'ZOO';
	private $params = null;
	private $db = null;
	private $max_list_count = 0;

	protected function getInput()
	{
		if (!NNFrameworkFunctions::extensionInstalled('zoo'))
		{
			return '<fieldset class="alert alert-danger">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_FILES_NOT_FOUND', JText::_('NN_ZOO')) . '</fieldset>';
		}

		$this->params = $this->element->attributes();
		$this->db = JFactory::getDBO();

		$group = $this->get('group', 'categories');

		$tables = $this->db->getTableList();
		if (!in_array($this->db->getPrefix() . 'zoo_' . ($group == 'applications' ? 'application' : 'category'), $tables))
		{
			return '<fieldset class="alert alert-danger">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_TABLE_NOT_FOUND', JText::_('NN_ZOO')) . '</fieldset>';
		}

		$parameters = NNParameters::getInstance();
		$params = $parameters->getPluginParams('nnframework');
		$this->max_list_count = $params->max_list_count;

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
			->from('#__zoo_category AS c')
			->where('c.published > -1');
		$this->db->setQuery($query);
		$total = $this->db->loadResult();

		if ($total > $this->max_list_count)
		{
			return -1;
		}

		$show_ignore = $this->get('show_ignore');

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

		$query->clear()
			->select('a.id, a.name')
			->from('#__zoo_application AS a')
			->order('a.name, a.id');
		$this->db->setQuery($query);
		$apps = $this->db->loadObjectList();

		foreach ($apps as $i => $app)
		{
			$query->clear()
				->select('c.id, c.parent AS parent_id, c.name AS title, c.published')
				->from('#__zoo_category AS c')
				->where('c.application_id = ' . (int) $app->id)
				->where('c.published > -1')
				->order('c.ordering, c.name');
			$this->db->setQuery($query);
			$items = $this->db->loadObjectList();

			if ($i)
			{
				$options[] = JHtml::_('select.option', '-', '&nbsp;', 'value', 'text', 1);
			}

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
			$options[] = JHtml::_('select.option', 'app' . $app->id, '[' . $app->name . ']', 'value', 'text', 0);
			foreach ($list as $item)
			{
				$item->treename = '  ' . str_replace('&#160;&#160;- ', '  ', $item->treename);
				$item->treename = NNText::prepareSelectItem($item->treename, $item->published);
				$option = JHtml::_('select.option', $item->id, $item->treename, 'value', 'text', 0);
				$option->level = 1;
				$options[] = $option;
			}
		}

		return $options;
	}

	function getItems()
	{
		$query = $this->db->getQuery(true)
			->select('i.id, i.name, a.name as app, i.state as published')
			->from('#__zoo_item AS i')
			->join('LEFT', '#__zoo_application AS a ON a.id = i.application_id')
			->where('i.state > -1')
			->order('i.name, i.priority, i.id');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		// assemble items to the array
		$options = array();
		foreach ($list as $item)
		{
			$item->name = $item->name . ' [' . $item->id . '] [' . $item->app . ']';
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
