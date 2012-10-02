<?php
/**
 * Element: ZOO
 * Displays a multiselectbox of available ZOO categories
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

class JFormFieldNN_ZOO extends JFormField
{
	public $type = 'ZOO';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_zoo/zoo.php')) {
			return '<fieldset class="radio">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_FILES_NOT_FOUND', JText::_('NN_ZOO')) . '</fieldset>';
		}

		$group = $this->def('group', 'categories');

		$this->db = JFactory::getDBO();
		$tables = $this->db->getTableList();
		if (!in_array($this->db->getPrefix() . 'zoo_' . ($group == 'applications' ? 'application' : 'category'), $tables)) {
			return '<fieldset class="radio">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_TABLE_NOT_FOUND', JText::_('NN_ZOO')) . '</fieldset>';
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

		$options = array();
		if ($show_ignore) {
			if (in_array('-1', $this->value)) {
				$this->value = array('-1');
			}
			$options[] = JHtml::_('select.option', '-1', '- ' . JText::_('NN_IGNORE') . ' -', 'value', 'text', 0);
			$options[] = JHtml::_('select.option', '-', '&nbsp;', 'value', 'text', 1);
		}

		$query = $this->db->getQuery(true);
		$query->select('a.id, a.name');
		$query->from('#__zoo_application AS a');
		$query->order('a.name, a.id');
		$this->db->setQuery($query);
		$apps = $this->db->loadObjectList();

		foreach ($apps as $i => $app) {
			$query = $this->db->getQuery(true);
			$query->select('c.id, c.parent AS parent_id, c.name AS title, c.published');
			$query->from('#__zoo_category AS c');
			$query->where('c.published > -1');
			$query->where('c.application_id = ' . (int) $app->id);
			$query->order('c.ordering, c.name');
			$this->db->setQuery($query);
			$items = $this->db->loadObjectList();

			if ($i) {
				$options[] = JHtml::_('select.option', '-', '&nbsp;', 'value', 'text', 1);
			}

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
			$options[] = JHtml::_('select.option', 'app' . $app->id, '[' . $app->name . ']', 'value', 'text', 0);
			foreach ($list as $item) {
				$item->treename = '  ' . str_replace('&#160;&#160;- ', '  ', $item->treename);
				$item->treename = NNText::prepareSelectItem($item->treename, $item->published);
				$options[] = JHtml::_('select.option', $item->id, $item->treename, 'value', 'text', 0);
			}
		}

		return $options;
	}

	function getItems()
	{
		$query = $this->db->getQuery(true);
		$query->select('COUNT(*)');
		$query->from('#__zoo_item AS i');
		$query->where('i.state > -1');
		$this->db->setQuery($query);
		$total = $this->db->loadResult();

		if ($total > 2500) {
			return -1;
		}

		$query = $this->db->getQuery(true);
		$query->select('i.id, i.name, a.name as app, i.state as published');
		$query->from('#__zoo_item AS i');
		$query->join('LEFT', '#__zoo_application AS a ON a.id = i.application_id');
		$query->where('i.state > -1');
		$query->order('i.name, i.priority, i.id');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		// assemble items to the array
		$options = array();
		foreach ($list as $item) {
			$item->name = $item->name . ' [' . $item->id . '] [' . $item->app . ']';
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