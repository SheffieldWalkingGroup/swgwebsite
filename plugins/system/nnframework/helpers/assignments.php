<?php
/**
 * NoNumber Framework Helper File: Assignments
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

require_once __DIR__ . '/functions.php';

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
	var $cache = array();

	function __construct()
	{
		$this->db = JFactory::getDBO();
		$this->q = $this->db->getQuery(true);

		$this->date = JFactory::getDate();
		$tz = new DateTimeZone(JFactory::getApplication()->getCfg('offset'));
		$this->date->setTimeZone($tz);

		$this->has = array();
		$this->has['flexicontent'] = NNFrameworkFunctions::extensionInstalled('flexicontent');
		$this->has['k2'] = NNFrameworkFunctions::extensionInstalled('k2');
		$this->has['zoo'] = NNFrameworkFunctions::extensionInstalled('zoo');
		$this->has['akeebasubs'] = NNFrameworkFunctions::extensionInstalled('akeebasubs');
		$this->has['hikashop'] = NNFrameworkFunctions::extensionInstalled('hikashop');
		$this->has['mijoshop'] = NNFrameworkFunctions::extensionInstalled('mijoshop');
		$this->has['redshop'] = NNFrameworkFunctions::extensionInstalled('redshop');
		$this->has['virtuemart'] = NNFrameworkFunctions::extensionInstalled('virtuemart');

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
			'IPs',
			'Geo_Continents',
			'Geo_Countries',
			'Geo_Regions',
			'Templates',
			'URLs',
			'Agents_OS',
			'Agents_Browsers',
			'Components',
			'Tags',
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
			'MijoShop_PageTypes',
			'MijoShop_Categories',
			'MijoShop_Products',
			'RedShop_PageTypes',
			'RedShop_Categories',
			'RedShop_Products',
			'VirtueMart_PageTypes',
			'VirtueMart_Categories',
			'VirtueMart_Products',
			'PHP'
		);
		$this->nonarray = array(
			'PHP'
		);

		$this->setIdNames();

		$this->classes = array();
	}

	function setIdNames()
	{
		$this->names = array();
		foreach ($this->types as $type)
		{
			$type = explode('_', $type, 2);
			$this->names[strtolower($type['0'])] = $type['0'];
			if (isset($type['1']))
			{
				$this->names[strtolower($type['1'])] = $type['1'];
			}
		}
		$this->names['menuitems'] = 'Menu';
		$this->names['cats'] = 'Categories';
	}

	function initParams()
	{
		if ($this->init)
		{
			return;
		}

		$this->params = new stdClass;
		$this->params->idname = 'id';
		$this->params->option = JFactory::getApplication()->input->get('option');
		$this->params->view = JFactory::getApplication()->input->get('view');
		$this->params->task = JFactory::getApplication()->input->get('task');
		$this->params->layout = JFactory::getApplication()->input->get('layout');
		$this->params->id = JFactory::getApplication()->input->getInt('id', 0);
		$this->params->Itemid = JFactory::getApplication()->input->getInt('Itemid', 0);

		if ($this->params->option)
		{
			switch ($this->params->option)
			{
				case 'com_categories':
					$extension = JFactory::getApplication()->input->getCmd('extension');
					$this->params->option = $extension ? $extension : 'com_content';
					$this->params->view = 'category';
					break;
				case 'com_breezingforms':
					if ($this->params->view == 'article')
					{
						$this->params->option = 'com_content';
					}
					break;
			}
		}

		$option = strtolower(str_replace('com_', '', $this->params->option));
		if (JFile::exists(__DIR__ . '/assignments/' . $option . '.php'))
		{
			require_once __DIR__ . '/assignments/' . $option . '.php';
			$class = 'NNFrameworkAssignments' . $option;
			if (class_exists($class))
			{
				$this->classes[$this->maintype] = new $class;
				if (method_exists($class, 'init'))
				{
					$this->classes[$this->maintype]->init($this);
				}
			}
		}

		if (!$this->params->id)
		{
			$cid = JFactory::getApplication()->input->get('cid', array(0), 'array');
			JArrayHelper::toInteger($cid);
			$this->params->id = $cid['0'];
		}

		// if no id is found, check if menuitem exists to get view and id
		if (!$this->params->option || !$this->params->id)
		{
			if (JFactory::getApplication()->isSite())
			{
				if (empty($this->params->Itemid))
				{
					$menuItem = JFactory::getApplication()->getMenu('site')->getActive();
				}
				else
				{
					$menuItem = JFactory::getApplication()->getMenu('site')->getItem($this->params->Itemid);
				}
				if ($menuItem)
				{
					if (!$this->params->option)
					{
						$this->params->option = (empty($menuItem->query['option'])) ? null : $menuItem->query['option'];
					}
					$this->params->view = (empty($menuItem->query['view'])) ? null : $menuItem->query['view'];
					$this->params->task = (empty($menuItem->query['task'])) ? null : $menuItem->query['task'];
					if (!$this->params->id)
					{
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
		$params->id = $type;
		if (!(strpos($type, '_') === false))
		{
			$type = explode('_', $type, 2);
			$params->maintype = $type['0'];
			$params->subtype = $type['1'];
		}
		else
		{
			$params->maintype = $type;
			$params->subtype = $type;
		}
	}

	function passAll(&$assignments, $match_method = 'and', $article = 0)
	{
		if (empty($assignments))
		{
			return 1;
		}

		$this->initParams();

		$aid = ($article && isset($article->id)) ? '[' . $article->id . ']' : '';
		$id = md5($aid . json_encode($assignments));

		if (isset($this->passes[$id]))
		{
			$pass = $this->passes[$id];
		}
		else
		{
			jimport('joomla.filesystem.file');
			$pass = ($match_method == 'and') ? 1 : 0;
			foreach ($this->types as $type)
			{
				if (isset($assignments[$type]))
				{
					$this->initParamsByType($assignments[$type], $type);
					if (($pass && $match_method == 'and') || (!$pass && $match_method == 'or'))
					{
						$tid = md5($type . $aid . ':' . json_encode($assignments[$type]));
						if (isset($this->passes[$tid]))
						{
							$pass = $this->passes[$tid];
						}
						else
						{
							if ($assignments[$type]->assignment == 'all')
							{
								$pass = 1;
							}
							else if ($assignments[$type]->assignment == 'none')
							{
								$pass = 0;
							}
							else
							{
								$c = $assignments[$type]->maintype;
								$f = $assignments[$type]->subtype;
								if (!isset($this->classes[$c]) && JFile::exists(__DIR__ . '/assignments/' . strtolower($c) . '.php'))
								{
									require_once __DIR__ . '/assignments/' . strtolower($c) . '.php';
									$class = 'NNFrameworkAssignments' . $c;
									$this->classes[$c] = new $class;
								}
								if (isset($this->classes[$c]))
								{
									$method = 'pass' . $f;
									if (method_exists('NNFrameworkAssignments' . $c, $method))
									{
										self::fixAssignment($assignments[$type], $assignments[$type]->id);
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

	function fixAssignment(&$a, $type = '')
	{
		$a->params = isset($a->params) ? $a->params : new stdClass();
		$a->assignment = isset($a->assignment) ? $a->assignment : '';
		if (!in_array($type, $this->nonarray))
		{
			$a->selection = isset($a->selection) ? $this->makeArray($a->selection) : array();
		}
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
		foreach ($values as $value)
		{
			if ($caseinsensitive)
			{
				if (in_array(strtolower($value), array_map('strtolower', $selection)))
				{
					$pass = 1;
					break;
				}
			}
			else
			{
				if (in_array($value, $selection))
				{
					$pass = 1;
					break;
				}
			}
		}

		return $this->pass($pass, $assignment);
	}

	function passPageTypes($option, $selection = array(), $assignment = 'all', $add = 0)
	{
		if ($this->params->option != $option)
		{
			return $this->pass(0, $assignment);
		}

		$type = $this->params->view;
		if ($this->params->layout && $this->params->layout != 'default')
		{
			if ($add)
			{
				$type .= '_' . $this->params->layout;
			}
			else
			{
				$type = $this->params->layout;
			}
		}

		return $this->passSimple($type, $selection, $assignment);
	}

	function getAssignmentState(&$assignment)
	{
		switch ($assignment)
		{
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
		$hash = 'MenuItemParams_' . $id;

		if (!isset($this->cache[$hash]))
		{
			$this->q->clear()
				->select('m.params')
				->from('#__menu AS m')
				->where('m.id = ' . (int) $id);
			$this->db->setQuery($this->q);
			$params = $this->db->loadResult();

			$parameters = NNParameters::getInstance();
			$this->cache[$hash] = $parameters->getParams($params);
		}

		return $this->cache[$hash];
	}

	function getParentIds($id = 0, $table = 'menu', $parent = 'parent_id', $child = 'id')
	{
		if (!$id)
		{
			return array();
		}

		$hash = 'ParentIds_' . $id . '_' . $table . '_' . $parent . '_' . $child;

		$parent_ids = array();
		if (!isset($this->cache[$hash]))
		{
			while ($id)
			{
				$this->q->clear()
					->select('t.' . $parent)
					->from('#__' . $table . ' as t')
					->where('t.' . $child . ' = ' . (int) $id);
				$this->db->setQuery($this->q);
				$id = $this->db->loadResult();
				if ($id)
				{
					$parent_ids[] = $id;
				}
			}
			$this->cache[$hash] = $parent_ids;
		}

		return $this->cache[$hash];
	}

	function makeArray($array = '', $onlycommas = 0, $trim = 1)
	{
		if (!is_array($array))
		{
			if (!$onlycommas && strpos($array, '|') !== false)
			{
				$array = explode('|', $array);
			}
			else
			{
				$array = explode(',', $array);
			}
		}
		else if (count($array) === 1 && strpos($array['0'], ',') !== false)
		{
			$array = explode(',', $array['0']);
		}
		else if (isset($array['0']) && is_array($array['0']))
		{
			$array = $array['0'];
		}

		if ($trim)
		{
			if ($trim && !empty($array))
			{
				foreach ($array as $k => $v)
				{
					if (is_string($v))
					{
						$array[$k] = trim($v);
					}
				}
			}
		}

		return $array;
	}

	function getAssignmentsFromParams(&$params)
	{
		jimport('joomla.filesystem.file');

		$assignments = array();

		list($id, $name) = $this->setAssignmentParams($assignments, $params, 'menuitems');
		if ($id)
		{
			$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
			$assignments[$name]->params->inc_noItemid = $params->{'assignto_' . $id . '_inc_noitemid'};
		}

		$this->setAssignmentParams($assignments, $params, 'homepage');

		list($id, $name) = $this->setAssignmentParams($assignments, $params, 'datetime', 'date');
		if ($id)
		{
			$assignments[$name]->params->publish_up = $params->{'assignto_' . $id . '_publish_up'};
			$assignments[$name]->params->publish_down = $params->{'assignto_' . $id . '_publish_down'};
			$assignments[$name]->params->recurring = isset($params->{'assignto_' . $id . '_recurring'}) ? $params->{'assignto_' . $id . '_recurring'} : 0;
		}
		list($id, $name) = $this->setAssignmentParams($assignments, $params, 'datetime', 'seasons');
		if ($id)
		{
			$assignments[$name]->params->hemisphere = $params->{'assignto_' . $id . '_hemisphere'};
		}
		$this->setAssignmentParams($assignments, $params, 'datetime', 'months');
		$this->setAssignmentParams($assignments, $params, 'datetime', 'days');
		list($id, $name) = $this->setAssignmentParams($assignments, $params, 'datetime', 'time');
		if ($id)
		{
			$assignments[$name]->params->publish_up = $params->{'assignto_' . $id . '_publish_up'};
			$assignments[$name]->params->publish_down = $params->{'assignto_' . $id . '_publish_down'};
		}

		$this->setAssignmentParams($assignments, $params, 'users', 'usergrouplevels');
		$this->setAssignmentParams($assignments, $params, 'users', 'users');

		$this->setAssignmentParams($assignments, $params, 'languages');

		$this->setAssignmentParams($assignments, $params, 'ips');

		$this->setAssignmentParams($assignments, $params, 'geo', 'continents', 1);
		$this->setAssignmentParams($assignments, $params, 'geo', 'countries', 1);
		$this->setAssignmentParams($assignments, $params, 'geo', 'regions', 1);

		$this->setAssignmentParams($assignments, $params, 'templates');

		list($id, $name) = $this->setAssignmentParams($assignments, $params, 'urls');
		if ($id)
		{
			$assignments[$name]->selection = $params->{'assignto_' . $id . '_selection'};
			if (isset($params->{'assignto_' . $id . '_selection_sef'}))
			{
				$assignments[$name]->selection .= "\n" . $params->{'assignto_' . $id . '_selection_sef'};
			}
			$assignments[$name]->selection = trim(str_replace("\r", '', $assignments[$name]->selection));
			$assignments[$name]->selection = explode("\n", $assignments[$name]->selection);
			$assignments[$name]->params->regex = isset($params->{'assignto_' . $id . '_regex'}) ? $params->{'assignto_' . $id . '_regex'} : 1;
		}

		$this->setAssignmentParams($assignments, $params, 'agents', 'os');
		list($id, $name) = $this->setAssignmentParams($assignments, $params, 'agents', 'browsers');
		if ($id)
		{
			$selection = $assignments[$name]->selection;
			if (isset($params->assignto_mobile_selection) && !empty($params->assignto_mobile_selection))
			{
				$selection = array_merge($selection, $this->makeArray($params->assignto_mobile_selection));
			}
			if (isset($params->assignto_searchbots_selection) && !empty($params->assignto_searchbots_selection))
			{
				$selection = array_merge($selection, $this->makeArray($params->assignto_searchbots_selection));
			}
			$assignments[$name]->selection = $selection;
		}

		$this->setAssignmentParams($assignments, $params, 'components');

		list($id, $name) = $this->setAssignmentParams($assignments, $params, 'tags');
		if ($id)
		{
			$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
		}

		$this->setAssignmentParams($assignments, $params, 'content', 'pagetypes', 1);
		list($id, $name) = $this->setAssignmentParams($assignments, $params, 'content', 'cats');
		if ($id)
		{
			$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
			$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
			$assignments[$name]->params->inc_articles = in_array('inc_arts', $incs);
			$assignments[$name]->params->inc_others = in_array('inc_others', $incs);
			$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
		}
		list($id, $name) = $this->setAssignmentParams($assignments, $params, 'content', 'articles');
		if ($id)
		{
			$assignments[$name]->params->keywords = $params->{'assignto_' . $id . '_keywords'};
		}

		if ($this->has['flexicontent'])
		{
			$this->setAssignmentParams($assignments, $params, 'flexicontent', 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, 'flexicontent', 'tags', 1);
			if ($id)
			{
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_tags = in_array('inc_tags', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, 'flexicontent', 'types', 1);
		}

		if ($this->has['k2'])
		{
			$this->setAssignmentParams($assignments, $params, 'k2', 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, 'k2', 'cats', 1);
			if ($id)
			{
				$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			list($id, $name) = $this->setAssignmentParams($assignments, $params, 'k2', 'tags', 1);
			if ($id)
			{
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_tags = in_array('inc_tags', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, 'k2', 'items', 1);
		}

		if ($this->has['zoo'])
		{
			$this->setAssignmentParams($assignments, $params, 'zoo', 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, 'zoo', 'cats', 1);
			if ($id)
			{
				$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_apps = in_array('inc_apps', $incs);
				$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, 'zoo', 'items', 1);
		}

		if ($this->has['akeebasubs'])
		{
			$this->setAssignmentParams($assignments, $params, 'akeebasubs', 'pagetypes', 1);
			$this->setAssignmentParams($assignments, $params, 'akeebasubs', 'levels', 1);
		}

		if ($this->has['hikashop'])
		{
			$this->setAssignmentParams($assignments, $params, 'hikashop', 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, 'hikashop', 'cats', 1);
			if ($id)
			{
				$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, 'hikashop', 'products', 1);
		}

		if ($this->has['mijoshop'])
		{
			$this->setAssignmentParams($assignments, $params, 'mijoshop', 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, 'mijoshop', 'cats', 1);
			if ($id)
			{
				$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, 'mijoshop', 'products', 1);
		}

		if ($this->has['redshop'])
		{
			$this->setAssignmentParams($assignments, $params, 'redshop', 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, 'redshop', 'cats', 1);
			if ($id)
			{
				$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, 'redshop', 'products', 1);
		}

		if ($this->has['virtuemart'])
		{
			$this->setAssignmentParams($assignments, $params, 'virtuemart', 'pagetypes', 1);

			list($id, $name) = $this->setAssignmentParams($assignments, $params, 'virtuemart', 'cats', 1);
			if ($id)
			{
				$assignments[$name]->params->inc_children = $params->{'assignto_' . $id . '_inc_children'};
				$incs = $this->makeArray($params->{'assignto_' . $id . '_inc'});
				$assignments[$name]->params->inc_categories = in_array('inc_cats', $incs);
				$assignments[$name]->params->inc_items = in_array('inc_items', $incs);
			}

			$this->setAssignmentParams($assignments, $params, 'virtuemart', 'products', 1);
		}

		$this->setAssignmentParams($assignments, $params, 'php');

		return $assignments;
	}

	function setAssignmentParams(&$assignments, &$params, $maintype, $subtype = '', $usemain = 0)
	{
		$id = $maintype;
		$name = $this->names[$maintype];
		if ($subtype)
		{
			$name .= '_' . $this->names[$subtype];
			if ($usemain)
			{
				$id .= $subtype;
			}
			else
			{
				$id = $subtype;
			}
		}
		if (isset($params->{'assignto_' . $id}) && $params->{'assignto_' . $id})
		{
			$assignments[$name] = new stdClass;
			$assignments[$name]->assignment = $params->{'assignto_' . $id};
			$assignments[$name]->selection = array();
			$assignments[$name]->params = new stdClass;
			if (isset($params->{'assignto_' . $id . '_selection'}) && !empty($params->{'assignto_' . $id . '_selection'}))
			{
				$assignments[$name]->selection = $params->{'assignto_' . $id . '_selection'};
			}
		}
		else
		{
			$id = '';
		}

		return array($id, $name);
	}
}
