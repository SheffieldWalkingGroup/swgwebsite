<?php
/**
 * Element: Components
 * Displays a list of components with check boxes
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

class JFormFieldNN_Components extends JFormField
{
	public $type = 'Components';
	private $params = null;
	private $db = null;

	protected function getInput()
	{
		$this->params = $this->element->attributes();
		$this->db = JFactory::getDBO();

		$frontend = $this->get('frontend', 1);
		$admin = $this->get('admin', 1);
		$size = (int) $this->get('size');

		if (!$frontend && !$admin)
		{
			return '';
		}

		$components = $this->getComponents($frontend, $admin);

		$options = array();

		foreach ($components as $component)
		{
			$options[] = JHtml::_('select.option', $component->element, $component->name);
		}

		$attr = '';
		$attr .= $size ? ' size="' . (int) $size . '"' : '';
		$attr .= ' multiple="multiple"';

		return JHtml::_('select.genericlist', $options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);
	}

	function getComponents($frontend = 1, $admin = 1)
	{
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		$query = $this->db->getQuery(true)
			->select('e.name, e.element')
			->from('#__extensions AS e')
			->where('e.type = ' . $this->db->quote('component'))
			->where('e.name != ""')
			->where('e.element != ""')
			->group('e.element')
			->order('e.element, e.name');
		$this->db->setQuery($query);
		$components = $this->db->loadObjectList();

		$comps = array();
		$lang = JFactory::getLanguage();

		foreach ($components as $i => $component)
		{
			// return if there is no main component folder
			if (!($frontend && JFolder::exists(JPATH_SITE . '/components/' . $component->element))
				&& !($admin && JFolder::exists(JPATH_ADMINISTRATOR . '/components/' . $component->element))
			)
			{
				continue;
			}

			// return if there is no views folder
			if (!($frontend && JFolder::exists(JPATH_SITE . '/components/' . $component->element . '/views'))
				&& !($admin && JFolder::exists(JPATH_ADMINISTRATOR . '/components/' . $component->element . '/views'))
			)
			{
				continue;
			}
			if (!empty($component->element))
			{
				// Load the core file then
				// Load extension-local file.
				$lang->load($component->element . '.sys', JPATH_BASE, null, false, false)
				|| $lang->load($component->element . '.sys', JPATH_ADMINISTRATOR . '/components/' . $component->element, null, false, false)
				|| $lang->load($component->element . '.sys', JPATH_BASE, $lang->getDefault(), false, false)
				|| $lang->load($component->element . '.sys', JPATH_ADMINISTRATOR . '/components/' . $component->element, $lang->getDefault(), false, false);
			}
			$component->name = JText::_(strtoupper($component->name));
			$comps[preg_replace('#[^a-z0-9_]#i', '', $component->name . '_' . $component->element)] = $component;
		}
		ksort($comps);

		return $comps;
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
