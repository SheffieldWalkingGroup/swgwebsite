<?php
/**
 * Element: Content
 * Displays a multiselectbox of available categories / items
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

require_once JPATH_PLUGINS . '/system/nnframework/helpers/parameters.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';

class JFormFieldNN_Content extends JFormField
{
	public $type = 'Content';
	private $params = null;
	private $db = null;
	private $max_list_count = 0;

	protected function getInput()
	{
		$this->params = $this->element->attributes();
		$this->db = JFactory::getDbo();

		$parameters = NNParameters::getInstance();
		$params = $parameters->getPluginParams('nnframework');
		$this->max_list_count = $params->max_list_count;

		if (!is_array($this->value))
		{
			$this->value = explode(',', $this->value);
		}

		$group = $this->get('group', 'categories');
		$options = $this->{'get' . $group}();

		$size = (int) $this->get('size');
		$multiple = $this->get('multiple');

		if ($group == 'categories')
		{
			require_once JPATH_PLUGINS . '/system/nnframework/helpers/html.php';

			return nnHtml::selectlist($options, $this->name, $this->value, $this->id, $size, $multiple);
		}
		else
		{
			$attr = '';
			$attr .= ' size="' . (int) $size . '"';
			$attr .= $multiple ? ' multiple="multiple"' : '';

			return JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
		}
	}

	function getCategories()
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from('#__categories AS c')
			->where('c.extension = ' . $this->db->quote('com_content'))
			->where('c.parent_id > 0')
			->where('c.published > -1');
		$this->db->setQuery($query);
		$total = $this->db->loadResult();

		if ($total > $this->max_list_count)
		{
			return -1;
		}

		$show_ignore = $this->get('show_ignore');

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

		$query->clear()
			->select('c.id, c.title, c.level, c.published, c.language')
			->from('#__categories AS c')
			->where('c.extension = ' . $this->db->quote('com_content'))
			->where('c.parent_id > 0')
			->where('c.published > -1')
			->order('c.lft');

		$this->db->setQuery($query);
		$items = $this->db->loadObjectList();

		foreach ($items as &$item)
		{
			if ($item->language && $item->language != '*')
			{
				$item->title .= ' (' . $item->language . ')';
			}
			$item->title = NNText::prepareSelectItem($item->title, $item->published);
			$option = JHtml::_('select.option', $item->id, $item->title);
			$option->level = $item->level - 1;
			$options[] = $option;
		}

		return $options;
	}

	function getItems()
	{
		$query = $this->db->getQuery(true)
			->select('i.id, i.title as name, i.language, c.title as cat, i.access as published')
			->from('#__content AS i')
			->join('LEFT', '#__categories AS c ON c.id = i.catid')
			->where('i.access > -1')
			->order('i.title, i.ordering, i.id');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		// assemble items to the array
		$options = array();
		foreach ($list as $item)
		{
			$item->name .= ' [' . $item->id . ']';
			if ($item->language && $item->language != '*')
			{
				$item->name .= ' (' . $item->language . ')';
			}
			$item->name .= ($item->cat ? ' [' . $item->cat . ']' : '');
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
