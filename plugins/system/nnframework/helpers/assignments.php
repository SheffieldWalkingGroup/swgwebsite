<?php
/**
 * NoNumber Framework Helper File: Assignments
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

/**
 * Assignments
 * $assignment = no / include / exclude / none
 */
class NNFrameworkAssignmentsHelper
{
	var $db = null;
	var $params = null;
	var $init = 0;
	var $types = array();
	var $passes = array();
	var $maintype = '';
	var $subtype = '';

	function __construct()
	{
		$this->db = JFactory::getDBO();

		$config = JFactory::getConfig();
		$this->date = JFactory::getDate();
		$this->date->setTimeZone(new DateTimeZone($config->getValue('config.offset')));

		$this->has = array();
		$this->has['flexicontent'] = (int) JFile::exists(JPATH_ADMINISTRATOR . '/components/com_flexicontent/admin.flexicontent.php');
		$this->has['k2'] = (int) JFile::exists(JPATH_ADMINISTRATOR . '/components/com_k2/admin.k2.php');
		$this->has['zoo'] = (int) JFile::exists(JPATH_ADMINISTRATOR . '/components/com_zoo/zoo.php');
		$this->has['akeebasubs'] = (int) JFile::exists(JPATH_ADMINISTRATOR . '/components/com_akeebasubs/akeebasubs.php');
		$this->has['hikashop'] = (int) JFile::exists(JPATH_ADMINISTRATOR . '/components/com_hikashop/admin.hikashop.php');
		$this->has['redshop'] = (int) JFile::exists(JPATH_ADMINISTRATOR . '/components/com_redshop/redshop.php');
		$this->has['virtuemart'] = (int) JFile::exists(JPATH_ADMINISTRATOR . '/components/com_virtuemart/admin.virtuemart.php');

		$this->types = array(
			'Menu',
			'HomePage',
			'DateTime_Date',
			'DateTime_Seasons',
			'DateTime_Months',
			'DateTime_Days',
			'DateTime_Time',
			'Users_UserGroupLevels',
			'Users_Users',
			'Languages',
			'Templates',
			'URLs',
			'Agents_OS',
			'Agents_Browsers',
			'Components',
			'Content_PageTypes',
			'Content_Categories',
			'Content_Articles',
			'FlexiContent_PageTypes',
			'FlexiContent_Tags',
			'FlexiContent_Types',
			'K2_PageTypes',
			'K2_Categories',
			'K2_Tags',
			'K2_Items',
			'ZOO_PageTypes',
			'ZOO_Categories',
			'ZOO_Items',
			'AkeebaSubs_PageTypes',
			'AkeebaSubs_Levels',
			'HikaShop_PageTypes',
			'HikaShop_Categories',
			'HikaShop_Products',
			'RedShop_PageTypes',
			'RedShop_Categories',
			'RedShop_Products',
			'VirtueMart_PageTypes',
			'VirtueMart_Categories',
			'VirtueMart_Products',
			'PHP'
		);

		$this->setIdNames();

		$this->classes = array();
	}

	function setIdNames()
	{
		$this->names = array();
		foreach ($this->types as $type) {
			$type = explode('_', $type, 2);
			$this->names[strtolower($type['0'])] = $type['0'];
			if (isset($type['1'])) {
				$this->names[strtolower($type['1'])] = $type['1'];
			}
		}
		$this->names['menuitems'] = 'Menu';
		$this->names['cats'] = 'Categories';
	}

