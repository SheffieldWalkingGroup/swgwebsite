<?php
/**
 * Element: MenuItems
 * Display a menuitem field with a button
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

class JFormFieldNN_MenuItems extends JFormField
{
	public $type = 'MenuItems';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		JHtml::_('behavior.modal', 'a.modal');

		$size = (int) $this->def('size');
		$multiple = $this->def('multiple', 1);
		$showinput = $this->def('showinput');
		$disable_types = $this->def('disable');

		$db = JFactory::getDBO();

		// load the list of menu types
		$query = $db->getQuery(true);
		$query->select('m.menutype, m.title');
		$query->from('#__menu_types AS m');
		$query->order('m.title');
		$db->setQuery($query);
		$menuTypes = $db->loadObjectList();

		// load the list of menu items
		$query = $db->getQuery(true);
		$query->select('m.id, m.parent_id, m.title, m.alias, m.menutype, m.type, m.published, m.home');
		$query->select('m.title AS name');
		$query->from('#__menu AS m');
		$query->where('m.published != -2');
		$query->order('m.menutype, m.parent_id, m.lft, m.id');
		$db->setQuery($query);
		$items = $db->loadObjectList();

		// establish the hierarchy of the menu
		$children = array();

		if ($items) {
			// first pass - collect children
			foreach ($items as $v) {
				if ($v->type != 'separator') {
					if (preg_replace('#[^a-z0-9]#', '', strtolower($v->title)) !== preg_replace('#[^a-z0-9]#', '', $v->alias)) {
						$v->title .= ' [' . $v->alias . ']';
					}
				}
				$pt = $v->parent_id;
				$list = @$children[$pt] ? $children[$pt] : array();
				array_push($list, $v);
				$children[$pt] = $list;
			}
		}

		// second pass - get an indent list of the items
		require_once JPATH_LIBRARIES . '/joomla/html/html/menu.php';
		$list = JHTMLMenu::treerecurse(0, '', array(), $children, 9999, 0, 0);

		// assemble into menutype groups
		$groupedList = array();
		foreach ($list as $k => $v) {
			$groupedList[$v->menutype][] =& $list[$k];
		}

		// assemble menu items to the array
		$options = array();

		$count = 0;
		foreach ($menuTypes as $type) {
			if (isset($groupedList[$type->menutype])) {
				if ($count > 0) {
					$options[] = JHtml::_('select.option', '-', '&nbsp;', 'value', 'text', true);
				}
				$count++;
				$options[] = JHtml::_('select.option', $type->menutype, '[ ' . $type->title . ' ]', 'value', 'text', true);
				$n = count($groupedList[$type->menutype]);
				for ($i = 0; $i < $n; $i++) {
					$item =& $groupedList[$type->menutype][$i];

					//If menutype is changed but item is not saved yet, use the new type in the list
					if (JRequest::getString('option', '', 'get') == 'com_menus') {
						$currentItemArray = JRequest::getVar('cid', array(0), '', 'array');
						$currentItemId = (int) $currentItemArray['0'];
						$currentItemType = JRequest::getString('type', $item->type, 'get');
						if ($currentItemId == $item->id && $currentItemType != $item->type) {
							$item->type = $currentItemType;
						}
					}

					if ($showinput) {
						$item->treename .= ' [' . $item->id . ']';
					}
					if ($item->home) {
						$item->treename .= ' [' . JText::_('JDEFAULT') . ']';
					}
					$item->treename = NNText::prepareSelectItem($item->treename, $item->published, $item->type, 2);

					if ($type == 'separator' && !$item->children) {
						$disable = 1;
					} else {
						$disable = ($disable_types && !(strpos($disable_types, $item->type) === false));
					}

					$options[] = JHtml::_('select.option', $item->id, $item->treename, 'value', 'text', $disable);
				}
			}
		}

		if ($showinput) {
			array_unshift($options, JHtml::_('select.option', '-', '&nbsp;', 'value', 'text', true));
			array_unshift($options, JHtml::_('select.option', '-', '- ' . JText::_('Select Item') . ' -'));

			if ($multiple) {
				$onchange = 'if ( this.value ) { if ( ' . $this->id . '.value ) { ' . $this->id . '.value+=\',\'; } ' . $this->id . '.value+=this.value; } this.value=\'\';';
			} else {
				$onchange = 'if ( this.value ) { ' . $this->id . '.value=this.value;' . $this->id . '_text.value=this.options[this.selectedIndex].innerHTML.replace( /^((&|&amp;|&#160;)nbsp;|-)*/gm, \'\' ).trim(); } this.value=\'\';';
			}
			$attribs = 'class="inputbox" onchange="' . $onchange . '"';

			$html = '<table cellpadding="0" cellspacing="0"><tr><td style="padding: 0px;">' . "\n";
			if (!$multiple) {
				$val_name = $this->value;
				if ($this->value) {
					foreach ($items as $item) {
						if ($item->id == $this->value) {
							$val_name = $item->name . ' [' . $this->value . ']';
							;
							break;
						}
					}
				}
				$html .= '<input type="text" id="' . $this->id . '_text" value="' . $val_name . '" class="inputbox" size="' . $size . '" disabled="disabled" />';
				$html .= '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="' . $this->value . '" />';
			} else {
				$html .= '<input type="text" name="' . $this->name . '" id="' . $this->id . '" value="' . $this->value . '" class="inputbox" size="' . $size . '" />';
			}
			$html .= '</td><td style="padding: 0px;"padding-left: 5px;>' . "\n";
			$html .= JHtml::_('select.genericlist', $options, '', $attribs, 'value', 'text', '', '');
			$html .= '</td></tr></table>' . "\n";
			return $html;
		} else {
			require_once JPATH_PLUGINS . '/system/nnframework/helpers/html.php';
			return nnHTML::selectlist($options, $this->name, $this->value, $this->id, $size, $multiple);
		}
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}