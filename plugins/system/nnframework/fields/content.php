<?php
/**
 * Element: Content
 * Displays a multiselectbox of available categories / items
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

class JFormFieldNN_Content extends JFormField
{
	public $type = 'Content';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$this->db = JFactory::getDbo();

		if (!is_array($this->value)) {
			$this->value = explode(',', $this->value);
		}

		$group = $this->def('group', 'categories');
		$options = $this->{'get' . $group}();

		$size = (int) $this->def('size');
		$multiple = $this->def('multiple');

		require_once JPATH_PLUGINS . '/system/nnframework/helpers/html.php';
		return nnHTML::selectlist($options, $this->name, $this->value, $this->id, $size, $multiple);
	}

	function getCategories()
	{
		$show_ignore = $this->def('show_ignore');

		// assemble items to the array
		$options = array();
		if ($show_ignore) {
			if (in_array('-1', $this->value)) {
				$this->value = array('-1');
			}
			$options[] = JHtml::_('select.option', '-1', '- ' . JText::_('NN_IGNORE') . ' -', 'value', 'text', 0);
			$options[] = JHtml::_('select.option', '-', '&nbsp;', 'value', 'text', 1);
		}

		$query = $this->db->getQuery(true);
		$query->select('c.id, c.title, c.level, c.published');
		$query->from('#__categories AS c');
		$query->where('c.parent_id > 0');
		$query->where('c.published > -1');
		$query->where('extension = ' . $this->db->quote('com_content'));
		$query->order('c.lft');

		$this->db->setQuery($query);
		$items = $this->db->loadObjectList();

		foreach ($items as &$item) {
			$repeat = ($item->level - 1 >= 0) ? $item->level - 1 : 0;
			$item->title = str_repeat('- ', $repeat) . $item->title;
			$item->title = NNText::prepareSelectItem($item->title, $item->published);
			$options[] = JHtml::_('select.option', $item->id, $item->title);
		}

		return $options;
	}

	function getItems()
	{
		$query = $this->db->getQuery(true);
		$query->select('COUNT(*)');
		$query->from('#__content AS i');
		$query->where('i.access > -1');
		$this->db->setQuery($query);
		$total = $this->db->loadResult();

		if ($total > 2500) {
			return -1;
		}

		$query = $this->db->getQuery(true);
		$query->select('i.id, i.title as name, c.title as cat, i.access as published');
		$query->from('#__content AS i');
		$query->join('LEFT', '#__categories AS c ON c.id = i.catid');
		$query->where('i.access > -1');
		$query->order('i.title, i.ordering, i.id');
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