	function initParams()
	{
		if ($this->init) {
			return;
		}

		$this->params = new stdClass();
		$this->params->idname = 'id';
		$this->params->option = JRequest::getCmd('option');
		$this->params->view = JRequest::getCmd('view');
		$this->params->task = JRequest::getCmd('task');
		$this->params->layout = JRequest::getCmd('layout');
		$this->params->id = JRequest::getInt('id');
		$this->params->Itemid = JRequest::getInt('Itemid');

		if ($this->params->option) {
			switch ($this->params->option) {
				case 'com_categories':
					$this->params->option = 'com_content';
					$this->params->view = 'category';
					break;
			}
		}

		$option = strtolower(str_replace('com_', '', $this->params->option));
		if (JFile::exists(__DIR__ . '/assignments/' . $option . '.php')) {
			require_once __DIR__ . '/assignments/' . $option . '.php';
			$class = 'NNFrameworkAssignments' . $option;
			if (class_exists($class)) {
				$this->classes[$this->maintype] = new $class;
				if (method_exists($class, 'init')) {
					$this->classes[$this->maintype]->init($this);
				}
			}
		}

		if (!$this->params->id) {
			$cid = JRequest::getVar('cid', array(0), 'method', 'array');
			$cid = array((int) $cid['0']);
			$this->params->id = $cid['0'];
		}

		// if no id is found, check if menuitem exists to get view and id
		if (!$this->params->option || !$this->params->id) {
			$app = JFactory::getApplication();
			if ($app->isSite()) {
				$menu = $app->getMenu('site');
				if (empty($this->params->Itemid)) {
					$menuItem = $menu->getActive();
				} else {
					$menuItem = $menu->getItem($this->params->Itemid);
				}
				if($menuItem) {
					if (!$this->params->option) {
						$this->params->option = (empty($menuItem->query['option'])) ? null : $menuItem->query['option'];
					}
					$this->params->view = (empty($menuItem->query['view'])) ? null : $menuItem->query['view'];
					$this->params->task = (empty($menuItem->query['task'])) ? null : $menuItem->query['task'];
					if (!$this->params->id) {
						$this->params->id = (empty($menuItem->query[$this->params->idname])) ? $menuItem->params->get($this->params->idname) : $menuItem->query[$this->params->idname];
					}
				}
				unset($menuItem);
			}
		}

		$this->init = 1;
	}

	function initParamsByType(&$params, $type = '')
	{
		$this->getAssignmentState($params->assignment);
		if (!(strpos($type, '_') === false)) {
			$type = explode('_', $type, 2);
			$params->maintype = $type['0'];
			$params->subtype = $type['1'];
		} else {
			$params->maintype = $type;
			$params->subtype = $type;
		}
	}

	function passAll(&$assignments, $match_method = 'and', $article = 0)
	{
		if (empty($assignments)) {
			return 1;
		}

		$this->initParams();

		$aid = ($article && isset($article->id)) ? '[' . $article->id . ']' : '';
		$id = md5($aid . json_encode($assignments));

		if (isset($this->passes[$id])) {
			$pass = $this->passes[$id];
		} else {
			jimport('joomla.filesystem.file');
			$pass = ($match_method == 'and') ? 1 : 0;
			foreach ($this->types as $type) {
				if (isset($assignments[$type])) {
					$this->initParamsByType($assignments[$type], $type);
					if (($pass && $match_method == 'and') || (!$pass && $match_method == 'or')) {
						$tid = md5($type . $aid . ':' . json_encode($assignments[$type]));
						if (isset($this->passes[$tid])) {
							$pass = $this->passes[$tid];
						} else {
							if ($assignments[$type]->assignment == 'all') {
								$pass = 1;
							} else if ($assignments[$type]->assignment == 'none') {
								$pass = 0;
							} else {
								$c = $assignments[$type]->maintype;
								$f = $assignments[$type]->subtype;
								if (!isset($this->classes[$c]) && JFile::exists(__DIR__ . '/assignments/' . strtolower($c) . '.php')) {
									require_once __DIR__ . '/assignments/' . strtolower($c) . '.php';
									$class = 'NNFrameworkAssignments' . $c;
									$this->classes[$c] = new $class;
								}
								if (isset($this->classes[$c])) {
									$method = 'pass' . $f;
									if (method_exists('NNFrameworkAssignments' . $c, $method)) {
										$pass = $this->classes[$c]->$method($this, $assignments[$type]->params, $assignments[$type]->selection, $assignments[$type]->assignment, $article);
									}
								}
							}
							$this->passes[$tid] = $pass;
						}
					}
				}
			}
			$this->passes[$id] = $pass;
		}

		return ($pass) ? 1 : 0;
	}

	function pass($pass = 1, $assignment = 'all')
	{
		return ($pass) ? ($assignment == 'include') : ($assignment == 'exclude');
	}

	function passSimple($values = '', $selection = array(), $assignment = 'all', $caseinsensitive = 0)
	{
		$values = $this->makeArray($values, 1);
		$selection = $this->makeArray($selection);

		$pass = 0;
		foreach ($values as $value) {
			if ($caseinsensitive) {
				if (in_array(strtolower($value), array_map('strtolower', $selection))) {
					$pass = 1;
					break;
				}
			} else {
				if (in_array($value, $selection)) {
					$pass = 1;
					break;
				}
			}
		}

		return $this->pass($pass, $assignment);
	}

