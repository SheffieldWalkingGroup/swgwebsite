<?php
/**
 * Element: FlexiContent
 * Displays a multiselectbox of available Flexicontent Tags / Types
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

class JFormFieldNN_FlexiContent extends JFormField
{
	public $type = 'FlexiContent';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_flexicontent/admin.flexicontent.php')) {
			return '<fieldset class="radio">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_FILES_NOT_FOUND', JText::_('NN_FLEXICONTENT')) . '</fieldset>';
		}

		$group = $this->def('group', 'categories');

		$this->db = JFactory::getDBO();
		$tables = $this->db->getTableList();
		if (!in_array($this->db->getPrefix() . 'flexicontent_' . $group, $tables)) {
			return '<fieldset class="radio">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_TABLE_NOT_FOUND', JText::_('NN_FLEXICONTENT')) . '</fieldset>';
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

	function getTags()
	{
		$query = $this->db->getQuery(true);
		$query->select('t.name as id, t.name');
		$query->from('#__flexicontent_tags AS t');
		$query->where('t.published = 1');
		$query->order('t.name');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		return $this->getOptions($list);
	}

	function getTypes()
	{
		$query = $this->db->getQuery(true);
		$query->select('t.id, t.name');
		$query->from('#__flexicontent_types AS t');
		$query->where('t.published = 1');
		$query->order('t.name, t.id');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		return $this->getOptions($list);
	}

	function getOptions($list)
	{
		// assemble items to the array
		$options = array();
		foreach ($list as $item) {
			$options[] = JHtml::_('select.option', $item->id, $item->name, 'value', 'text', 0);
		}

		return $options;
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}