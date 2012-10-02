<?php
/**
 * Element: Group Level
 * Displays a select box of backend group levels
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

class JFormFieldNN_GroupLevel extends JFormField
{
	public $type = 'GroupLevel';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$root = $this->def('root', 'USERS');
		$size = (int) $this->def('size');
		$multiple = $this->def('multiple');
		$show_all = $this->def('show_all');

		$attribs = 'class="inputbox"';

		$groups = $this->getUserGroups();
		$options = array();
		if ($show_all) {
			$option = new stdClass();
			$option->value = -1;
			$option->text = '- ' . JText::_('JALL') . ' -';
			$option->disable = '';
			$options[] = $option;
		}

		foreach ($groups as $group) {
			$option = new stdClass();
			$option->value = $group->id;
			$option->text = $group->title;

			$repeat = $show_all ? $group->level + 1 : $group->level;
			$option->text = str_repeat('- ', $repeat) . $option->text;
			$option->text = NNText::prepareSelectItem($option->text);

			$option->disable = '';
			$options[] = $option;
		}

		require_once JPATH_PLUGINS . '/system/nnframework/helpers/html.php';
		return nnHTML::selectlist($options, $this->name, $this->value, $this->id, $size, $multiple, $attribs);
	}

	protected function getUserGroups()
	{
		// Get a database object.
		$db = JFactory::getDBO();

		// Get the user groups from the database.
		$db->setQuery(
			'SELECT a.id, a.title, a.parent_id AS parent, COUNT(DISTINCT b.id) AS level' .
				' FROM #__usergroups AS a' .
				' LEFT JOIN `#__usergroups` AS b ON a.lft > b.lft AND a.rgt < b.rgt' .
				' GROUP BY a.id' .
				' ORDER BY a.lft ASC'
		);
		$options = $db->loadObjectList();

		return $options;
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}