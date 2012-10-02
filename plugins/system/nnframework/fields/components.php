<?php
/**
 * Element: Components
 * Displays a list of components with check boxes
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

class JFormFieldNN_Components extends JFormField
{
	public $type = 'Components';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$frontend = $this->def('frontend', 1);
		$admin = $this->def('admin', 1);
		$size = (int) $this->def('size');

		if (!$frontend && !$admin) {
			return '';
		}

		$components = $this->getComponents($frontend, $admin);

		$options = array();

		foreach ($components as $component) {
			$options[] = JHtml::_('select.option', $component->element, $component->name);
		}

		require_once JPATH_PLUGINS . '/system/nnframework/helpers/html.php';
		return nnHTML::selectlist($options, $this->name, $this->value, $this->id, $size, 1);
	}

	function getComponents($frontend = 1, $admin = 1)
	{
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		$db = JFactory::getDBO();

		$query = $db->getQuery(true);
		$query->select('e.name, e.element');
		$query->from('#__extensions AS e');
		$query->where('e.name != ""');
		$query->where('e.element != ""');
		$query->where('e.type = ' . $db->quote('component'));
		$query->group('e.element');
		$query->order('e.element, e.name');
		$db->setQuery($query);
		$components = $db->loadObjectList();

		$comps = array();
		$lang = JFactory::getLanguage();

		foreach ($components as $i => $component) {
			// return if there is no main component folder
			if (!($frontend && JFolder::exists(JPATH_SITE . '/components/' . $component->element))
				&& !($admin && JFolder::exists(JPATH_ADMINISTRATOR . '/components/' . $component->element))
			) {
				continue;
			}

			// return if there is no views folder
			if (!($frontend && JFolder::exists(JPATH_SITE . '/components/' . $component->element . '/views'))
				&& !($admin && JFolder::exists(JPATH_ADMINISTRATOR . '/components/' . $component->element . '/views'))
			) {
				continue;
			}
			if (!empty($component->element)) {
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

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}