	function passPageTypes($option, $selection = array(), $assignment = 'all', $add = 0)
	{
		if ($this->params->option != $option) {
			return $this->pass(0, $assignment);
		}

		$type = $this->params->view;
		if ($this->params->layout && $this->params->layout != 'default') {
			if ($add) {
				$type .= '_' . $this->params->layout;
			} else {
				$type = $this->params->layout;
			}
		}

		return $this->passSimple($type, $selection, $assignment);
	}

	function getAssignmentState(&$assignment)
	{
		switch ($assignment) {
			case 1:
			case 'include':
				$assignment = 'include';
				break;
			case 2:
			case 'exclude':
				$assignment = 'exclude';
				break;
			case 3:
			case -1:
			case 'none':
				$assignment = 'none';
				break;
			default:
				$assignment = 'all';
				break;
		}
	}

	function getMenuItemParams($id = 0)
	{
		$query = $this->db->getQuery(true);
		$query->select('m.params');
		$query->from('#__menu AS m');
		$query->where('m.id = ' . (int) $id);
		$this->db->setQuery($query);
		$params = $this->db->loadResult();

		$parameters = NNParameters::getInstance();
		return $parameters->getParams($params);
	}

	function getParentIds($id = 0, $table = 'menu', $parent = 'parent_id', $child = 'id')
	{
		$parent_ids = array();

		if (!$id) {
			return $parent_ids;
		}

		while ($id) {
			$query = $this->db->getQuery(true);
			$query->select($this->db->qn($parent));
			$query->from('#__' . $table);
			$query->where($this->db->qn($child) . ' = ' . (int) $id);
			$this->db->setQuery($query);
			$id = $this->db->loadResult();
			if ($id) {
				$parent_ids[] = $id;
			}
		}
		return $parent_ids;
	}

	function makeArray($array = '', $onlycommas = 0, $trim = 1)
	{
		if (!is_array($array)) {
			if (!$onlycommas && !(strpos($array, '|') === false)) {
				$array = explode('|', $array);
			} else {
				$array = explode(',', $array);
			}
		}
		if ($trim) {
			if ($trim && !empty($array)) {
				foreach ($array as $key => $val) {
					$array[$key] = trim($val);
				}
			}
		}
		return $array;
	}

