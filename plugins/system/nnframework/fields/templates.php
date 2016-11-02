<?php
/**
 * Element: Templates
 * Displays a select box of templates
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

require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';

class JFormFieldNN_Templates extends JFormField
{
	public $type = 'Templates';
	private $params = null;

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$size = (int) $this->get('size');
		$multiple = $this->get('multiple');
		$attribs = 'class="inputbox"';

		$options = array();

		$templates = $this->getTemplates();

		foreach ($templates as $styles)
		{
			$level = 0;
			foreach ($styles as $style)
			{
				$style->level = $level;
				$options[] = $style;
				if (count($styles) <= 2)
				{
					$level = 0;
					break;
				}
				$level = 1;
			}
		}

		return nnHtml::selectlist($options, $this->name, $this->value, $this->id, $size, $multiple, $attribs);
	}

	protected function getTemplates()
	{
		$groups = array();
		$lang = JFactory::getLanguage();

		// Get the database object and a new query object.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('s.id, s.title, e.name as name, s.template')
			->from('#__template_styles as s')
			->where('s.client_id = 0')
			->join('LEFT', '#__extensions as e on e.element=s.template')
			->where('e.enabled=1')
			->where($db->quoteName('e.type') . '=' . $db->quote('template'))
			->order('s.template')
			->order('s.title');

		// Set the query and load the styles.
		$db->setQuery($query);
		$styles = $db->loadObjectList();

		// Build the grouped list array.
		if ($styles)
		{
			foreach ($styles as $style)
			{
				$template = $style->template;
				$lang->load('tpl_' . $template . '.sys', JPATH_SITE, null, false, false)
				|| $lang->load('tpl_' . $template . '.sys', JPATH_SITE . '/templates/' . $template, null, false, false)
				|| $lang->load('tpl_' . $template . '.sys', JPATH_SITE, $lang->getDefault(), false, false)
				|| $lang->load('tpl_' . $template . '.sys', JPATH_SITE . '/templates/' . $template, $lang->getDefault(), false, false);
				$name = JText::_($style->name);

				// Initialize the group if necessary.
				if (!isset($groups[$template]))
				{
					$groups[$template] = array();
					$groups[$template][] = JHtml::_('select.option', $template, $name);
				}

				$groups[$template][] = JHtml::_('select.option', $template . '::' . $style->id, $style->title);
			}
		}

		return $groups;
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
