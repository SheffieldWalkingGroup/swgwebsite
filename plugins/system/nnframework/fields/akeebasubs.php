<?php
/**
 * Element: AkeebaSubs
 * Displays a multiselectbox of available Akeeba Subsriptons levels
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
require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';

class JFormFieldNN_AkeebaSubs extends JFormField
{
	public $type = 'AkeebaSubs';
	private $params = null;
	private $db = null;

	protected function getInput()
	{
		if (!NNFrameworkFunctions::extensionInstalled('akeebasubs'))
		{
			return '<fieldset class="alert alert-danger">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_FILES_NOT_FOUND', JText::_('NN_AKEEBASUBS')) . '</fieldset>';
		}
		$this->params = $this->element->attributes();
		$this->db = JFactory::getDBO();

		$group = $this->get('group', 'categories');

		$tables = $this->db->getTableList();
		if (!in_array($this->db->getPrefix() . 'akeebasubs_' . $group, $tables))
		{
			return '<fieldset class="alert alert-danger">' . JText::_('ERROR') . ': ' . JText::sprintf('NN_TABLE_NOT_FOUND', JText::_('NN_AKEEBASUBS')) . '</fieldset>';
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
		else
		{
			$attr = '';
			$attr .= ' size="' . (int) $size . '"';
			$attr .= $multiple ? ' multiple="multiple"' : '';

			return JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
		}
	}

	function getLevels()
	{
		$query = $this->db->getQuery(true)
			->select('l.akeebasubs_level_id as id, l.title AS name, l.enabled as published')
			->from('#__akeebasubs_levels AS l')
			->where('l.enabled > -1')
			->order('l.title, l.akeebasubs_level_id');
		$this->db->setQuery($query);
		$list = $this->db->loadObjectList();

		// assemble items to the array
		$options = array();
		foreach ($list as $item)
		{
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