	function getAssignmentsFromParams(&$params, $use_sef = 2)
	{
		jimport('joomla.filesystem.file');

		$assignments = array();

		list($id, $name) = $this->setAssignmentParams($assignments, $params, 'menuitems');
		if ($id) {
			$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
			$assignments[$name]->params->inc_noItemid = $params->{'assignto_' . $id . '_inc_noitemid'};
		}

		$this->setAssignmentParams($assignments, $params, 'homepage');

		$maintype = 'datetime';
		list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'date');
		if ($id) {
			$assignments[$name]->params->publish_up = $params->{'assignto_' . $id . '_publish_up'};
			$assignments[$name]->params->publish_down = $params->{'assignto_' . $id . '_publish_down'};
		}
		list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'seasons');
		if ($id) {
			$assignments[$name]->params->hemisphere = $params->{'assignto_' . $id . '_hemisphere'};
		}
		$this->setAssignmentParams($assignments, $params, $maintype, 'months');
		$this->setAssignmentParams($assignments, $params, $maintype, 'days');
		list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'time');
		if ($id) {
			$assignments[$name]->params->publish_up = $params->{'assignto_' . $id . '_publish_up'};
			$assignments[$name]->params->publish_down = $params->{'assignto_' . $id . '_publish_down'};
		}

		$this->setAssignmentParams($assignments, $params, 'users', 'usergrouplevels');
		$this->setAssignmentParams($assignments, $params, 'users', 'users');

		$this->setAssignmentParams($assignments, $params, 'languages');

		$this->setAssignmentParams($assignments, $params, 'templates');

		list($id, $name) = $this->setAssignmentParams($assignments, $params, 'urls');
		if ($id) {
			if ($use_sef == 2) {
				$config = JFactory::getConfig();
				$use_sef = ($config->getValue('config.sef') == 1);
			}
			$assignments[$name]->selection = $use_sef ? $params->{'assignto_' . $id . '_selection_sef'} : $params->{'assignto_' . $id . '_selection'};
			$assignments[$name]->selection = str_replace("\r", '', $assignments[$name]->selection);
			$assignments[$name]->selection = explode("\n", $assignments[$name]->selection);
		}

		$maintype = 'agents';
		$this->setAssignmentParams($assignments, $params, $maintype, 'os');
		list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'browsers');
		if ($id) {
			$selection = $assignments[$name]->selection;
			if (isset($params->{'assignto_mobile_selection'}) && !empty($params->{'assignto_mobile_selection'})) {
				$selection = array_merge($selection, $params->{'assignto_' . $id . '_selection'});
			}
			if (isset($params->{'assignto_searchbots_selection'}) && !empty($params->{'assignto_searchbots_selection'})) {
				$selection = array_merge($selection, $params->{'assignto_' . $id . '_selection'});
			}
			$assignments[$name]->selection = $selection;
		}

		$this->setAssignmentParams($assignments, $params, 'components');

		$maintype = 'content';
		$this->setAssignmentParams($assignments, $params, $maintype, 'pagetypes', 1);
		list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'cats');
		if ($id) {
			$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
			$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
			$assignments[$name]->params->inc_articles = in_array('inc_arts', $incs);
			$assignments[$name]->params->inc_others = in_array('inc_others', $incs);
			$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
		}
		list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'articles');
		if ($id) {
			$assignments[$name]->params->keywords = $params->{'assignto_' . $id . '_keywords'};
		}

		$maintype = 'flexicontent';
		if ($this->has[$maintype]) {
			$this->setAssignmentParams($assignments, $params, $maintype, 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'tags', 1);
			if ($id) {
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_tags = in_array('inc_tags', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, $maintype, 'types', 1);
		}

		$maintype = 'k2';
		if ($this->has[$maintype]) {
			$this->setAssignmentParams($assignments, $params, $maintype, 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'cats', 1);
			if ($id) {
				$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'tags', 1);
			if ($id) {
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_tags = in_array('inc_tags', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, $maintype, 'items', 1);
		}

		$maintype = 'zoo';
		if ($this->has[$maintype]) {
			$this->setAssignmentParams($assignments, $params, $maintype, 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'cats', 1);
			if ($id) {
				$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_apps = in_array('inc_apps', $incs);
				$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, $maintype, 'items', 1);
		}

		$maintype = 'akeebasubs';
		if ($this->has[$maintype]) {
			$this->setAssignmentParams($assignments, $params, $maintype, 'pagetypes', 1);
			$this->setAssignmentParams($assignments, $params, $maintype, 'levels', 1);
		}

		$maintype = 'hikashop';
		if ($this->has[$maintype]) {
			$this->setAssignmentParams($assignments, $params, $maintype, 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'cats', 1);
			if ($id) {
				$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, $maintype, 'products', 1);
		}

		$maintype = 'redshop';
		if ($this->has[$maintype]) {
			$this->setAssignmentParams($assignments, $params, $maintype, 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'cats', 1);
			if ($id) {
				$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, $maintype, 'products', 1);
		}

		$maintype = 'virtuemart';
		if ($this->has[$maintype]) {
			$this->setAssignmentParams($assignments, $params, $maintype, 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, $maintype, 'cats', 1);
			if ($id) {
				$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, $maintype, 'products', 1);
		}

		$this->setAssignmentParams($assignments, $params, 'php');

		return $assignments;
	}

	function setAssignmentParams(&$assignments, &$params, $maintype, $subtype = '', $usemain = 0)
	{
		$id = $maintype;
		$name = $this->names[$maintype];
		if ($subtype) {
			$name .= '_' . $this->names[$subtype];
			if ($usemain) {
				$id .= $subtype;
			} else {
				$id = $subtype;
			}
		}
		if (isset($params->{'assignto_' . $id}) && $params->{'assignto_' . $id}) {
			$assignments[$name] = new stdClass();
			$assignments[$name]->assignment = $params->{'assignto_' . $id};
			$assignments[$name]->selection = array();
			$assignments[$name]->params = new stdClass();
			if (isset($params->{'assignto_' . $id . '_selection'})) {
				$assignments[$name]->selection = $params->{'assignto_' . $id . '_selection'};
			}
		} else {
			$id = '';
		}

		return array($id, $name);
	}
}