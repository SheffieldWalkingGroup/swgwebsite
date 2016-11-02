<?php
/**
 * Element: MenuItems
 * Display a menuitem field with a button
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

class JFormFieldNN_MenuItems extends JFormField
{
	public $type = 'MenuItems';
	private $params = null;

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$size = (int) $this->get('size');
		$multiple = $this->get('multiple', 0);

		require_once JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php';
		$options = $this->getMenuLinks();

		require_once JPATH_PLUGINS . '/system/nnframework/helpers/html.php';

		return nnHtml::selectlist($options, $this->name, $this->value, $this->id, $size, $multiple);
	}

	/**
	 * Get a list of menu links for one or all menus.
	 */
	public static function getMenuLinks()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('a.id AS value, a.title AS text, a.alias, a.level, a.menutype, a.type, a.template_style_id, a.checked_out, a.language')
			->from('#__menu AS a')
			->join('LEFT', $db->quoteName('#__menu') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt')
			->where('a.published != -2')
			->group('a.id, a.title, a.level, a.menutype, a.type, a.template_style_id, a.checked_out, a.lft')
			->order('a.lft ASC');

		// Get the options.
		$db->setQuery($query);

		try
		{
			$links = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());
			return false;
		}

		// Group the items by menutype.
		$query->clear()
			->select('*')
			->from('#__menu_types')
			->where('menutype <> ' . $db->quote(''))
			->order('title, menutype');
		$db->setQuery($query);

		try
		{
			$menuTypes = $db->loadObjectList();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());
			return false;
		}

		// Create a reverse lookup and aggregate the links.
		$rlu = array();
		foreach ($menuTypes as &$type)
		{
			$type->value = 'type.' . $type->menutype;
			$type->text = $type->title;
			$type->level = 0;
			$type->class = 'hidechildren';
			$type->labelclass = 'nav-header';

			$rlu[$type->menutype] = & $type;
			$type->links = array();
		}

		// Loop through the list of menu links.
		foreach ($links as &$link)
		{
			if (isset($rlu[$link->menutype]))
			{
				if (preg_replace('#[^a-z0-9]#', '', strtolower($link->text)) !== preg_replace('#[^a-z0-9]#', '', $link->alias))
				{
					$link->text .= ' [' . $link->alias . ']';
				}

				if ($link->language && $link->language != '*')
				{
					$link->text .= ' (' . $link->language . ')';
				}

				$rlu[$link->menutype]->links[] = & $link;

				// Cleanup garbage.
				unset($link->menutype);
			}
		}

		return $menuTypes;
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
